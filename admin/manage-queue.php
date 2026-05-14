<?php
// ============================================================
//  AquaQueue — manage-queue.php  (DB-connected, role-scoped)
//  Place in: queue-system/admin/manage-queue.php
// ============================================================
session_start();
require_once('../includes/users_store.php');

// ── Access control ───────────────────────────────────────────
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'developer', 'service_admin'])) {
    $_SESSION['error'] = 'Access denied.';
    header('Location: ../public/login.php');
    exit();
}

$isServiceAdmin = $_SESSION['user_role'] === 'service_admin';
$isAdminOrDev   = in_array($_SESSION['user_role'], ['admin', 'developer']);
$sessionUserId  = (int)($_SESSION['user_id'] ?? 0);

// ── DB connection ─────────────────────────────────────────────
$db = new mysqli('localhost', 'root', '', 'aquaqueue_db');
if ($db->connect_error) die('DB error: ' . $db->connect_error);
$db->set_charset('utf8mb4');

// ── Queue log helper ──────────────────────────────────────────
function writeQueueLog(
    mysqli $db,
    int    $aptId,
    string $queueNo,
    string $action,
    int    $performedBy,
    string $performedByName,
    ?int   $serviceId    = null,
    string $serviceName  = '',
    string $customerName = '',
    string $customerPhone= '',
    string $cancelReason = '',
    string $notes        = ''
): void {
    $stmt = $db->prepare(
        'INSERT INTO queue_logs
         (appointment_id, queue_number, service_id, service_name,
          customer_name, customer_phone, action,
          performed_by, performed_by_name, cancel_reason, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'ississsisss',
        $aptId, $queueNo, $serviceId, $serviceName,
        $customerName, $customerPhone, $action,
        $performedBy, $performedByName, $cancelReason, $notes
    );
    $stmt->execute();
    $stmt->close();
}

// ── Determine which service(s) this user may see ─────────────
if ($isServiceAdmin) {
    // Pull the assigned service(s) from service_admin_assignments
    $saStmt = $db->prepare(
        'SELECT bs.id, bs.slug, bs.name, bs.icon_class, bs.color_hex,
                saa.can_manage_queue, saa.can_manage_bookings, saa.can_view_reports
         FROM service_admin_assignments saa
         JOIN booking_services bs ON bs.id = saa.service_id
         WHERE saa.user_id = ? AND bs.is_active = 1'
    );
    $saStmt->bind_param('i', $sessionUserId);
    $saStmt->execute();
    $assignedServices = $saStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $saStmt->close();

    if (empty($assignedServices)) {
        die('<div style="font-family:sans-serif;padding:40px;color:#c00">
             No service assigned to your account yet. Please contact an admin.</div>');
    }
    $allowedServiceIds = array_column($assignedServices, 'id');
} else {
    // Admin / Developer sees all active services
    $allSvcResult = $db->query(
        'SELECT id, slug, name, icon_class, color_hex,
                1 AS can_manage_queue, 1 AS can_manage_bookings, 1 AS can_view_reports
         FROM booking_services WHERE is_active = 1 ORDER BY id ASC'
    );
    $assignedServices  = $allSvcResult->fetch_all(MYSQLI_ASSOC);
    $allowedServiceIds = array_column($assignedServices, 'id');
}

// ── Helper: is a service ID allowed for current user? ─────────
function isAllowedService(int $svcId, array $allowed): bool {
    return in_array($svcId, $allowed);
}

// ── Handle POST actions ───────────────────────────────────────
$msg = $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── ADD TO QUEUE ───────────────────────────────────────────
    if ($action === 'add_queue') {
        $svcId     = (int)($_POST['service_id']   ?? 0);
        $locId     = (int)($_POST['location_id']  ?? 0);
        $custName  = trim($_POST['customer_name'] ?? '');
        $custPhone = trim($_POST['customer_phone'] ?? '');
        $priority  = in_array($_POST['priority'] ?? '', ['standard','express','vip'])
                     ? $_POST['priority'] : 'standard';
        $qType     = $_POST['queue_type'] ?? 'walk_in';
        $notes     = trim($_POST['notes'] ?? '');

        if (!$custName || !$svcId || !$locId) {
            $msg = 'Customer name, service, and location are required.';
            $msgType = 'error';
        } elseif (!isAllowedService($svcId, $allowedServiceIds)) {
            $msg = 'You are not authorised to add to that service queue.';
            $msgType = 'error';
        } else {
            // Get/create today's queue_status row and auto-increment queue number
            $today = date('Y-m-d');
            $qs = $db->prepare(
                'SELECT id, counter_prefix, last_issued FROM queue_status
                  WHERE location_id = ? AND queue_date = ?'
            );
            $qs->bind_param('is', $locId, $today);
            $qs->execute();
            $qsRow = $qs->get_result()->fetch_assoc();
            $qs->close();

            if (!$qsRow) {
                // Find prefix from service
                $pfxRes = $db->query(
                    "SELECT UPPER(SUBSTR(slug,1,1)) AS pfx FROM booking_services WHERE id = $svcId LIMIT 1"
                );
                $pfx = $pfxRes ? ($pfxRes->fetch_assoc()['pfx'] ?? 'A') : 'A';
                $ins2 = $db->prepare(
                    'INSERT IGNORE INTO queue_status (location_id, queue_date, counter_prefix, last_issued, is_open)
                     VALUES (?, ?, ?, 0, 1)'
                );
                $ins2->bind_param('iss', $locId, $today, $pfx);
                $ins2->execute();
                $ins2->close();
                $qsId      = $db->insert_id;
                $prefix    = $pfx;
                $lastIssued = 0;
            } else {
                $qsId       = $qsRow['id'];
                $prefix     = $qsRow['counter_prefix'];
                $lastIssued = (int)$qsRow['last_issued'];
            }

            $newNum    = $lastIssued + 1;
            $queueNo   = sprintf('%s-%03d', $prefix, $newNum);
            $aptDate   = $today;
            $aptTime   = date('H:i:s');
            $basePrice = 0.00;

            // Insert appointment
            $insApt = $db->prepare(
                'INSERT INTO appointments
                 (user_id, service_id, location_id, queue_number, appointment_date,
                  appointment_time, priority, base_price, notes,
                  guest_name, guest_phone, status, confirmed_at)
                 VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "in_queue", NOW())'
            );
            $insApt->bind_param(
                'iisssssdss',
                $svcId, $locId, $queueNo, $aptDate, $aptTime,
                $priority, $basePrice, $notes, $custName, $custPhone
            );

            if ($insApt->execute()) {
                $newAptId = $db->insert_id;
                // Update counter
                $upd = $db->prepare('UPDATE queue_status SET last_issued = ? WHERE id = ?');
                $upd->bind_param('ii', $newNum, $qsId);
                $upd->execute();
                $upd->close();

                // Get service name for log
                $svcNameRes = $db->query("SELECT name FROM booking_services WHERE id = $svcId LIMIT 1");
                $svcNameLog = $svcNameRes ? ($svcNameRes->fetch_assoc()['name'] ?? '') : '';

                writeQueueLog(
                    $db, $newAptId, $queueNo,
                    'added',
                    $sessionUserId,
                    $_SESSION['user_name'] ?? 'Staff',
                    $svcId, $svcNameLog,
                    $custName, $custPhone,
                    '', $notes
                );

                $msg = "Added <strong>{$custName}</strong> to queue as <strong>{$queueNo}</strong>.";
                $msgType = 'success';
            } else {
                $msg = 'Failed to add to queue. Please try again.';
                $msgType = 'error';
            }
            $insApt->close();
        }
    }

    // ── ADVANCE QUEUE (Next) ───────────────────────────────────
    if ($action === 'next_queue') {
        $locId = (int)($_POST['location_id'] ?? 0);
        $svcId = (int)($_POST['service_id']  ?? 0);

        if (!isAllowedService($svcId, $allowedServiceIds)) {
            $msg = 'Not authorised.'; $msgType = 'error';
        } else {
            // Get next in_queue record
            $nxt = $db->prepare(
                "SELECT id, queue_number FROM appointments
                  WHERE service_id=? AND appointment_date=CURDATE() AND status='in_queue'
                  ORDER BY
                    CASE priority WHEN 'vip' THEN 1 WHEN 'express' THEN 2 ELSE 3 END,
                    id ASC
                  LIMIT 1"
            );
            $nxt->bind_param('i', $svcId);
            $nxt->execute();
            $nextRow = $nxt->get_result()->fetch_assoc();
            $nxt->close();

            if ($nextRow) {
                $upd2 = $db->prepare(
                    "UPDATE appointments SET status='serving', served_at=NOW() WHERE id=?"
                );
                $upd2->bind_param('i', $nextRow['id']);
                $upd2->execute();
                $upd2->close();

                // Update current_number in queue_status
                $upd3 = $db->prepare(
                    "UPDATE queue_status SET current_number=? WHERE location_id=? AND queue_date=CURDATE()"
                );
                $upd3->bind_param('si', $nextRow['queue_number'], $locId);
                $upd3->execute();
                $upd3->close();

                $msg = "Now serving <strong>{$nextRow['queue_number']}</strong>.";
                $msgType = 'success';
            } else {
                $msg = 'No more people in queue.';
                $msgType = 'info';
            }
        }
    }

    // ── PAUSE / RESUME QUEUE ───────────────────────────────────
    if ($action === 'toggle_pause') {
        $locId   = (int)($_POST['location_id'] ?? 0);
        $svcId   = (int)($_POST['service_id']  ?? 0);
        $paused  = (int)($_POST['paused']       ?? 0);
        $newPaused = $paused ? 0 : 1;

        if (!isAllowedService($svcId, $allowedServiceIds)) {
            $msg = 'Not authorised.'; $msgType = 'error';
        } else {
            $upd = $db->prepare(
                "UPDATE queue_status SET is_paused=? WHERE location_id=? AND queue_date=CURDATE()"
            );
            $upd->bind_param('ii', $newPaused, $locId);
            $upd->execute();
            $upd->close();
            $msg = $newPaused ? 'Queue paused.' : 'Queue resumed.';
            $msgType = 'info';
        }
    }

    // ── CANCEL APPOINTMENT ─────────────────────────────────────
    if ($action === 'cancel_apt') {
        $aptId  = (int)($_POST['apt_id'] ?? 0);
        $reason = trim($_POST['cancel_reason'] ?? 'Cancelled by admin');

        // Verify this appointment belongs to an allowed service
        $chk = $db->prepare('SELECT service_id FROM appointments WHERE id = ? LIMIT 1');
        $chk->bind_param('i', $aptId);
        $chk->execute();
        $aptRow = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$aptRow || !isAllowedService((int)$aptRow['service_id'], $allowedServiceIds)) {
            $msg = 'Not authorised to cancel that appointment.'; $msgType = 'error';
        } else {
            $upd = $db->prepare(
                "UPDATE appointments SET status='cancelled', cancelled_at=NOW(), cancellation_reason=?
                  WHERE id=?"
            );
            $upd->bind_param('si', $reason, $aptId);
            $upd->execute();
            $upd->close();

            // Log the cancellation
            $logStmt = $db->prepare(
                "SELECT a.queue_number,
                        COALESCE(NULLIF(TRIM(a.guest_name),''), CONCAT(u.first_name,' ',u.last_name)) AS cname,
                        COALESCE(NULLIF(TRIM(a.guest_phone),''), u.phone) AS cphone,
                        bs.name AS svc_name
                 FROM appointments a
                 LEFT JOIN users u  ON u.id  = a.user_id
                 LEFT JOIN booking_services bs ON bs.id = a.service_id
                 WHERE a.id = ? LIMIT 1"
            );
            $logStmt->bind_param('i', $aptId);
            $logStmt->execute();
            $logRow = $logStmt->get_result()->fetch_assoc();
            $logStmt->close();

            writeQueueLog(
                $db, $aptId,
                $logRow['queue_number'] ?? '',
                'cancelled',
                $sessionUserId,
                $_SESSION['user_name'] ?? 'Staff',
                (int)$aptRow['service_id'],
                $logRow['svc_name']  ?? '',
                $logRow['cname']     ?? '',
                $logRow['cphone']    ?? '',
                $reason
            );

            $msg = 'Appointment cancelled.'; $msgType = 'success';
        }
    }

    // ── MARK NO-SHOW ───────────────────────────────────────────
    if ($action === 'mark_noshow') {
        $aptId = (int)($_POST['apt_id'] ?? 0);
        $chk   = $db->prepare('SELECT service_id FROM appointments WHERE id=? LIMIT 1');
        $chk->bind_param('i', $aptId);
        $chk->execute();
        $aptRow = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$aptRow || !isAllowedService((int)$aptRow['service_id'], $allowedServiceIds)) {
            $msg = 'Not authorised.'; $msgType = 'error';
        } else {
            $upd = $db->prepare("UPDATE appointments SET status='no_show', cancelled_at=NOW() WHERE id=?");
            $upd->bind_param('i', $aptId);
            $upd->execute();
            $upd->close();

            $logStmt = $db->prepare(
                "SELECT a.queue_number,
                        COALESCE(NULLIF(TRIM(a.guest_name),''), CONCAT(u.first_name,' ',u.last_name)) AS cname,
                        COALESCE(NULLIF(TRIM(a.guest_phone),''), u.phone) AS cphone,
                        bs.name AS svc_name
                 FROM appointments a
                 LEFT JOIN users u  ON u.id  = a.user_id
                 LEFT JOIN booking_services bs ON bs.id = a.service_id
                 WHERE a.id = ? LIMIT 1"
            );
            $logStmt->bind_param('i', $aptId);
            $logStmt->execute();
            $logRow = $logStmt->get_result()->fetch_assoc();
            $logStmt->close();

            writeQueueLog(
                $db, $aptId,
                $logRow['queue_number'] ?? '',
                'no_show',
                $sessionUserId,
                $_SESSION['user_name'] ?? 'Staff',
                (int)$aptRow['service_id'],
                $logRow['svc_name'] ?? '',
                $logRow['cname']    ?? '',
                $logRow['cphone']   ?? ''
            );

            $msg = 'Marked as no-show.'; $msgType = 'info';
        }
    }

    // ── CONFIRM / ACCEPT APPOINTMENT ──────────────────────────
    if ($action === 'confirm_apt') {
        $aptId = (int)($_POST['apt_id'] ?? 0);
        $chk   = $db->prepare('SELECT service_id FROM appointments WHERE id=? LIMIT 1');
        $chk->bind_param('i', $aptId);
        $chk->execute();
        $aptRow = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$aptRow || !isAllowedService((int)$aptRow['service_id'], $allowedServiceIds)) {
            $msg = 'Not authorised to confirm that appointment.'; $msgType = 'error';
        } else {
            $upd = $db->prepare(
                "UPDATE appointments SET status='in_queue', confirmed_at=NOW() WHERE id=? AND status IN ('pending','confirmed')"
            );
            $upd->bind_param('i', $aptId);
            $upd->execute();
            $affectedRows = $upd->affected_rows;
            $upd->close();

            if ($affectedRows > 0) {
                // Fetch extra details for the log
                $logStmt = $db->prepare(
                    "SELECT a.queue_number,
                            COALESCE(NULLIF(TRIM(a.guest_name),''), CONCAT(u.first_name,' ',u.last_name)) AS cname,
                            COALESCE(NULLIF(TRIM(a.guest_phone),''), u.phone) AS cphone,
                            bs.name AS svc_name
                     FROM appointments a
                     LEFT JOIN users u  ON u.id  = a.user_id
                     LEFT JOIN booking_services bs ON bs.id = a.service_id
                     WHERE a.id = ? LIMIT 1"
                );
                $logStmt->bind_param('i', $aptId);
                $logStmt->execute();
                $logRow = $logStmt->get_result()->fetch_assoc();
                $logStmt->close();

                writeQueueLog(
                    $db, $aptId,
                    $logRow['queue_number'] ?? '',
                    'accepted',
                    $sessionUserId,
                    $_SESSION['user_name'] ?? 'Staff',
                    (int)$aptRow['service_id'],
                    $logRow['svc_name']  ?? '',
                    $logRow['cname']     ?? '',
                    $logRow['cphone']    ?? ''
                );
            }

            $msg = 'Appointment accepted and added to queue.'; $msgType = 'success';
        }
    }

    // ── MARK COMPLETED ─────────────────────────────────────────
    if ($action === 'complete_apt') {
        $aptId = (int)($_POST['apt_id'] ?? 0);
        $chk   = $db->prepare('SELECT service_id FROM appointments WHERE id=? LIMIT 1');
        $chk->bind_param('i', $aptId);
        $chk->execute();
        $aptRow = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$aptRow || !isAllowedService((int)$aptRow['service_id'], $allowedServiceIds)) {
            $msg = 'Not authorised.'; $msgType = 'error';
        } else {
            $upd = $db->prepare(
                "UPDATE appointments SET status='completed', completed_at=NOW() WHERE id=? AND status='serving'"
            );
            $upd->bind_param('i', $aptId);
            $upd->execute();
            $affected = $upd->affected_rows;
            $upd->close();

            if ($affected > 0) {
                $logStmt = $db->prepare(
                    "SELECT a.queue_number,
                            COALESCE(NULLIF(TRIM(a.guest_name),''), CONCAT(u.first_name,' ',u.last_name)) AS cname,
                            COALESCE(NULLIF(TRIM(a.guest_phone),''), u.phone) AS cphone,
                            bs.name AS svc_name
                     FROM appointments a
                     LEFT JOIN users u  ON u.id  = a.user_id
                     LEFT JOIN booking_services bs ON bs.id = a.service_id
                     WHERE a.id = ? LIMIT 1"
                );
                $logStmt->bind_param('i', $aptId);
                $logStmt->execute();
                $logRow = $logStmt->get_result()->fetch_assoc();
                $logStmt->close();

                writeQueueLog(
                    $db, $aptId,
                    $logRow['queue_number'] ?? '',
                    'completed',
                    $sessionUserId,
                    $_SESSION['user_name'] ?? 'Staff',
                    (int)$aptRow['service_id'],
                    $logRow['svc_name'] ?? '',
                    $logRow['cname']    ?? '',
                    $logRow['cphone']   ?? ''
                );
            }

            $msg = 'Appointment marked as completed.'; $msgType = 'success';
        }
    }

    // ── RESET DAILY COUNTERS (admin/dev only) ─────────────────
    if ($action === 'reset_counters' && $isAdminOrDev) {
        $db->query("UPDATE queue_status SET last_issued=0, current_number=NULL WHERE queue_date=CURDATE()");
        $msg = 'Daily counters reset.'; $msgType = 'success';
    }

    // Redirect to prevent form re-submit
    $_SESSION['mq_msg']      = $msg;
    $_SESSION['mq_msg_type'] = $msgType;
    header('Location: manage-queue.php');
    exit();
}

// Pull flash message
if (isset($_SESSION['mq_msg'])) {
    $msg     = $_SESSION['mq_msg'];
    $msgType = $_SESSION['mq_msg_type'] ?? 'info';
    unset($_SESSION['mq_msg'], $_SESSION['mq_msg_type']);
}

// ── Build queue data from DB ──────────────────────────────────
// For each assigned service get: location(s), queue_status, live appointments
$serviceData = [];
foreach ($assignedServices as $svc) {
    $svcId = $svc['id'];

    // Locations
    $locRes = $db->prepare(
        'SELECT id, name, address, phone, hours, price, duration_min, is_active
           FROM service_locations WHERE service_id=? AND is_active=1 ORDER BY id ASC'
    );
    $locRes->bind_param('i', $svcId);
    $locRes->execute();
    $locations = $locRes->get_result()->fetch_all(MYSQLI_ASSOC);
    $locRes->close();

    // Queue status per location (today)
    $qsData = [];
    foreach ($locations as $loc) {
        $locId = $loc['id'];
        $qsStmt = $db->prepare(
            "SELECT qs.*, 
                COUNT(CASE WHEN a.status='in_queue' THEN 1 END)  AS waiting_count,
                COUNT(CASE WHEN a.status='serving'  THEN 1 END)  AS serving_count,
                COUNT(CASE WHEN a.status='completed' THEN 1 END) AS done_count
             FROM queue_status qs
             LEFT JOIN appointments a ON a.service_id = ? AND a.appointment_date = qs.queue_date
             WHERE qs.location_id=? AND qs.queue_date=CURDATE()
             GROUP BY qs.id"
        );
        $qsStmt->bind_param('ii', $svcId, $locId);
        $qsStmt->execute();
        $row = $qsStmt->get_result()->fetch_assoc();
        $qsStmt->close();

        if (!$row) {
            // Auto-create today's status row
            $pfx = strtoupper(substr($svc['slug'], 0, 1));
            $ci  = $db->prepare(
                'INSERT IGNORE INTO queue_status (location_id, queue_date, counter_prefix, last_issued, is_open)
                 VALUES (?, CURDATE(), ?, 0, 1)'
            );
            $ci->bind_param('is', $locId, $pfx);
            $ci->execute();
            $ci->close();
            $row = [
                'id' => $db->insert_id, 'location_id' => $locId,
                'counter_prefix' => $pfx, 'last_issued' => 0,
                'current_number' => null, 'is_paused' => 0, 'is_open' => 1,
                'waiting_count' => 0, 'serving_count' => 0, 'done_count' => 0,
            ];
        }
        $qsData[$locId] = $row;
    }

    // Live appointments: today's queue (in_queue/serving) + all pending/confirmed bookings (any date)
    if (!empty($locations)) {
        $aptStmt = $db->prepare(
            "SELECT a.id, a.queue_number,
                    COALESCE(NULLIF(TRIM(a.guest_name),''), CONCAT(u.first_name,' ',u.last_name)) AS guest_name,
                    COALESCE(NULLIF(TRIM(a.guest_phone),''), u.phone) AS guest_phone,
                    a.status, a.priority, a.notes, a.appointment_date, a.appointment_time
             FROM appointments a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.service_id = ?
               AND (
                   (a.status IN ('in_queue','serving') AND a.appointment_date = CURDATE())
                   OR a.status IN ('pending','confirmed')
               )
             ORDER BY
               CASE a.status WHEN 'serving' THEN 0 WHEN 'in_queue' THEN 1
                             WHEN 'confirmed' THEN 2 ELSE 3 END,
               CASE a.priority WHEN 'vip' THEN 1 WHEN 'express' THEN 2 ELSE 3 END,
               a.appointment_date ASC, a.id ASC"
        );
        $aptStmt->bind_param('i', $svcId);
        $aptStmt->execute();
        $liveAppointments = $aptStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $aptStmt->close();
    } else {
        $liveAppointments = [];
    }

    $serviceData[] = array_merge($svc, [
        'locations'        => $locations,
        'queue_status'     => $qsData,
        'live_appointments'=> $liveAppointments,
    ]);
}

// ── Recent activity log (last 30, across allowed services) ──
if (!empty($allowedServiceIds)) {
    $ph2  = implode(',', array_fill(0, count($allowedServiceIds), '?'));
    $recStmt = $db->prepare(
        "SELECT ql.queue_number, ql.customer_name, ql.service_name,
                ql.action, ql.performed_by_name, ql.cancel_reason, ql.created_at
         FROM queue_logs ql
         WHERE ql.service_id IN ($ph2)
           AND DATE(ql.created_at) = CURDATE()
         ORDER BY ql.created_at DESC
         LIMIT 30"
    );
    $recStmt->bind_param(str_repeat('i', count($allowedServiceIds)), ...$allowedServiceIds);
    $recStmt->execute();
    $recentDone = $recStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recStmt->close();
} else {
    $recentDone = [];
}

$pageTitle = 'Queue Management';
include('../includes/header.php');
?>

<!-- Extra styles -->
<style>
.status-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
.badge-serving  { background:#dcfce7; color:#15803d; }
.badge-in_queue { background:#e0f2fe; color:#0369a1; }
.badge-pending  { background:#fef9c3; color:#92400e; }
.badge-confirmed{ background:#ede9fe; color:#5b21b6; }
.badge-completed{ background:#f1f5f9; color:#475569; }
.badge-cancelled{ background:#fee2e2; color:#991b1b; }
.badge-no_show  { background:#fce7f3; color:#9d174d; }
.priority-vip     { color:#7c3aed; font-weight:800; }
.priority-express { color:#d97706; font-weight:700; }
.priority-standard{ color:#64748b; }
</style>

<div class="max-w-7xl mx-auto">

    <!-- ── Page header ────────────────────────────────────────── -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Queue Management</h1>
            <p class="text-gray-500 mt-1">
                <?php if ($isServiceAdmin): ?>
                    Managing:
                    <?php foreach ($assignedServices as $i => $s): ?>
                        <strong class="text-[#3aabb1]"><?php echo htmlspecialchars($s['name']); ?></strong><?php echo $i < count($assignedServices)-1 ? ', ' : ''; ?>
                    <?php endforeach; ?>
                    — Service Admin access
                <?php else: ?>
                    Full access — all services live view
                <?php endif; ?>
            </p>
        </div>
        <div class="flex gap-3">
            <?php if ($isAdminOrDev): ?>
            <a href="dashboard.php" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <?php endif; ?>
            <button onclick="location.reload()" class="px-5 py-2 gradient-bg text-white font-semibold rounded-xl hover:opacity-90 text-sm transition-all shadow-sm">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- ── Flash message ──────────────────────────────────────── -->
    <?php if ($msg): ?>
    <div class="mb-6 px-5 py-3.5 rounded-xl text-sm font-semibold flex items-center gap-2 flash-message
        <?php echo $msgType==='error' ? 'bg-red-50 text-red-700 border border-red-200'
                : ($msgType==='success' ? 'bg-green-50 text-green-700 border border-green-200'
                : 'bg-blue-50 text-blue-700 border border-blue-200'); ?>">
        <i class="fas <?php echo $msgType==='error' ? 'fa-exclamation-circle' : ($msgType==='success' ? 'fa-check-circle' : 'fa-info-circle'); ?>"></i>
        <?php echo $msg; ?>
    </div>
    <?php endif; ?>

    <!-- ── Service Admin notice ───────────────────────────────── -->
    <?php if ($isServiceAdmin): ?>
    <div class="mb-6 bg-[#f0fdfd] border border-[#A6E3E9] rounded-xl p-4 flex items-start gap-3">
        <i class="fas fa-user-cog text-[#71C9CE] mt-0.5 flex-shrink-0"></i>
        <div>
            <div class="font-semibold text-[#3aabb1] text-sm">Service Admin Access</div>
            <div class="text-gray-500 text-xs mt-0.5">
                You can only manage your assigned service(s). For system-wide access, contact your main admin.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════
         LOOP: one section per service
    ══════════════════════════════════════════════════════════ -->
    <?php foreach ($serviceData as $svc): ?>
    <?php
        $svcId    = $svc['id'];
        $svcSlug  = htmlspecialchars($svc['slug']);
        $svcName  = htmlspecialchars($svc['name']);
        $svcIcon  = htmlspecialchars($svc['icon_class'] ?? 'fa-concierge-bell');
        $svcColor = htmlspecialchars($svc['color_hex'] ?? '#71C9CE');
        $locs     = $svc['locations'];
        $liveApts = $svc['live_appointments'];
        $qsMap    = $svc['queue_status'];
        $canQueue = (bool)$svc['can_manage_queue'];
        $canBook  = (bool)$svc['can_manage_bookings'];
        $canReport= (bool)$svc['can_view_reports'];
    ?>
    <div class="mb-10">

        <!-- Service heading -->
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-lg"
                 style="background:<?php echo $svcColor; ?>">
                <i class="fas <?php echo $svcIcon; ?>"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?php echo $svcName; ?></h2>
                <div class="text-xs text-gray-400 flex gap-3 mt-0.5">
                    <?php if ($canQueue): ?><span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Manage Queue</span><?php endif; ?>
                    <?php if ($canBook): ?><span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Manage Bookings</span><?php endif; ?>
                    <?php if ($canReport): ?><span class="text-blue-600"><i class="fas fa-chart-bar mr-1"></i>Reports</span><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── LOCATION QUEUE STATUS CARDS ──────────────────── -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <?php foreach ($locs as $loc): ?>
        <?php
            $locId   = $loc['id'];
            $qs      = $qsMap[$locId] ?? [];
            $waiting = (int)($qs['waiting_count'] ?? 0);
            $serving = (int)($qs['serving_count']  ?? 0);
            $done    = (int)($qs['done_count']      ?? 0);
            $paused  = (int)($qs['is_paused']       ?? 0);
            $curNum  = $qs['current_number'] ?? '—';
            $total   = $waiting + $serving + $done;
        ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <!-- Card header -->
            <div class="px-5 py-4 text-white" style="background:<?php echo $svcColor; ?>">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-widest opacity-80 mb-0.5">Location</div>
                        <div class="font-bold leading-tight"><?php echo htmlspecialchars($loc['name']); ?></div>
                        <?php if ($loc['address']): ?>
                        <div class="text-xs opacity-70 mt-0.5"><?php echo htmlspecialchars($loc['address']); ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full font-bold
                        <?php echo $paused ? 'bg-yellow-400 text-yellow-900' : 'bg-white/20 text-white'; ?>">
                        <?php echo $paused ? 'Paused' : 'Active'; ?>
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-xl font-black"><?php echo $curNum; ?></div>
                        <div class="text-xs opacity-70">Serving</div>
                    </div>
                    <div>
                        <div class="text-xl font-black"><?php echo $waiting; ?></div>
                        <div class="text-xs opacity-70">Waiting</div>
                    </div>
                    <div>
                        <div class="text-xl font-black"><?php echo $done; ?></div>
                        <div class="text-xs opacity-70">Done</div>
                    </div>
                </div>
            </div>
            <!-- Card actions -->
            <?php if ($canQueue): ?>
            <div class="px-4 py-3 flex gap-2">
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action" value="toggle_pause">
                    <input type="hidden" name="location_id" value="<?php echo $locId; ?>">
                    <input type="hidden" name="service_id"  value="<?php echo $svcId; ?>">
                    <input type="hidden" name="paused"      value="<?php echo $paused; ?>">
                    <button type="submit" class="w-full py-2 rounded-lg text-xs font-semibold transition-all
                        <?php echo $paused ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100'; ?>">
                        <i class="fas <?php echo $paused ? 'fa-play' : 'fa-pause'; ?> mr-1"></i>
                        <?php echo $paused ? 'Resume' : 'Pause'; ?>
                    </button>
                </form>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action" value="next_queue">
                    <input type="hidden" name="location_id" value="<?php echo $locId; ?>">
                    <input type="hidden" name="service_id"  value="<?php echo $svcId; ?>">
                    <button type="submit" class="w-full py-2 rounded-lg text-xs font-semibold bg-[#E3FDFD] text-[#3aabb1] hover:bg-[#c7f5f7] transition-all"
                        onclick="return confirm('Advance queue to next customer?')">
                        <i class="fas fa-forward mr-1"></i>Next
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($locs)): ?>
        <div class="col-span-3 bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-sm text-yellow-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>No active locations found for this service.
            <?php if ($isAdminOrDev): ?> <a href="dashboard.php" class="underline">Add one from Dashboard</a>.<?php endif; ?>
        </div>
        <?php endif; ?>
        </div>

        <!-- ── LIVE QUEUE TABLE ───────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    Live Queue — Today
                    <span class="text-xs text-gray-400 font-normal">(<?php echo date('M d, Y'); ?>)</span>
                </h3>
                <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full font-semibold">
                    <?php echo count($liveApts); ?> active
                </span>
            </div>
            <?php if (empty($liveApts)): ?>
            <div class="py-12 text-center text-gray-400">
                <i class="fas fa-inbox text-3xl mb-3 block opacity-30"></i>
                No active queue entries for today. Add a walk-in below.
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-50">
                    <thead class="bg-gray-50">
                        <tr>
                            <?php foreach (['#','Queue No.','Customer','Service','Priority','Status','Date','Time','Actions'] as $h): ?>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap"><?php echo $h; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php foreach ($liveApts as $i => $apt): ?>
                    <tr class="hover:bg-gray-50 transition-colors <?php echo $apt['status']==='serving' ? 'bg-green-50' : ''; ?>">
                        <td class="px-4 py-3 text-sm text-gray-400"><?php echo $i+1; ?></td>
                        <td class="px-4 py-3 font-bold text-gray-900 text-sm"><?php echo htmlspecialchars($apt['queue_number']); ?></td>
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-gray-800"><?php echo htmlspecialchars($apt['guest_name'] ?? 'N/A'); ?></div>
                            <?php if ($apt['guest_phone']): ?>
                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($apt['guest_phone']); ?></div>
                            <?php endif; ?>
                            <?php if ($apt['notes']): ?>
                            <div class="text-xs text-blue-500 mt-0.5 italic truncate max-w-[140px]" title="<?php echo htmlspecialchars($apt['notes']); ?>">
                                <?php echo htmlspecialchars($apt['notes']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($svcName); ?></td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap priority-<?php echo $apt['priority']; ?>">
                            <i class="fas <?php echo $apt['priority']==='vip' ? 'fa-crown' : ($apt['priority']==='express' ? 'fa-bolt' : 'fa-user'); ?> mr-1"></i>
                            <?php echo ucfirst($apt['priority']); ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="status-badge badge-<?php echo $apt['status']; ?>">
                                <?php echo str_replace('_',' ', ucfirst($apt['status'])); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                            <?php
                            $apDate  = $apt['appointment_date'] ?? '';
                            $isToday = ($apDate === date('Y-m-d'));
                            echo $isToday
                                ? '<span class="text-green-600 font-semibold">Today</span>'
                                : htmlspecialchars(date('M j, Y', strtotime($apDate)));
                            ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                            <?php echo substr($apt['appointment_time'], 0, 5); ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($canQueue): ?>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <?php if (in_array($apt['status'], ['pending','confirmed'])): ?>
                                <form method="POST" class="inline"
                                      onsubmit="return confirm('Accept this booking and add to queue?')">
                                    <input type="hidden" name="action" value="confirm_apt">
                                    <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 text-green-700 hover:bg-green-200 rounded-lg text-xs font-bold transition-all"
                                        title="Accept & Add to Queue">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($apt['status'] === 'in_queue'): ?>
                                <form method="POST" class="inline"
                                      onsubmit="return confirm('Call this customer and start serving?')">
                                    <input type="hidden" name="action"      value="next_queue">
                                    <input type="hidden" name="location_id" value="<?php echo $locId; ?>">
                                    <input type="hidden" name="service_id"  value="<?php echo $svcId; ?>">
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 text-blue-700 hover:bg-blue-200 rounded-lg text-xs font-bold transition-all"
                                        title="Call / Serve">
                                        <i class="fas fa-play"></i> Serve
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if ($apt['status'] === 'serving'): ?>
                                <form method="POST" class="inline"
                                      onsubmit="return confirm('Mark this appointment as completed?')">
                                    <input type="hidden" name="action" value="complete_apt">
                                    <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-teal-100 text-teal-700 hover:bg-teal-200 rounded-lg text-xs font-bold transition-all"
                                        title="Mark Done">
                                        <i class="fas fa-check-double"></i> Done
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="inline"
                                      onsubmit="return confirm('Mark as no-show?')">
                                    <input type="hidden" name="action" value="mark_noshow">
                                    <input type="hidden" name="apt_id" value="<?php echo $apt['id']; ?>">
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 rounded-lg text-xs font-bold transition-all"
                                        title="No-Show">
                                        <i class="fas fa-user-slash"></i>
                                    </button>
                                </form>
                                <button onclick="openCancelModal(<?php echo $apt['id']; ?>, '<?php echo addslashes($apt['guest_name'] ?? ''); ?>')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-500 hover:bg-red-100 rounded-lg text-xs font-bold transition-all"
                                    title="Cancel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── ADD TO QUEUE FORM ──────────────────────────────── -->
        <?php if ($canQueue && !empty($locs)): ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-user-plus text-[#71C9CE]"></i>
                Add Walk-In to Queue
                <?php if ($isServiceAdmin): ?>
                <span class="text-xs text-gray-400 font-normal ml-1">— <?php echo $svcName; ?></span>
                <?php endif; ?>
            </h3>
            <form method="POST" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="action"     value="add_queue">
                <input type="hidden" name="service_id" value="<?php echo $svcId; ?>">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Location *</label>
                    <select name="location_id" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                        <?php foreach ($locs as $l): ?>
                        <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Customer Name *</label>
                    <input type="text" name="customer_name" required placeholder="e.g. Juan Dela Cruz"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Phone</label>
                    <input type="tel" name="customer_phone" placeholder="+63 917 000 0000"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Priority</label>
                    <select name="priority"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                        <option value="standard">Standard</option>
                        <option value="express">Express</option>
                        <option value="vip">VIP</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Queue Type</label>
                    <select name="queue_type"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                        <option value="walk_in">Walk-in</option>
                        <option value="appointment">Appointment</option>
                        <option value="online">Online Booking</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Notes</label>
                    <input type="text" name="notes" placeholder="Optional notes…"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <button type="submit" class="px-6 py-2.5 gradient-bg text-white font-semibold rounded-xl hover:opacity-90 text-sm transition-all shadow-sm">
                        <i class="fas fa-plus-circle mr-2"></i>Add to Queue
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </div><!-- end service block -->
    <?php endforeach; ?>

    <!-- ── ACTIVITY LOG ──────────────────────────────────────── -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-5">Today's Activity Log</h2>
        <?php if (empty($recentDone)): ?>
        <div class="text-center text-gray-400 py-8">
            <i class="fas fa-clipboard-list text-3xl opacity-30 mb-2 block"></i>No activity yet today.
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-50">
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach (['Time','Queue No.','Customer','Service','Action','Staff','Note'] as $h): ?>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap"><?php echo $h; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                <?php
                $actionBadge = [
                    'accepted'  => ['bg-green-100 text-green-700',  'fa-check-circle',  'Accepted'],
                    'cancelled' => ['bg-red-100 text-red-600',       'fa-times-circle',  'Cancelled'],
                    'completed' => ['bg-teal-100 text-teal-700',     'fa-check-double',  'Completed'],
                    'no_show'   => ['bg-yellow-100 text-yellow-700', 'fa-user-slash',    'No-Show'],
                    'added'     => ['bg-blue-100 text-blue-600',     'fa-plus-circle',   'Added'],
                    'served'    => ['bg-purple-100 text-purple-700', 'fa-play-circle',   'Served'],
                ];
                foreach ($recentDone as $r):
                    $ab = $actionBadge[$r['action']] ?? ['bg-gray-100 text-gray-600', 'fa-circle', ucfirst($r['action'])];
                ?>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                        <?php echo date('H:i', strtotime($r['created_at'])); ?>
                    </td>
                    <td class="px-4 py-3 font-bold text-sm text-gray-800 whitespace-nowrap">
                        <?php echo htmlspecialchars($r['queue_number'] ?: '—'); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">
                        <?php echo htmlspecialchars($r['customer_name'] ?? '—'); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                        <?php echo htmlspecialchars($r['service_name'] ?? '—'); ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold <?php echo $ab[0]; ?>">
                            <i class="fas <?php echo $ab[1]; ?>"></i><?php echo $ab[2]; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                        <?php echo htmlspecialchars($r['performed_by_name'] ?? '—'); ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 max-w-[140px] truncate" title="<?php echo htmlspecialchars($r['cancel_reason'] ?? ''); ?>">
                        <?php echo htmlspecialchars($r['cancel_reason'] ?: ''); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── ADMIN: Bulk actions & reset ───────────────────────── -->
    <?php if ($isAdminOrDev): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-5">Admin Bulk Actions</h2>
        <div class="flex flex-wrap gap-3">
            <form method="POST" onsubmit="return confirm('Reset ALL daily queue counters?')">
                <input type="hidden" name="action" value="reset_counters">
                <button type="submit" class="px-5 py-2.5 bg-green-50 text-green-700 border border-green-200 rounded-xl text-sm font-semibold hover:bg-green-100 transition-all">
                    <i class="fas fa-redo mr-2"></i>Reset Daily Counters
                </button>
            </form>
            <a href="dashboard.php" class="px-5 py-2.5 bg-[#E3FDFD] text-[#3aabb1] border border-[#A6E3E9] rounded-xl text-sm font-semibold hover:bg-[#c6f5f7] transition-all">
                <i class="fas fa-users-cog mr-2"></i>Manage Service Admins
            </a>
        </div>
    </div>
    <?php endif; ?>

</div><!-- end max-w -->

<!-- ── Cancel Modal ──────────────────────────────────────────── -->
<div id="cancel-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Cancel Appointment</h3>
        <p class="text-sm text-gray-500 mb-4">Cancel for <strong id="cancel-name"></strong>?</p>
        <form method="POST">
            <input type="hidden" name="action"  value="cancel_apt">
            <input type="hidden" name="apt_id"  id="cancel-apt-id">
            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 uppercase block mb-1.5">Reason (optional)</label>
                <input type="text" name="cancel_reason" placeholder="e.g. Customer request"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-[#71C9CE] focus:outline-none">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeCancelModal()"
                    class="flex-1 py-2.5 border-2 border-gray-200 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-50">
                    Keep It
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 bg-red-500 text-white rounded-xl text-sm font-semibold hover:bg-red-600">
                    <i class="fas fa-times mr-1"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(aptId, name) {
    document.getElementById('cancel-apt-id').value = aptId;
    document.getElementById('cancel-name').textContent = name;
    const m = document.getElementById('cancel-modal');
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function closeCancelModal() {
    const m = document.getElementById('cancel-modal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}
document.getElementById('cancel-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});
</script>

<?php
$db->close();
include('../includes/footer.php');
?>