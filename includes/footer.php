    </main>

    <footer class="bg-white border-t mt-16">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

                <!-- Brand -->
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="gradient-bg w-8 h-8 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-800">AquaQueue</span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        Streamlining appointments with modern queue management — so your time is never wasted again.
                    </p>
                    <!-- Social icons -->
                    <div class="flex gap-2 mt-4">
                        <?php
                        $socials = [
                            ['fab fa-facebook-f', '#', 'Facebook'],
                            ['fab fa-instagram',  '#', 'Instagram'],
                            ['fab fa-twitter',    '#', 'Twitter'],
                            ['fab fa-github',     '#', 'GitHub'],
                        ];
                        foreach ($socials as $s):
                        ?>
                        <a href="<?php echo $s[1]; ?>" title="<?php echo $s[2]; ?>"
                           class="w-8 h-8 rounded-lg bg-[#E3FDFD] text-[#4db8be] hover:bg-[#71C9CE] hover:text-white flex items-center justify-center text-sm transition-all">
                            <i class="<?php echo $s[0]; ?>"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <?php
                        // Detect if we're in the admin folder so we can use correct relative paths
                        $isAdmin = in_array(basename($_SERVER['PHP_SELF']), ['dashboard.php','manage-queue.php']);
                        $pub = $isAdmin ? '../public/' : '';
                        $links = [
                            [$pub.'index.php',  'Home'],
                            [$pub.'book.php',   'Book Appointment'],
                            [$pub.'queue.php',  'My Queue'],
                            [$pub.'about.php',  'About Us'],
                        ];
                        foreach ($links as $l):
                        ?>
                        <li>
                            <a href="<?php echo $l[0]; ?>" class="text-gray-500 hover:text-[#71C9CE] text-sm transition-colors">
                                <?php echo $l[1]; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Account -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase mb-4">Account</h3>
                    <ul class="space-y-2">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo $pub; ?>login.php"    class="text-gray-500 hover:text-[#71C9CE] text-sm transition-colors">Login</a></li>
                        <li><a href="<?php echo $pub; ?>register.php" class="text-gray-500 hover:text-[#71C9CE] text-sm transition-colors">Register</a></li>
                        <li><a href="<?php echo $pub; ?>forgotpass.php" class="text-gray-500 hover:text-[#71C9CE] text-sm transition-colors">Forgot Password</a></li>
                        <?php else: ?>
                        <li>
                            <span class="text-gray-500 text-sm">
                                Hi, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </span>
                        </li>
                        <?php if (in_array($_SESSION['user_role']??'', ['admin','developer'])): ?>
                        <li><a href="<?php echo $isAdmin ? 'dashboard.php' : '../admin/dashboard.php'; ?>" class="text-gray-500 hover:text-[#71C9CE] text-sm transition-colors">Dashboard</a></li>
                        <?php elseif (($_SESSION['user_role']??'') === 'service_admin'): ?>
                        <li><a href="<?php echo $isAdmin ? 'manage-queue.php' : '../admin/manage-queue.php'; ?>" class="text-gray-500 hover:text-[#71C9CE] text-sm transition-colors">Manage Queue</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo $pub; ?>logout.php" class="text-gray-500 hover:text-red-500 text-sm transition-colors">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 tracking-wider uppercase mb-4">Contact</h3>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-envelope text-[#71C9CE] mt-0.5"></i>
                            <a href="mailto:support@aquaqueue.com" class="hover:text-[#71C9CE] transition-colors">support@aquaqueue.com</a>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-phone text-[#71C9CE] mt-0.5"></i>
                            <span>+63 (2) 8123 4567</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-clock text-[#71C9CE] mt-0.5"></i>
                            <span>9 AM – 6 PM, Mon–Fri</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-map-marker-alt text-[#71C9CE] mt-0.5"></i>
                            <span>Quezon City, Philippines</span>
                        </li>
                    </ul>
                </div>

            </div><!-- /.grid -->

            <!-- Bottom bar -->
            <div class="border-t border-gray-100 mt-10 pt-8 flex flex-col sm:flex-row justify-between items-center gap-3">
                <p class="text-gray-400 text-sm text-center">
                    &copy; <?php echo date('Y'); ?> AquaQueue Appointment System. All rights reserved.
                </p>
                <div class="flex gap-4 text-sm">
                    <a href="<?php echo $pub; ?>Terms.php"   class="text-gray-400 hover:text-[#71C9CE] transition-colors">Terms of Service</a>
                    <a href="<?php echo $pub; ?>Privacy.php" class="text-gray-400 hover:text-[#71C9CE] transition-colors">Privacy Policy</a>
                </div>
            </div>

        </div>
    </footer>

    <script src="<?php echo $isAdmin ? '../' : ''; ?>assets/js/main.js"></script>
</body>
</html>
