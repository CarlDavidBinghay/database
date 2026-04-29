<?php
session_start();
$pageTitle = "Book Appointment";
include('../includes/header.php');
?>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: white;
    border-radius: 1.5rem;
    max-width: 640px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    animation: slideUp 0.3s ease;
}
.email-modal-box {
    background: white;
    border-radius: 1.5rem;
    max-width: 500px;
    width: 100%;
    padding: 2rem;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
    animation: slideUp 0.3s ease;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.modal-image {
    width: 100%;
    height: 210px;
    object-fit: cover;
    border-radius: 1.5rem 1.5rem 0 0;
}
.tag {
    display: inline-block;
    padding: 3px 11px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    background: #E3FDFD;
    color: #3aabb1;
    margin: 2px;
}
.card-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}
.btn-info {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 10px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    outline: none;
}
.btn-info-outline {
    background: transparent;
    border: 2px solid #71C9CE;
    color: #71C9CE;
}
.btn-info-outline:hover { background: #E3FDFD; }
.btn-email { background: #71C9CE; color: white; }
.btn-email:hover { background: #5ab4b9; }
.btn-book { background: #f0fffe; color: #3aabb1; border: 2px solid #A6E3E9; }
.btn-book:hover { background: #E3FDFD; }
.info-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
    font-size: 0.875rem;
    color: #4b5563;
}
.info-row:last-child { border-bottom: none; }
.info-icon { color: #71C9CE; width: 18px; flex-shrink: 0; margin-top: 2px; }
.email-input {
    width: 100%;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.9rem;
    transition: border 0.2s;
    outline: none;
    margin-bottom: 12px;
    box-sizing: border-box;
    font-family: inherit;
}
.email-input:focus { border-color: #71C9CE; box-shadow: 0 0 0 3px rgba(113,201,206,0.15); }
.email-textarea {
    width: 100%;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.9rem;
    transition: border 0.2s;
    outline: none;
    resize: vertical;
    box-sizing: border-box;
    font-family: inherit;
}
.email-textarea:focus { border-color: #71C9CE; box-shadow: 0 0 0 3px rgba(113,201,206,0.15); }

/* ── Form field shared style ── */
.field-input {
    width: 100%;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 11px 14px;
    font-size: 0.9rem;
    transition: border 0.2s, box-shadow 0.2s;
    outline: none;
    box-sizing: border-box;
    font-family: inherit;
    color: #0d1117;
    background: #fff;
}
.field-input:focus { border-color: #71C9CE; box-shadow: 0 0 0 3px rgba(113,201,206,0.15); }
.field-input[readonly] { background: #f9fafb; color: #6b7280; cursor: default; }
.field-label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 6px;
}

/* ── Step number badge ── */
.step-badge {
    display: inline-flex;
    width: 28px; height: 28px;
    align-items: center; justify-content: center;
    background: linear-gradient(135deg, #71C9CE, #4db8be);
    color: white;
    border-radius: 50%;
    font-size: 0.8rem;
    font-weight: 700;
    margin-right: 8px;
    flex-shrink: 0;
}
</style>

<?php
// ── All location data ─────────────────────────────────────────
$locationData = [
    'medical' => [
        [
            'name' => 'City Medical Center',
            'address' => '123 Main St, Quezon City',
            'price' => 1500, 'duration' => 30, 'rating' => 4.8,
            'sub' => 'General Practitioner',
            'phone' => '+63 2 8123 4567',
            'hours' => 'Mon–Sat: 8:00 AM – 6:00 PM',
            'website' => 'www.citymedical.ph',
            'description' => 'City Medical Center is a trusted general practice clinic serving Quezon City for over 15 years. Our board-certified GPs handle consultations, check-ups, and referrals with a patient-first approach.',
            'services' => ['General Check-up', 'Lab Referrals', 'Prescription', 'Vaccination'],
            'image' => 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?w=700&q=80',
        ],
        [
            'name' => 'HealthPlus Clinic',
            'address' => '456 Quezon Ave, Quezon City',
            'price' => 2000, 'duration' => 45, 'rating' => 4.9,
            'sub' => 'Specialist Available',
            'phone' => '+63 2 8456 7890',
            'hours' => 'Mon–Sun: 7:00 AM – 9:00 PM',
            'website' => 'www.healthplusclinic.ph',
            'description' => 'HealthPlus Clinic offers comprehensive outpatient services with access to various medical specialists. State-of-the-art diagnostic equipment and a warm professional staff make every visit comfortable.',
            'services' => ['Specialist Consult', 'ECG', 'Ultrasound', 'Blood Work'],
            'image' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=700&q=80',
        ],
        [
            'name' => 'Prime Medical Hub',
            'address' => '789 Commonwealth Ave, Quezon City',
            'price' => 1200, 'duration' => 30, 'rating' => 4.7,
            'sub' => 'Budget-Friendly',
            'phone' => '+63 2 8789 0123',
            'hours' => 'Mon–Fri: 8:00 AM – 5:00 PM',
            'website' => 'www.primemedical.ph',
            'description' => 'Prime Medical Hub makes quality healthcare accessible to everyone. We offer affordable consultations without compromising on quality, making us the go-to clinic for budget-conscious families.',
            'services' => ['Consultations', 'Minor Procedures', 'Health Certificates', 'Teleconsult'],
            'image' => 'https://images.unsplash.com/photo-1631217868264-e5b90bb7e133?w=700&q=80',
        ],
        [
            'name' => 'Elite Health Center',
            'address' => '321 EDSA, Quezon City',
            'price' => 2500, 'duration' => 45, 'rating' => 5.0,
            'sub' => 'Premium Service',
            'phone' => '+63 2 8321 6540',
            'hours' => 'Mon–Sun: 6:00 AM – 10:00 PM',
            'website' => 'www.elitehealth.ph',
            'description' => 'Elite Health Center redefines the healthcare experience with concierge-level medical service. Enjoy private suites, minimal wait times, and access to the top specialists in Metro Manila.',
            'services' => ['Executive Check-up', 'Private Suites', 'Specialist Panel', 'Home Service'],
            'image' => 'https://images.unsplash.com/photo-1538108149393-fbbd81895907?w=700&q=80',
        ],
    ],
    'salon' => [
        [
            'name' => 'StyleCut Salon',
            'address' => 'SM North EDSA, Quezon City',
            'price' => 500, 'duration' => 60, 'rating' => 4.6,
            'sub' => 'Basic Cut & Style',
            'phone' => '+63 2 8111 2222',
            'hours' => 'Mon–Sun: 10:00 AM – 8:00 PM',
            'website' => 'www.stylecutsalon.ph',
            'description' => 'StyleCut Salon is your go-to spot for a fresh, stylish cut at an everyday price. Located inside SM North EDSA, we offer fast, reliable haircuts and styling by trained professional stylists.',
            'services' => ['Haircut', 'Blowout', 'Rebonding', 'Hair Color'],
            'image' => 'https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?w=700&q=80',
        ],
        [
            'name' => 'Glamour Studio',
            'address' => 'Trinoma Mall, Quezon City',
            'price' => 800, 'duration' => 90, 'rating' => 4.8,
            'sub' => 'Premium Styling',
            'phone' => '+63 2 8333 4444',
            'hours' => 'Mon–Sun: 10:00 AM – 9:00 PM',
            'website' => 'www.glamourstudio.ph',
            'description' => 'Glamour Studio is a premium hair salon experience in Trinoma Mall. Our award-winning stylists specialize in advanced coloring techniques, keratin treatments, and editorial looks for every occasion.',
            'services' => ['Balayage', 'Keratin', 'Perm', 'Bridal Styling'],
            'image' => 'https://images.unsplash.com/photo-1560066984-138daaa5de6b?w=700&q=80',
        ],
        [
            'name' => 'Trendy Cuts',
            'address' => 'Cubao, Quezon City',
            'price' => 400, 'duration' => 45, 'rating' => 4.5,
            'sub' => 'Walk-ins Welcome',
            'phone' => '+63 2 8555 6666',
            'hours' => 'Mon–Sat: 9:00 AM – 7:00 PM',
            'website' => 'www.trendycuts.ph',
            'description' => 'Trendy Cuts is a neighborhood favorite in Cubao, known for quick turnarounds, friendly service, and always keeping up with the latest styles. Walk-ins are always welcome — no appointment needed!',
            'services' => ['Haircut', 'Trim', 'Shampoo & Blowdry', 'Bang Trim'],
            'image' => 'https://images.unsplash.com/photo-1562322140-8baeececf3df?w=700&q=80',
        ],
    ],
    'dental' => [
        [
            'name' => 'Smile Dental Clinic',
            'address' => 'Katipunan Ave, Quezon City',
            'price' => 1800, 'duration' => 45, 'rating' => 4.9,
            'sub' => 'General Dentistry',
            'phone' => '+63 2 8700 1234',
            'hours' => 'Mon–Sat: 9:00 AM – 6:00 PM',
            'website' => 'www.smiledentalqc.ph',
            'description' => 'Smile Dental Clinic provides comprehensive general dentistry in a warm, anxiety-free environment. Our experienced dentists use modern techniques to ensure comfortable and efficient treatment every time.',
            'services' => ['Cleaning', 'Tooth Extraction', 'Fillings', 'X-Ray'],
            'image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=700&q=80',
        ],
        [
            'name' => 'Perfect Teeth Center',
            'address' => 'Timog Ave, Quezon City',
            'price' => 2200, 'duration' => 60, 'rating' => 5.0,
            'sub' => 'Cosmetic Dentistry',
            'phone' => '+63 2 8800 5678',
            'hours' => 'Mon–Sat: 8:00 AM – 7:00 PM',
            'website' => 'www.perfectteeth.ph',
            'description' => 'Perfect Teeth Center specializes in cosmetic and restorative dentistry. From porcelain veneers to dental implants, our cosmetic specialists craft beautiful smiles with precision and artistry.',
            'services' => ['Veneers', 'Whitening', 'Implants', 'Invisalign'],
            'image' => 'https://images.unsplash.com/photo-1588776814546-1ffedbe47100?w=700&q=80',
        ],
        [
            'name' => 'Bright Smile Dental',
            'address' => 'España Blvd, Quezon City',
            'price' => 1500, 'duration' => 45, 'rating' => 4.7,
            'sub' => 'Family Dentistry',
            'phone' => '+63 2 8900 9012',
            'hours' => 'Mon–Fri: 8:00 AM – 5:00 PM, Sat: 9:00 AM – 3:00 PM',
            'website' => 'www.brightsmile.ph',
            'description' => 'Bright Smile Dental has been a trusted family dentist along España Blvd for over a decade. We cater to patients of all ages from toddlers to seniors in a friendly and welcoming clinic setting.',
            'services' => ['Pediatric Dentistry', 'Orthodontics', 'Dentures', 'Cleaning'],
            'image' => 'https://images.unsplash.com/photo-1606811971618-4486d14f3f99?w=700&q=80',
        ],
    ],
    'legal' => [
        [
            'name' => 'Santos Law Office',
            'address' => 'Ortigas Center, Quezon City',
            'price' => 3000, 'duration' => 60, 'rating' => 4.8,
            'sub' => 'Corporate Law',
            'phone' => '+63 2 8200 3456',
            'hours' => 'Mon–Fri: 8:00 AM – 6:00 PM',
            'website' => 'www.santoslaw.ph',
            'description' => 'Santos Law Office is a reputable corporate law firm in Ortigas Center. With over 20 years of experience, Atty. Santos and his team handle business registrations, contracts, and corporate disputes with precision.',
            'services' => ['Contract Review', 'Business Registration', 'Corporate Litigation', 'Legal Opinions'],
            'image' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=700&q=80',
        ],
        [
            'name' => 'Reyes & Associates',
            'address' => 'Makati Ave, Makati City',
            'price' => 4500, 'duration' => 60, 'rating' => 5.0,
            'sub' => 'Full Legal Services',
            'phone' => '+63 2 8400 7890',
            'hours' => 'Mon–Fri: 9:00 AM – 7:00 PM',
            'website' => 'www.reyesassociates.ph',
            'description' => 'Reyes & Associates is a full-service law firm with expertise spanning corporate, family, criminal, and real estate law. Their Makati team delivers top-tier legal counsel for both individuals and corporations.',
            'services' => ['Family Law', 'Real Estate', 'Criminal Defense', 'Immigration'],
            'image' => 'https://images.unsplash.com/photo-1575505586569-646b2ca898fc?w=700&q=80',
        ],
        [
            'name' => 'Cruz Legal Services',
            'address' => 'West Ave, Quezon City',
            'price' => 2500, 'duration' => 60, 'rating' => 4.7,
            'sub' => 'General Practice',
            'phone' => '+63 2 8600 1122',
            'hours' => 'Mon–Sat: 8:00 AM – 5:00 PM',
            'website' => 'www.cruzlegal.ph',
            'description' => 'Cruz Legal Services provides practical and affordable legal advice for everyday Filipinos. Specializing in general practice, Atty. Cruz is known for clear communication and straightforward legal guidance.',
            'services' => ['Notarization', 'Legal Advice', 'Court Representation', 'Affidavits'],
            'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=700&q=80',
        ],
    ],
    'vehicle' => [
        [
            'name' => 'AutoCare Service Center',
            'address' => 'Commonwealth Ave, Quezon City',
            'price' => 2500, 'duration' => 90, 'rating' => 4.6,
            'sub' => 'General Service',
            'phone' => '+63 2 8700 3344',
            'hours' => 'Mon–Sat: 7:00 AM – 6:00 PM',
            'website' => 'www.autocare.ph',
            'description' => 'AutoCare Service Center is a trusted general auto shop along Commonwealth Ave. Our experienced mechanics provide honest, reliable service for all vehicle makes and models at competitive prices.',
            'services' => ['Oil Change', 'Tire Rotation', 'Brake Check', 'A/C Service'],
            'image' => 'https://images.unsplash.com/photo-1625047509248-ec889cbff17f?w=700&q=80',
        ],
        [
            'name' => 'Premium Auto Shop',
            'address' => 'EDSA, Quezon City',
            'price' => 3500, 'duration' => 120, 'rating' => 4.9,
            'sub' => 'Full Diagnostics',
            'phone' => '+63 2 8800 5566',
            'hours' => 'Mon–Sun: 7:00 AM – 8:00 PM',
            'website' => 'www.premiumautoph.com',
            'description' => 'Premium Auto Shop offers full vehicle diagnostics with cutting-edge OBD scanning technology. We specialize in European and Japanese performance vehicles and provide detailed inspection reports after every service.',
            'services' => ['Full Diagnostics', 'Engine Tune-up', 'Suspension', 'Detailing'],
            'image' => 'https://images.unsplash.com/photo-1487754180451-c456f719a1fc?w=700&q=80',
        ],
        [
            'name' => 'QuickFix Garage',
            'address' => 'Fairview, Quezon City',
            'price' => 1800, 'duration' => 60, 'rating' => 4.5,
            'sub' => 'Basic Maintenance',
            'phone' => '+63 2 8900 7788',
            'hours' => 'Mon–Sat: 8:00 AM – 5:00 PM',
            'website' => 'www.quickfixgarage.ph',
            'description' => 'QuickFix Garage lives up to its name — fast, affordable, and dependable. Located in Fairview, we focus on routine vehicle maintenance to keep your car running smoothly without breaking the bank.',
            'services' => ['Lube & Oil', 'Battery Check', 'Wiper Replacement', 'Tire Pressure'],
            'image' => 'https://images.unsplash.com/photo-1504222490345-c075b6008014?w=700&q=80',
        ],
    ],
    'business' => [
        [
            'name' => 'CoWork Space QC',
            'address' => 'Eastwood City, Quezon City',
            'price' => 1000, 'duration' => 45, 'rating' => 4.7,
            'sub' => 'Meeting Room',
            'phone' => '+63 2 8100 1234',
            'hours' => 'Mon–Fri: 7:00 AM – 10:00 PM, Sat: 8:00 AM – 6:00 PM',
            'website' => 'www.coworkqc.ph',
            'description' => 'CoWork Space QC is a modern coworking facility in the heart of Eastwood City. Fully equipped meeting rooms, fast WiFi, and a professional atmosphere make it ideal for client presentations and team huddles.',
            'services' => ['Meeting Rooms', 'Hot Desks', 'Video Conferencing', 'Printing'],
            'image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=700&q=80',
        ],
        [
            'name' => 'Executive Hub',
            'address' => 'BGC, Taguig City',
            'price' => 1500, 'duration' => 60, 'rating' => 4.9,
            'sub' => 'Premium Facilities',
            'phone' => '+63 2 8200 5678',
            'hours' => 'Mon–Sun: 6:00 AM – 11:00 PM',
            'website' => 'www.executivehub.ph',
            'description' => 'Executive Hub in BGC is the premium choice for high-stakes business meetings. Elegant boardrooms, concierge support, and world-class amenities ensure every meeting is held at the standard your brand deserves.',
            'services' => ['Boardrooms', 'Event Spaces', 'Catering', 'Business Address'],
            'image' => 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=700&q=80',
        ],
        [
            'name' => 'The Business Center',
            'address' => 'Cubao, Quezon City',
            'price' => 800, 'duration' => 45, 'rating' => 4.5,
            'sub' => 'Basic Setup',
            'phone' => '+63 2 8300 9012',
            'hours' => 'Mon–Sat: 8:00 AM – 7:00 PM',
            'website' => 'www.thebusinesscenterqc.ph',
            'description' => 'The Business Center in Cubao is a no-frills, budget-friendly venue for your business meetings. With clean, functional rooms and reliable internet, it gets the job done at the right price point.',
            'services' => ['Meeting Rooms', 'WiFi', 'Whiteboard', 'Parking'],
            'image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=700&q=80',
        ],
    ],
];

$serviceConfig = [
    'medical'  => ['label' => 'Medical Consultation', 'icon' => 'fa-stethoscope', 'dur' => '30-45 min'],
    'salon'    => ['label' => 'Hair Salon',            'icon' => 'fa-cut',         'dur' => '60-90 min'],
    'dental'   => ['label' => 'Dental Checkup',        'icon' => 'fa-tooth',       'dur' => '45-60 min'],
    'legal'    => ['label' => 'Legal Consultation',    'icon' => 'fa-gavel',       'dur' => '60 min'],
    'vehicle'  => ['label' => 'Vehicle Service',       'icon' => 'fa-car',         'dur' => '90-120 min'],
    'business' => ['label' => 'Business Meeting',      'icon' => 'fa-briefcase',   'dur' => '45-60 min'],
];

// Pre-fill values from session
$sessionFirstName = htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? '')[0] ?? '');
$sessionLastName  = htmlspecialchars(implode(' ', array_slice(explode(' ', $_SESSION['user_name'] ?? ''), 1)) ?? '');
$sessionEmail     = htmlspecialchars($_SESSION['user_email'] ?? '');

function renderStars(float $r): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++)
        $html .= $i <= floor($r)
            ? '<i class="fas fa-star text-yellow-400 text-xs"></i>'
            : '<i class="far fa-star text-yellow-300 text-xs"></i>';
    return $html;
}
?>

<div class="max-w-6xl mx-auto">

    <!-- Page header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Book New Appointment</h1>
        <p class="text-gray-600 mt-2">Select your service, location, date, time, and your details</p>
    </div>

    <!-- ── Guest warning banner (from simple book.php) ── -->
    <?php if (!isset($_SESSION['user_id'])): ?>
    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
        <i class="fas fa-info-circle text-amber-500 mt-0.5 flex-shrink-0"></i>
        <div>
            <div class="font-semibold text-amber-800 text-sm">You're booking as a guest</div>
            <div class="text-amber-600 text-xs mt-0.5">
                <a href="login.php" class="underline font-medium">Login</a> or
                <a href="register.php" class="underline font-medium">register</a>
                to track your bookings and get notifications.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8">

        <!-- ══════════════════════════════════════════
             STEP 1 — SELECT SERVICE
        ═══════════════════════════════════════════ -->
        <div id="step1">
            <h2 class="text-2xl font-semibold mb-6 flex items-center">
                <span class="step-badge">1</span> Select Service
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($serviceConfig as $key => $cfg): ?>
                <div onclick="showLocations('<?= $key ?>')"
                     class="service-card border-2 border-gray-200 rounded-xl p-6 hover:border-[#71C9CE] hover:bg-[#E3FDFD] cursor-pointer transition-all">
                    <div class="text-[#71C9CE] mb-3"><i class="fas <?= $cfg['icon'] ?> text-3xl"></i></div>
                    <h3 class="font-semibold text-lg"><?= $cfg['label'] ?></h3>
                    <p class="text-sm text-gray-500 mt-1"><?= $cfg['dur'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             STEP 2 — SELECT LOCATION
        ═══════════════════════════════════════════ -->
        <div id="step2" class="hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold flex items-center">
                    <span class="step-badge">2</span> Select Location
                </h2>
                <button onclick="backToServices()" class="text-[#71C9CE] hover:text-[#5ab4b9] font-medium text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Services
                </button>
            </div>

            <?php foreach ($locationData as $svcKey => $places): ?>
            <div id="<?= $svcKey ?>-locations" class="locations-container hidden">
                <div class="grid md:grid-cols-2 gap-5">
                    <?php foreach ($places as $idx => $p): ?>
                    <?php $uid = $svcKey . '_' . $idx; ?>

                    <!-- Location Card -->
                    <div class="location-card border-2 border-gray-200 rounded-xl p-5 hover:border-[#71C9CE] hover:bg-[#E3FDFD] transition-all">
                        <img src="<?= $p['image'] ?>"
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             class="w-full h-36 object-cover rounded-lg mb-4"
                             onerror="this.src='https://images.unsplash.com/photo-1497366216548-37526070297c?w=700&q=80'">

                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-semibold text-lg leading-tight"><?= htmlspecialchars($p['name']) ?></h3>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($p['address']) ?></p>
                            </div>
                            <div class="text-right ml-3 flex-shrink-0">
                                <div class="text-xl font-bold text-[#71C9CE]">₱<?= number_format($p['price']) ?></div>
                                <div class="text-xs text-gray-500"><?= $p['duration'] ?> min</div>
                            </div>
                        </div>

                        <div class="flex items-center text-sm text-gray-600 mb-1">
                            <?= renderStars($p['rating']) ?>
                            <span class="ml-1 font-medium"><?= $p['rating'] ?></span>
                            <span class="mx-2">•</span>
                            <span><?= htmlspecialchars($p['sub']) ?></span>
                        </div>

                        <div class="card-actions">
                            <button class="btn-info btn-info-outline" onclick="openInfo('<?= $uid ?>')">
                                <i class="fas fa-info-circle"></i> Info
                            </button>
                            <button class="btn-info btn-email"
                                    onclick="openEmail('<?= htmlspecialchars(addslashes($p['name'])) ?>', '<?= $svcKey ?>')">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                            <button class="btn-info btn-book"
                                    onclick="selectLocation('<?= $svcKey ?>', '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['price'] ?>, <?= $p['duration'] ?>)">
                                <i class="fas fa-calendar-check"></i> Book
                            </button>
                        </div>
                    </div>

                    <!-- Info Modal -->
                    <div id="modal-<?= $uid ?>" class="modal-overlay" onclick="closeIfOverlay(event,'modal-<?= $uid ?>')">
                        <div class="modal-box">
                            <img src="<?= $p['image'] ?>"
                                 alt="<?= htmlspecialchars($p['name']) ?>"
                                 class="modal-image"
                                 onerror="this.src='https://images.unsplash.com/photo-1497366216548-37526070297c?w=700&q=80'">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-1">
                                    <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($p['name']) ?></h2>
                                    <button onclick="closeModal('modal-<?= $uid ?>')" class="text-gray-400 hover:text-gray-600 ml-3">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="flex items-center gap-1 mb-4 flex-wrap">
                                    <?= renderStars($p['rating']) ?>
                                    <span class="ml-1 text-sm font-semibold text-gray-700"><?= $p['rating'] ?>/5.0</span>
                                    <span class="mx-1 text-gray-300">|</span>
                                    <span class="text-sm text-[#71C9CE] font-semibold">₱<?= number_format($p['price']) ?></span>
                                    <span class="text-xs text-gray-400">/ <?= $p['duration'] ?> min</span>
                                </div>

                                <p class="text-gray-600 text-sm leading-relaxed mb-5"><?= htmlspecialchars($p['description']) ?></p>

                                <div class="mb-5">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Services Offered</p>
                                    <?php foreach ($p['services'] as $svc): ?>
                                    <span class="tag"><?= htmlspecialchars($svc) ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="bg-gray-50 rounded-xl p-4 mb-5">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Contact & Hours</p>
                                    <div class="info-row"><i class="fas fa-map-marker-alt info-icon"></i><span><?= htmlspecialchars($p['address']) ?></span></div>
                                    <div class="info-row"><i class="fas fa-phone info-icon"></i><span><?= htmlspecialchars($p['phone']) ?></span></div>
                                    <div class="info-row"><i class="fas fa-clock info-icon"></i><span><?= htmlspecialchars($p['hours']) ?></span></div>
                                    <div class="info-row"><i class="fas fa-globe info-icon"></i><span><?= htmlspecialchars($p['website']) ?></span></div>
                                </div>

                                <div class="flex gap-3">
                                    <button onclick="closeModal('modal-<?= $uid ?>'); openEmail('<?= htmlspecialchars(addslashes($p['name'])) ?>','<?= $svcKey ?>')"
                                            class="flex-1 py-2.5 px-4 bg-[#71C9CE] text-white font-semibold rounded-xl hover:bg-[#5ab4b9] transition-all text-sm">
                                        <i class="fas fa-envelope mr-2"></i>Send Email
                                    </button>
                                    <button onclick="closeModal('modal-<?= $uid ?>'); selectLocation('<?= $svcKey ?>','<?= htmlspecialchars(addslashes($p['name'])) ?>',<?= $p['price'] ?>,<?= $p['duration'] ?>)"
                                            class="flex-1 py-2.5 px-4 bg-[#E3FDFD] text-[#3aabb1] font-semibold border-2 border-[#A6E3E9] rounded-xl hover:bg-[#cbf5f7] transition-all text-sm">
                                        <i class="fas fa-calendar-check mr-2"></i>Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ══════════════════════════════════════════
             STEP 3 — DATE, TIME & OPTIONS
        ═══════════════════════════════════════════ -->
        <div id="step3" class="hidden mt-8">
            <hr class="my-8 border-gray-200">
            <h2 class="text-2xl font-semibold mb-6 flex items-center">
                <span class="step-badge">3</span> Select Date & Time
            </h2>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Calendar -->
                <div class="md:col-span-1">
                    <h3 class="text-base font-semibold text-gray-700 mb-3">Pick a Date</h3>
                    <div class="border border-gray-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-4">
                            <button id="cal-prev" onclick="changeMonth(-1)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-[#E3FDFD] text-[#71C9CE] transition-all">
                                <i class="fas fa-chevron-left text-sm"></i>
                            </button>
                            <div id="cal-month-label" class="text-base font-bold text-gray-800"></div>
                            <button id="cal-next" onclick="changeMonth(1)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-[#E3FDFD] text-[#71C9CE] transition-all">
                                <i class="fas fa-chevron-right text-sm"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-xs mb-1">
                            <?php foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $d): ?>
                            <div class="text-gray-500 py-1 font-semibold"><?= $d ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div id="cal-grid" class="grid grid-cols-7 gap-1 text-center text-sm"></div>
                    </div>
                </div>

                <!-- Time Slots -->
                <div class="md:col-span-2">
                    <h3 class="text-base font-semibold text-gray-700 mb-1">Pick a Time Slot</h3>
                    <p id="time-slot-hint" class="text-sm text-gray-400 mb-4">Pick a date first to see available slots</p>
                    <div id="time-slot-grid" class="grid grid-cols-3 md:grid-cols-4 gap-3"></div>
                </div>
            </div>

            <!-- Priority -->
            <div class="mt-8">
                <h3 class="text-base font-semibold text-gray-700 mb-3">Priority Level</h3>
                <div class="flex flex-wrap gap-3">
                    <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-[#71C9CE] transition-all has-[:checked]:border-[#71C9CE] has-[:checked]:bg-[#E3FDFD]">
                        <input type="radio" name="priority" value="0" class="accent-[#71C9CE]" checked onchange="onPriorityChange(0)">
                        <span class="font-medium text-sm">Standard</span>
                        <span class="text-xs text-gray-400">No extra charge</span>
                    </label>
                    <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-[#71C9CE] transition-all has-[:checked]:border-[#71C9CE] has-[:checked]:bg-[#E3FDFD]">
                        <input type="radio" name="priority" value="200" class="accent-[#71C9CE]" onchange="onPriorityChange(200)">
                        <span class="font-medium text-sm">Express</span>
                        <span class="text-xs text-[#71C9CE] font-semibold">+₱200</span>
                    </label>
                    <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-[#71C9CE] transition-all has-[:checked]:border-[#71C9CE] has-[:checked]:bg-[#E3FDFD]">
                        <input type="radio" name="priority" value="500" class="accent-[#71C9CE]" onchange="onPriorityChange(500)">
                        <span class="font-medium text-sm">VIP</span>
                        <span class="text-xs text-[#71C9CE] font-semibold">+₱500</span>
                    </label>
                </div>
            </div>

            <!-- Notes -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                <textarea id="booking-notes" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-[#71C9CE] focus:border-transparent" rows="3" placeholder="Any special requests or notes..."></textarea>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             STEP 4 — YOUR DETAILS (from simple book.php)
        ═══════════════════════════════════════════ -->
        <div id="step4" class="hidden mt-8">
            <hr class="my-8 border-gray-200">
            <h2 class="text-2xl font-semibold mb-6 flex items-center">
                <span class="step-badge">4</span> Your Details
            </h2>

            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="field-label">First Name</label>
                    <input id="field-first" type="text" class="field-input" placeholder="First Name"
                           value="<?= $sessionFirstName ?>" <?= $sessionFirstName ? 'readonly' : '' ?>>
                </div>
                <div>
                    <label class="field-label">Last Name</label>
                    <input id="field-last" type="text" class="field-input" placeholder="Last Name"
                           value="<?= $sessionLastName ?>" <?= $sessionLastName ? 'readonly' : '' ?>>
                </div>
            </div>
            <div class="mb-4">
                <label class="field-label">Email Address</label>
                <input id="field-email" type="email" class="field-input" placeholder="your@email.com"
                       value="<?= $sessionEmail ?>" <?= $sessionEmail ? 'readonly' : '' ?>>
                <?php if ($sessionEmail): ?>
                <p class="text-xs text-gray-400 mt-1"><i class="fas fa-lock text-[#71C9CE] mr-1"></i>Pre-filled from your account</p>
                <?php endif; ?>
            </div>
            <div class="mb-4">
                <label class="field-label">Phone Number</label>
                <input id="field-phone" type="tel" class="field-input" placeholder="+63 (917) 000-0000">
            </div>

            <!-- Summary + Confirm -->
            <div class="mt-8 p-6 bg-[#E3FDFD] border border-[#A6E3E9] rounded-xl">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="text-lg font-semibold text-gray-900 mb-1">Appointment Summary</div>
                        <div id="summary-text" class="text-gray-600 text-sm">Select a service and location to continue</div>
                        <div id="summary-price-breakdown" class="text-xs text-gray-400 mt-1 hidden">
                            Base: <span id="summary-base-price"></span>
                            + Priority: <span id="summary-priority-add"></span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div id="summary-price" class="text-3xl font-bold text-[#71C9CE] mb-3">₱0</div>
                        <button onclick="confirmBooking()"
                                class="px-8 py-3 bg-[#71C9CE] text-white font-semibold rounded-xl hover:bg-[#5ab4b9] transition-all shadow-md hover:shadow-lg">
                            <i class="fas fa-calendar-check mr-2"></i>Confirm Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /card -->

    <!-- ══════════════════════════════════════════
         CURRENT QUEUE STATUS
    ═══════════════════════════════════════════ -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        <h2 class="text-2xl font-semibold mb-6">Current Queue Status</h2>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="w-16 h-16 rounded-full bg-[#E3FDFD] flex items-center justify-center mb-4">
                <i class="fas fa-lock text-2xl text-[#71C9CE]"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-1">Login to View Queue</h3>
            <p class="text-sm text-gray-400 max-w-xs">Queue status is only visible to logged-in users. Sign in to see real-time wait times and your position.</p>
            <a href="login.php"
               class="mt-5 px-6 py-2.5 bg-[#71C9CE] text-white font-semibold rounded-xl hover:bg-[#5ab4b9] transition-all text-sm shadow-sm">
                <i class="fas fa-sign-in-alt mr-2"></i>Login to View
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Current No.</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Wait Time</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">Medical Consultation</td>
                        <td class="px-6 py-4 text-sm font-bold text-[#71C9CE]">A-042</td>
                        <td class="px-6 py-4 text-sm text-gray-600">~15 minutes</td>
                        <td class="px-6 py-4"><span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">Hair Salon</td>
                        <td class="px-6 py-4 text-sm font-bold text-[#71C9CE]">B-018</td>
                        <td class="px-6 py-4 text-sm text-gray-600">~25 minutes</td>
                        <td class="px-6 py-4"><span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Medium</span></td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">Dental Checkup</td>
                        <td class="px-6 py-4 text-sm font-bold text-[#71C9CE]">C-009</td>
                        <td class="px-6 py-4 text-sm text-gray-600">~5 minutes</td>
                        <td class="px-6 py-4"><span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /max-w -->

<!-- ══════════════════════════════════════════
     LOGIN REQUIRED MODAL
═══════════════════════════════════════════ -->
<div id="login-modal" class="modal-overlay" onclick="closeIfOverlay(event,'login-modal')">
    <div class="email-modal-box text-center">
        <div class="w-16 h-16 rounded-full bg-[#E3FDFD] flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-lock text-2xl text-[#71C9CE]"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-1">Login Required</h2>
        <p class="text-sm text-gray-500 mb-6">You need to be logged in to confirm a booking. Please sign in or create an account to continue.</p>
        <div class="bg-[#E3FDFD] border border-[#A6E3E9] rounded-xl p-4 mb-6 text-left text-sm text-gray-600">
            <div class="font-semibold text-gray-800 mb-2">Your selection will be saved:</div>
            <div id="login-modal-summary" class="text-[#3aabb1] font-medium"></div>
        </div>
        <div class="flex gap-3">
            <button onclick="closeModal('login-modal')"
                    class="flex-1 py-2.5 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl hover:bg-gray-50 transition-all text-sm">
                Cancel
            </button>
            <a href="login.php"
               class="flex-1 py-2.5 bg-[#71C9CE] text-white font-semibold rounded-xl hover:bg-[#5ab4b9] transition-all text-sm flex items-center justify-center">
                <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
            </a>
        </div>
        <p class="text-xs text-gray-400 mt-4">Don't have an account?
            <a href="register.php" class="text-[#71C9CE] font-semibold hover:underline">Sign up here</a>
        </p>
    </div>
</div>

<!-- ══════════════════════════════════════════
     EMAIL MODAL
═══════════════════════════════════════════ -->
<div id="email-modal" class="modal-overlay" onclick="closeIfOverlay(event,'email-modal')">
    <div class="email-modal-box">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Send an Email</h2>
                <p id="email-subtitle" class="text-sm text-gray-500 mt-0.5">Inquiry about your appointment</p>
            </div>
            <button onclick="closeModal('email-modal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">From</label>
        <input class="email-input" value="<?= $sessionEmail ?: 'your@email.com' ?>" readonly style="background:#f9fafb;color:#6b7280;">

        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">To</label>
        <input id="email-to" class="email-input" type="email" placeholder="recipient@example.com">

        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Subject</label>
        <input id="email-subject" class="email-input" type="text" placeholder="Subject">

        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Message</label>
        <textarea id="email-body" class="email-textarea" rows="5" placeholder="Write your message here..."></textarea>

        <div class="flex gap-3 mt-5">
            <button onclick="closeModal('email-modal')"
                    class="flex-1 py-2.5 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl hover:bg-gray-50 transition-all text-sm">
                Cancel
            </button>
            <button onclick="sendEmail()"
                    class="flex-1 py-2.5 bg-[#71C9CE] text-white font-semibold rounded-xl hover:bg-[#5ab4b9] transition-all text-sm">
                <i class="fas fa-paper-plane mr-2"></i>Send via Gmail
            </button>
        </div>
    </div>
</div>

<script>
/* ── State ─────────────────────────────────────────────────── */
let selectedService   = '';
let selectedLocation  = '';
let selectedPrice     = 0;
let selectedDuration  = 0;
let selectedDate      = null;
let selectedTime      = null;
let prioritySurcharge = 0;

const IS_LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

let calYear  = new Date().getFullYear();
let calMonth = new Date().getMonth();

const TODAY = new Date();
TODAY.setHours(0,0,0,0);

const serviceLabels = {
    medical: 'Medical Consultation', salon: 'Hair Salon', dental: 'Dental Checkup',
    legal: 'Legal Consultation', vehicle: 'Vehicle Service', business: 'Business Meeting'
};

const ALL_SLOTS = ['09:00 AM','10:00 AM','11:00 AM','12:00 PM',
                   '01:00 PM','02:00 PM','03:00 PM','04:00 PM',
                   '05:00 PM','06:00 PM','07:00 PM','08:00 PM'];

function bookedSlotsForDate(dateStr) {
    let hash = 0;
    for (let i = 0; i < dateStr.length; i++)
        hash = (hash * 31 + dateStr.charCodeAt(i)) & 0xffffffff;
    const booked = new Set();
    const count  = 4 + (Math.abs(hash) % 3);
    for (let i = 0; i < count; i++) {
        hash = (hash * 1664525 + 1013904223) & 0xffffffff;
        booked.add(Math.abs(hash) % ALL_SLOTS.length);
    }
    return booked;
}

/* ── Calendar ───────────────────────────────────────────────── */
const MONTH_NAMES = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];

function renderCalendar() {
    const label   = document.getElementById('cal-month-label');
    const grid    = document.getElementById('cal-grid');
    const prevBtn = document.getElementById('cal-prev');
    label.textContent = `${MONTH_NAMES[calMonth]} ${calYear}`;

    const nowY = TODAY.getFullYear(), nowM = TODAY.getMonth();
    prevBtn.disabled = (calYear === nowY && calMonth === nowM);
    prevBtn.classList.toggle('opacity-30', prevBtn.disabled);
    prevBtn.classList.toggle('cursor-not-allowed', prevBtn.disabled);

    const daysInMonth  = new Date(calYear, calMonth + 1, 0).getDate();
    const firstWeekday = new Date(calYear, calMonth, 1).getDay();
    grid.innerHTML = '';

    for (let i = 0; i < firstWeekday; i++)
        grid.insertAdjacentHTML('beforeend', '<div></div>');

    for (let d = 1; d <= daysInMonth; d++) {
        const dateObj = new Date(calYear, calMonth, d);
        const dateStr = `${calYear}-${String(calMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const isPast  = dateObj < TODAY;
        const isSel   = dateStr === selectedDate;

        let cls = 'py-1.5 rounded-lg text-sm transition-all ';
        if (isPast)      cls += 'text-gray-300 cursor-not-allowed';
        else if (isSel)  cls += 'bg-[#71C9CE] text-white font-bold cursor-pointer shadow-sm';
        else             cls += 'text-gray-700 cursor-pointer hover:bg-[#E3FDFD] hover:text-[#3aabb1] font-medium';

        const cell = document.createElement('div');
        cell.className   = cls;
        cell.textContent = d;
        if (!isPast) cell.addEventListener('click', () => pickDate(dateStr));
        grid.appendChild(cell);
    }
}

function changeMonth(dir) {
    calMonth += dir;
    if (calMonth > 11) { calMonth = 0;  calYear++; }
    if (calMonth < 0)  { calMonth = 11; calYear--; }
    renderCalendar();
}

function pickDate(dateStr) {
    selectedDate = dateStr;
    selectedTime = null;
    renderCalendar();
    renderTimeSlots();
    updateSummary();
}

/* ── Time Slots ─────────────────────────────────────────────── */
function renderTimeSlots() {
    const grid = document.getElementById('time-slot-grid');
    const hint = document.getElementById('time-slot-hint');

    if (!selectedDate) {
        grid.innerHTML = '';
        hint.textContent = 'Pick a date first to see available slots';
        return;
    }

    const [y, m, d] = selectedDate.split('-');
    const dateObj   = new Date(Number(y), Number(m)-1, Number(d));
    const dayName   = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
    const monthName = dateObj.toLocaleDateString('en-US', { month: 'long' });
    hint.textContent = `Available slots for ${dayName}, ${monthName} ${Number(d)}, ${y}`;

    const booked = bookedSlotsForDate(selectedDate);
    grid.innerHTML = '';

    ALL_SLOTS.forEach((t, i) => {
        const isBooked = booked.has(i);
        const isSel    = t === selectedTime;
        const cell     = document.createElement('div');
        cell.className = 'text-center py-3 rounded-xl text-sm transition-all font-medium ';

        if (isBooked) {
            cell.className += 'border border-gray-200 text-gray-400 cursor-not-allowed bg-gray-50';
            cell.innerHTML  = `<div>${t}</div><div class="text-xs mt-0.5">Booked</div>`;
        } else if (isSel) {
            cell.className += 'border-2 border-[#71C9CE] bg-[#71C9CE] text-white cursor-pointer shadow-sm';
            cell.innerHTML  = `<div>${t}</div><div class="text-xs mt-0.5">✓ Selected</div>`;
            cell.addEventListener('click', () => pickTime(t));
        } else {
            cell.className += 'border-2 border-[#71C9CE] text-[#71C9CE] hover:bg-[#71C9CE] hover:text-white cursor-pointer';
            cell.textContent = t;
            cell.addEventListener('click', () => pickTime(t));
        }
        grid.appendChild(cell);
    });
}

function pickTime(time) {
    selectedTime = time;
    renderTimeSlots();
    updateSummary();
}

/* ── Priority ───────────────────────────────────────────────── */
function onPriorityChange(surcharge) {
    prioritySurcharge = surcharge;
    updateSummary();
}

/* ── Summary ────────────────────────────────────────────────── */
function updateSummary() {
    if (!selectedLocation) return;
    const label    = serviceLabels[selectedService] || selectedService;
    const datePart = selectedDate ? formatDate(selectedDate) : '— pick a date —';
    const timePart = selectedTime ? selectedTime              : '— pick a time —';
    const total    = selectedPrice + prioritySurcharge;

    document.getElementById('summary-text').textContent =
        `${label} • ${selectedLocation} • ${datePart} • ${timePart}`;
    document.getElementById('summary-price').textContent = `₱${total.toLocaleString()}`;

    const breakdownEl = document.getElementById('summary-price-breakdown');
    if (prioritySurcharge > 0) {
        breakdownEl.classList.remove('hidden');
        document.getElementById('summary-base-price').textContent     = `₱${selectedPrice.toLocaleString()}`;
        document.getElementById('summary-priority-add').textContent   = `₱${prioritySurcharge.toLocaleString()}`;
    } else {
        breakdownEl.classList.add('hidden');
    }
}

function formatDate(dateStr) {
    const [y, m, d] = dateStr.split('-');
    return new Date(Number(y), Number(m)-1, Number(d))
        .toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

/* ── Confirm Booking ────────────────────────────────────────── */
function confirmBooking() {
    if (!selectedLocation) { alert('Please select a service and location first.'); return; }
    if (!selectedDate)      { alert('Please select a date.'); return; }
    if (!selectedTime)      { alert('Please select a time slot.'); return; }

    const firstName = document.getElementById('field-first').value.trim();
    const lastName  = document.getElementById('field-last').value.trim();
    const email     = document.getElementById('field-email').value.trim();
    const phone     = document.getElementById('field-phone').value.trim();

    if (!firstName) { alert('Please enter your first name.'); return; }
    if (!lastName)  { alert('Please enter your last name.');  return; }
    if (!email)     { alert('Please enter your email address.'); return; }

    if (!IS_LOGGED_IN) {
        const label    = serviceLabels[selectedService] || selectedService;
        const total    = selectedPrice + prioritySurcharge;
        const priority = prioritySurcharge === 0 ? 'Standard' : prioritySurcharge === 200 ? 'Express' : 'VIP';
        document.getElementById('login-modal-summary').textContent =
            `${label} • ${selectedLocation} • ${formatDate(selectedDate)} • ${selectedTime} • ₱${total.toLocaleString()} (${priority})`;
        document.getElementById('login-modal').classList.add('active');
        document.body.style.overflow = 'hidden';
    } else {
        alert(`Booking confirmed for ${firstName} ${lastName}! Redirecting to your appointments…`);
        // window.location.href = '../appointments.php';
    }
}

/* ── Step navigation ────────────────────────────────────────── */
function showLocations(type) {
    document.getElementById('step1').classList.add('hidden');
    document.getElementById('step2').classList.remove('hidden');
    document.querySelectorAll('.locations-container').forEach(el => el.classList.add('hidden'));
    document.getElementById(type + '-locations').classList.remove('hidden');
    selectedService = type;
}

function selectLocation(service, name, price, duration) {
    selectedLocation  = name;
    selectedPrice     = price;
    selectedDuration  = duration;
    selectedDate      = null;
    selectedTime      = null;
    prioritySurcharge = 0;

    document.querySelectorAll('input[name="priority"]').forEach(r => { r.checked = (r.value === '0'); });

    document.getElementById('step3').classList.remove('hidden');
    document.getElementById('step4').classList.remove('hidden');
    updateSummary();
    renderCalendar();
    renderTimeSlots();
    document.getElementById('step3').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function backToServices() {
    document.getElementById('step1').classList.remove('hidden');
    document.getElementById('step2').classList.add('hidden');
    document.getElementById('step3').classList.add('hidden');
    document.getElementById('step4').classList.add('hidden');
    selectedService = ''; selectedLocation = ''; selectedPrice = 0;
    selectedDuration = 0; selectedDate = null; selectedTime = null;
    prioritySurcharge = 0;
}

/* ── Modals ─────────────────────────────────────────────────── */
function openInfo(uid) {
    document.getElementById('modal-' + uid).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function openEmail(locationName, serviceType) {
    document.getElementById('email-subtitle').textContent = `Inquiry for: ${locationName}`;
    document.getElementById('email-to').value      = '';
    document.getElementById('email-subject').value = `Appointment Inquiry – ${locationName}`;
    document.getElementById('email-body').value    =
`Hello,

I am interested in booking a ${serviceLabels[serviceType] || serviceType} appointment at ${locationName}.

Could you please confirm your available slots and any requirements?

Thank you,
[Your Name]`;
    document.getElementById('email-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function sendEmail() {
    const to      = document.getElementById('email-to').value.trim();
    const subject = document.getElementById('email-subject').value.trim();
    const body    = document.getElementById('email-body').value.trim();
    if (!to)      { alert('Please enter a recipient email address.'); return; }
    if (!subject) { alert('Please enter a subject.'); return; }
    const url = `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(to)}&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    window.open(url, '_blank');
    closeModal('email-modal');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

function closeIfOverlay(e, id) {
    if (e.target === document.getElementById(id)) closeModal(id);
}

// Init calendar
renderCalendar();
</script>

<?php include('../includes/footer.php'); ?>