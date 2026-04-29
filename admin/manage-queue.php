<?php
session_start();
require_once('../includes/users_store.php');

// ── Access control ───────────────────────────────────────────────────────
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'developer', 'service_admin'])) {
    $_SESSION['error'] = 'Access denied.';
    header('Location: ../public/login.php');
    exit();
}

$isServiceAdmin = $_SESSION['user_role'] === 'service_admin';
$isAdminOrDev   = in_array($_SESSION['user_role'], ['admin', 'developer']);
$assignedSvc    = $_SESSION['assigned_service'] ?? null; // e.g. 'medical'

// All available services (admin/dev see all; service_admin sees only their own)
$allQueues = [
    'medical'  => ['title' => 'Medical Consultation', 'current' => 'A-045', 'next' => 'A-046', 'waiting' => 8,  'avg_time' => '15 min', 'status' => 'Active', 'color' => 'from-[#71C9CE] to-[#4db8be]', 'icon' => 'fa-stethoscope'],
    'salon'    => ['title' => 'Hair Salon',            'current' => 'B-018', 'next' => 'B-019', 'waiting' => 12, 'avg_time' => '25 min', 'status' => 'Busy',   'color' => 'from-pink-500 to-red-400',     'icon' => 'fa-cut'],
    'dental'   => ['title' => 'Dental Checkup',        'current' => 'C-009', 'next' => 'C-010', 'waiting' => 3,  'avg_time' => '8 min',  'status' => 'Active', 'color' => 'from-teal-500 to-green-400',   'icon' => 'fa-tooth'],
    'legal'    => ['title' => 'Legal Consultation',    'current' => 'D-015', 'next' => 'D-016', 'waiting' => 5,  'avg_time' => '60 min', 'status' => 'Active', 'color' => 'from-yellow-400 to-orange-400', 'icon' => 'fa-gavel'],
    'vehicle'  => ['title' => 'Vehicle Service',       'current' => 'E-031', 'next' => 'E-032', 'waiting' => 6,  'avg_time' => '90 min', 'status' => 'Busy',   'color' => 'from-blue-400 to-blue-600',    'icon' => 'fa-car'],
    'business' => ['title' => 'Business Meeting',      'current' => 'F-007', 'next' => 'F-008', 'waiting' => 2,  'avg_time' => '45 min', 'status' => 'Active', 'color' => 'from-violet-400 to-purple-500', 'icon' => 'fa-briefcase'],
];

// Filter to assigned service only for service_admin
$visibleQueues = $isServiceAdmin && $assignedSvc
    ? [$assignedSvc => $allQueues[$assignedSvc] ?? reset($allQueues)]
    : $allQueues;

$pageTitle = 'Queue Management';
include('../includes/header.php');
?>

<div class="max-w-7xl mx-auto">

    <!-- Page header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Queue Management</h1>
            <p class="text-gray-500 mt-1">
                <?php if ($isServiceAdmin): ?>
                    Managing: <strong class="text-[#3aabb1]"><?php echo $visibleQueues[$assignedSvc]['title'] ?? 'Your Service'; ?></strong>
                    — Service Admin access only
                <?php else: ?>
                    Manage all queues and appointments in real-time
                <?php endif; ?>
            </p>
        </div>
        <div class="flex gap-3">
            <?php if ($isAdminOrDev): ?>
            <a href="dashboard.php" class="px-4 py-2 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </a>
            <?php endif; ?>
            <button onclick="refreshAll()" class="px-5 py-2 gradient-bg text-white font-semibold rounded-xl hover:opacity-90 text-sm transition-all shadow-sm">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Service Admin restriction notice -->
    <?php if ($isServiceAdmin): ?>
    <div class="mb-6 bg-[#f0fdfd] border border-[#A6E3E9] rounded-xl p-4 flex items-start gap-3">
        <i class="fas fa-user-cog text-[#71C9CE] mt-0.5 flex-shrink-0"></i>
        <div>
            <div class="font-semibold text-[#3aabb1] text-sm">Service Admin Access</div>
            <div class="text-gray-500 text-xs mt-0.5">You can only manage your assigned service queue. For system-wide access, contact your main admin.</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── QUEUE CONTROL CARDS ──────────────────────────────────── -->
    <div class="grid md:grid-cols-<?php echo count($visibleQueues) === 1 ? '1' : (count($visibleQueues) <= 2 ? '2' : '3'); ?> gap-6 mb-8">
        <?php foreach ($visibleQueues as $key => $q): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r <?php echo $q['color']; ?> p-6 text-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold leading-tight"><?php echo $q['title']; ?></h3>
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas <?php echo $q['icon']; ?>"></i>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm opacity-80 mb-1">Current</div>
                        <div class="text-3xl font-bold"><?php echo $q['current']; ?></div>
                    </div>
                    <div>
                        <div class="text-sm opacity-80 mb-1">Next</div>
                        <div class="text-3xl font-bold"><?php echo $q['next']; ?></div>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                    <div>
                        <div class="text-gray-400 text-xs font-medium uppercase tracking-wide">Waiting</div>
                        <div class="font-semibold text-gray-800 mt-0.5"><?php echo $q['waiting']; ?> people</div>
                    </div>
                    <div>
                        <div class="text-gray-400 text-xs font-medium uppercase tracking-wide">Avg. Time</div>
                        <div class="font-semibold text-gray-800 mt-0.5"><?php echo $q['avg_time']; ?></div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-semibold transition-all">
                        <i class="fas fa-pause mr-1"></i>Pause
                    </button>
                    <button class="flex-1 py-2 bg-[#E3FDFD] hover:bg-[#c6f5f7] text-[#3aabb1] rounded-lg text-xs font-semibold transition-all">
                        <i class="fas fa-forward mr-1"></i>Next
                    </button>
                    <button class="flex-1 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-semibold transition-all">
                        <i class="fas fa-stop mr-1"></i>Stop
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── DETAILED QUEUE TABLE ─────────────────────────────────── -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Live Queue List</h2>
            <!-- Filter — only admins/devs see all service filters -->
            <?php if ($isAdminOrDev): ?>
            <select class="border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                <option value="">All Services</option>
                <?php foreach ($allQueues as $k => $q): ?>
                <option value="<?php echo $k; ?>"><?php echo $q['title']; ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach (['Queue No.','Customer','Service','Check-in','Wait','Status','Actions'] as $h): ?>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo $h; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php
                    $details = [
                        ['A-046','John Smith',    'Medical Consultation','09:45 AM','25 min','Waiting', '',        'medical'],
                        ['A-047','Emma Wilson',   'Medical Consultation','10:00 AM','10 min','Next',    '',        'medical'],
                        ['A-048','Michael Brown', 'Medical Consultation','10:15 AM','Now',   'Serving', 'serving', 'medical'],
                        ['B-019','Sarah Johnson', 'Hair Salon',          '10:30 AM','35 min','Waiting', '',        'salon'],
                        ['B-020','David Lee',     'Hair Salon',          '10:45 AM','20 min','Waiting', '',        'salon'],
                        ['C-010','Robert Chen',   'Dental Checkup',      '11:00 AM','5 min', 'Next',    '',        'dental'],
                        ['C-011','Lisa Wang',     'Dental Checkup',      '11:15 AM','Now',   'Serving', 'serving', 'dental'],
                        ['D-016','Miguel Santos', 'Legal Consultation',   '11:30 AM','40 min','Waiting', '',        'legal'],
                        ['E-032','Grace Tan',     'Vehicle Service',      '09:00 AM','90 min','Waiting', '',        'vehicle'],
                    ];

                    $statusStyles = [
                        'Serving' => 'bg-green-100 text-green-800',
                        'Next'    => 'bg-blue-100 text-blue-800',
                        'Waiting' => 'bg-yellow-100 text-yellow-800',
                    ];

                    foreach ($details as [$qnum, $customer, $service, $checkin, $wait, $status, $flag, $svc]):
                        // Service admin: skip rows not for their service
                        if ($isServiceAdmin && $svc !== $assignedSvc) continue;
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors <?php echo $flag === 'serving' ? 'bg-[#f0fdfd]' : ''; ?>">
                        <td class="px-4 py-3 font-bold text-gray-900"><?php echo $qnum; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-700"><?php echo $customer; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?php echo $service; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500"><?php echo $checkin; ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 font-medium"><?php echo $wait; ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $statusStyles[$status] ?? 'bg-gray-100 text-gray-700'; ?>">
                                <?php echo $status; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-3">
                                <button class="text-blue-500 hover:text-blue-700 text-sm" title="Call"><i class="fas fa-phone-alt"></i></button>
                                <button class="text-green-500 hover:text-green-700 text-sm" title="Start Service"><i class="fas fa-play-circle"></i></button>
                                <button class="text-yellow-500 hover:text-yellow-700 text-sm" title="Delay"><i class="fas fa-clock"></i></button>
                                <button class="text-red-400 hover:text-red-600 text-sm" title="Cancel"><i class="fas fa-times"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── CONTROLS: Add to Queue + Settings ────────────────────── -->
    <div class="grid md:grid-cols-2 gap-8">

        <!-- Add to Queue -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-5">Add to Queue</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Select Service</label>
                    <select class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]"
                            <?php echo $isServiceAdmin ? 'disabled' : ''; ?>>
                        <?php foreach ($visibleQueues as $k => $q): ?>
                        <option value="<?php echo $k; ?>"><?php echo $q['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isServiceAdmin): ?>
                    <p class="text-xs text-gray-400 mt-1">Service locked to your assigned queue.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Customer Name</label>
                    <input type="text" class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]" placeholder="Enter customer name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                    <input type="tel" class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]" placeholder="+63 (917) 000-0000">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
                        <select class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                            <option>Standard</option>
                            <option>Express</option>
                            <option>VIP</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Queue Type</label>
                        <select class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C9CE]">
                            <option>Walk-in</option>
                            <option>Appointment</option>
                            <option>Online Booking</option>
                        </select>
                    </div>
                </div>
                <button class="w-full py-3 gradient-bg text-white font-semibold rounded-xl hover:opacity-90 text-sm transition-all shadow-sm">
                    <i class="fas fa-plus-circle mr-2"></i>Add to Queue
                </button>
            </div>
        </div>

        <!-- Bulk Actions + Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-5">Bulk Actions</h2>
            <div class="space-y-3">
                <?php
                $bulkActions = [
                    ['fa-forward',      'text-[#71C9CE]', 'bg-[#E3FDFD]', 'Advance All Queues',   'Move all queues to next',   'bg-[#71C9CE]', true],
                    ['fa-pause',        'text-yellow-600', 'bg-yellow-50', 'Pause All Queues',     'Temporarily halt services', 'bg-yellow-500', true],
                    ['fa-redo',         'text-green-600',  'bg-green-50',  'Reset Daily Counters', 'Reset for new day',         'bg-green-500',  $isAdminOrDev],
                    ['fa-times-circle', 'text-red-600',    'bg-red-50',    'Clear All Queues',     'Remove all pending',        'bg-red-500',    $isAdminOrDev],
                ];
                foreach ($bulkActions as [$icon, $iconColor, $iconBg, $label, $desc, $btnBg, $show]):
                    if (!$show) continue;
                ?>
                <div class="flex items-center gap-3 p-3 <?php echo $iconBg; ?> rounded-xl border border-transparent hover:border-gray-200 transition-all">
                    <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas <?php echo $icon; ?> <?php echo $iconColor; ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-gray-800 text-sm"><?php echo $label; ?></div>
                        <div class="text-xs text-gray-500"><?php echo $desc; ?></div>
                    </div>
                    <button class="<?php echo $btnBg; ?> text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:opacity-90 transition-all flex-shrink-0">Run</button>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Queue Settings toggles -->
            <div class="mt-6 pt-5 border-t border-gray-100">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Queue Settings</h3>
                <div class="space-y-3">
                    <?php
                    $toggles = [
                        ['auto-advance',      'Auto-advance queue',   true],
                        ['send-notifs',       'Send notifications',    true],
                        ['allow-walkins',     'Allow walk-ins',        true],
                        ['require-login',     'Require login to book', false],
                    ];
                    foreach ($toggles as [$id, $label, $checked]):
                    ?>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo $label; ?></span>
                        <label class="relative inline-flex cursor-pointer">
                            <input type="checkbox" id="<?php echo $id; ?>" class="sr-only peer" <?php echo $checked ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-[#71C9CE] transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function refreshAll() {
    const btn = event.currentTarget;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Refresh';
    }, 1200);
}
</script>

<?php include('../includes/footer.php'); ?>
