document.addEventListener('DOMContentLoaded', function() {
    // --- Roue principale ---
    const canvas = document.getElementById('wheel');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const spinBtn = document.getElementById('spin');

    // Définition des cases
    const segments = [
        'Perdu', 'Gagné', 'Perdu', 'Gagné', 'Perdu', 'Gagné'
    ];
    const colors = [
        '#f44336', '#ff9800', '#2196f3', '#4caf50', '#9c27b0', '#ffc107'
    ];
    const segmentCount = segments.length;
    const anglePerSegment = 2 * Math.PI / segmentCount;
    let currentAngle = 0;
    let spinning = false;

    function drawWheel(angle = 0) {
        if (!ctx) { return; }
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const radius = canvas.width / 2;
        const startAngle = angle - Math.PI / 2;
        for (let i = 0; i < segmentCount; i++) {
            ctx.beginPath();
            ctx.moveTo(radius, radius);
            ctx.arc(
                radius,
                radius,
                radius - 5,
                startAngle + i * anglePerSegment,
                startAngle + (i + 1) * anglePerSegment
            );
            ctx.closePath();
            ctx.fillStyle = colors[i % colors.length];
            ctx.fill();
            ctx.save();
            ctx.translate(radius, radius);
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#fff';
            ctx.font = 'bold 20px Arial';
            const textAngle = startAngle + (i + 0.5) * anglePerSegment;
            const textRadius = radius - 60;
            ctx.fillText(segments[i], Math.cos(textAngle) * textRadius, Math.sin(textAngle) * textRadius);
            ctx.restore();
        }
        ctx.save();
        ctx.translate(radius, radius);
        ctx.beginPath();
        ctx.moveTo(0, -radius + 40);
        ctx.lineTo(-15, -radius + 10);
        ctx.lineTo(15, -radius + 10);
        ctx.closePath();
        ctx.fillStyle = '#333';
        ctx.fill();
        ctx.restore();
    }
    if (canvas && ctx) {
        drawWheel();
    }

    function spinWheel() {
        if (spinning) return;
        spinning = true;
        const winningIndex = 3;
        const randomTurns = Math.floor(Math.random() * 3) + 5;
        const targetAngle = winningIndex * anglePerSegment;
        const finalAngle = 2 * Math.PI * randomTurns - targetAngle + Math.PI / 2;
        const duration = 4000;
        const start = performance.now();
        let lastAngle = currentAngle;

        function animate(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 3);
            currentAngle = lastAngle + (finalAngle - lastAngle) * ease;
            drawWheel(currentAngle);
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                spinning = false;
                currentAngle = finalAngle % (2 * Math.PI);
                drawWheel(currentAngle);
                if (segments[winningIndex] === 'Gagné') {
                    localStorage.setItem('dtc_popup_gift_wheel_played', '1');
                    localStorage.setItem('dtc_popup_gift_wheel_victory', '1');
                }
                setPopupImage();
            }
        }
        requestAnimationFrame(animate);
    }

    // Gestion de la popup
    const modal = document.getElementById('modal');
    const openModalBtn = document.getElementById('openModal') || document.getElementById('openModalMini');
    const closeModalBtn = document.getElementById('closeModal');

    // L'état "déjà tenté" est géré côté landing (on n'altère pas le bandeau vert).

    if (openModalBtn && modal) {
        openModalBtn.addEventListener('click', () => {
            modal.style.display = 'block';
        });
    }

    if (closeModalBtn && modal) {
        closeModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    if (spinBtn) {
        spinBtn.addEventListener('click', spinWheel);
    }

    // Mini roue d'appel
    const miniWheel = document.getElementById('mini-wheel');
    if (miniWheel) {
        const ctxMini = miniWheel.getContext('2d');
        const colors = ['#f44336', '#ff9800', '#2196f3', '#4caf50', '#9c27b0', '#ffc107'];
        const segmentCount = colors.length;
        const anglePerSegment = 2 * Math.PI / segmentCount;
        const radius = miniWheel.width / 2;
        for (let i = 0; i < segmentCount; i++) {
            ctxMini.beginPath();
            ctxMini.moveTo(radius, radius);
            ctxMini.arc(
                radius,
                radius,
                radius - 2,
                i * anglePerSegment,
                (i + 1) * anglePerSegment
            );
            ctxMini.closePath();
            ctxMini.fillStyle = colors[i];
            ctxMini.fill();
        }
        // Pas de flèche sur la mini roue
    }

    document.addEventListener('click', function(e) {
        const miniWheelContainer = document.getElementById('mini-wheel-container');
        if (miniWheelContainer && (e.target === miniWheelContainer || miniWheelContainer.contains(e.target))) {
            const openModalBtn = document.getElementById('openModal') || document.getElementById('openModalMini');
            if (openModalBtn) {
                openModalBtn.click();
            }
        }
    });

    function setPopupImage() {
        const popupBg = document.querySelector('.popup-bg');
        if (!popupBg) return;
        if (localStorage.getItem('dtc_popup_gift_wheel_victory') === '1') {
            if (window.innerWidth <= 700) {
                popupBg.src = '/modules/dtc_popup_gift/views/img/fond-victoire-mobil.jpg';
            } else {
                popupBg.src = '/modules/dtc_popup_gift/views/img/fond-victoire.jpg';
            }
        } else {
            if (window.innerWidth <= 700) {
                popupBg.src = '/modules/dtc_popup_gift/views/img/fond-mobil.jpg';
            } else {
                popupBg.src = '/modules/dtc_popup_gift/views/img/fond.jpg';
            }
        }
    }

    setPopupImage();
    window.addEventListener('resize', setPopupImage);
    if (openModalBtn) {
        openModalBtn.addEventListener('click', setPopupImage);
    }
});
