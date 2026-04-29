<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaQueue - <?php echo $pageTitle ?? 'Appointment System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #71C9CE 0%, #A6E3E9 100%); }
        .card-hover  { transition: all 0.3s ease; }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(113,201,206,0.2);
        }

        /* ── Mobile menu ─────────────────────────────────────── */
        #mobile-menu {
            display: none;
            flex-direction: column;
            background: #fff;
            border-top: 1px solid #E3FDFD;
            padding: 0.75rem 1rem 1rem;
            gap: 2px;
        }
        #mobile-menu.open { display: flex; }
        #mobile-menu a {
            display: block;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #374151;
            text-decoration: none;
            transition: background 0.15s;
        }
        #mobile-menu a:hover,
        #mobile-menu a.active-link { background: #E3FDFD; color: #3aabb1; }
        #mobile-menu .mobile-divider {
            height: 1px; background: #E3FDFD; margin: 6px 0;
        }
        #mobile-menu .mobile-btn-primary {
            display: block; text-align: center;
            padding: 11px 14px; border-radius: 10px;
            background: linear-gradient(135deg,#71C9CE,#4db8be);
            color: #fff; font-weight: 600; font-size: 0.9rem;
            text-decoration: none; margin-top: 4px;
        }
        #mobile-menu .mobile-btn-outline {
            display: block; text-align: center;
            padding: 10px 14px; border-radius: 10px;
            border: 1.5px solid #A6E3E9; color: #3aabb1;
            font-weight: 600; font-size: 0.9rem;
            text-decoration: none; margin-top: 4px;
            background: transparent;
        }

        /* hamburger animation */
        .hamburger span {
            display: block; width: 22px; height: 2px;
            background: #374151; border-radius: 2px;
            transition: all 0.25s ease;
        }
        .hamburger.open span:nth-child(1) { transform: translateY(8px) rotate(45deg); }
        .hamburger.open span:nth-child(2) { opacity: 0; }
        .hamburger.open span:nth-child(3) { transform: translateY(-8px) rotate(-45deg); }

        /* Nav scroll shadow */
        #main-nav { transition: box-shadow 0.3s ease; background: #ffffff !important; }
        #main-nav.scrolled { box-shadow: 0 4px 24px rgba(113,201,206,0.18); }
    </style>
</head>

<?php
$isFullBleed = in_array(basename($_SERVER['PHP_SELF']), ['index.php', 'about.php']);
$currentPage = basename($_SERVER['PHP_SELF']);

// Helper to detect admin pages
$isAdminPage = in_array($currentPage, ['dashboard.php', 'manage-queue.php']);
?>

<body class="<?php echo $isFullBleed ? 'bg-[#f0fdfd]' : 'bg-[#E3FDFD]'; ?>">

    <!-- ── NAVBAR ──────────────────────────────────────────────── -->
    <nav id="main-nav" class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">

                <!-- Left: Logo + Desktop Links -->
                <div class="flex items-center">
                    <a href="<?php echo $isAdminPage ? '../public/index.php' : 'index.php'; ?>" class="flex items-center space-x-2 flex-shrink-0">
                        <div class="gradient-bg w-8 h-8 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-800">AquaQueue</span>
                    </a>

                    <!-- Desktop nav links -->
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-1">
                        <?php
                        $base = $isAdminPage ? '../public/' : '';
                        $navLinks = [
                            ['href' => $base.'index.php',  'label' => 'Home',  'page' => 'index.php'],
                            ['href' => $base.'book.php',   'label' => 'Book',  'page' => 'book.php'],
                            ['href' => $base.'about.php',  'label' => 'About', 'page' => 'about.php'],
                        ];
                        foreach ($navLinks as $link):
                            $isActive = ($currentPage === $link['page']);
                        ?>
                        <a href="<?php echo $link['href']; ?>"
                           class="<?php echo $isActive
                               ? 'text-[#71C9CE] border-b-2 border-[#71C9CE] bg-[#f0fdfd]'
                               : 'text-gray-500 hover:text-[#71C9CE] hover:bg-[#f0fdfd]'; ?>
                               inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all">
                            <?php echo $link['label']; ?>
                        </a>
                        <?php endforeach; ?>

                        <!-- My Queue — logged-in users only -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $base; ?>queue.php"
                           class="<?php echo $currentPage === 'queue.php'
                               ? 'text-[#71C9CE] border-b-2 border-[#71C9CE] bg-[#f0fdfd]'
                               : 'text-gray-500 hover:text-[#71C9CE] hover:bg-[#f0fdfd]'; ?>
                               inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all">
                            My Queue
                        </a>
                        <?php endif; ?>

                        <!-- Admin link — admin & developer only -->
                        <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'developer'])): ?>
                        <a href="<?php echo $isAdminPage ? 'dashboard.php' : '../admin/dashboard.php'; ?>"
                           class="<?php echo $isAdminPage
                               ? 'text-[#71C9CE] border-b-2 border-[#71C9CE] bg-[#f0fdfd]'
                               : 'text-gray-500 hover:text-[#71C9CE] hover:bg-[#f0fdfd]'; ?>
                               inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all">
                            Admin
                        </a>
                        <?php endif; ?>

                        <!-- Service Admin link — only sees their own service -->
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'service_admin'): ?>
                        <a href="<?php echo $isAdminPage ? 'manage-queue.php' : '../admin/manage-queue.php'; ?>"
                           class="<?php echo $currentPage === 'manage-queue.php'
                               ? 'text-[#71C9CE] border-b-2 border-[#71C9CE] bg-[#f0fdfd]'
                               : 'text-gray-500 hover:text-[#71C9CE] hover:bg-[#f0fdfd]'; ?>
                               inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-all">
                            My Service
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Auth + Hamburger -->
                <div class="flex items-center gap-2">

                    <!-- Desktop auth -->
                    <div class="hidden sm:flex items-center gap-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- Role badge -->
                            <?php
                            $roleBadge = [
                                'developer'     => ['bg-purple-100 text-purple-700', 'fa-code'],
                                'admin'         => ['bg-blue-100 text-blue-700',     'fa-user-shield'],
                                'service_admin' => ['bg-orange-100 text-orange-700', 'fa-user-cog'],
                                'user'          => ['bg-green-100 text-green-700',   'fa-user'],
                                'client'        => ['bg-gray-100 text-gray-600',     'fa-user-clock'],
                            ];
                            $role = $_SESSION['user_role'] ?? 'user';
                            [$badgeClass, $badgeIcon] = $roleBadge[$role] ?? $roleBadge['user'];
                            ?>
                            <span class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $badgeClass; ?>">
                                <i class="fas <?php echo $badgeIcon; ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $role)); ?>
                            </span>

                            <span class="text-gray-600 text-sm font-medium hidden md:block">
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </span>

                            <?php if (in_array($role, ['admin', 'developer'])): ?>
                            <a href="<?php echo $isAdminPage ? 'dashboard.php' : '../admin/dashboard.php'; ?>"
                               class="bg-[#71C9CE] text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-[#5ab4b9] transition-all">
                                Dashboard
                            </a>
                            <?php elseif ($role === 'service_admin'): ?>
                            <a href="<?php echo $isAdminPage ? 'manage-queue.php' : '../admin/manage-queue.php'; ?>"
                               class="bg-[#71C9CE] text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-[#5ab4b9] transition-all">
                                Manage Queue
                            </a>
                            <?php endif; ?>

                            <a href="<?php echo $isAdminPage ? '../public/logout.php' : 'logout.php'; ?>"
                               class="text-gray-500 hover:text-red-500 text-sm font-medium transition-colors px-2">
                                <i class="fas fa-sign-out-alt mr-1"></i>Logout
                            </a>

                        <?php else: ?>
                            <a href="<?php echo $isAdminPage ? '../public/login.php' : 'login.php'; ?>"
                               class="<?php echo $currentPage === 'login.php' ? 'text-[#71C9CE]' : 'text-gray-600 hover:text-gray-900'; ?>
                                      px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                Login
                            </a>
                            <a href="<?php echo $isAdminPage ? '../public/register.php' : 'register.php'; ?>"
                               class="bg-[#71C9CE] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#5ab4b9] transition-all shadow-sm">
                                Sign Up
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Hamburger (mobile) -->
                    <button id="hamburger-btn" class="sm:hidden hamburger flex flex-col gap-1.5 p-2 rounded-md hover:bg-gray-100 transition-colors" aria-label="Toggle menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>

            </div><!-- /.flex -->
        </div><!-- /.max-w -->

        <!-- ── MOBILE MENU ──────────────────────────────────────── -->
        <div id="mobile-menu">
            <?php
            $mobileBase = $isAdminPage ? '../public/' : '';
            ?>
            <a href="<?php echo $mobileBase; ?>index.php"  class="<?php echo $currentPage==='index.php'  ? 'active-link':'' ?>"><i class="fas fa-home w-5 mr-2 text-[#71C9CE]"></i>Home</a>
            <a href="<?php echo $mobileBase; ?>book.php"   class="<?php echo $currentPage==='book.php'   ? 'active-link':'' ?>"><i class="fas fa-calendar-plus w-5 mr-2 text-[#71C9CE]"></i>Book Appointment</a>
            <a href="<?php echo $mobileBase; ?>about.php"  class="<?php echo $currentPage==='about.php'  ? 'active-link':'' ?>"><i class="fas fa-info-circle w-5 mr-2 text-[#71C9CE]"></i>About</a>

            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo $mobileBase; ?>queue.php"  class="<?php echo $currentPage==='queue.php'  ? 'active-link':'' ?>"><i class="fas fa-list-ol w-5 mr-2 text-[#71C9CE]"></i>My Queue</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin','developer'])): ?>
            <a href="<?php echo $isAdminPage ? 'dashboard.php' : '../admin/dashboard.php'; ?>"
               class="<?php echo $isAdminPage ? 'active-link':'' ?>">
               <i class="fas fa-tachometer-alt w-5 mr-2 text-[#71C9CE]"></i>Admin Dashboard</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'service_admin'): ?>
            <a href="<?php echo $isAdminPage ? 'manage-queue.php' : '../admin/manage-queue.php'; ?>"
               class="<?php echo $currentPage==='manage-queue.php' ? 'active-link':'' ?>">
               <i class="fas fa-tasks w-5 mr-2 text-[#71C9CE]"></i>Manage My Queue</a>
            <?php endif; ?>

            <div class="mobile-divider"></div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="px-3 py-2 text-sm text-gray-500">
                    Signed in as <strong class="text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
                    <span class="ml-1 px-1.5 py-0.5 bg-[#E3FDFD] text-[#3aabb1] rounded text-xs font-bold"><?php echo ucfirst(str_replace('_',' ',$_SESSION['user_role']??'user')); ?></span>
                </div>
                <a href="<?php echo $isAdminPage ? '../public/logout.php' : 'logout.php'; ?>" class="mobile-btn-outline">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            <?php else: ?>
                <a href="<?php echo $mobileBase; ?>login.php"    class="mobile-btn-outline"><i class="fas fa-sign-in-alt mr-2"></i>Login</a>
                <a href="<?php echo $mobileBase; ?>register.php" class="mobile-btn-primary"><i class="fas fa-user-plus mr-2"></i>Sign Up</a>
            <?php endif; ?>
        </div>

    </nav><!-- /#main-nav -->

    <!-- MAIN wrapper -->
    <?php if ($isFullBleed): ?>
    <main>
    <?php else: ?>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?php endif; ?>

<script>
// Nav scroll shadow
const nav = document.getElementById('main-nav');
window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 10));

// Hamburger toggle
const hamburgerBtn = document.getElementById('hamburger-btn');
const mobileMenu   = document.getElementById('mobile-menu');
hamburgerBtn.addEventListener('click', () => {
    hamburgerBtn.classList.toggle('open');
    mobileMenu.classList.toggle('open');
});

// Close mobile menu on outside click
document.addEventListener('click', (e) => {
    if (!nav.contains(e.target)) {
        hamburgerBtn.classList.remove('open');
        mobileMenu.classList.remove('open');
    }
});
</script>
