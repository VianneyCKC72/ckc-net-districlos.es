// Animations légères optionnelles (MVP)
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var current = document.querySelector('.dtc-timeline__item.is-current');
    if (!current) return;

    current.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
  });
})();

