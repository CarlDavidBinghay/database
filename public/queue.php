<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Please log in to view your queue status.';
    header('Location: login.php');
    exit();
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

    <!-- Current Queue Card -->
    <div class="bg-gradient-to-r from-[#71C9CE] to-[#4db8be] rounded-2xl p-8 text-white mb-8 shadow-md">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <div class="text-sm font-medium opacity-80 mb-1">Currently Serving</div>
                <div class="text-5xl font-bold mb-2">A-045</div>
                <div class="flex items-center text-sm opacity-90">
                    <i class="fas fa-stethoscope mr-2"></i>Medical Consultation
                </div>
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold opacity-90 mb-1">Your Position</div>
                <div class="text-6xl font-bold">#3</div>
                <div class="text-sm opacity-80 mt-1">~15 minutes away</div>
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold opacity-90 mb-1">Your Queue No.</div>
                <div class="text-5xl font-bold mb-3">A-048</div>
                <button class="bg-white text-[#3aabb1] px-5 py-2 rounded-xl font-semibold text-sm hover:bg-gray-50 transition-all shadow-sm">
                    <i class="fas fa-qrcode mr-2"></i>Show QR Code
                </button>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-8">
            <div class="flex justify-between text-sm opacity-80 mb-2">
                <span>Queue Progress</span>
                <span>75% complete</span>
            </div>
            <div class="w-full bg-white/30 rounded-full h-3">
                <div class="bg-white h-3 rounded-full transition-all" style="width:75%"></div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-8">

        <!-- Upcoming Appointments + Queue History -->
        <div class="md:col-span-2 space-y-8">

            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Upcoming Appointments</h2>
                <div class="space-y-4">

                    <!-- Active appointment -->
                    <div class="border border-[#A6E3E9] bg-[#f0fdfd] rounded-xl p-4">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-stethoscope text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">Medical Consultation</div>
                                    <div class="text-sm text-gray-500">Dr. Smith • Room 302</div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-lg font-bold text-[#3aabb1]">A-048</div>
                                <div class="text-xs text-gray-500">Today, 10:30 AM</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-3 text-sm flex-wrap">
                            <span class="px-3 py-1 bg-[#71C9CE] text-white rounded-full text-xs font-semibold">In Queue</span>
                            <span class="text-gray-500"><i class="fas fa-clock mr-1 text-[#71C9CE]"></i>Est. wait: 15 min</span>
                        </div>
                    </div>

                    <!-- Scheduled appointment -->
                    <div class="border border-gray-200 rounded-xl p-4 hover:border-[#71C9CE] transition-colors">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-tooth text-gray-500 text-sm"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">Dental Checkup</div>
                                    <div class="text-sm text-gray-500">Dr. Johnson • Clinic B</div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-lg font-bold text-gray-500">C-012</div>
                                <div class="text-xs text-gray-500">Tomorrow, 2:00 PM</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-3 text-sm flex-wrap">
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Scheduled</span>
                            <button class="ml-auto text-red-500 hover:text-red-700 text-xs font-medium flex items-center gap-1 transition-colors">
                                <i class="fas fa-times"></i>Cancel
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Queue History Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Recent Queue History</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php foreach (['Date','Service','Queue No.','Status','Wait Time'] as $h): ?>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo $h; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php
                            $history = [
                                ['Apr 10, 2026', 'Medical Consultation', 'A-039', '12 min'],
                                ['Apr 8, 2026',  'Hair Salon',           'B-022', '18 min'],
                                ['Apr 5, 2026',  'Dental Checkup',       'C-007', '8 min'],
                                ['Apr 1, 2026',  'Legal Consultation',   'D-015', '25 min'],
                                ['Mar 28, 2026', 'Vehicle Service',      'E-031', '45 min'],
                            ];
                            foreach ($history as [$date, $service, $qnum, $wait]):
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo $date; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $service; ?></td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900"><?php echo $qnum; ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2.5 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Completed</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?php echo $wait; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /.main -->

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Notifications -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Notifications</h2>
                <div class="space-y-4">
                    <?php
                    $notifs = [
                        ['fa-bell',  'bg-blue-100',   'text-blue-500',  'Your turn is next',       'Queue A-047 is being served'],
                        ['fa-check', 'bg-green-100',  'text-green-500', 'Appointment confirmed',    'Dental checkup tomorrow'],
                        ['fa-clock', 'bg-yellow-100', 'text-yellow-500','Reminder',                 'Arrive 10 mins early'],
                    ];
                    foreach ($notifs as [$icon, $iconBg, $iconColor, $title, $desc]):
                    ?>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 <?php echo $iconBg; ?> rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas <?php echo $icon; ?> <?php echo $iconColor; ?> text-sm"></i>
                        </div>
                        <div>
                            <div class="font-medium text-gray-800 text-sm"><?php echo $title; ?></div>
                            <div class="text-xs text-gray-500 mt-0.5"><?php echo $desc; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

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
                    <button class="w-full flex items-center p-3 border border-gray-100 rounded-xl hover:border-[#71C9CE] hover:bg-[#f0fdfd] transition-all group">
                        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0 group-hover:scale-105 transition-transform">
                            <i class="fas fa-print text-blue-600 text-sm"></i>
                        </div>
                        <span class="font-medium text-sm text-gray-700">Print Queue Ticket</span>
                    </button>
                    <button class="w-full flex items-center p-3 border border-gray-100 rounded-xl hover:border-[#71C9CE] hover:bg-[#f0fdfd] transition-all group">
                        <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0 group-hover:scale-105 transition-transform">
                            <i class="fas fa-share-alt text-green-600 text-sm"></i>
                        </div>
                        <span class="font-medium text-sm text-gray-700">Share Status</span>
                    </button>
                    <button class="w-full flex items-center p-3 border border-gray-100 rounded-xl hover:border-red-200 hover:bg-red-50 transition-all group">
                        <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0 group-hover:scale-105 transition-transform">
                            <i class="fas fa-times text-red-500 text-sm"></i>
                        </div>
                        <span class="font-medium text-sm text-gray-700">Cancel Appointment</span>
                    </button>
                </div>
            </div>

            <!-- Estimated Wait Times -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Service Wait Times</h2>
                <div class="space-y-4">
                    <?php
                    $waits = [
                        ['Medical Consultation', '15 min', 60,  '#71C9CE'],
                        ['Hair Salon',           '25 min', 80,  '#f472b6'],
                        ['Dental Checkup',       '5 min',  20,  '#2dd4bf'],
                        ['Legal Consultation',   '45 min', 75,  '#fbbf24'],
                        ['Vehicle Service',      '90 min', 90,  '#60a5fa'],
                    ];
                    foreach ($waits as [$label, $time, $pct, $color]):
                    ?>
                    <div>
                        <div class="flex justify-between mb-1.5 text-sm">
                            <span class="text-gray-600"><?php echo $label; ?></span>
                            <span class="font-semibold text-gray-800"><?php echo $time; ?></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /.sidebar -->

    </div>
</div>

<?php include('../includes/footer.php'); ?>
