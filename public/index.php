<?php
session_start();
$pageTitle = "Home - AquaQueue";
include('../includes/header.php');
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap');

    :root {
        --teal: #71C9CE;
        --teal-dark: #4db8be;
        --teal-dim: #A6E3E9;
        --teal-pale: #E3FDFD;
        --ink: #0d1117;
        --muted: #6b7a8d;
        --surface: #ffffff;
        --border: rgba(113, 201, 206, 0.18);
    }

    body { font-family: 'DM Sans', sans-serif; overflow-x: hidden; background: #f0fdfd !important; }
    h1, h2, h3, h4 { font-family: 'Sora', sans-serif; }

    /* ── Transparent wrappers ── */
    body > div, body > main, body > div > div,
    [class*="max-w"], [class*="container"],
    .page-wrapper, .content-wrapper, .wrapper,
    #app, #main, #content { background: transparent !important; }
    .bg-white:not(nav):not(nav *) { background-color: transparent !important; }
    .bg-gray-50:not(nav):not(nav *) { background-color: transparent !important; }
    .bg-gray-100:not(nav):not(nav *) { background-color: transparent !important; }

    /* ── Section backgrounds ── */
    section.features-section    { background-color: #ffffff !important; }
    section.services-section    { background: linear-gradient(135deg, #f0fdfd 0%, #f8fffe 100%) !important; }
    section.hiw-section         { background: linear-gradient(135deg, #f0fdfd 0%, #f8fffe 100%) !important; }
    section.marquee-section     { background-color: #ffffff !important; border-top: 1px solid #E3FDFD; border-bottom: 1px solid #E3FDFD; }
    section.testimonial-section { background-color: #ffffff !important; }
    section.stats-section       { background: linear-gradient(135deg, #e3fdfd 0%, #cbf5f7 100%) !important; }
    section.cta-section         { background: linear-gradient(135deg, #e3fdfd 0%, #cbf5f7 100%) !important; }

    /* ── Hero ── */
    .hero-bg {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #f0fdfd 0%, #e8f9f9 40%, #f5fffe 100%);
        width: 100vw; left: 50%; right: 50%; margin-left: -50vw; margin-right: -50vw;
    }
    .blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.45; animation: drift 12s ease-in-out infinite alternate; }
    .blob-1 { width: 520px; height: 520px; background: #71C9CE; top: -140px; left: -100px; animation-duration: 14s; }
    .blob-2 { width: 380px; height: 380px; background: #A6E3E9; top: 60px; right: -80px; animation-duration: 10s; animation-delay: -4s; }
    .blob-3 { width: 260px; height: 260px; background: #CBF5F7; bottom: -60px; left: 38%; animation-duration: 16s; animation-delay: -8s; }
    @keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(30px,20px) scale(1.08); } }

    /* ── Animations ── */
    @keyframes fadeUp  { from { opacity:0; transform:translateY(32px); } to { opacity:1; transform:translateY(0); } }
    @keyframes fadeIn  { from { opacity:0; } to { opacity:1; } }
    @keyframes scaleIn { from { opacity:0; transform:scale(.92); } to { opacity:1; transform:scale(1); } }
    .fade-up  { opacity:0; animation: fadeUp  0.7s cubic-bezier(.22,.68,0,1.2) forwards; }
    .fade-in  { opacity:0; animation: fadeIn  0.8s ease forwards; }
    .scale-in { opacity:0; animation: scaleIn 0.6s cubic-bezier(.22,.68,0,1.2) forwards; }
    .d1{animation-delay:.05s} .d2{animation-delay:.15s} .d3{animation-delay:.25s}
    .d4{animation-delay:.38s} .d5{animation-delay:.52s} .d6{animation-delay:.66s}

    /* ── Glass card ── */
    .glass {
        background: rgba(255,255,255,0.72);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--border);
        box-shadow: 0 4px 32px rgba(113,201,206,0.10), 0 1px 0 rgba(255,255,255,0.9) inset;
    }
    .glass:hover {
        box-shadow: 0 8px 40px rgba(113,201,206,0.18), 0 1px 0 rgba(255,255,255,0.9) inset;
        transform: translateY(-3px); transition: all 0.3s ease;
    }

    /* ── Buttons ── */
    .btn-primary {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 14px 28px;
        background: linear-gradient(135deg, var(--teal) 0%, var(--teal-dark) 100%);
        color: white; font-family: 'Sora', sans-serif; font-weight: 600; font-size: 0.95rem;
        border-radius: 14px; text-decoration: none;
        box-shadow: 0 6px 24px rgba(113,201,206,0.45); transition: all 0.25s ease;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(113,201,206,0.5); }
    .btn-ghost {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 13px 28px;
        background: rgba(255,255,255,0.8); color: var(--ink);
        font-family: 'Sora', sans-serif; font-weight: 600; font-size: 0.95rem;
        border-radius: 14px; text-decoration: none;
        border: 1.5px solid rgba(113,201,206,0.3);
        backdrop-filter: blur(8px); transition: all 0.25s ease;
    }
    .btn-ghost:hover { border-color: var(--teal); color: var(--teal-dark); transform: translateY(-2px); }

    /* ── Labels & stats ── */
    .section-label {
        display: inline-block; padding: 4px 14px; border-radius: 99px;
        background: var(--teal-pale); color: var(--teal-dark);
        font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; margin-bottom: 1rem; font-family: 'Sora', sans-serif;
    }
    .stat-val {
        font-family: 'Sora', sans-serif; font-weight: 800; font-size: 2.4rem;
        background: linear-gradient(135deg, var(--teal-dark) 0%, #3aabb1 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1.1;
    }

    /* ── Feature icon ── */
    .feat-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, var(--teal-pale) 0%, #cbf5f7 100%);
        margin-bottom: 1.25rem; font-size: 1.35rem; color: var(--teal-dark); transition: transform 0.3s;
    }
    .glass:hover .feat-icon { transform: scale(1.1) rotate(-4deg); }

    /* ── Service card icon ── */
    .service-icon {
        width: 56px; height: 56px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem; font-size: 1.5rem; transition: transform 0.3s;
    }
    .glass:hover .service-icon { transform: scale(1.1) rotate(-4deg); }

    /* ── Steps ── */
    .step-num {
        width: 56px; height: 56px; border-radius: 16px;
        background: linear-gradient(135deg, var(--teal) 0%, #4db8be 100%);
        color: white; font-family: 'Sora', sans-serif; font-weight: 700; font-size: 1.4rem;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 20px rgba(113,201,206,0.4);
        margin: 0 auto 1.25rem; transition: transform 0.3s;
    }
    .step-card:hover .step-num { transform: scale(1.08) rotate(3deg); }

    /* ── Testimonial ── */
    .quote-mark { font-family: 'Sora', serif; font-size: 4rem; line-height: 1; color: var(--teal-dim); margin-bottom: -1rem; display: block; }

    /* ── Stats banner ── */
    .stats-val-lg { font-family: 'Sora', sans-serif; font-weight: 800; font-size: 2.8rem; line-height: 1.1; }

    /* ── Reveal ── */
    .reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.6s ease, transform 0.6s ease; }
    .reveal.visible { opacity: 1; transform: translateY(0); }

    /* ── Marquee ── */
    .marquee-track { display: flex; gap: 0; animation: marquee 22s linear infinite; width: max-content; }
    @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    .marquee-item {
        display: inline-flex; align-items: center; gap: 10px; padding: 0 36px;
        font-family: 'Sora', sans-serif; font-size: 0.85rem; font-weight: 600; color: #6b7a8d;
        white-space: nowrap; border-right: 1.5px solid #E3FDFD;
    }
    .marquee-item i { color: #71C9CE; font-size: 1rem; }
</style>

<!-- ══════════════════════════════════════════
     HERO
═══════════════════════════════════════════ -->
<section class="hero-bg min-h-[88vh] flex items-center">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="relative z-10 max-w-6xl mx-auto px-6 py-24 w-full">
        <div class="max-w-3xl mx-auto text-center">

            <!-- Eyebrow -->
            <div class="fade-in d1 inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/70 border border-[#71C9CE]/25 backdrop-blur-sm text-sm font-semibold text-[#3aabb1] mb-6 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-[#71C9CE] animate-pulse"></span>
                Smart Queue Management — Now Live
            </div>

            <!-- Headline -->
            <h1 class="fade-up d2 text-5xl md:text-[4.2rem] font-extrabold text-[#0d1117] leading-[1.08] tracking-tight mb-6">
                Skip the Wait.<br>
                <span style="background:linear-gradient(135deg,#71C9CE 0%,#3aabb1 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                    Book Your Spot Online.
                </span>
            </h1>

            <!-- Sub -->
            <p class="fade-up d3 text-lg text-[#6b7a8d] leading-relaxed max-w-xl mx-auto mb-10 font-light">
                AquaQueue lets you book appointments and track your queue position in real-time — no more wasted hours in line.
            </p>

            <!-- CTAs — session-aware from original code -->
            <div class="fade-up d4 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="book.php" class="btn-primary">
                    <i class="fas fa-calendar-plus"></i> Book Appointment
                </a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn-ghost">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
                <?php else: ?>
                <a href="queue.php" class="btn-ghost">
                    <i class="fas fa-list-ol"></i> My Queue Status
                </a>
                <?php endif; ?>
            </div>

            <!-- Trust strip -->
            <div class="fade-in d5 mt-12 flex flex-wrap gap-6 justify-center text-sm text-[#6b7a8d]">
                <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-[#71C9CE]"></i> No credit card required</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-[#71C9CE]"></i> Works on all devices</span>
                <span class="flex items-center gap-1.5"><i class="fas fa-check-circle text-[#71C9CE]"></i> 24/7 online booking</span>
            </div>
        </div>

        <!-- Floating stat cards -->
        <div class="scale-in d6 mt-16 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
            <?php
            $heroStats = [
                ['1,500+', 'Appointments Today', 'fa-calendar-check'],
                ['98%',    'Satisfaction Rate',  'fa-star'],
                ['15 min', 'Avg. Wait Time',     'fa-clock'],
                ['24/7',   'Online Booking',     'fa-globe'],
            ];
            foreach ($heroStats as $s):
            ?>
            <div class="glass rounded-2xl p-5 text-center transition-all duration-300">
                <i class="fas <?= $s[2] ?> text-[#71C9CE] text-xl mb-2 block"></i>
                <div class="stat-val"><?= $s[0] ?></div>
                <div class="text-xs text-[#6b7a8d] font-medium mt-1"><?= $s[1] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     FEATURES
═══════════════════════════════════════════ -->
<section class="py-28 features-section">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="section-label">Why AquaQueue</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-2">Everything you need,<br>nothing you don't</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <?php
            $features = [
                ['fa-bolt',       'Instant Booking',     'Reserve a slot in seconds across all supported services — no phone calls, no waiting on hold.'],
                ['fa-clock',      'Real-time Queue',     'Live position updates and accurate ETAs so you never have to guess when your turn is coming.'],
                ['fa-bell',       'Smart Notifications', 'Get notified via email when your appointment is confirmed and when your turn is approaching.'],
                ['fa-shield-alt', 'Secure & Private',    'Your booking data is encrypted and never shared. We take your privacy seriously.'],
                ['fa-mobile-alt', 'Mobile First',        'Designed from the ground up for phones. Book, track, and manage on any screen, anywhere.'],
                ['fa-chart-bar',  'Admin Analytics',     'Businesses get powerful dashboards to track queue flow, peak times, and service performance.'],
            ];
            foreach ($features as $i => $f):
            ?>
            <div class="glass rounded-2xl p-7 reveal cursor-default" style="transition-delay:<?= $i * 0.08 ?>s">
                <div class="feat-icon"><i class="fas <?= $f[0] ?>"></i></div>
                <h3 class="font-bold text-lg text-[#0d1117] mb-2"><?= $f[1] ?></h3>
                <p class="text-[#6b7a8d] text-sm leading-relaxed"><?= $f[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     SERVICES  (from original index.php)
═══════════════════════════════════════════ -->
<section class="py-28 services-section">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="section-label">Available Services</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-2">Book any service,<br>get a queue number instantly</h2>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
            <?php
            $services = [
                ['fa-stethoscope', '#71C9CE', 'Medical Consultation', 'General & specialist consultations'],
                ['fa-cut',         '#f472b6', 'Hair Salon',           'Haircut, coloring & styling'],
                ['fa-tooth',       '#2dd4bf', 'Dental Checkup',       'General & cosmetic dental care'],
                ['fa-gavel',       '#fbbf24', 'Legal Consultation',   'Corporate & family legal advice'],
                ['fa-car',         '#60a5fa', 'Vehicle Service',      'Car maintenance & repair'],
                ['fa-briefcase',   '#a78bfa', 'Business Meeting',     'Meeting room reservations'],
            ];
            foreach ($services as $i => [$icon, $color, $name, $desc]):
            ?>
            <a href="book.php" class="glass rounded-2xl p-7 reveal text-center group block" style="transition-delay:<?= $i * 0.08 ?>s; text-decoration:none;">
                <div class="service-icon" style="background:<?= $color ?>22">
                    <i class="fas <?= $icon ?>" style="color:<?= $color ?>"></i>
                </div>
                <div class="font-bold text-[#0d1117] mb-1"><?= $name ?></div>
                <div class="text-xs text-[#6b7a8d]"><?= $desc ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════════ -->
<section class="py-28 hiw-section">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="section-label">The Process</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-2">Up and running<br>in four simple steps</h2>
        </div>
        <div class="grid md:grid-cols-4 gap-8">
            <?php
            $steps = [
                ['Book',       'Pick your service, location, date, and time slot in under a minute.'],
                ['Get Number', 'Receive a unique queue number and booking confirmation instantly.'],
                ['Track Live', 'Watch your position in the queue update in real time from any device.'],
                ['Get Served', 'Walk in right on time — no waiting room anxiety, no wasted time.'],
            ];
            foreach ($steps as $i => $s):
            ?>
            <div class="step-card text-center reveal" style="transition-delay:<?= $i * 0.1 ?>s">
                <div class="step-num"><?= $i + 1 ?></div>
                <h3 class="font-bold text-[#0d1117] text-lg mb-2"><?= $s[0] ?></h3>
                <p class="text-[#6b7a8d] text-sm leading-relaxed"><?= $s[1] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     SERVICES MARQUEE
═══════════════════════════════════════════ -->
<section class="py-10 marquee-section overflow-hidden">
    <div class="marquee-track">
        <?php
        $marqueeServices = [
            ['fa-stethoscope', 'Medical Consultation'],
            ['fa-cut',         'Hair Salon'],
            ['fa-tooth',       'Dental Checkup'],
            ['fa-gavel',       'Legal Consultation'],
            ['fa-car',         'Vehicle Service'],
            ['fa-briefcase',   'Business Meeting'],
            ['fa-stethoscope', 'Medical Consultation'],
            ['fa-cut',         'Hair Salon'],
            ['fa-tooth',       'Dental Checkup'],
            ['fa-gavel',       'Legal Consultation'],
            ['fa-car',         'Vehicle Service'],
            ['fa-briefcase',   'Business Meeting'],
        ];
        foreach ($marqueeServices as $sv):
        ?>
        <div class="marquee-item"><i class="fas <?= $sv[0] ?>"></i><?= $sv[1] ?></div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════
     STATS BANNER  (from original index.php)
═══════════════════════════════════════════ -->
<section class="py-20 stats-section">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12 reveal">
            <span class="section-label">By the Numbers</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-2">Trusted by thousands</h2>
            <p class="text-[#6b7a8d] mt-2">Streamlining appointments across multiple service providers</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            $statsData = [
                ['50K+',   'Happy Customers',    'fa-users'],
                ['98%',    'Satisfaction Rate',  'fa-star'],
                ['6',      'Services Available', 'fa-th-large'],
                ['< 5min', 'Avg. Booking Time',  'fa-bolt'],
            ];
            foreach ($statsData as $i => [$val, $label, $icon]):
            ?>
            <div class="glass rounded-2xl p-7 text-center reveal" style="transition-delay:<?= $i * 0.08 ?>s">
                <i class="fas <?= $icon ?> text-[#71C9CE] text-2xl mb-3 block"></i>
                <div class="stat-val"><?= $val ?></div>
                <div class="text-sm text-[#6b7a8d] font-medium mt-1"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════════ -->
<section class="py-28 testimonial-section">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="section-label">What People Say</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-2">Trusted by thousands<br>across the Philippines</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            <?php
            $testimonials = [
                ['Maria S.', 'Patient, City Medical Center', 4.8, 'I used to dread doctor visits because of the long wait. AquaQueue changed that completely — I just walk in when it\'s almost my turn. Amazing!'],
                ['James T.', 'Client, Santos Law Office',    5.0, 'Booked a legal consultation in two minutes flat. The whole experience felt premium. Highly recommend for busy professionals.'],
                ['Carla R.', 'Customer, Glamour Studio',     4.9, 'Finally a salon that respects my time! I track my queue from a coffee shop nearby and walk in fresh. Love it.'],
            ];
            foreach ($testimonials as $i => $t):
            ?>
            <div class="glass rounded-2xl p-7 reveal" style="transition-delay:<?= $i * 0.1 ?>s">
                <span class="quote-mark">"</span>
                <p class="text-[#374151] text-sm leading-relaxed mb-6"><?= $t[3] ?></p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                         style="background:linear-gradient(135deg,#71C9CE,#4db8be)">
                        <?= strtoupper(substr($t[0], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="font-semibold text-sm text-[#0d1117]"><?= $t[0] ?></div>
                        <div class="text-xs text-[#6b7a8d]"><?= $t[1] ?></div>
                    </div>
                    <div class="ml-auto flex gap-0.5">
                        <?php for ($s = 0; $s < 5; $s++): ?>
                        <i class="fas fa-star text-xs <?= $s < floor($t[2]) ? 'text-yellow-400' : 'text-gray-200' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     CTA BANNER  (session-aware from original)
═══════════════════════════════════════════ -->
<section class="py-20 cta-section">
    <div class="max-w-3xl mx-auto px-6 text-center reveal">
        <h2 class="text-4xl md:text-5xl font-extrabold text-[#0d1117] mb-4 leading-tight">
            Ready to skip<br>the queue?
        </h2>
        <p class="text-[#6b7a8d] mb-10 text-lg font-light">
            Join thousands of users who've taken back their time with AquaQueue.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn-primary text-base px-10 py-4">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <a href="book.php" class="btn-ghost text-base px-10 py-4">
                <i class="fas fa-calendar-alt"></i> Book as Guest
            </a>
            <?php else: ?>
            <a href="book.php" class="btn-primary text-base px-10 py-4">
                <i class="fas fa-calendar-plus"></i> Book Appointment
            </a>
            <a href="queue.php" class="btn-ghost text-base px-10 py-4">
                <i class="fas fa-list-ol"></i> My Queue Status
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    const revealEls = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    revealEls.forEach(el => observer.observe(el));
</script>

<?php include('../includes/footer.php'); ?>