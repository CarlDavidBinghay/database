<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in to view your queue status.';
    header('Location: login.php');
    exit();
}

require_once('../includes/db.php');
$db = getDB();

$userId = (int)$_SESSION['user_id'];

// ── Fetch this user's real appointments ──────────────────────────
// Upcoming / active
$upcomingStmt = $db->prepare(
    "SELECT a.id, a.queue_number, a.status, a.priority,
            a.appointment_date, a.appointment_time,
            a.base_price, a.notes,
            bs.name AS service_name, bs.icon_class, bs.color_hex,
            sl.name AS location_name,
            qs.current_number,
            -- position in queue (how many in_queue records have a lower/equal id)
            (SELECT COUNT(*) FROM appointments a2
             WHERE a2.service_id = a.service_id
               AND a2.appointment_date = a.appointment_date
               AND a2.status = 'in_queue'
               AND a2.id <= a.id) AS queue_position,
            -- total in queue
            (SELECT COUNT(*) FROM appointments a3
             WHERE a3.service_id = a.service_id
               AND a3.appointment_date = a.appointment_date
               AND a3.status = 'in_queue') AS total_in_queue
     FROM appointments a
     JOIN booking_services bs ON bs.id = a.service_id
     JOIN service_locations sl ON sl.id = a.location_id
     LEFT JOIN queue_status qs ON qs.location_id = a.location_id
                               AND qs.queue_date = a.appointment_date
     WHERE a.user_id = ?
       AND a.status NOT IN ('completed','cancelled','no_show')
     ORDER BY a.appointment_date ASC, a.appointment_time ASC"
);
$upcomingStmt->execute([$userId]);
$upcoming = $upcomingStmt->fetchAll();

// History (last 20)
$historyStmt = $db->prepare(
    "SELECT a.id, a.queue_number, a.status, a.priority,
            a.appointment_date, a.appointment_time, a.completed_at,
            bs.name AS service_name, bs.icon_class,
            sl.name AS location_name,
            TIMESTAMPDIFF(MINUTE, a.served_at, a.completed_at) AS wait_min
     FROM appointments a
     JOIN booking_services bs ON bs.id = a.service_id
     JOIN service_locations sl ON sl.id = a.location_id
     WHERE a.user_id = ?
       AND a.status IN ('completed','cancelled','no_show')
     ORDER BY a.appointment_date DESC, a.id DESC
     LIMIT 20"
);
$historyStmt->execute([$userId]);
$history = $historyStmt->fetchAll();

// ── Find the most active / soonest appointment for the hero card ─
$heroApt = null;
foreach ($upcoming as $apt) {
    if (in_array($apt['status'], ['serving','in_queue'])) {
        $heroApt = $apt;
        break;
    }
}
if (!$heroApt && !empty($upcoming)) {
    $heroApt = $upcoming[0];
}

$pageTitle = 'My Queue Status';
include('../includes/header.php');
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">My Queue Status</h1>
            <p class="text-gray-500 mt-1">Track your appointments and queue positions</p>
        </div>
        <a href="book.php"
           class="inline-flex items-center gap-2 px-5 py-2.5 gradient-bg text-white font-semibold rounded-xl hover:opacity-90 text-sm shadow-sm transition-all">
            <i class="fas fa-calendar-plus"></i>Book New Appointment
        </a>
    </div>

    <?php if ($heroApt): ?>
    <!-- ── Current Queue Hero Card ── -->
    <div class="bg-gradient-to-r from-[#71C9CE] to-[#4db8be] rounded-2xl p-8 text-white mb-8 shadow-md">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <div class="text-sm font-medium opacity-80 mb-1">Service</div>
                <div class="text-2xl font-bold mb-1"><?= htmlspecialchars($heroApt['service_name']) ?></div>
                <div class="flex items-center text-sm opacity-90">
                    <i class="fas <?= htmlspecialchars($heroApt['icon_class'] ?? 'fa-calendar') ?> mr-2"></i>
                    <?= htmlspecialchars($heroApt['location_name']) ?>
                </div>
                <div class="mt-2 text-sm opacity-80">
                    <?= date('M j, Y', strtotime($heroApt['appointment_date'])) ?>
                    &nbsp;·&nbsp;
                    <?= date('g:i A', strtotime($heroApt['appointment_time'])) ?>
                </div>
            </div>

            <?php if (in_array($heroApt['status'], ['in_queue','serving'])): ?>
            <div class="text-center">
                <div class="text-lg font-semibold opacity-90 mb-1">Currently Serving</div>
                <div class="text-4xl font-bold"><?= htmlspecialchars($heroApt['current_number'] ?? '—') ?></div>
                <?php if ($heroApt['queue_position'] > 0): ?>
                <div class="text-sm opacity-80 mt-1">You are #<?= (int)$heroApt['queue_position'] ?> in line</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="text-center">
                <div class="text-lg font-semibold opacity-90 mb-1">Your Queue No.</div>
                <div class="text-4xl font-bold mb-3"><?= htmlspecialchars($heroApt['queue_number']) ?></div>
                <button
                    onclick="showQrModal(<?= $heroApt['id'] ?>, '<?= addslashes(htmlspecialchars($heroApt['queue_number'])) ?>', '<?= addslashes(htmlspecialchars($heroApt['service_name'])) ?>', '<?= $heroApt['appointment_date'] ?>', '<?= date('g:i A', strtotime($heroApt['appointment_time'])) ?>', '<?= $heroApt['status'] ?>')"
                    class="bg-white text-[#3aabb1] px-5 py-2 rounded-xl font-semibold text-sm hover:bg-gray-50 transition-all shadow-sm">
                    <i class="fas fa-qrcode mr-2"></i>Show QR Code
                </button>
            </div>
        </div>

        <?php if (in_array($heroApt['status'], ['in_queue','serving']) && $heroApt['total_in_queue'] > 0): ?>
        <!-- Progress Bar -->
        <?php
            $totalQ   = (int)$heroApt['total_in_queue'];
            $position = (int)$heroApt['queue_position'];
            $done     = max(0, $totalQ - $position);
            $pct      = $totalQ > 0 ? round(($done / $totalQ) * 100) : 0;
        ?>
        <div class="mt-8">
            <div class="flex justify-between text-sm opacity-80 mb-2">
                <span>Queue Progress</span>
                <span><?= $done ?> of <?= $totalQ ?> served (<?= $pct ?>%)</span>
            </div>
            <div class="w-full bg-white/30 rounded-full h-3">
                <div class="bg-white h-3 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- No active appointments hero -->
    <div class="bg-gradient-to-r from-[#71C9CE] to-[#4db8be] rounded-2xl p-8 text-white mb-8 shadow-md text-center">
        <i class="fas fa-calendar-check text-5xl opacity-60 mb-4 block"></i>
        <p class="text-xl font-semibold opacity-90">No active queue entries</p>
        <p class="text-sm opacity-70 mt-1">Book an appointment to get your queue number.</p>
        <a href="book.php" class="inline-block mt-4 px-6 py-2.5 bg-white text-[#3aabb1] font-bold rounded-xl text-sm hover:bg-gray-50 transition-all shadow-sm">
            <i class="fas fa-calendar-plus mr-2"></i>Book Now
        </a>
    </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-8">

        <!-- ── LEFT: Upcoming + History ── -->
        <div class="md:col-span-2 space-y-8">

            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Upcoming Appointments</h2>

                <?php if (empty($upcoming)): ?>
                <div class="text-center text-gray-400 py-8">
                    <i class="fas fa-calendar text-4xl opacity-20 mb-3 block"></i>
                    <p class="text-sm">No upcoming appointments.</p>
                    <a href="book.php" class="mt-3 inline-block text-[#71C9CE] text-sm font-semibold hover:underline">Book one now →</a>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($upcoming as $apt):
                        $isActive = in_array($apt['status'], ['in_queue','serving']);
                        $statusColors = [
                            'pending'   => 'bg-yellow-100 text-yellow-800',
                            'confirmed' => 'bg-blue-100 text-blue-700',
                            'in_queue'  => 'bg-[#71C9CE] text-white',
                            'serving'   => 'bg-green-500 text-white',
                        ];
                        $statusLabel = [
                            'pending'   => 'Pending',
                            'confirmed' => 'Confirmed',
                            'in_queue'  => 'In Queue',
                            'serving'   => 'Being Served',
                        ];
                        $cardBorder = $isActive
                            ? 'border border-[#A6E3E9] bg-[#f0fdfd]'
                            : 'border border-gray-200 hover:border-[#71C9CE]';
                    ?>
                    <div class="<?= $cardBorder ?> rounded-xl p-4 transition-colors">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                     style="background:<?= htmlspecialchars($apt['color_hex'] ?? '#71C9CE') ?>22">
                                    <i class="fas <?= htmlspecialchars($apt['icon_class'] ?? 'fa-calendar') ?>"
                                       style="color:<?= htmlspecialchars($apt['color_hex'] ?? '#71C9CE') ?>"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800"><?= htmlspecialchars($apt['service_name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($apt['location_name']) ?></div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-lg font-bold text-[#3aabb1]"><?= htmlspecialchars($apt['queue_number']) ?></div>
                                <div class="text-xs text-gray-500">
                                    <?= date('M j', strtotime($apt['appointment_date'])) ?>, <?= date('g:i A', strtotime($apt['appointment_time'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-3 text-sm flex-wrap">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $statusColors[$apt['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= $statusLabel[$apt['status']] ?? ucfirst($apt['status']) ?>
                            </span>
                            <?php if ($apt['priority'] !== 'standard'): ?>
                            <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold capitalize">
                                <?= htmlspecialchars($apt['priority']) ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($isActive && $apt['queue_position']): ?>
                            <span class="text-gray-500">
                                <i class="fas fa-users mr-1 text-[#71C9CE]"></i>
                                Position #<?= (int)$apt['queue_position'] ?>
                            </span>
                            <?php endif; ?>
                            <!-- QR button per appointment -->
                            <button
                                onclick="showQrModal(<?= $apt['id'] ?>, '<?= addslashes(htmlspecialchars($apt['queue_number'])) ?>', '<?= addslashes(htmlspecialchars($apt['service_name'])) ?>', '<?= $apt['appointment_date'] ?>', '<?= date('g:i A', strtotime($apt['appointment_time'])) ?>', '<?= $apt['status'] ?>')"
                                class="ml-auto flex items-center gap-1 px-3 py-1 bg-[#E3FDFD] text-[#3aabb1] rounded-lg text-xs font-semibold hover:bg-[#A6E3E9] transition-all">
                                <i class="fas fa-qrcode"></i> QR Code
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Queue History Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Recent Queue History</h2>
                <?php if (empty($history)): ?>
                <div class="text-center text-gray-400 py-8 text-sm">No history yet.</div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php foreach (['Date','Service','Queue No.','Status','Wait'] as $h): ?>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= $h ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($history as $r):
                                $stBadge = match($r['status']) {
                                    'completed' => 'bg-green-100 text-green-700',
                                    'cancelled' => 'bg-red-100 text-red-600',
                                    'no_show'   => 'bg-yellow-100 text-yellow-700',
                                    default     => 'bg-gray-100 text-gray-600',
                                };
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-600"><?= date('M j, Y', strtotime($r['appointment_date'])) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($r['service_name']) ?></td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-700"><?= htmlspecialchars($r['queue_number']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $stBadge ?>">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    <?= $r['wait_min'] ? $r['wait_min'].' min' : '—' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /.main -->

        <!-- ── Sidebar ── -->
        <div class="space-y-6">

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    <a href="book.php" class="flex items-center p-3 border border-gray-100 rounded-xl hover:border-[#71C9CE] hover:bg-[#f0fdfd] transition-all group">
                        <div class="w-9 h-9 gradient-bg rounded-lg flex items-center justify-center mr-3 flex-shrink-0 group-hover:scale-105 transition-transform">
                            <i class="fas fa-calendar-plus text-white text-sm"></i>
                        </div>
                        <span class="font-medium text-sm text-gray-700">Book New Appointment</span>
                    </a>
                    <?php if ($heroApt): ?>
                    <button onclick="showQrModal(<?= $heroApt['id'] ?>, '<?= addslashes(htmlspecialchars($heroApt['queue_number'])) ?>', '<?= addslashes(htmlspecialchars($heroApt['service_name'])) ?>', '<?= $heroApt['appointment_date'] ?>', '<?= date('g:i A', strtotime($heroApt['appointment_time'])) ?>', '<?= $heroApt['status'] ?>')"
                        class="w-full flex items-center p-3 border border-gray-100 rounded-xl hover:border-[#71C9CE] hover:bg-[#f0fdfd] transition-all group">
                        <div class="w-9 h-9 bg-teal-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0 group-hover:scale-105 transition-transform">
                            <i class="fas fa-qrcode text-teal-600 text-sm"></i>
                        </div>
                        <span class="font-medium text-sm text-gray-700">Show My QR Code</span>
                    </button>
                    <?php endif; ?>
                    <button onclick="window.print()"
                        class="w-full flex items-center p-3 border border-gray-100 rounded-xl hover:border-[#71C9CE] hover:bg-[#f0fdfd] transition-all group">
                        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0 group-hover:scale-105 transition-transform">
                            <i class="fas fa-print text-blue-600 text-sm"></i>
                        </div>
                        <span class="font-medium text-sm text-gray-700">Print Queue Ticket</span>
                    </button>
                </div>
            </div>

            <!-- Your Appointments Summary -->
            <?php if (!empty($upcoming)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Queue Summary</h2>
                <div class="space-y-3">
                    <?php foreach ($upcoming as $apt): ?>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background:<?= htmlspecialchars($apt['color_hex'] ?? '#71C9CE') ?>22">
                                <i class="fas <?= htmlspecialchars($apt['icon_class'] ?? 'fa-calendar') ?> text-xs"
                                   style="color:<?= htmlspecialchars($apt['color_hex'] ?? '#71C9CE') ?>"></i>
                            </div>
                            <span class="text-gray-600 truncate max-w-[120px]"><?= htmlspecialchars($apt['service_name']) ?></span>
                        </div>
                        <span class="font-bold text-[#3aabb1]"><?= htmlspecialchars($apt['queue_number']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.sidebar -->

    </div>
</div>

<!-- ── QR Code Modal ── -->
<div id="qrModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden"
     onclick="handleModalBackdropClick(event)">
    <div class="bg-white rounded-2xl p-8 text-center shadow-xl w-80 relative">
        <button onclick="closeQrModal()"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors text-xl leading-none">
            <i class="fas fa-times"></i>
        </button>
        <div class="text-sm text-gray-500 mb-1">Queue Ticket</div>
        <div id="qrQueueNo"   class="text-4xl font-bold text-[#3aabb1] mb-0.5"></div>
        <div id="qrSubtitle"  class="text-xs text-gray-400 mb-1"></div>
        <div id="qrStatus"    class="mb-4"></div>
        <div id="qrcode"      class="flex justify-center mb-5"></div>
        <p class="text-xs text-gray-400 mb-3">Scan this code at the counter to check in</p>
        <button onclick="downloadQR()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#71C9CE] text-white rounded-xl text-xs font-semibold hover:bg-[#5ab4b9] transition-all">
            <i class="fas fa-download"></i> Save QR
        </button>
    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
var currentQrInstance = null;

function showQrModal(aptId, queueNo, serviceName, date, time, status) {
    // Clear previous QR
    var qrcodeEl = document.getElementById('qrcode');
    qrcodeEl.innerHTML = '';
    if (currentQrInstance) { currentQrInstance = null; }

    // Set display info
    document.getElementById('qrQueueNo').textContent = queueNo;
    document.getElementById('qrSubtitle').textContent = serviceName + ' · ' + date + ' ' + time;

    // Status badge
    var statusColors = {
        'pending':   '#f59e0b',
        'confirmed': '#3b82f6',
        'in_queue':  '#71C9CE',
        'serving':   '#22c55e'
    };
    var statusLabels = {
        'pending':   'Pending',
        'confirmed': 'Confirmed',
        'in_queue':  'In Queue',
        'serving':   'Being Served'
    };
    var color = statusColors[status] || '#9ca3af';
    var label = statusLabels[status] || status;
    document.getElementById('qrStatus').innerHTML =
        '<span style="display:inline-block;padding:2px 12px;border-radius:999px;font-size:0.75rem;font-weight:700;background:' +
        color + '22;color:' + color + '">' + label + '</span>';

    // Build QR payload — structured JSON that manage-queue.php scanner can decode
    var payload = JSON.stringify({
        apt_id:    aptId,
        queue_no:  queueNo,
        service:   serviceName,
        date:      date,
        time:      time,
        status:    status,
        ver:       '1'
    });

    currentQrInstance = new QRCode(qrcodeEl, {
        text:         payload,
        width:        200,
        height:       200,
        colorDark:    '#3aabb1',
        colorLight:   '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });

    document.getElementById('qrModal').classList.remove('hidden');
}

function closeQrModal() {
    document.getElementById('qrModal').classList.add('hidden');
}

function handleModalBackdropClick(event) {
    if (event.target === document.getElementById('qrModal')) closeQrModal();
}

function downloadQR() {
    var img = document.querySelector('#qrcode img');
    if (!img) {
        // QRCode.js renders a canvas on some browsers
        var canvas = document.querySelector('#qrcode canvas');
        if (canvas) {
            var a = document.createElement('a');
            a.download = 'AquaQueue-' + document.getElementById('qrQueueNo').textContent + '.png';
            a.href = canvas.toDataURL('image/png');
            a.click();
        }
        return;
    }
    var a = document.createElement('a');
    a.download = 'AquaQueue-' + document.getElementById('qrQueueNo').textContent + '.png';
    a.href = img.src;
    a.click();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeQrModal();
});
</script>

<?php include('../includes/footer.php'); ?>