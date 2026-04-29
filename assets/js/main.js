// ── AquaQueue — Main JavaScript ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    console.log('AquaQueue System Loaded!');

    // ── Demo login quick-fill ─────────────────────────────────────────────
    const demoLoginBtn = document.getElementById('demo-login');
    if (demoLoginBtn) {
        demoLoginBtn.addEventListener('click', function () {
            const emailEl = document.getElementById('email');
            const passEl  = document.getElementById('password');
            if (emailEl) emailEl.value = 'user@test.com';
            if (passEl)  passEl.value  = 'password123';
            document.querySelector('form')?.submit();
        });
    }

    // ── Service card selection ────────────────────────────────────────────
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('click', function () {
            serviceCards.forEach(c => {
                c.classList.remove('selected', 'border-[#71C9CE]', 'bg-[#CBF1F5]');
            });
            this.classList.add('selected', 'border-[#71C9CE]', 'bg-[#CBF1F5]');
        });
    });

    // ── Time slot selection ───────────────────────────────────────────────
    const timeSlots = document.querySelectorAll('.time-slot:not(.booked)');
    timeSlots.forEach(slot => {
        slot.addEventListener('click', function () {
            timeSlots.forEach(s => s.classList.remove('selected', 'bg-[#71C9CE]', 'text-white'));
            this.classList.add('selected', 'bg-[#71C9CE]', 'text-white');
        });
    });

    // ── Form validation (generic) ─────────────────────────────────────────
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            let valid = true;
            const inputs = this.querySelectorAll('input[required], select[required], textarea[required]');

            inputs.forEach(input => {
                const errId = input.id + '-error';
                let errEl = document.getElementById(errId);

                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('border-red-500');
                    input.classList.remove('border-gray-300');

                    if (!errEl) {
                        errEl = document.createElement('p');
                        errEl.id        = errId;
                        errEl.className = 'text-red-500 text-xs mt-1';
                        errEl.textContent = 'This field is required.';
                        input.parentNode.appendChild(errEl);
                    }
                } else {
                    input.classList.remove('border-red-500');
                    input.classList.add('border-gray-300');
                    if (errEl) errEl.remove();
                }
            });

            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                const firstErr = form.querySelector('.border-red-500');
                if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });

    // ── Flash message auto-dismiss ────────────────────────────────────────
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = 'opacity 0.5s ease';
            msg.style.opacity    = '0';
            setTimeout(() => msg.remove(), 500);
        }, 4000);
    });

    // ── Queue live update simulation ──────────────────────────────────────
    const queueDisplays = document.querySelectorAll('.queue-number');
    if (queueDisplays.length > 0) {
        setInterval(() => {
            console.log('[AquaQueue] Queue polling tick');
            // Replace with fetch('/api/queue-status.php') in production
        }, 30000);
    }

    // ── Admin: Next Queue button ──────────────────────────────────────────
    document.querySelectorAll('[data-action="next-queue"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const serviceName = this.dataset.service || 'this service';
            if (confirm(`Advance queue for ${serviceName}?`)) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-forward mr-1"></i>Next';
                    showToast('Queue advanced successfully!', 'success');
                }, 800);
            }
        });
    });

    // ── QR Code modal (queue ticket) ──────────────────────────────────────
    const qrBtn = document.querySelector('[data-action="show-qr"]');
    if (qrBtn) {
        qrBtn.addEventListener('click', showQRModal);
    }

    // ── Print queue ticket ────────────────────────────────────────────────
    document.querySelectorAll('[data-action="print-ticket"]').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

    // ── Cancel appointment confirmation ───────────────────────────────────
    document.querySelectorAll('[data-action="cancel-apt"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const aptId = this.dataset.id || '';
            if (confirm('Are you sure you want to cancel this appointment?')) {
                showToast('Appointment cancelled.', 'info');
                this.closest('[data-apt-row]')?.remove();
            }
        });
    });

    // ── Smooth scroll for anchor links ────────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ── Tooltip init for icon buttons ────────────────────────────────────
    document.querySelectorAll('[title]').forEach(el => {
        el.setAttribute('aria-label', el.getAttribute('title'));
    });
});

// ── Toast notification ────────────────────────────────────────────────────
function showToast(message, type = 'success') {
    const colors = {
        success: 'bg-green-500',
        error:   'bg-red-500',
        info:    'bg-[#71C9CE]',
        warning: 'bg-yellow-500',
    };

    const toast = document.createElement('div');
    toast.className = `fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl text-white text-sm font-semibold shadow-lg flex items-center gap-2 ${colors[type] || colors.info}`;
    toast.innerHTML = `<i class="fas fa-check-circle"></i>${message}`;
    document.body.appendChild(toast);

    // Animate in
    toast.style.transform = 'translateY(20px)';
    toast.style.opacity   = '0';
    toast.style.transition = 'all 0.3s ease';
    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity   = '1';
    });

    // Auto-remove after 3 s
    setTimeout(() => {
        toast.style.transform = 'translateY(20px)';
        toast.style.opacity   = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── QR Code modal ─────────────────────────────────────────────────────────
function showQRModal(queueNumber = 'A-048') {
    const existing = document.getElementById('qr-modal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'qr-modal';
    modal.className = 'fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-8 max-w-xs w-full text-center shadow-2xl">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Your Queue Ticket</h3>
            <p class="text-sm text-gray-500 mb-5">Show this at the counter</p>
            <div class="w-36 h-36 bg-gray-100 rounded-xl mx-auto mb-4 flex items-center justify-center">
                <i class="fas fa-qrcode text-gray-400 text-6xl"></i>
            </div>
            <div class="text-4xl font-bold text-[#3aabb1] mb-1">${queueNumber}</div>
            <div class="text-xs text-gray-400 mb-6">Medical Consultation • Today</div>
            <button onclick="document.getElementById('qr-modal').remove()"
                    class="w-full py-2.5 bg-[#71C9CE] text-white rounded-xl font-semibold text-sm hover:bg-[#5ab4b9] transition-all">
                Close
            </button>
        </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
}

// ── Confirm dangerous action ──────────────────────────────────────────────
function confirmAction(message, callback) {
    if (confirm(message)) callback();
}

// ── Format countdown timer ────────────────────────────────────────────────
function formatTime(seconds) {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}
