/**
 * Compte à rebours des contenus promotionnels affichés au-dessus du prix.
 */
(function () {
    "use strict";

    const SECOND = 1000;
    const MINUTE = 60 * SECOND;
    const HOUR = 60 * MINUTE;
    const DAY = 24 * HOUR;

    function pad(value) {
        return String(value).padStart(2, "0");
    }

    function updateCountdown(element) {
        const endTimestamp = Number(element.dataset.ckcCountdownEnd);
        const remaining = endTimestamp - Date.now();

        if (!Number.isFinite(endTimestamp) || remaining <= 0) {
            element.hidden = true;
            if (element.ckcCountdownInterval) {
                window.clearInterval(element.ckcCountdownInterval);
                element.ckcCountdownInterval = null;
            }
            return;
        }

        const days = Math.floor(remaining / DAY);
        const hours = Math.floor((remaining % DAY) / HOUR);
        const minutes = Math.floor((remaining % HOUR) / MINUTE);
        const seconds = Math.floor((remaining % MINUTE) / SECOND);

        element.querySelector("[data-countdown-days]").textContent = pad(days);
        element.querySelector("[data-countdown-hours]").textContent = pad(hours);
        element.querySelector("[data-countdown-minutes]").textContent = pad(minutes);
        element.querySelector("[data-countdown-seconds]").textContent = pad(seconds);
    }

    function initCountdowns() {
        document.querySelectorAll("[data-ckc-countdown-end]").forEach(function (element) {
            if (element.ckcCountdownInterval) {
                return;
            }

            updateCountdown(element);
            if (!element.hidden) {
                element.ckcCountdownInterval = window.setInterval(function () {
                    if (!document.documentElement.contains(element)) {
                        window.clearInterval(element.ckcCountdownInterval);
                        element.ckcCountdownInterval = null;
                        return;
                    }
                    updateCountdown(element);
                }, SECOND);
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initCountdowns);
    } else {
        initCountdowns();
    }

    if (typeof prestashop !== "undefined" && prestashop.on) {
        prestashop.on("updatedProduct", initCountdowns);
    }
}());
