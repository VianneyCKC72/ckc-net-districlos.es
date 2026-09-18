(function() {
    'use strict';
    
    function initCadeau() {
        const cadeauContainer = document.getElementById('cadeauContainer');
        const cadeauCouvercle = document.getElementById('cadeauCouvercle');
        const cadeauBase = document.getElementById('cadeauBase');

        if (!cadeauContainer || !cadeauCouvercle || !cadeauBase) {
            setTimeout(initCadeau, 100);
            return;
        }

        function alignCouvercle() {
            if (cadeauBase.complete && cadeauCouvercle.complete) {
                const baseWidth = cadeauBase.offsetWidth;
                if (baseWidth > 0) {
                    cadeauCouvercle.style.width = baseWidth + 'px';
                }
                
                const isMobile = window.innerWidth <= 700;
                const lidOffset = isMobile ? '65px' : '90px';
                cadeauCouvercle.style.setProperty('--lid-offset', lidOffset);
            } else {
                cadeauBase.addEventListener('load', alignCouvercle);
                cadeauCouvercle.addEventListener('load', alignCouvercle);
            }
        }

        alignCouvercle();
        
        window.addEventListener('resize', function() {
            alignCouvercle();
        });

        let isOpened = false;

        cadeauContainer.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();

            if (isOpened) return;

            isOpened = true;
            cadeauContainer.classList.add('opened');

            const textImgPath = cadeauBase.dataset.textImg;
            if (textImgPath) {
                cadeauBase.src = textImgPath;
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCadeau);
    } else {
        initCadeau();
    }
})();

