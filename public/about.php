<?php
session_start();
$pageTitle = "About - AquaQueue";
include('../includes/header.php');
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap');

:root {
    --teal:      #71C9CE;
    --teal-dark: #4db8be;
    --teal-dim:  #A6E3E9;
    --teal-pale: #E3FDFD;
    --ink:       #0d1117;
    --muted:     #6b7a8d;
}

body  { font-family: 'DM Sans', sans-serif; background: #f0fdfd; }
h1, h2, h3, h4 { font-family: 'Sora', sans-serif; }

/* ── Blobs ─────────────────────────────────────────────────── */
.blob {
    position: absolute; border-radius: 50%;
    filter: blur(80px); opacity: 0.38;
    animation: drift 12s ease-in-out infinite alternate;
    pointer-events: none;
}
.blob-1 { width:480px; height:480px; background:#71C9CE; top:-120px; left:-80px;  animation-duration:14s; }
.blob-2 { width:320px; height:320px; background:#A6E3E9; top:80px;  right:-60px; animation-duration:10s; animation-delay:-5s; }
@keyframes drift {
    from { transform: translate(0,0) scale(1); }
    to   { transform: translate(25px,18px) scale(1.07); }
}

/* ── Glass card ─────────────────────────────────────────────── */
.glass {
    background: rgba(255,255,255,0.72);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(113,201,206,0.18);
    box-shadow: 0 4px 32px rgba(113,201,206,0.10), 0 1px 0 rgba(255,255,255,0.9) inset;
    transition: all 0.3s ease;
}
.glass:hover {
    box-shadow: 0 10px 40px rgba(113,201,206,0.18), 0 1px 0 rgba(255,255,255,0.9) inset;
    transform: translateY(-4px);
}

/* ── Section label pill ─────────────────────────────────────── */
.section-label {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 99px;
    background: var(--teal-pale);
    color: var(--teal-dark);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    font-family: 'Sora', sans-serif;
}

/* ── Gradient text ──────────────────────────────────────────── */
.grad-text {
    background: linear-gradient(135deg, var(--teal) 0%, #3aabb1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ── Team photo ─────────────────────────────────────────────── */
.team-photo {
    width: 140px; height: 140px;
    border-radius: 50%;
    object-fit: cover;
    object-position: top center;
    border: 4px solid white;
    box-shadow: 0 8px 28px rgba(113,201,206,0.3);
    transition: transform 0.3s ease;
}
.glass:hover .team-photo { transform: scale(1.05); }

/* ── Social icon btn ─────────────────────────────────────────── */
.social-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 42px; height: 42px;
    border-radius: 12px;
    background: rgba(113,201,206,0.1);
    color: var(--teal-dark);
    font-size: 1.1rem;
    transition: all 0.2s;
    text-decoration: none;
    border: 1px solid rgba(113,201,206,0.2);
}
.social-btn:hover {
    background: var(--teal);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(113,201,206,0.35);
}

/* ── Contact input ───────────────────────────────────────────── */
.contact-input {
    width: 100%; padding: 11px 16px;
    border: 1.5px solid rgba(113,201,206,0.25);
    border-radius: 12px;
    background: rgba(255,255,255,0.8);
    font-size: 0.9rem;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    transition: border 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.contact-input:focus {
    border-color: var(--teal);
    box-shadow: 0 0 0 3px rgba(113,201,206,0.15);
}

/* ── Scroll reveal ───────────────────────────────────────────── */
.reveal       { opacity:0; transform:translateY(26px);   transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal-left  { opacity:0; transform:translateX(-30px);  transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal-right { opacity:0; transform:translateX(30px);   transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal.visible, .reveal-left.visible, .reveal-right.visible { opacity:1; transform:translate(0); }
</style>

<!-- ══════════════════════════════════════════════════════════
     HERO BANNER
════════════════════════════════════════════════════════════ -->
<section class="relative overflow-hidden py-24"
         style="background:linear-gradient(135deg,#f0fdfd 0%,#e8f9f9 50%,#f5fffe 100%)">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center">
        <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/70 border border-[#71C9CE]/25 backdrop-blur-sm text-sm font-semibold text-[#3aabb1] mb-6 shadow-sm">
            <i class="fas fa-info-circle"></i> About AquaQueue
        </div>
        <h1 class="reveal text-5xl md:text-6xl font-extrabold text-[#0d1117] leading-tight mb-5" style="transition-delay:.1s">
            Built to give you<br>
            <span class="grad-text">your time back.</span>
        </h1>
        <p class="reveal text-lg text-[#6b7a8d] leading-relaxed max-w-2xl mx-auto font-light" style="transition-delay:.2s">
            AquaQueue was designed from the ground up to eliminate the inefficiency of traditional queuing systems —
            making appointments smarter, faster, and more respectful of your time.
        </p>

        <!-- Stat pill from simple about.php -->
        <div class="reveal mt-10 inline-flex items-center gap-4 px-8 py-4 rounded-2xl bg-white/70 border border-[#71C9CE]/20 backdrop-blur-sm shadow-sm" style="transition-delay:.3s">
            <i class="fas fa-clock text-3xl text-[#71C9CE]"></i>
            <div class="text-left">
                <div class="text-3xl font-extrabold grad-text leading-none">50,000+</div>
                <div class="text-sm text-[#6b7a8d] font-medium">Hours saved for customers</div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     MISSION & VISION
════════════════════════════════════════════════════════════ -->
<section class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="section-label">Our Purpose</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-3">Mission & Vision</h2>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Mission -->
            <div class="glass rounded-2xl p-8 reveal-left">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5"
                     style="background:linear-gradient(135deg,#E3FDFD,#cbf5f7)">
                    <i class="fas fa-bullseye text-2xl text-[#4db8be]"></i>
                </div>
                <h3 class="text-2xl font-bold text-[#0d1117] mb-4">Our Mission</h3>
                <p class="text-[#6b7a8d] leading-relaxed mb-4">
                    To provide individuals and businesses across the Philippines with a reliable, accessible, and efficient
                    appointment and queue management platform that eliminates unnecessary waiting and maximizes productivity.
                </p>
                <p class="text-[#6b7a8d] leading-relaxed">
                    We are committed to delivering a seamless digital experience that bridges the gap between service providers
                    and their clients — with transparency, fairness, and respect for everyone's time.
                </p>
            </div>

            <!-- Vision -->
            <div class="glass rounded-2xl p-8 reveal-right">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5"
                     style="background:linear-gradient(135deg,#E3FDFD,#cbf5f7)">
                    <i class="fas fa-eye text-2xl text-[#4db8be]"></i>
                </div>
                <h3 class="text-2xl font-bold text-[#0d1117] mb-4">Our Vision</h3>
                <p class="text-[#6b7a8d] leading-relaxed mb-4">
                    To become the leading queue and appointment management solution in Southeast Asia — a platform where
                    no one ever has to wait unnecessarily again.
                </p>
                <p class="text-[#6b7a8d] leading-relaxed">
                    We envision a future where every service interaction — whether medical, legal, automotive, or personal —
                    begins with dignity and efficiency, powered by intelligent technology built by Filipinos, for the world.
                </p>
            </div>
        </div>

        <!-- Core Values (expanded with simple about.php cards merged in) -->
        <div class="mt-10 grid grid-cols-2 md:grid-cols-3 gap-4 reveal">
            <?php
            $values = [
                ['fa-shield-alt', '#71C9CE', 'Integrity',   'We operate with honesty and transparency in everything we do.'],
                ['fa-bolt',       '#71C9CE', 'Speed',        'Every feature is designed to save time and reduce friction — real-time updates and instant confirmations.'],
                ['fa-users',      '#71C9CE', 'Inclusivity',  'Built to serve everyone regardless of background or ability.'],
                ['fa-lightbulb',  '#71C9CE', 'Innovation',   'We continuously improve to meet the evolving needs of our users.'],
                ['fa-heart',      '#f472b6', 'Care',         'We design every feature with the end user in mind.'],
                ['fa-lock',       '#2dd4bf', 'Security',     'Your data is protected with industry-standard encryption.'],
            ];
            foreach ($values as $i => [$icon, $color, $title, $desc]):
            ?>
            <div class="glass rounded-xl p-5 text-center" style="transition-delay:<?= $i * 0.06 ?>s">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3"
                     style="background:<?= $color ?>22">
                    <i class="fas <?= $icon ?> text-xl" style="color:<?= $color ?>"></i>
                </div>
                <div class="font-bold text-sm text-[#0d1117] mb-1"><?= $title ?></div>
                <div class="text-xs text-[#6b7a8d] leading-snug"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     MEET THE TEAM
════════════════════════════════════════════════════════════ -->
<section class="py-24" style="background:linear-gradient(135deg,#f0fdfd 0%,#f8fffe 100%)">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="section-label">The People Behind It</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-3">Meet the Team</h2>
            <p class="text-[#6b7a8d] mt-3 max-w-xl mx-auto">
                AquaQueue was conceived, designed, and developed by two passionate developers dedicated to solving
                real problems with thoughtful technology.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-1xl mx-auto">

            <!-- Carl -->
            <div class="glass rounded-2xl p-8 text-center reveal-left">
                <img src="../assets/js/carl.png"
                     alt="Carl David L. Binghay"
                     class="team-photo mx-auto mb-5"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="w-36 h-36 rounded-full mx-auto mb-5 items-center justify-center text-4xl font-bold text-white hidden"
                     style="background:linear-gradient(135deg,#71C9CE,#4db8be)">C</div>
                <h3 class="text-xl font-bold text-[#0d1117]">Carl David L. Binghay</h3>
                <p class="text-[#71C9CE] font-semibold text-sm mt-1 mb-3">Lead Developer & System Architect</p>
                <p class="text-[#6b7a8d] text-sm leading-relaxed mb-5">
                    Carl spearheads the system architecture and backend development of AquaQueue, ensuring robust,
                    scalable, and secure infrastructure that powers every booking and queue operation.
                </p>
                <div class="flex justify-center gap-2">
                    <a href="mailto:carl@aquaqueue.ph" class="social-btn" title="Email"><i class="fas fa-envelope"></i></a>
                    <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

            <!-- Gil -->
            <div class="glass rounded-2xl p-8 text-center reveal-right">
                <img src="../assets/js/gil.png"
                     alt="Gil A. Tabanag"
                     class="team-photo mx-auto mb-5"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="w-36 h-36 rounded-full mx-auto mb-5 items-center justify-center text-4xl font-bold text-white hidden"
                     style="background:linear-gradient(135deg,#71C9CE,#4db8be)">G</div>
                <h3 class="text-xl font-bold text-[#0d1117]">Gil A. Tabanag</h3>
                <p class="text-[#71C9CE] font-semibold text-sm mt-1 mb-3">UI/UX Designer & Frontend Developer</p>
                <p class="text-[#6b7a8d] text-sm leading-relaxed mb-5">
                    Gil is responsible for the visual identity and user experience of AquaQueue, crafting intuitive
                    interfaces that make every interaction feel effortless and modern across all devices.
                </p>
                <div class="flex justify-center gap-2">
                    <a href="mailto:t.gil0409@gmail.com" class="social-btn" title="Email"><i class="fas fa-envelope"></i></a>
                    <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

            <!-- Labiaga -->
            <div class="glass rounded-2xl p-8 text-center reveal-right">
                <img src="../assets/js/labiaga.png"
                     alt="Roshele Faye Labiaga"
                     class="team-photo mx-auto mb-5"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="w-36 h-36 rounded-full mx-auto mb-5 items-center justify-center text-4xl font-bold text-white hidden"
                     style="background:linear-gradient(135deg,#71C9CE,#4db8be)">R</div>
                <h3 class="text-xl font-bold text-[#0d1117]">Roshele Faye Labiaga</h3>
                <p class="text-[#71C9CE] font-semibold text-sm mt-1 mb-3">Project Manager</p>
                <p class="text-[#6b7a8d] text-sm leading-relaxed mb-5">
                    Roshele serves as the Project Manager of AquaQueue, overseeing the development process, coordinating the team, and 
                    ensuring that all project goals, timelines, and system requirements are successfully achieved.


                </p>
                <div class="flex justify-center gap-2">
                    <a href="mailto:roshele.labiaga@gmail.com" class="social-btn" title="Email"><i class="fas fa-envelope"></i></a>
                    <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

            <!-- Paradilla -->
            <div class="glass rounded-2xl p-8 text-center reveal-right">
                <img src="../assets/js/paradilla.png"
                     alt="Joseph Paradilla"
                     class="team-photo mx-auto mb-5"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="w-36 h-36 rounded-full mx-auto mb-5 items-center justify-center text-4xl font-bold text-white hidden"
                     style="background:linear-gradient(135deg,#71C9CE,#4db8be)">J</div>
                <h3 class="text-xl font-bold text-[#0d1117]">Joseph Paradilla</h3>
                <p class="text-[#71C9CE] font-semibold text-sm mt-1 mb-3">Tester</p>
                <p class="text-[#6b7a8d] text-sm leading-relaxed mb-5">
                    Joseph serves as the System Tester of AquaQueue, conducting thorough testing to identify bugs, verify system functionality, 
                    and ensure a smooth and reliable user experience.


                </p>
                <div class="flex justify-center gap-2">
                    <a href="mailto:joseph.paradilla@gmail.com" class="social-btn" title="Email"><i class="fas fa-envelope"></i></a>
                    <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

            <!-- Bumanglag -->
            <div class="glass rounded-2xl p-8 text-center reveal-right">
                <img src="../assets/js/bumanglag.png"
                     alt="Rhenz Yvan Bumanglag"
                     class="team-photo mx-auto mb-5"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="w-36 h-36 rounded-full mx-auto mb-5 items-center justify-center text-4xl font-bold text-white hidden"
                     style="background:linear-gradient(135deg,#71C9CE,#4db8be)">R</div>
                <h3 class="text-xl font-bold text-[#0d1117]">Rhenz Yvan Bumanglag</h3>
                <p class="text-[#71C9CE] font-semibold text-sm mt-1 mb-3">Programmer</p>
                <p class="text-[#6b7a8d] text-sm leading-relaxed mb-5">
                    Rhenz serves as the Programmer of AquaQueue, developing and implementing system features to 
                    ensure smooth functionality, efficiency, and reliable performance.


                </p>
                <div class="flex justify-center gap-2">
                    <a href="mailto:rhenz.bumanglag@gmail.com" class="social-btn" title="Email"><i class="fas fa-envelope"></i></a>
                    <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

            <!-- Metante -->
            <div class="glass rounded-2xl p-8 text-center reveal-right">
                <img src="../assets/js/metante.png"
                     alt="Jastine A. Metante"
                     class="team-photo mx-auto mb-5"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="w-36 h-36 rounded-full mx-auto mb-5 items-center justify-center text-4xl font-bold text-white hidden"
                     style="background:linear-gradient(135deg,#71C9CE,#4db8be)">J</div>
                <h3 class="text-xl font-bold text-[#0d1117]">Jastine A. Metante</h3>
                <p class="text-[#71C9CE] font-semibold text-sm mt-1 mb-3">Tester</p>
                <p class="text-[#6b7a8d] text-sm leading-relaxed mb-5">
                    Jastine is responsible for quality assurance and testing in AquaQueue, evaluating system performance, checking for errors, 
                    and ensuring that all features work correctly before deployment.


                </p>
                <div class="flex justify-center gap-2">
                    <a href="mailto:jastine.metante@gmail.com" class="social-btn" title="Email"><i class="fas fa-envelope"></i></a>
                    <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" title="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     CONTACT / SOCIALS
════════════════════════════════════════════════════════════ -->
<section class="py-24 bg-white">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-16 reveal">
            <span class="section-label">Get In Touch</span>
            <h2 class="text-4xl font-bold text-[#0d1117] mt-3">Contact Us</h2>
            <p class="text-[#6b7a8d] mt-3 max-w-xl mx-auto">
                Have a question, a suggestion, or want to partner with us? We would love to hear from you.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-10 items-start">

            <!-- Contact info -->
            <div class="reveal-left space-y-5">
                <div class="glass rounded-2xl p-6 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background:linear-gradient(135deg,#E3FDFD,#cbf5f7)">
                        <i class="fas fa-envelope text-[#4db8be] text-lg"></i>
                    </div>
                    <div>
                        <div class="font-bold text-[#0d1117] mb-0.5">Email</div>
                        <a href="mailto:t.gil0409@gmail.com" class="text-[#71C9CE] text-sm hover:underline">t.gil0409@gmail.com</a>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background:linear-gradient(135deg,#E3FDFD,#cbf5f7)">
                        <i class="fas fa-map-marker-alt text-[#4db8be] text-lg"></i>
                    </div>
                    <div>
                        <div class="font-bold text-[#0d1117] mb-0.5">Location</div>
                        <p class="text-[#6b7a8d] text-sm">Quezon City, Metro Manila, Philippines</p>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6">
                    <div class="font-bold text-[#0d1117] mb-4">Follow Us</div>
                    <div class="flex gap-3">
                        <a href="#" class="social-btn" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-btn" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-btn" title="Twitter / X"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-btn" title="GitHub"><i class="fab fa-github"></i></a>
                        <a href="#" class="social-btn" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <!-- Message form -->
            <div class="glass rounded-2xl p-8 reveal-right">
                <h3 class="text-xl font-bold text-[#0d1117] mb-6">Send us a Message</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#6b7a8d] uppercase tracking-wide mb-1.5">First Name</label>
                            <input type="text" class="contact-input" placeholder="Juan">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#6b7a8d] uppercase tracking-wide mb-1.5">Last Name</label>
                            <input type="text" class="contact-input" placeholder="dela Cruz">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#6b7a8d] uppercase tracking-wide mb-1.5">Email Address</label>
                        <input type="email" class="contact-input" placeholder="you@example.com">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#6b7a8d] uppercase tracking-wide mb-1.5">Subject</label>
                        <input type="text" class="contact-input" placeholder="How can we help?">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#6b7a8d] uppercase tracking-wide mb-1.5">Message</label>
                        <textarea class="contact-input" rows="4" placeholder="Write your message here..."></textarea>
                    </div>
                    <button onclick="submitContact()"
                            class="w-full py-3 rounded-xl font-semibold text-white text-sm transition-all hover:-translate-y-0.5"
                            style="background:linear-gradient(135deg,#71C9CE,#4db8be);box-shadow:0 6px 20px rgba(113,201,206,0.4)">
                        <i class="fas fa-paper-plane mr-2"></i>Send Message
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     CTA STRIP
════════════════════════════════════════════════════════════ -->
<section class="py-16 reveal" style="background:linear-gradient(135deg,#e3fdfd 0%,#cbf5f7 100%)">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="text-3xl font-extrabold text-[#0d1117] mb-3">Ready to get started?</h2>
        <p class="text-[#6b7a8d] mb-8">Join AquaQueue today and experience a smarter way to manage appointments.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="register.php"
               class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl font-semibold text-white text-sm transition-all hover:-translate-y-1"
               style="background:linear-gradient(135deg,#71C9CE,#4db8be);box-shadow:0 6px 20px rgba(113,201,206,0.4)">
                <i class="fas fa-user-plus"></i> Create Free Account
            </a>
            <a href="book.php"
               class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl font-semibold text-[#3aabb1] text-sm border-2 border-[#A6E3E9] bg-white/80 hover:bg-[#E3FDFD] hover:-translate-y-1 transition-all">
                <i class="fas fa-calendar-alt"></i> Book an Appointment
            </a>
        </div>
    </div>
</section>

<script>
// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
            observer.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right')
        .forEach(el => observer.observe(el));

// Contact form — opens Gmail compose
function submitContact() {
    const inputs  = document.querySelectorAll('.contact-input');
    const fname   = inputs[0].value.trim();
    const lname   = inputs[1].value.trim();
    const email   = inputs[2].value.trim();
    const subject = inputs[3].value.trim();
    const message = inputs[4].value.trim();

    if (!fname || !email || !subject || !message) {
        alert('Please fill in all required fields.');
        return;
    }

    const body = `From: ${fname} ${lname} <${email}>\n\n${message}`;
    const url  = `https://mail.google.com/mail/?view=cm&fs=1&to=t.gil0409@gmail.com&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    window.open(url, '_blank');
}
</script>

<?php include('../includes/footer.php'); ?>