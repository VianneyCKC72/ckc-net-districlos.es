$(document).ready(function () {
    let skeepers = $('.skeepers_product__stars');

    if (skeepers.length > 0) {
        let observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.addedNodes.length > 0) {
                    let rateElement = skeepers.find('.rate-total');
                    if (rateElement.length > 0) {
                        let note = rateElement.text().trim();

                        if (note.length > 0) {

                            let avisLi = $(`
                                <li class="nav-item">
                                    <a class="nav-link avisVerfies" data-toggle="tab" href="#netreviews_reviews_tab" role="tab" aria-controls="avisverifies">
                                        <img src="/img/avislogo.svg" alt="logo avis vérifiés">
                                        <span class="avis_verifies_inserted">
                                            <svg class="star-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 31.575 30.402">
                                                <defs>
                                                  <linearGradient id="skeepers-31-linearGradient-100">
                                                    <stop offset="0%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                    <stop offset="100%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                  </linearGradient>
                                                </defs>
                                            
                                                <path data-name="Tracé 1" d="m977.223 685.241-7.775 7.8 2.031 10.988-9.836-5.168-9.678 5.211 1.7-11-8.012-7.767 10.885-1.628 4.726-10.011 5.031 9.99z" transform="translate(-945.648 -673.671)" fill="url(#skeepers-31-linearGradient-100)"></path>
                                            </svg>
                                            <svg class="star-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 31.575 30.402">
                                                <defs>
                                                  <linearGradient id="skeepers-31-linearGradient-100">
                                                    <stop offset="0%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                    <stop offset="100%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                  </linearGradient>
                                                </defs>
                                            
                                                <path data-name="Tracé 1" d="m977.223 685.241-7.775 7.8 2.031 10.988-9.836-5.168-9.678 5.211 1.7-11-8.012-7.767 10.885-1.628 4.726-10.011 5.031 9.99z" transform="translate(-945.648 -673.671)" fill="url(#skeepers-31-linearGradient-100)"></path>
                                            </svg>
                                            <svg class="star-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 31.575 30.402">
                                                <defs>
                                                  <linearGradient id="skeepers-31-linearGradient-100">
                                                    <stop offset="0%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                    <stop offset="100%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                  </linearGradient>
                                                </defs>
                                            
                                                <path data-name="Tracé 1" d="m977.223 685.241-7.775 7.8 2.031 10.988-9.836-5.168-9.678 5.211 1.7-11-8.012-7.767 10.885-1.628 4.726-10.011 5.031 9.99z" transform="translate(-945.648 -673.671)" fill="url(#skeepers-31-linearGradient-100)"></path>
                                            </svg>
                                            <svg class="star-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 31.575 30.402">
                                                <defs>
                                                  <linearGradient id="skeepers-31-linearGradient-100">
                                                    <stop offset="0%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                    <stop offset="100%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                  </linearGradient>
                                                </defs>
                                            
                                                <path data-name="Tracé 1" d="m977.223 685.241-7.775 7.8 2.031 10.988-9.836-5.168-9.678 5.211 1.7-11-8.012-7.767 10.885-1.628 4.726-10.011 5.031 9.99z" transform="translate(-945.648 -673.671)" fill="url(#skeepers-31-linearGradient-100)"></path>
                                            </svg>
                                            <svg class="star-svg" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 31.575 30.402">
                                                <defs>
                                                  <linearGradient id="skeepers-31-linearGradient-100">
                                                    <stop offset="0%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                    <stop offset="100%" stop-color="var(--skeepers-widget-star-color)"></stop>
                                                  </linearGradient>
                                                </defs>
                                            
                                                <path data-name="Tracé 1" d="m977.223 685.241-7.775 7.8 2.031 10.988-9.836-5.168-9.678 5.211 1.7-11-8.012-7.767 10.885-1.628 4.726-10.011 5.031 9.99z" transform="translate(-945.648 -673.671)" fill="url(#skeepers-31-linearGradient-100)"></path>
                                            </svg>
                                            ${note} avis
                                        </span>
                                    </a>
                                </li>
                            `);

                            let navTabs = $('.nav.nav-tabs[role="tablist"]');
                            if (navTabs.length > 0) {
                                navTabs.append(avisLi);
                            }

                            observer.disconnect();
                        }
                    }
                }
            });
        });

        observer.observe(skeepers[0], { childList: true, subtree: true });
    }

});
