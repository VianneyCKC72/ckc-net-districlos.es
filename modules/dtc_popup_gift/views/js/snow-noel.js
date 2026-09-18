(function() {
    'use strict';
    
    const canvas = document.getElementById('snow-canvas-noel');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const modalImage = document.querySelector('.popup-bg-noel');
    if (!modalImage) return;
    
    let width, height;
    let snowflakes = [];
    let animationId;
    let isRunning = false;

    function updateCanvasSize() {
        width = canvas.width = modalImage.offsetWidth || modalImage.naturalWidth;
        height = canvas.height = modalImage.offsetHeight || modalImage.naturalHeight;
        
        if (isRunning) {
            snowflakes = [];
            createSnowflakes();
        }
    }

    function resize() {
        if (modalImage.complete) {
            updateCanvasSize();
        } else {
            modalImage.addEventListener('load', updateCanvasSize);
        }
    }

    window.addEventListener('resize', function() {
        setTimeout(resize, 100);
    });

    class Snowflake {
        constructor(layer = 1) {
            this.reset(layer);
        }

        reset(layer = 1) {
            if (width && height) {
                this.x = Math.random() * width;
                this.y = Math.random() * -height;
            } else {
                this.x = 0;
                this.y = -100;
            }
            this.radius = Math.random() * (2 / layer) + 1;
            this.speedY = Math.random() * 1.5 * layer + 0.5;
            this.speedX = Math.random() * 0.6 - 0.3;
            this.opacity = Math.random() * 0.5 + 0.5;
            this.layer = layer;
        }

        update() {
            this.y += this.speedY;
            this.x += this.speedX + Math.sin(this.y * 0.01) * 0.5;

            if (this.y > height) {
                this.reset(this.layer);
                this.y = 0;
            }

            if (this.x > width) {
                this.x = 0;
            } else if (this.x < 0) {
                this.x = width;
            }
        }

        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
            ctx.fill();
        }
    }

    function createSnowflakes() {
        if (!width || !height) return;
        
        const flakeCount = Math.max(50, Math.floor((width * height) / 5000));
        createSnowflakesLayer(Math.floor(flakeCount * 0.6), 1);
        createSnowflakesLayer(Math.floor(flakeCount * 0.3), 2);
        createSnowflakesLayer(Math.floor(flakeCount * 0.1), 3);
    }

    function createSnowflakesLayer(count, layer) {
        for (let i = 0; i < count; i++) {
            snowflakes.push(new Snowflake(layer));
        }
    }

    function animate() {
        if (!canvas || !isRunning || canvas.width === 0 || canvas.height === 0) {
            if (isRunning) {
                animationId = requestAnimationFrame(animate);
            }
            return;
        }
        
        ctx.clearRect(0, 0, width, height);
        
        for (let flake of snowflakes) {
            flake.update();
            flake.draw();
        }
        
        animationId = requestAnimationFrame(animate);
    }

    function startSnow() {
        if (isRunning) return;
        
        if (!modalImage.complete) {
            setTimeout(startSnow, 100);
            return;
        }
        
        isRunning = true;
        resize();
        
        setTimeout(function() {
            if (width > 0 && height > 0) {
                if (!snowflakes.length) {
                    createSnowflakes();
                }
                animate();
            } else {
                startSnow();
            }
        }, 300);
    }

    function stopSnow() {
        isRunning = false;
        if (animationId) {
            cancelAnimationFrame(animationId);
            animationId = null;
        }
        ctx.clearRect(0, 0, width || canvas.width, height || canvas.height);
    }

    const modal = document.getElementById('modal-noel');
    if (modal) {
        const observer = new MutationObserver(function() {
            const displayValue = window.getComputedStyle(modal).display;
            if (displayValue === 'none') {
                stopSnow();
            } else {
                startSnow();
            }
        });
        
        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['style']
        });

        if (window.getComputedStyle(modal).display !== 'none') {
            startSnow();
        }
    }
})();

