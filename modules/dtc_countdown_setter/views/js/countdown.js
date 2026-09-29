document.addEventListener("DOMContentLoaded", function () {
    if (typeof countdownData === "undefined") {
        return;
    }

    countdownData.forEach((countdown, index) => {
        const selector = countdown.selector;
        const target = document.querySelector(selector);
        // Sélecteur absent sur certaines pages (ex. panier) : normal, on ignore.
        if (!target) {
            return;
        }

        const container = document.createElement("div");
        const id = `Countdown_${index}_${selector.replace(/[^a-zA-Z0-9]/g, "_")}`;
        container.id = id;
        container.style.display = "none";
        target.appendChild(container);

        createCountdownWidget({
            containerId: id,
            start_date: countdown.start_date,
            end_date: countdown.end_date,
            label: countdown.label,
            bg_color: countdown.bg_color,
            txt_color: countdown.txt_color,
            cta: countdown.cta === "1" ? {
                label: countdown.cta_label,
                link: countdown.cta_link,
                bg_color: countdown.cta_bg_color,
                txt_color: countdown.cta_txt_color
            } : null
        });
    });
});

function createCountdownWidget({
    containerId,
    start_date,
    end_date,
    label,
    bg_color,
    txt_color,
    cta
}) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const countdownElement = document.createElement("div");
    countdownElement.style.backgroundColor = bg_color;
    countdownElement.style.color = txt_color;
    countdownElement.style.padding = "0";
    countdownElement.style.marginTop = "0";
    countdownElement.style.borderRadius = "5px";
    countdownElement.style.textAlign = "center";
    countdownElement.style.display = "flex";
    countdownElement.style.alignItems = "center";
    countdownElement.style.justifyContent = "center";
    countdownElement.style.columnGap = "0";

    const labelElement = document.createElement("div");
    labelElement.textContent = label;
    labelElement.style.marginRight = "0.4em";
    countdownElement.appendChild(labelElement);

    const timerElement = document.createElement("div");
    countdownElement.appendChild(timerElement);

    if (cta) {
        const ctaElement = document.createElement("a");
        ctaElement.href = cta.link;
        ctaElement.textContent = cta.label;
        ctaElement.style.backgroundColor = cta.bg_color;
        ctaElement.style.color = cta.txt_color;
        ctaElement.style.display = "inline-block";
        ctaElement.style.padding = "8px 12px";
        ctaElement.style.marginTop = "10px";
        ctaElement.style.borderRadius = "3px";
        ctaElement.style.textDecoration = "none";
        countdownElement.appendChild(ctaElement);
    }

    container.appendChild(countdownElement);
    container.style.display = "block";

    function updateCountdown() {
        const now = new Date().getTime();
        const end = new Date(end_date).getTime();
        const start = new Date(start_date).getTime();

        const i18n = (typeof countdownI18n !== "undefined") ? countdownI18n : {
            upcoming: "À venir",
            ended: "Terminé",
            days: "j",
            hours: "h",
            minutes: "m",
            seconds: "s"
        };

        if (now < start) {
            timerElement.textContent = i18n.upcoming;
            return;
        }

        const distance = end - now;
        if (distance < 0) {
            timerElement.textContent = i18n.ended;
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        timerElement.textContent = days + i18n.days + " " + hours + i18n.hours + " " + minutes + i18n.minutes + " " + seconds + i18n.seconds;
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
}
