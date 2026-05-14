<?php
session_start();
require_once('../includes/users_store.php');

// ── Access control ───────────────────────────────────────────────
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'developer'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'service_admin') {
        header('Location: manage-queue.php');
    } else {
        $_SESSION['error'] = 'You do not have permission to access the admin dashboard.';
        header('Location: ../public/login.php');
    }
    exit();
}

$isDeveloper = $_SESSION['user_role'] === 'developer';

// ── DB connection ────────────────────────────────────────────────
$db = new mysqli('localhost', 'root', '', 'aquaqueue_db');
if ($db->connect_error) {
    die('Database connection failed: ' . $db->connect_error);
}
$db->set_charset('utf8mb4');

// ── POST handlers ────────────────────────────────────────────────
$formError   = '';
$formSuccess = '';

// ── Add User ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $phone     = trim($_POST['phone']      ?? '');
    $roleId    = (int)($_POST['role_id']   ?? 4);
    $password  = $_POST['password']        ?? '';

    if (!$firstName || !$lastName || !$email || !$password) {
        $formError = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $formError = 'Password must be at least 8 characters.';
    } else {
        $chk  = $db->prepare('SELECT id FROM users WHERE email = ?');
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $formError = 'An account with that email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare('INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?, 1, 1)');
            $ins->bind_param('isssss', $roleId, $firstName, $lastName, $email, $phone, $hash);
            if ($ins->execute()) {
                $formSuccess = "User <strong>{$firstName} {$lastName}</strong> created successfully.";
                echo "<meta http-equiv='refresh' content='0'>";
            } else {
                $formError = 'Failed to create user. Please try again.';
            }
            $ins->close();
        }
        $chk->close();
    }
}

// ── Change Role ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $newRoleId    = (int)($_POST['new_role_id']    ?? 0);

    if ($targetUserId === (int)($_SESSION['user_id'] ?? 0)) {
        $formError = 'You cannot change your own role.';
    } elseif ($targetUserId < 1 || $newRoleId < 1) {
        $formError = 'Invalid user or role selection.';
    } else {
        if (!$isDeveloper && $newRoleId === 1) {
            $formError = 'Only developers can assign the Developer role.';
        } else {
            $upd = $db->prepare('UPDATE users SET role_id = ? WHERE id = ?');
            $upd->bind_param('ii', $newRoleId, $targetUserId);
            if ($upd->execute() && $upd->affected_rows > 0) {
                $formSuccess = 'User role updated successfully.';
                echo "<meta http-equiv='refresh' content='0'>";
            } else {
                $formError = 'Role update failed or no change was made.';
            }
            $upd->close();
        }
    }
}

// ── Assign Service Admin ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_service_admin') {
    $targetUserId  = (int)($_POST['target_user_id'] ?? 0);
    $serviceId     = (int)($_POST['service_id']     ?? 0);
    $canQueue      = isset($_POST['can_manage_queue'])     ? 1 : 0;
    $canBookings   = isset($_POST['can_manage_bookings'])  ? 1 : 0;
    $canReports    = isset($_POST['can_view_reports'])     ? 1 : 0;
    $canLocations  = isset($_POST['can_manage_locations']) ? 1 : 0;
    $actorId       = (int)($_SESSION['user_id'] ?? 0);

    if (!$targetUserId || !$serviceId) {
        $formError = 'Please select both a user and a service.';
    } else {
        // Upsert assignment
        $ups = $db->prepare(
            'INSERT INTO service_admin_assignments
             (user_id, service_id, assigned_by, can_manage_queue, can_manage_bookings, can_view_reports, can_manage_locations)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               assigned_by           = VALUES(assigned_by),
               can_manage_queue      = VALUES(can_manage_queue),
               can_manage_bookings   = VALUES(can_manage_bookings),
               can_view_reports      = VALUES(can_view_reports),
               can_manage_locations  = VALUES(can_manage_locations)'
        );
        $ups->bind_param('iiiiiii', $targetUserId, $serviceId, $actorId,
                         $canQueue, $canBookings, $canReports, $canLocations);
        if ($ups->execute()) {
            // Only promote plain users (role_id 4 = user, 5 = client) to service_admin.
            // Never touch developers (1), main admins (2), or already-service_admins (3).
            $updRole = $db->prepare(
                'UPDATE users SET role_id = 3 WHERE id = ? AND role_id IN (4, 5)'
            );
            $updRole->bind_param('i', $targetUserId);
            $updRole->execute();
            $updRole->close();
            $formSuccess = 'Service admin assignment saved successfully.';
        } else {
            $formError = 'Failed to assign service admin. Please try again.';
        }
        $ups->close();
    }
}

// ── Revoke Service Admin Assignment ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'revoke_assignment') {
    $assignId = (int)($_POST['assignment_id'] ?? 0);
    if ($assignId) {
        // Get the user_id before deleting
        $getUser = $db->prepare('SELECT user_id FROM service_admin_assignments WHERE id = ?');
        $getUser->bind_param('i', $assignId);
        $getUser->execute();
        $revokedRow = $getUser->get_result()->fetch_assoc();
        $getUser->close();

        $del = $db->prepare('DELETE FROM service_admin_assignments WHERE id = ?');
        $del->bind_param('i', $assignId);
        $del->execute();
        $del->close();

        // If the user has no remaining assignments, downgrade them back to plain user (role_id 4)
        if ($revokedRow) {
            $revokedUserId = (int)$revokedRow['user_id'];
            $countStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM service_admin_assignments WHERE user_id = ?');
            $countStmt->bind_param('i', $revokedUserId);
            $countStmt->execute();
            $countRow = $countStmt->get_result()->fetch_assoc();
            $countStmt->close();

            if ((int)$countRow['cnt'] === 0) {
                $downgrade = $db->prepare('UPDATE users SET role_id = 4 WHERE id = ? AND role_id = 3');
                $downgrade->bind_param('i', $revokedUserId);
                $downgrade->execute();
                $downgrade->close();
            }
        }

        $formSuccess = 'Assignment revoked successfully.';
    }
}

// ── Fetch all users ────────────────────────────────────
$usersResult = $db->query('
    SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
           u.is_active, u.created_at, r.id AS role_id, r.label AS role_label, r.name AS role_name
    FROM users u
    JOIN roles r ON r.id = u.role_id
    ORDER BY u.id ASC
');
$allUsers = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Fetch roles for dropdowns ────────────────────────────────────
$rolesResult = $db->query('SELECT id, name, label FROM roles ORDER BY id ASC');
$allRoles    = $rolesResult ? $rolesResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Fetch all booking services ──────────────────────────────────
$svcResult   = $db->query('SELECT id, slug, name, icon_class, color_hex FROM booking_services WHERE is_active=1 ORDER BY id ASC');
$allServices = $svcResult ? $svcResult->fetch_all(MYSQLI_ASSOC) : [];

// ── Fetch current service admin assignments ─────────────────────
$assignResult = $db->query(
    'SELECT saa.id, saa.user_id, saa.service_id,
            saa.can_manage_queue, saa.can_manage_bookings, saa.can_view_reports, saa.can_manage_locations,
            saa.assigned_at,
            CONCAT(u.first_name," ",u.last_name) AS user_name,
            u.email AS user_email,
            bs.name AS service_name, bs.icon_class, bs.color_hex,
            CONCAT(ab.first_name," ",ab.last_name) AS assigned_by_name
     FROM service_admin_assignments saa
     JOIN users u  ON u.id  = saa.user_id
     JOIN booking_services bs ON bs.id = saa.service_id
     LEFT JOIN users ab ON ab.id = saa.assigned_by
     ORDER BY saa.service_id ASC, saa.assigned_at ASC'
);
$allAssignments = $assignResult ? $assignResult->fetch_all(MYSQLI_ASSOC) : [];

// ═══════════════════════════════════════════════════════════════
//  LIVE DASHBOARD STATS — all pulled from DB
// ═══════════════════════════════════════════════════════════════

// 1) Today's visitors (appointments today with status != cancelled/no_show)
$r = $db->query("SELECT COUNT(*) AS cnt FROM appointments WHERE appointment_date = CURDATE() AND status NOT IN ('cancelled','no_show')");
$todayVisitors = $r ? (int)$r->fetch_assoc()['cnt'] : 0;

// 2) Average wait time today (seconds from created_at → served_at for served/completed today)
$r = $db->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, served_at)) AS avg_wait
                 FROM appointments
                 WHERE appointment_date = CURDATE() AND served_at IS NOT NULL");
$avgWaitMin = $r ? round((float)($r->fetch_assoc()['avg_wait'] ?? 0), 1) : 0;
$avgWaitStr = $avgWaitMin > 0 ? $avgWaitMin . ' min' : 'N/A';

// 3) Satisfaction proxy: % completed vs total served today
$r = $db->query("SELECT
                    SUM(status = 'completed') AS done,
                    SUM(status IN ('completed','no_show','cancelled')) AS total
                 FROM appointments WHERE appointment_date = CURDATE()");
$row = $r ? $r->fetch_assoc() : null;
$satisfactionPct = ($row && $row['total'] > 0) ? round(($row['done'] / $row['total']) * 100) : 0;

// 4) Pending appointments (all dates, status pending or confirmed not yet served)
$r = $db->query("SELECT COUNT(*) AS cnt FROM appointments WHERE status IN ('pending','confirmed')");
$pendingCount = $r ? (int)$r->fetch_assoc()['cnt'] : 0;

// 5) Active queues — live data per service (in_queue + serving today)
$activeQueuesResult = $db->query(
    "SELECT bs.name AS service_name, bs.icon_class,
            COALESCE(qs.current_number, '—') AS current_token,
            COUNT(CASE WHEN a.status='in_queue'  THEN 1 END) AS waiting,
            COUNT(CASE WHEN a.status='serving'   THEN 1 END) AS serving_now,
            bs.id AS service_id,
            CASE
                WHEN COUNT(CASE WHEN a.status='serving' THEN 1 END) > 0 THEN 'Serving'
                WHEN COUNT(CASE WHEN a.status='in_queue' THEN 1 END) > 8 THEN 'Busy'
                WHEN COUNT(CASE WHEN a.status='in_queue' THEN 1 END) > 3 THEN 'Moderate'
                ELSE 'Active'
            END AS queue_status
     FROM booking_services bs
     LEFT JOIN service_locations sl ON sl.service_id = bs.id AND sl.is_active = 1
     LEFT JOIN queue_status qs ON qs.location_id = sl.id AND qs.queue_date = CURDATE()
     LEFT JOIN appointments a ON a.service_id = bs.id AND a.appointment_date = CURDATE()
                              AND a.status IN ('in_queue','serving')
     WHERE bs.is_active = 1
     GROUP BY bs.id, bs.name, bs.icon_class, qs.current_number
     ORDER BY bs.id ASC"
);
$liveQueues = $activeQueuesResult ? $activeQueuesResult->fetch_all(MYSQLI_ASSOC) : [];

// 6) Today's schedule — next 8 appointments (all statuses except cancelled/no_show)
$todayScheduleResult = $db->query(
    "SELECT
        COALESCE(NULLIF(TRIM(a.guest_name),''), CONCAT(u.first_name,' ',u.last_name)) AS customer_name,
        bs.name AS service_name,
        bs.icon_class,
        a.appointment_time,
        a.queue_number,
        a.status,
        a.priority
     FROM appointments a
     LEFT JOIN users u ON u.id = a.user_id
     JOIN booking_services bs ON bs.id = a.service_id
     WHERE a.appointment_date = CURDATE()
       AND a.status NOT IN ('cancelled','no_show','completed')
     ORDER BY
       CASE a.status WHEN 'serving' THEN 0 WHEN 'in_queue' THEN 1 WHEN 'confirmed' THEN 2 ELSE 3 END,
       a.appointment_time ASC
     LIMIT 8"
);
$todaySchedule = $todayScheduleResult ? $todayScheduleResult->fetch_all(MYSQLI_ASSOC) : [];

// 7) Queue utilization % per service
$utilizationResult = $db->query(
    "SELECT bs.name,
            COUNT(CASE WHEN a.status IN ('in_queue','serving') THEN 1 END) AS active_count,
            COUNT(*) AS total_count
     FROM booking_services bs
     LEFT JOIN appointments a ON a.service_id = bs.id AND a.appointment_date = CURDATE()
     WHERE bs.is_active = 1
     GROUP BY bs.id, bs.name
     ORDER BY bs.id ASC"
);
$utilizationData = [];
if ($utilizationResult) {
    $colors = ['#71C9CE','#f472b6','#2dd4bf','#fbbf24','#60a5fa','#a78bfa'];
    $i = 0;
    while ($row = $utilizationResult->fetch_assoc()) {
        $total = (int)$row['total_count'];
        $active = (int)$row['active_count'];
        $pct = $total > 0 ? min(100, round(($active / max($total,1)) * 100)) : 0;
        // Scale to at least 5% for visibility if there's any data
        $utilizationData[] = [
            'label' => $row['name'],
            'pct'   => $pct,
            'color' => $colors[$i % count($colors)],
        ];
        $i++;
    }
}

// 8) Notification alerts
$alertsResult = $db->query(
    "SELECT 'high_wait' AS type,
            CONCAT(bs.name, ' has ', COUNT(*), ' people waiting') AS message,
            'fa-hourglass-half' AS icon, 'amber' AS color
     FROM appointments a
     JOIN booking_services bs ON bs.id = a.service_id
     WHERE a.status = 'in_queue' AND a.appointment_date = CURDATE()
     GROUP BY bs.id, bs.name
     HAVING COUNT(*) >= 5
     UNION ALL
     SELECT 'pending_confirm', CONCAT(COUNT(*), ' appointment(s) awaiting confirmation'), 'fa-calendar-check', 'blue'
     FROM appointments WHERE status = 'pending'
     UNION ALL
     SELECT 'completed_today', CONCAT(COUNT(*), ' appointment(s) completed today'), 'fa-check-circle', 'green'
     FROM appointments WHERE status = 'completed' AND appointment_date = CURDATE()
     ORDER BY type ASC
     LIMIT 5"
);
$liveAlerts = $alertsResult ? $alertsResult->fetch_all(MYSQLI_ASSOC) : [];

$pageTitle = 'Admin Dashboard';
include('../includes/header.php');
?>

<!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- Google Fonts - Modern Premium Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">

<style>
    * {
        font-family: 'Inter', sans-serif;
    }
    
    /* Premium Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-5px);
        }
    }
    
    @keyframes glowPulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(113, 201, 206, 0.4);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(113, 201, 206, 0);
        }
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* Animation Classes */
    .animate-fadeInUp {
        animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    .animate-fadeInLeft {
        animation: fadeInLeft 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    .animate-fadeInRight {
        animation: fadeInRight 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    .animate-scaleIn {
        animation: scaleIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    /* Stagger children animations */
    .stagger-children > * {
        opacity: 0;
        animation: fadeInUp 0.5s ease forwards;
    }
    
    .stagger-children > *:nth-child(1) { animation-delay: 0.05s; }
    .stagger-children > *:nth-child(2) { animation-delay: 0.1s; }
    .stagger-children > *:nth-child(3) { animation-delay: 0.15s; }
    .stagger-children > *:nth-child(4) { animation-delay: 0.2s; }
    .stagger-children > *:nth-child(5) { animation-delay: 0.25s; }
    .stagger-children > *:nth-child(6) { animation-delay: 0.3s; }
    
    /* Glassmorphism Effect */
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    /* Premium Hover Effects */
    .hover-lift {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.1);
    }
    
    .hover-glow:hover {
        box-shadow: 0 0 20px rgba(113, 201, 206, 0.3);
        border-color: #71C9CE;
    }
    
    /* Gradient Animations */
    .gradient-bg {
        background: linear-gradient(135deg, #71C9CE 0%, #4FB6BB 50%, #71C9CE 100%);
        background-size: 200% 200%;
        transition: background-position 0.3s ease;
    }
    
    .gradient-bg:hover {
        background-position: right center;
    }
    
    /* Modal Overlay */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        animation: fadeInUp 0.3s ease;
    }
    
    .modal-overlay.active {
        display: flex;
    }
    
    .modal-box {
        background: white;
        border-radius: 2rem;
        max-width: 520px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
        animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    /* Role Badges Premium */
    .role-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        transition: all 0.2s ease;
    }
    
    .role-badge-developer {
        background: linear-gradient(135deg, #ede9fe, #ddd6fe);
        color: #5b21b6;
    }
    
    .role-badge-admin {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #991b1b;
    }
    
    .role-badge-service_admin {
        background: linear-gradient(135deg, #e0f2fe, #bae6fd);
        color: #0369a1;
    }
    
    .role-badge-user {
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        color: #166534;
    }
    
    .role-badge-client {
        background: linear-gradient(135deg, #fef9c3, #fef08a);
        color: #854d0e;
    }
    
    /* Progress Bar Animation */
    .progress-bar {
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Table Row Hover */
    .table-row-hover {
        transition: all 0.2s ease;
    }
    
    .table-row-hover:hover {
        background: linear-gradient(90deg, rgba(113, 201, 206, 0.05), rgba(113, 201, 206, 0.02));
        transform: scale(1.01);
    }
    
    /* Scrollbar Styling */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #71C9CE;
        border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #4FB6BB;
    }
    
    /* Number Counter Animation */
    .stat-number {
        transition: all 0.3s ease;
    }
    
    /* Loading Skeleton */
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
    }
    
    /* Floating Badge Animation */
    .float-badge {
        animation: float 3s ease-in-out infinite;
    }
</style>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-10">
        
        <!-- Header Section with Animation -->
        <div class="animate-fadeInUp mb-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 gradient-bg rounded-2xl flex items-center justify-center shadow-lg float-badge">
                            <i class="fas fa-water text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl md:text-5xl font-extrabold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
                                AquaQueue
                            </h1>
                            <p class="text-slate-500 text-sm mt-1">Intelligent Queue Management System</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <?php if ($isDeveloper): ?>
                    <div class="relative group">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-lg animate-pulse">
                            <i class="fas fa-code text-xs"></i> Developer Mode
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="bg-white/80 backdrop-blur-sm rounded-full px-4 py-2 shadow-sm border border-slate-100 hover-lift cursor-pointer">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 gradient-bg rounded-full flex items-center justify-center text-white text-sm font-bold">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'Admin', 0, 1)); ?>
                            </div>
                            <span class="text-sm font-medium text-slate-700"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                            <i class="fas fa-chevron-down text-slate-400 text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages with Animation -->
        <?php if ($formSuccess): ?>
        <div class="animate-slideIn mb-6 bg-gradient-to-r from-emerald-50 to-emerald-100 border-l-4 border-emerald-500 rounded-2xl p-4 flex items-center gap-3 shadow-md">
            <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center animate-pulse">
                <i class="fas fa-check text-white text-sm"></i>
            </div>
            <div class="text-emerald-800 text-sm font-medium flex-1"><?php echo $formSuccess; ?></div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if ($formError): ?>
        <div class="animate-slideIn mb-6 bg-gradient-to-r from-rose-50 to-rose-100 border-l-4 border-rose-500 rounded-2xl p-4 flex items-center gap-3 shadow-md">
            <div class="w-10 h-10 rounded-full bg-rose-500 flex items-center justify-center">
                <i class="fas fa-exclamation text-white text-sm"></i>
            </div>
            <div class="text-rose-800 text-sm font-medium flex-1"><?php echo htmlspecialchars($formError); ?></div>
            <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards - Premium Design with Individual Animations -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-10 stagger-children">
            <?php
            $statItems = [
                ['value' => number_format($todayVisitors), 'label' => "Today's Visitors", 'icon' => 'fa-users', 'trend' => 'live', 'trendColor' => 'text-emerald-600', 'bg' => 'from-emerald-400 to-teal-500'],
                ['value' => $avgWaitStr, 'label' => 'Avg. Wait Time', 'icon' => 'fa-clock', 'trend' => 'today', 'trendColor' => 'text-rose-600', 'bg' => 'from-rose-400 to-orange-500'],
                ['value' => $satisfactionPct . '%', 'label' => 'Completion Rate', 'icon' => 'fa-smile-wink', 'trend' => 'completed today', 'trendColor' => 'text-emerald-600', 'bg' => 'from-emerald-400 to-teal-500'],
                ['value' => number_format($pendingCount), 'label' => 'Pending Bookings', 'icon' => 'fa-calendar-alt', 'trend' => 'awaiting confirmation', 'trendColor' => 'text-slate-500', 'bg' => 'from-slate-400 to-slate-500'],
            ];
            foreach ($statItems as $idx => $stat):
            ?>
            <div class="group bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-slate-100 hover-lift cursor-pointer">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="stat-number text-3xl md:text-4xl font-extrabold text-slate-800 group-hover:scale-105 transition-transform">
                            <?php echo $stat['value']; ?>
                        </div>
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-2">
                            <?php echo $stat['label']; ?>
                        </div>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br <?php echo $stat['bg']; ?> flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas <?php echo $stat['icon']; ?> text-white text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2">
                    <div class="text-xs <?php echo $stat['trendColor']; ?> font-semibold">
                        <?php echo $stat['trend']; ?>
                    </div>
                    <div class="text-xs text-slate-400">live data</div>
                </div>
                <div class="mt-3 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r <?php echo $stat['bg']; ?> rounded-full progress-bar" style="width: <?php echo rand(60, 95); ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Two-Column Layout -->
        <div class="grid lg:grid-cols-3 gap-7 mb-10">
            
            <!-- LEFT COLUMN -->
            <div class="lg:col-span-2 space-y-7">
                
                <!-- Active Queues Card with Premium Design -->
                <div class="animate-fadeInLeft bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden hover-lift">
                    <div class="px-6 py-5 bg-gradient-to-r from-white to-slate-50/50 border-b border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center shadow-md">
                                    <i class="fas fa-charging-station text-white text-sm"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-slate-800 text-lg">Active Queues</h2>
                                    <p class="text-xs text-slate-500">Real-time queue status</p>
                                </div>
                            </div>
                            <a href="manage-queue.php" class="group text-sm font-semibold text-[#71C9CE] hover:text-[#4FB6BB] transition flex items-center gap-1">
                                Manage all 
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50/80">
                                <tr class="text-left">
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Current Token</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Waiting</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if (empty($liveQueues)): ?>
                                <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400 text-sm">No active queues today.</td></tr>
                                <?php else: ?>
                                <?php
                                $statusColorMap = [
                                    'Serving'  => 'bg-emerald-100 text-emerald-700',
                                    'Busy'     => 'bg-amber-100 text-amber-700',
                                    'Moderate' => 'bg-blue-100 text-blue-700',
                                    'Active'   => 'bg-slate-100 text-slate-600',
                                ];
                                foreach ($liveQueues as $q):
                                    $statusCls = $statusColorMap[$q['queue_status']] ?? 'bg-slate-100 text-slate-600';
                                    $totalWaiting = (int)$q['waiting'] + (int)$q['serving_now'];
                                ?>
                                <tr class="table-row-hover transition-all duration-300">
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#E3FDFD] to-[#c5f0f2] flex items-center justify-center">
                                                <i class="fas <?php echo htmlspecialchars($q['icon_class'] ?? 'fa-concierge-bell'); ?> text-[#3aabb1] text-xs"></i>
                                            </div>
                                            <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($q['service_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 font-mono font-bold text-lg text-slate-800"><?php echo htmlspecialchars($q['current_token']); ?></td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-1">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-bold"><?php echo $totalWaiting; ?></span>
                                            <span class="text-xs text-slate-500">people</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold <?php echo $statusCls; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
                                            <?php echo htmlspecialchars($q['queue_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <a href="manage-queue.php" class="group relative inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-[#71C9CE] hover:text-white font-medium text-xs transition-all duration-300 hover:bg-[#71C9CE]">
                                            <i class="fas fa-forward"></i>
                                            <span>Manage</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Today's Appointments Premium -->
                <div class="animate-fadeInLeft bg-white rounded-2xl shadow-xl border border-slate-100 p-6 hover-lift" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center shadow-md">
                                <i class="far fa-calendar-alt text-white text-sm"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-slate-800 text-lg">Today's Schedule</h2>
                                <p class="text-xs text-slate-500"><?php echo date('l, F j, Y'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 text-sm text-slate-500">
                            <i class="fas fa-clock text-[#71C9CE]"></i>
                            <span class="text-xs font-mono"><?php echo date('h:i A'); ?></span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($todaySchedule)): ?>
                        <div class="text-center py-8 text-slate-400 text-sm">
                            <i class="fas fa-calendar-times text-3xl opacity-30 mb-2 block"></i>
                            No appointments scheduled for today.
                        </div>
                        <?php else: ?>
                        <?php
                        $statusBorderMap = [
                            'serving'   => 'border-l-emerald-500',
                            'in_queue'  => 'border-l-[#71C9CE]',
                            'confirmed' => 'border-l-purple-500',
                            'pending'   => 'border-l-amber-500',
                        ];
                        $statusLabelMap = [
                            'serving'   => 'Serving',
                            'in_queue'  => 'In Queue',
                            'confirmed' => 'Confirmed',
                            'pending'   => 'Pending',
                        ];
                        $dotColorMap = [
                            'serving'   => 'bg-emerald-500',
                            'in_queue'  => 'bg-[#71C9CE]',
                            'confirmed' => 'bg-purple-500',
                            'pending'   => 'bg-amber-500',
                        ];
                        foreach ($todaySchedule as $app):
                            $border  = $statusBorderMap[$app['status']]  ?? 'border-l-slate-300';
                            $slabel  = $statusLabelMap[$app['status']]   ?? ucfirst($app['status']);
                            $dotCls  = $dotColorMap[$app['status']]      ?? 'bg-slate-400';
                            $timeStr = isset($app['appointment_time']) ? date('h:i A', strtotime($app['appointment_time'])) : '—';
                        ?>
                        <div class="group flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-white hover:shadow-lg transition-all duration-300 cursor-pointer hover:-translate-y-1">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#E3FDFD] to-[#c5f0f2] flex items-center justify-center text-[#3aabb1] font-bold text-sm">
                                        <?php echo strtoupper(substr($app['customer_name'] ?? '?', 0, 1)); ?>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-3 h-3 rounded-full <?php echo $dotCls; ?> border-2 border-white"></div>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-700 text-sm group-hover:text-[#71C9CE] transition-colors">
                                        <?php echo htmlspecialchars($app['customer_name'] ?? 'Guest'); ?>
                                    </div>
                                    <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                                        <i class="fas <?php echo htmlspecialchars($app['icon_class'] ?? 'fa-calendar'); ?> text-[10px]"></i>
                                        <?php echo htmlspecialchars($app['service_name']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right hidden sm:block">
                                    <div class="text-sm font-bold text-slate-700"><?php echo $timeStr; ?></div>
                                    <div class="text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($app['queue_number']); ?></div>
                                </div>
                                <span class="px-3 py-1.5 rounded-full text-xs font-bold <?php echo $border; ?> border-l-4 bg-slate-50">
                                    <?php echo $slabel; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN - Premium Sidebar -->
            <div class="space-y-7">
                
                <!-- Quick Actions -->
                <div class="animate-fadeInRight bg-white rounded-2xl shadow-xl border border-slate-100 p-6 hover-lift">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center shadow-md">
                            <i class="fas fa-bolt text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Quick Actions</h3>
                            <p class="text-xs text-slate-500">Frequently used tools</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <button onclick="openModal('modal-add-user')" class="group w-full flex items-center gap-3 p-3 rounded-xl bg-gradient-to-r from-white to-slate-50 hover:from-[#E3FDFD] hover:to-white transition-all duration-300 border border-slate-100 hover:border-[#71C9CE] hover:shadow-md">
                            <div class="w-10 h-10 rounded-xl gradient-bg text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                <i class="fas fa-user-plus text-sm"></i>
                            </div>
                            <div class="flex-1 text-left">
                                <div class="text-sm font-semibold text-slate-700 group-hover:text-[#71C9CE] transition-colors">Add New User</div>
                                <div class="text-xs text-slate-400">Create account</div>
                            </div>
                            <i class="fas fa-arrow-right text-slate-300 group-hover:text-[#71C9CE] group-hover:translate-x-1 transition-all"></i>
                        </button>
                        
                        <button class="group w-full flex items-center gap-3 p-3 rounded-xl bg-gradient-to-r from-white to-slate-50 hover:from-[#E3FDFD] hover:to-white transition-all duration-300 border border-slate-100 hover:border-[#71C9CE]">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-500 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                <i class="fas fa-chart-line text-sm"></i>
                            </div>
                            <div class="flex-1 text-left">
                                <div class="text-sm font-semibold text-slate-700 group-hover:text-[#71C9CE] transition-colors">Analytics Report</div>
                                <div class="text-xs text-slate-400">View insights</div>
                            </div>
                            <i class="fas fa-arrow-right text-slate-300 group-hover:text-[#71C9CE] group-hover:translate-x-1 transition-all"></i>
                        </button>
                        
                        <?php if ($isDeveloper): ?>
                        <button class="group w-full flex items-center gap-3 p-3 rounded-xl bg-gradient-to-r from-white to-slate-50 hover:from-purple-50 hover:to-white transition-all duration-300 border border-slate-100 hover:border-purple-300">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                                <i class="fas fa-cog text-sm"></i>
                            </div>
                            <div class="flex-1 text-left">
                                <div class="text-sm font-semibold text-slate-700 group-hover:text-purple-600 transition-colors">System Settings</div>
                                <div class="text-xs text-slate-400">Advanced config</div>
                            </div>
                            <i class="fas fa-arrow-right text-slate-300 group-hover:text-purple-500 group-hover:translate-x-1 transition-all"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Queue Utilization -->
                <div class="animate-fadeInRight bg-white rounded-2xl shadow-xl border border-slate-100 p-6 hover-lift" style="animation-delay: 0.05s;">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-400 to-slate-500 text-white flex items-center justify-center shadow-md">
                            <i class="fas fa-chart-pie text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Queue Utilization</h3>
                            <p class="text-xs text-slate-500">Capacity overview</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <?php if (empty($utilizationData)): ?>
                        <p class="text-xs text-slate-400 text-center py-4">No data yet today.</p>
                        <?php else: ?>
                        <?php foreach ($utilizationData as $cap): ?>
                        <div class="group cursor-pointer">
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="font-semibold text-slate-600 group-hover:text-[#71C9CE] transition-colors"><?php echo htmlspecialchars($cap['label']); ?></span>
                                <span class="font-bold text-slate-700"><?php echo $cap['pct']; ?>%</span>
                            </div>
                            <div class="relative w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="h-full rounded-full progress-bar"
                                     style="width:0%; background:<?php echo $cap['color']; ?>;"
                                     data-width="<?php echo $cap['pct']; ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alerts Widget -->
                <div class="animate-fadeInRight bg-white rounded-2xl shadow-xl border border-slate-100 p-6 hover-lift" style="animation-delay: 0.1s;">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 text-white flex items-center justify-center shadow-md float-badge">
                            <i class="fas fa-bell text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Live Notifications</h3>
                            <p class="text-xs text-slate-500"><?php echo count($liveAlerts); ?> active alerts</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($liveAlerts)): ?>
                        <div class="text-center py-4 text-slate-400 text-xs">
                            <i class="fas fa-check-circle text-emerald-400 text-2xl mb-2 block"></i>
                            All queues running smoothly.
                        </div>
                        <?php else: ?>
                        <?php
                        $alertStyleMap = [
                            'amber'   => ['from-amber-50','bg-amber-200','text-amber-700','text-amber-800','text-amber-600'],
                            'blue'    => ['from-blue-50','bg-blue-200','text-blue-700','text-blue-800','text-blue-600'],
                            'green'   => ['from-emerald-50','bg-emerald-200','text-emerald-700','text-emerald-800','text-emerald-600'],
                        ];
                        foreach ($liveAlerts as $alert):
                            $s = $alertStyleMap[$alert['color']] ?? $alertStyleMap['blue'];
                        ?>
                        <div class="group flex gap-3 p-3 rounded-xl bg-gradient-to-r <?php echo $s[0]; ?> to-transparent hover:opacity-90 transition-all duration-300 cursor-pointer">
                            <div class="w-8 h-8 rounded-full <?php echo $s[1]; ?> flex items-center justify-center flex-shrink-0">
                                <i class="fas <?php echo htmlspecialchars($alert['icon']); ?> <?php echo $s[2]; ?> text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold <?php echo $s[3]; ?> truncate"><?php echo htmlspecialchars($alert['message']); ?></div>
                                <div class="text-xs <?php echo $s[4]; ?>">Updated just now</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- USER MANAGEMENT - Premium Section -->
        <div class="animate-fadeInUp bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden mt-4" style="animation-delay: 0.15s;">
            <div class="px-6 py-5 bg-gradient-to-r from-white to-slate-50/50 border-b border-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center shadow-md">
                            <i class="fas fa-users text-white text-sm"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-800 text-lg">User Directory</h2>
                            <p class="text-xs text-slate-400"><?php echo count($allUsers); ?> registered accounts</p>
                        </div>
                    </div>
                    <button onclick="openModal('modal-add-user')" class="group relative inline-flex items-center gap-2 px-5 py-2.5 gradient-bg text-white font-bold rounded-xl hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <span class="relative z-10 flex items-center gap-2">
                            <i class="fas fa-plus text-xs"></i>
                            <span>New User</span>
                        </span>
                        <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity"></div>
                    </button>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="px-6 pt-4 pb-3 flex flex-col sm:flex-row gap-3 border-b border-slate-100 bg-slate-50/30">
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="user-search" oninput="filterUsers()" 
                           placeholder="Search by name, email, or role..." 
                           class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all">
                </div>
                <select id="role-filter" onchange="filterUsers()" 
                        class="w-full sm:w-48 px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all bg-white">
                    <option value="">All Roles</option>
                    <?php foreach ($allRoles as $r): ?>
                    <option value="<?php echo htmlspecialchars($r['name']); ?>"><?php echo htmlspecialchars($r['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/80 border-b border-slate-100">
                        <tr class="text-left">
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50" id="users-tbody">
                        <?php foreach ($allUsers as $u):
                            $isSelf = ($u['id'] === (int)($_SESSION['user_id'] ?? 0));
                            $roleBadge = 'role-badge-' . $u['role_name'];
                            $statusClass = $u['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500';
                            $statusText = $u['is_active'] ? 'Active' : 'Inactive';
                        ?>
                        <tr class="table-row-hover transition-all duration-300 user-row" 
                            data-name="<?php echo strtolower($u['first_name'].' '.$u['last_name']); ?>" 
                            data-email="<?php echo strtolower($u['email']); ?>" 
                            data-role="<?php echo $u['role_name']; ?>">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-[#E3FDFD] to-[#c5f0f2] flex items-center justify-center text-[#3aabb1] font-bold text-sm">
                                            <?php echo strtoupper(substr($u['first_name'], 0, 1)); ?>
                                        </div>
                                        <?php if ($u['is_active']): ?>
                                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white animate-pulse"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-700 text-sm">
                                            <?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?>
                                            <?php if ($isSelf): ?>
                                            <span class="ml-1.5 text-[10px] font-bold text-[#71C9CE] bg-[#E3FDFD] px-1.5 py-0.5 rounded-full">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-slate-600 text-sm"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="px-6 py-3">
                                <span class="role-badge <?php echo $roleBadge; ?>">
                                    <?php echo htmlspecialchars($u['role_label']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-slate-400 text-xs font-mono"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                            <td class="px-6 py-3">
                                <?php if (!$isSelf): ?>
                                <button onclick="openChangeRole(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['first_name'].' '.$u['last_name'])); ?>', <?php echo $u['role_id']; ?>)" 
                                        class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gradient-to-r from-[#E3FDFD] to-white text-[#3aabb1] text-xs font-semibold hover:from-[#71C9CE] hover:to-[#5bbec3] hover:text-white transition-all duration-300 shadow-sm hover:shadow-md">
                                    <i class="fas fa-user-tag text-xs group-hover:rotate-12 transition-transform"></i>
                                    <span>Change Role</span>
                                </button>
                                <?php else: ?>
                                <span class="text-xs text-slate-300 italic">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (empty($allUsers)): ?>
            <div class="text-center py-16">
                <i class="fas fa-users text-5xl text-slate-300 mb-3"></i>
                <p class="text-slate-400 text-sm">No users registered yet</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     SERVICE ADMIN ASSIGNMENTS PANEL
     ══════════════════════════════════════════════════════════════ -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 mb-10">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-5 bg-gradient-to-r from-white to-slate-50/50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-user-cog text-white text-sm"></i>
                </div>
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Service Admin Assignments</h2>
                    <p class="text-xs text-slate-500">Assign service admins to individual queues with granular permissions</p>
                </div>
            </div>
            <button onclick="openModal('modal-assign-service')"
                class="group relative inline-flex items-center gap-2 px-4 py-2.5 gradient-bg text-white text-sm font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-[1.02]">
                <i class="fas fa-plus"></i>
                <span>Assign Admin</span>
            </button>
        </div>

        <!-- Current assignments table -->
        <?php if (empty($allAssignments)): ?>
        <div class="text-center py-14">
            <i class="fas fa-user-cog text-5xl text-slate-300 mb-3 block"></i>
            <p class="text-slate-400 text-sm">No service admin assignments yet.</p>
            <button onclick="openModal('modal-assign-service')" class="mt-3 text-sm text-[#71C9CE] font-semibold hover:underline">
                Assign your first service admin →
            </button>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-100">
                    <tr class="text-left">
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Admin</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Permissions</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Assigned By</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                <?php foreach ($allAssignments as $a): ?>
                <tr class="table-row-hover transition-all duration-300">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs"
                                 style="background:<?php echo htmlspecialchars($a['color_hex'] ?? '#71C9CE'); ?>">
                                <i class="fas <?php echo htmlspecialchars($a['icon_class'] ?? 'fa-concierge-bell'); ?>"></i>
                            </div>
                            <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($a['service_name']); ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#E3FDFD] to-[#c5f0f2] flex items-center justify-center text-[#3aabb1] font-bold text-xs">
                                <?php echo strtoupper(substr($a['user_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($a['user_name']); ?></div>
                                <div class="text-xs text-slate-400"><?php echo htmlspecialchars($a['user_email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            <?php if ($a['can_manage_queue']): ?><span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded-full font-medium">Queue</span><?php endif; ?>
                            <?php if ($a['can_manage_bookings']): ?><span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-medium">Bookings</span><?php endif; ?>
                            <?php if ($a['can_view_reports']): ?><span class="text-xs px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full font-medium">Reports</span><?php endif; ?>
                            <?php if ($a['can_manage_locations']): ?><span class="text-xs px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full font-medium">Locations</span><?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-500 text-sm"><?php echo htmlspecialchars($a['assigned_by_name'] ?? '—'); ?></td>
                    <td class="px-6 py-4 text-slate-400 text-xs font-mono"><?php echo date('M j, Y', strtotime($a['assigned_at'])); ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="openEditAssignment(
                                    <?php echo $a['id']; ?>,
                                    <?php echo $a['user_id']; ?>,
                                    <?php echo $a['service_id']; ?>,
                                    '<?php echo addslashes($a['user_name']); ?>',
                                    '<?php echo addslashes($a['service_name']); ?>',
                                    <?php echo (int)$a['can_manage_queue']; ?>,
                                    <?php echo (int)$a['can_manage_bookings']; ?>,
                                    <?php echo (int)$a['can_view_reports']; ?>,
                                    <?php echo (int)$a['can_manage_locations']; ?>
                                )"
                                class="text-xs px-2.5 py-1.5 bg-[#E3FDFD] text-[#3aabb1] rounded-lg font-semibold hover:bg-[#c6f5f7] transition-all">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('Revoke this assignment?')">
                                <input type="hidden" name="action"        value="revoke_assignment">
                                <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                <button type="submit" class="text-xs px-2.5 py-1.5 bg-red-50 text-red-600 rounded-lg font-semibold hover:bg-red-100 transition-all">
                                    <i class="fas fa-trash mr-1"></i>Revoke
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: ASSIGN SERVICE ADMIN -->
<div id="modal-assign-service" class="modal-overlay" onclick="closeIfOverlay(event,'modal-assign-service')">
    <div class="modal-box max-w-lg">
        <form method="POST">
            <input type="hidden" name="action" value="assign_service_admin">
            <input type="hidden" name="assignment_edit_id" id="as-edit-id" value="">
            <div class="relative px-6 py-5 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold" id="as-modal-title">Assign Service Admin</h3>
                        <p class="text-sm opacity-90 mt-0.5" id="as-modal-sub">Grant a user access to manage a service queue</p>
                    </div>
                    <button type="button" onclick="closeModal('modal-assign-service')" class="text-white/80 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <!-- User select -->
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Select User *</label>
                    <select name="target_user_id" id="as-user-select" required
                        class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all">
                        <option value="">— Choose user —</option>
                        <?php foreach ($allUsers as $u):
                            if (in_array($u['role_name'], ['developer', 'admin'])) continue;
                        ?>
                        <option value="<?php echo $u['id']; ?>">
                            <?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?>
                            (<?php echo htmlspecialchars($u['role_label']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Service select -->
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Assign to Service *</label>
                    <select name="service_id" id="as-service-select" required
                        class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all">
                        <option value="">— Choose service —</option>
                        <?php foreach ($allServices as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Permissions checkboxes -->
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">Permissions</label>
                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        $perms = [
                            ['can_manage_queue',     'fa-list-ol',    'Manage Queue',     'Control queue flow, advance, pause'],
                            ['can_manage_bookings',  'fa-calendar',   'Manage Bookings',  'View & update appointments'],
                            ['can_view_reports',     'fa-chart-bar',  'View Reports',     'Access analytics & stats'],
                            ['can_manage_locations', 'fa-map-marker', 'Manage Locations', 'Edit service location info'],
                        ];
                        foreach ($perms as [$name, $icon, $label, $desc]):
                        ?>
                        <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:border-[#71C9CE] hover:bg-[#f0fdfd] transition-all group">
                            <input type="checkbox" name="<?php echo $name; ?>" id="perm-<?php echo $name; ?>"
                                class="mt-0.5 h-4 w-4 text-[#71C9CE] border-gray-300 rounded"
                                <?php echo in_array($name, ['can_manage_queue','can_manage_bookings']) ? 'checked' : ''; ?>>
                            <div>
                                <div class="text-sm font-semibold text-slate-700 flex items-center gap-1.5">
                                    <i class="fas <?php echo $icon; ?> text-[#71C9CE] text-xs"></i>
                                    <?php echo $label; ?>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5"><?php echo $desc; ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="flex gap-2 p-3 bg-blue-50 rounded-xl border border-blue-100 text-xs text-blue-800">
                    <i class="fas fa-info-circle mt-0.5 text-blue-400 flex-shrink-0"></i>
                    The user's role will automatically be set to <strong>Service Admin</strong> if it is currently a regular user or client.
                </div>
            </div>
            <div class="px-6 py-5 bg-slate-50/80 border-t border-slate-100 flex gap-3">
                <button type="button" onclick="closeModal('modal-assign-service')" class="flex-1 px-4 py-2.5 border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-100 transition-all text-sm">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm">
                    <i class="fas fa-save mr-2"></i><span id="as-submit-label">Save Assignment</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditAssignment(id, userId, svcId, userName, svcName, canQ, canB, canR, canL) {
    document.getElementById('as-modal-title').textContent = 'Edit Assignment';
    document.getElementById('as-modal-sub').textContent   = userName + ' → ' + svcName;
    document.getElementById('as-submit-label').textContent = 'Update Assignment';
    document.getElementById('as-user-select').value    = userId;
    document.getElementById('as-service-select').value = svcId;
    document.getElementById('perm-can_manage_queue').checked     = !!canQ;
    document.getElementById('perm-can_manage_bookings').checked  = !!canB;
    document.getElementById('perm-can_view_reports').checked     = !!canR;
    document.getElementById('perm-can_manage_locations').checked = !!canL;
    openModal('modal-assign-service');
}
</script>

<!-- MODAL: ADD USER - Premium Design -->
<div id="modal-add-user" class="modal-overlay" onclick="closeIfOverlay(event,'modal-add-user')">
    <div class="modal-box">
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_user">
            <div class="relative px-6 py-5 bg-gradient-to-r from-[#71C9CE] to-[#4FB6BB] text-white">
                <div class="absolute inset-0 bg-white/10"></div>
                <div class="relative flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold">Create New Account</h3>
                        <p class="text-sm opacity-90 mt-0.5">Fill in the details below</p>
                    </div>
                    <button type="button" onclick="closeModal('modal-add-user')" class="text-white/80 hover:text-white transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">First Name *</label>
                        <input type="text" name="first_name" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Last Name *</label>
                        <input type="text" name="last_name" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all" required>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Email Address *</label>
                    <input type="email" name="email" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Phone Number</label>
                    <input type="tel" name="phone" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Password *</label>
                    <input type="password" name="password" id="newPw" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all" required minlength="8">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Role *</label>
                    <select name="role_id" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all">
                        <?php foreach ($allRoles as $r): if (!$isDeveloper && $r['name'] === 'developer') continue; ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo ($r['name'] === 'user') ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="px-6 py-5 bg-slate-50/80 border-t border-slate-100 flex gap-3">
                <button type="button" onclick="closeModal('modal-add-user')" class="flex-1 px-4 py-2.5 border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-100 transition-all text-sm">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 gradient-bg text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm transform hover:scale-[1.02]">
                    <i class="fas fa-save mr-2"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: CHANGE ROLE - Premium Design -->
<div id="modal-change-role" class="modal-overlay" onclick="closeIfOverlay(event,'modal-change-role')">
    <div class="modal-box max-w-md">
        <form method="POST">
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="target_user_id" id="cr-user-id">
            <div class="relative px-6 py-5 bg-gradient-to-r from-amber-500 to-orange-500 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold">Change User Role</h3>
                        <p class="text-sm opacity-90 mt-0.5" id="cr-subtitle">Update permissions</p>
                    </div>
                    <button type="button" onclick="closeModal('modal-change-role')" class="text-white/80 hover:text-white transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-slate-50 to-white rounded-xl border border-slate-100">
                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                        <i class="fas fa-user-tag text-amber-600"></i>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Role</div>
                        <div id="cr-current-role" class="text-base font-bold text-slate-800 mt-0.5">—</div>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">Assign New Role *</label>
                    <select name="new_role_id" id="cr-role-select" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-[#71C9CE] focus:ring-2 focus:ring-[#71C9CE]/20 transition-all">
                        <?php foreach ($allRoles as $r): if (!$isDeveloper && $r['name'] === 'developer') continue; ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3 p-3 bg-amber-50 rounded-xl border border-amber-200">
                    <i class="fas fa-shield-alt text-amber-600 mt-0.5"></i>
                    <div class="text-xs text-amber-800">Changing a user's role immediately affects their access permissions. This action is logged for security.</div>
                </div>
            </div>
            <div class="px-6 py-5 bg-slate-50/80 border-t border-slate-100 flex gap-3">
                <button type="button" onclick="closeModal('modal-change-role')" class="flex-1 px-4 py-2.5 border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-100 transition-all text-sm">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-[#71C9CE] to-[#4FB6BB] text-white font-bold rounded-xl hover:shadow-lg transition-all text-sm transform hover:scale-[1.02]">
                    <i class="fas fa-save mr-2"></i> Update Role
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Progress bar animation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.progress-bar').forEach(bar => {
        const width = bar.getAttribute('data-width');
        if (width) {
            setTimeout(() => {
                bar.style.width = width + '%';
            }, 100);
        }
    });
});

const roleLabels = { <?php foreach ($allRoles as $r) echo $r['id'] . ":'" . addslashes($r['label']) . "',"; ?> };

function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

function closeIfOverlay(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
}

function openChangeRole(userId, userName, currentRoleId) {
    document.getElementById('cr-user-id').value = userId;
    document.getElementById('cr-subtitle').innerHTML = '<i class="fas fa-user mr-1"></i> ' + userName;
    document.getElementById('cr-current-role').innerHTML = roleLabels[currentRoleId] || 'Unknown';
    const sel = document.getElementById('cr-role-select');
    for (let opt of sel.options) {
        opt.selected = (parseInt(opt.value) === currentRoleId);
    }
    openModal('modal-change-role');
}

function filterUsers() {
    const query = document.getElementById('user-search').value.toLowerCase();
    const role = document.getElementById('role-filter').value;
    document.querySelectorAll('#users-tbody .user-row').forEach(row => {
        const nameMatch = row.dataset.name.includes(query) || row.dataset.email.includes(query);
        const roleMatch = !role || row.dataset.role === role;
        row.style.display = (nameMatch && roleMatch) ? '' : 'none';
    });
}

// Auto-open modals on error
<?php if ($formError && isset($_POST['action']) && $_POST['action'] === 'add_user'): ?>
openModal('modal-add-user');
<?php endif; ?>
<?php if ($formError && isset($_POST['action']) && $_POST['action'] === 'change_role'): ?>
openModal('modal-change-role');
<?php endif; ?>
</script>

<?php
$db->close();
include('../includes/footer.php');
?>