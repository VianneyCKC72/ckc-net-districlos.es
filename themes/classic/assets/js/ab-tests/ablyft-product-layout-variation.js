(function () {
  'use strict';

  var STYLE_ID = 'ab-product-layout-b-styles';
  var MARKER = 'data-ab-product-layout';
  var SELECTOR = 'body#product .product-container.js-product-container';

  function getAblyftTools() {
    if (typeof ablyftTools !== 'undefined' && ablyftTools) return ablyftTools;
    try {
      if (window.ablyft && typeof window.ablyft.getTools === 'function') {
        return window.ablyft.getTools();
      }
    } catch (e) {}
    return null;
  }

  function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;

    var css = [
      'html[data-ab-product-layout="b"] #product .ab-psl__info{',
      '  position:relative!important;top:auto!important;height:auto!important;',
      '}',
      '@media (min-width:992px){',
      '  html[data-ab-product-layout="b"] #product .ab-psl__gallery{',
      '    position:sticky!important;top:150px!important;align-self:start;height:fit-content!important;',
      '  }',
      '}',

      'html[data-ab-product-layout="b"] #product .ab-psl__info > .product-information > h1.ab-psl__h1{',
      '  display:block!important;',
      '  font-size:32px!important;',
      '  line-height:1.25!important;',
      '  font-weight:800!important;',
      '  margin:0 0 1rem!important;',
      '}',
      '@media (max-width:460px){',
      '  html[data-ab-product-layout="b"] #product .ab-psl__info > .product-information > h1.ab-psl__h1{font-size:28px!important;}',
      '}',
      '@media (min-width:768px){',
      '  html[data-ab-product-layout="b"] #product .ab-psl__info > .product-information > h1.ab-psl__h1{',
      '    font-size:36px!important;line-height:1.3!important;margin-bottom:1.5rem!important;',
      '  }',
      '}',

      'html[data-ab-product-layout="b"] #product .ab-pig{',
      '  position:relative;overflow:hidden;margin-bottom:1.5rem;height:auto!important;',
      '}',
      'html[data-ab-product-layout="b"] #product .ab-pig__track{',
      '  display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;',
      '  -webkit-overflow-scrolling:touch;scrollbar-width:none;',
      '}',
      'html[data-ab-product-layout="b"] #product .ab-pig__track::-webkit-scrollbar{display:none;}',
      'html[data-ab-product-layout="b"] #product .ab-pig__item{',
      '  position:relative;flex:0 0 86%;scroll-snap-align:start;border-radius:4px;overflow:hidden;',
      '}',
      'html[data-ab-product-layout="b"] #product .ab-pig__item > a{display:block;outline:none;cursor:zoom-in;}',
      'html[data-ab-product-layout="b"] #product .ab-pig__item > a img{',
      '  width:100%!important;height:auto!important;aspect-ratio:1/1;object-fit:cover;',
      '  border-radius:4px;display:block;',
      '}',
      'html[data-ab-product-layout="b"] #product .ab-pig__badge{',
      '  position:absolute!important;z-index:5;pointer-events:none;',
      '  width:auto!important;height:auto!important;aspect-ratio:auto!important;',
      '  max-width:none!important;object-fit:contain!important;',
      '}',
      'html[data-ab-product-layout="b"] #product .ab-pig__badge--fr{top:10px;left:10px;}',
      'html[data-ab-product-layout="b"] #product .ab-pig__badge--fr img{width:70px!important;height:auto!important;aspect-ratio:auto!important;}',
      'html[data-ab-product-layout="b"] #product .ab-pig__badge--tag{',
      '  top:10px!important;right:10px!important;left:auto!important;',
      '  width:65px!important;height:auto!important;',
      '}',
      '@media (max-width:768px){',
      '  html[data-ab-product-layout="b"] #product .ab-pig__badge--tag{width:30px!important;}',
      '  html[data-ab-product-layout="b"] #product .ab-pig__badge--fr img{width:50px!important;}',
      '}',
      '@media (min-width:768px){',
      '  html[data-ab-product-layout="b"] #product .ab-pig{overflow:visible;margin-bottom:0;}',
      '  html[data-ab-product-layout="b"] #product .ab-pig__track{',
      '    display:grid;grid-template-columns:1fr 1fr;gap:16px;overflow:visible;scroll-snap-type:none;',
      '  }',
      '  html[data-ab-product-layout="b"] #product .ab-pig__item{flex:none;width:auto;}',
      '}',

      'html[data-ab-product-layout="b"] #product .ab-pig-hidden{',
      '  display:none!important;visibility:hidden!important;pointer-events:none!important;',
      '  position:absolute!important;left:-9999px!important;width:0!important;height:0!important;overflow:hidden!important;',
      '}',

      'html[data-ab-product-layout="b"] #product .banner-quick-view{',
      '  position:fixed!important;',
      '  bottom:0!important;',
      '  left:0!important;',
      '  right:0!important;',
      '  width:100%!important;',
      '  z-index:10050!important;',
      '  background-color:#F6F2EB!important;',
      '}',
      'html[data-ab-product-layout="b"] #product .banner-quick-view.visible{',
      '  opacity:1!important;',
      '  visibility:visible!important;',
      '  pointer-events:auto!important;',
      '}',
      'html[data-ab-product-layout="b"] body > .banner-quick-view{',
      '  display:none!important;',
      '}',

      '#ab-psl-lightbox{',
      '  position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.85);',
      '  display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;',
      '}',
      '#ab-psl-lightbox img{',
      '  max-width:min(92vw,1100px);max-height:90vh;width:auto;height:auto;object-fit:contain;',
      '  border-radius:4px;background:#fff;',
      '}',
      '#ab-psl-lightbox button{',
      '  position:absolute;top:16px;right:20px;border:0;background:transparent;color:#fff;',
      '  font-size:36px;line-height:1;cursor:pointer;padding:8px;',
      '}'
    ].join('');

    var style = document.createElement('style');
    style.id = STYLE_ID;
    style.appendChild(document.createTextNode(css));
    (document.head || document.documentElement).appendChild(style);
  }

  function toLargeUrl(url) {
    if (!url) return url;
    return url
      .replace(/-small_default/g, '-large_default')
      .replace(/-medium_default/g, '-large_default')
      .replace(/-home_default/g, '-large_default')
      .replace(/-cart_default/g, '-large_default');
  }

  function isJunkImage(url) {
    return /tag-price|pastille|sprite|placeholder|\.svg(\?|$)/i.test(url || '');
  }

  function collectCoverImages(galleryRoot) {
    var images = [];
    var seen = {};

    function push(url, alt) {
      url = toLargeUrl(url);
      if (!url || isJunkImage(url) || seen[url]) return;
      seen[url] = true;
      images.push({ url: url, alt: alt || '' });
    }

    galleryRoot.querySelectorAll('.swiper__product-img__cover .swiper-slide').forEach(function (slide) {
      var a = slide.querySelector('a[href]');
      var img = slide.querySelector('img.img-cover, picture img, img');
      push((a && a.getAttribute('href')) || (img && img.getAttribute('src')), img && img.getAttribute('alt'));
    });

    if (!images.length) {
      galleryRoot.querySelectorAll('.swiper__product-img__thumbnails img').forEach(function (img) {
        push(img.getAttribute('src'), img.getAttribute('alt'));
      });
    }

    return images;
  }

  function destroyOldSwipers(gallery) {
    try {
      gallery.querySelectorAll('.swiper').forEach(function (el) {
        if (el.swiper && typeof el.swiper.destroy === 'function') {
          el.swiper.destroy(true, true);
        }
      });
    } catch (e) {}
  }

  function openProductModal(imageUrl, alt) {
    var existing = document.getElementById('ab-psl-lightbox');
    if (existing) existing.parentNode.removeChild(existing);

    var overlay = document.createElement('div');
    overlay.id = 'ab-psl-lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Fermer');
    closeBtn.innerHTML = '&times;';

    var img = document.createElement('img');
    img.src = imageUrl;
    img.alt = alt || '';

    function close() {
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
      document.removeEventListener('keydown', onKey);
    }
    function onKey(e) {
      if (e.key === 'Escape') close();
    }

    closeBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      close();
    });
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });
    document.addEventListener('keydown', onKey);

    overlay.appendChild(closeBtn);
    overlay.appendChild(img);
    document.body.appendChild(overlay);
  }

  function buildGallery(images, oldGallery) {
    var pastille = oldGallery.querySelector('.pastille_fr');
    var tag = oldGallery.querySelector('img.tag-web-price');

    var root = document.createElement('div');
    root.className = 'images-container ab-pig mb-5 mb-lg-0';

    var track = document.createElement('div');
    track.className = 'ab-pig__track';

    images.forEach(function (item, index) {
      var itemEl = document.createElement('div');
      itemEl.className = 'ab-pig__item';

      if (index === 0) {
        if (pastille) {
          var bFr = document.createElement('div');
          bFr.className = 'ab-pig__badge ab-pig__badge--fr';
          bFr.innerHTML = pastille.innerHTML;
          itemEl.appendChild(bFr);
        }
        if (tag) {
          var bTag = document.createElement('img');
          bTag.className = 'ab-pig__badge ab-pig__badge--tag';
          bTag.src = tag.getAttribute('src') || '/themes/classic/assets/img/tag-price.svg';
          bTag.alt = '';
          bTag.setAttribute('loading', 'lazy');
          itemEl.appendChild(bTag);
        }
      }

      var a = document.createElement('a');
      a.href = item.url;
      a.setAttribute('rel', 'nofollow');
      a.addEventListener('click', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        openProductModal(item.url, item.alt);
      });

      var img = document.createElement('img');
      img.className = 'img-cover rounded-4';
      img.src = item.url;
      img.alt = item.alt || '';
      if (index !== 0) img.setAttribute('loading', 'lazy');

      a.appendChild(img);
      itemEl.appendChild(a);
      track.appendChild(itemEl);
    });

    root.appendChild(track);
    return root;
  }

  function prepareTitle(h1) {
    h1.classList.remove('h2');
    h1.classList.add('ab-psl__h1');
    h1.style.cssText =
      'font-size:32px!important;line-height:1.25!important;font-weight:800!important;margin:0 0 1rem!important;display:block!important;';
    return h1;
  }

  function getFormBanner() {
    return (
      document.querySelector('#add-to-cart-or-refresh .banner-quick-view') ||
      document.querySelector('form.ap5-buy-block .banner-quick-view') ||
      document.querySelector('#product .product-actions .banner-quick-view') ||
      document.querySelector('#product .banner-quick-view')
    );
  }

  function dedupeBanners() {
    var banners = Array.prototype.slice.call(document.querySelectorAll('.banner-quick-view'));
    if (!banners.length) return null;

    var keep = getFormBanner() || banners[banners.length - 1];
    var i;
    for (i = 0; i < banners.length; i++) {
      if (banners[i] !== keep && banners[i].parentNode) {
        banners[i].parentNode.removeChild(banners[i]);
      }
    }

    if (keep && keep.parentNode === document.body) {
      var form =
        document.querySelector('#add-to-cart-or-refresh') ||
        document.querySelector('form.ap5-buy-block') ||
        document.querySelector('#product .product-actions form');
      var prices = document.querySelector('#product .product-prices.js-product-prices') ||
        document.querySelector('#product .js-product-prices') ||
        document.querySelector('#product .product-prices');
      if (prices) {
        prices.appendChild(keep);
      } else if (form) {
        form.appendChild(keep);
      }
    }

    return keep;
  }

  function bindBannerCartProxy(banner) {
    if (!banner || banner.getAttribute('data-ab-cart-proxy') === '1') return;
    banner.setAttribute('data-ab-cart-proxy', '1');

    banner.addEventListener(
      'click',
      function (ev) {
        var btn = ev.target.closest('button[data-button-action="add-to-cart"], button.add-to-cart');
        if (!btn || !banner.contains(btn)) return;

        var form =
          document.querySelector('#add-to-cart-or-refresh') ||
          document.querySelector('form.ap5-buy-block');
        if (!form) return;

        if (form.contains(btn)) return;

        ev.preventDefault();
        ev.stopPropagation();

        var bannerQty = banner.querySelector('input[name="qty"], #quantity_wanted');
        var formQty = form.querySelector(
          '.product-prices > .js-product-add-to-cart input[name="qty"], .product-prices > .product-add-to-cart input[name="qty"], input#quantity_wanted'
        );
        if (bannerQty && formQty) {
          formQty.value = bannerQty.value;
          try {
            formQty.dispatchEvent(new Event('change', { bubbles: true }));
          } catch (e) {}
        }

        var mainBtn =
          form.querySelector('.product-prices > .js-product-add-to-cart button[data-button-action="add-to-cart"]') ||
          form.querySelector('.product-prices > .product-add-to-cart button.add-to-cart') ||
          form.querySelector('button[data-button-action="add-to-cart"]');

        if (mainBtn && !mainBtn.disabled) {
          mainBtn.click();
        } else if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }
      },
      true
    );
  }

  function fixStickyBanner() {
    var orphanBodyBanners = Array.prototype.slice.call(document.querySelectorAll('body > .banner-quick-view'));
    orphanBodyBanners.forEach(function (b) {
      if (b.parentNode) b.parentNode.removeChild(b);
    });

    var banner = dedupeBanners();
    if (!banner) return;
    bindBannerCartProxy(banner);
  }

  function watchBannerDuplicates(container) {
    if (window.__abPslBannerMo || typeof MutationObserver === 'undefined') return;

    var timer = null;
    window.__abPslBannerMo = new MutationObserver(function () {
      if (timer) clearTimeout(timer);
      timer = setTimeout(function () {
        var bodyBanners = document.querySelectorAll('body > .banner-quick-view').length;
        var all = document.querySelectorAll('.banner-quick-view').length;
        if (bodyBanners > 0 || all > 1) {
          fixStickyBanner(container);
        }
      }, 80);
    });

    window.__abPslBannerMo.observe(document.body, { childList: true, subtree: true });
  }

  function transformClassicProduct(container) {
    var row = container.querySelector(':scope > .row') || container.querySelector('.row');
    if (!row || row.classList.contains('ab-psl')) return false;

    var colLeft = null;
    var colRight = null;
    row.querySelectorAll(':scope > [class*="col-"]').forEach(function (col) {
      if (col.className.indexOf('col-lg-7') !== -1) colLeft = col;
      if (col.className.indexOf('col-lg-5') !== -1) colRight = col;
    });
    if (!colLeft || !colRight) return false;

    var h1 = colLeft.querySelector('h1');
    var gallery = colLeft.querySelector('.wrapper__swiper__product-img');
    var info = colRight.querySelector('.product-information');
    if (!h1 || !gallery || !info) return false;

    var images = collectCoverImages(gallery);
    if (!images.length) return false;

    destroyOldSwipers(gallery);

    row.classList.add('ab-psl');
    colLeft.classList.add('ab-psl__gallery');
    colRight.classList.add('ab-psl__info');

    info.insertBefore(prepareTitle(h1), info.firstChild);

    var newGallery = buildGallery(images, gallery);
    gallery.parentNode.insertBefore(newGallery, gallery);
    gallery.parentNode.removeChild(gallery);

    return true;
  }

  function transformPackProduct(container) {
    var rows = Array.prototype.slice.call(container.querySelectorAll(':scope > .row'));
    if (rows.length < 2) rows = Array.prototype.slice.call(container.querySelectorAll('.row'));
    if (rows.length < 2) return false;

    var titleRow = rows[0];
    var mainRow = rows[1];
    if (mainRow.classList.contains('ab-psl')) return false;

    var h1 = titleRow.querySelector('h1');
    var colLeft = mainRow.querySelector('[class*="col-lg-7"]');
    var colRight = mainRow.querySelector('[class*="col-lg-5"]');
    var gallery = colLeft && colLeft.querySelector('.wrapper__swiper__product-img');
    var info = colRight && colRight.querySelector('.product-information');
    if (!h1 || !gallery || !info) return false;

    var images = collectCoverImages(gallery);
    if (!images.length) return false;

    destroyOldSwipers(gallery);

    titleRow.style.display = 'none';
    mainRow.classList.add('ab-psl');
    colLeft.classList.add('ab-psl__gallery');
    colRight.classList.add('ab-psl__info');

    info.insertBefore(prepareTitle(h1), info.firstChild);

    var promoSource = titleRow.querySelector('.col-12, [class*="col-"]');
    if (promoSource) {
      Array.prototype.slice.call(promoSource.children).forEach(function (child) {
        if (child && child.tagName) colLeft.insertBefore(child, colLeft.firstChild);
      });
    }

    var newGallery = buildGallery(images, gallery);
    gallery.parentNode.insertBefore(newGallery, gallery);
    gallery.parentNode.removeChild(gallery);

    return true;
  }

  function applyVariation(container) {
    if (!container || container.getAttribute(MARKER) === 'done') return;
    if (container.querySelector('.ab-pig')) return;

    injectStyles();
    document.documentElement.setAttribute(MARKER, 'b');

    var ok = transformClassicProduct(container);
    if (!ok) ok = transformPackProduct(container);

    if (!ok) {
      document.documentElement.removeAttribute(MARKER);
      return;
    }

    container.setAttribute(MARKER, 'done');
    if (document.body) document.body.setAttribute(MARKER, 'b');

    fixStickyBanner(container);
    watchBannerDuplicates(container);
  }

  function run() {
    var tools = getAblyftTools();

    function onReady() {
      if (!document.body || document.body.id !== 'product') return;

      if (tools && typeof tools.waitForElement === 'function') {
        tools.waitForElement(SELECTOR, function (elem) {
          var el = elem && elem.length !== undefined ? elem[0] : elem;
          applyVariation(el || document.querySelector(SELECTOR));
        });
      } else {
        applyVariation(document.querySelector(SELECTOR));
      }
    }

    if (tools && typeof tools.domLoaded === 'function') {
      tools.domLoaded(onReady);
    } else if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', onReady);
    } else {
      onReady();
    } 
  }

  run();
})();

(function () {
  'use strict';

  var STYLE_ID = 'ab-kit-card-styles';
  var MARKER = 'data-ab-kit';
  var TITLE_TEXT = 'Votre kit de clôture :';
  var SUBTITLE_TEXT = 'Vos produits :';
  var CHEVRON =
    'url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23026473%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27m6 9 6 6 6-6%27/%3E%3C/svg%3E")';

  function getTools() {
    if (typeof ablyftTools !== 'undefined' && ablyftTools) return ablyftTools;
    try {
      if (window.ablyft && typeof window.ablyft.getTools === 'function') {
        return window.ablyft.getTools();
      }
    } catch (e) {}
    return null;
  }

  function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;

    var p = 'html[' + MARKER + '="1"] #product ';

    var css = [
      p + '.ab-kit-card{',
      '  background:#F6F2EB;',
      '  border-radius:12px;',
      '  padding:20px 18px 10px;',
      '  margin:0 0 1.5rem;',
      '}',
      p + '.ab-kit-card__title{',
      '  margin:0 0 18px;',
      '  text-align:center;',
      '  color:#026473;',
      '  font-size:16px;',
      '  font-weight:800;',
      '  line-height:1.3;',
      '}',
      p + '.ab-kit-card__subtitle{',
      '  margin:0 0 12px;',
      '  color:#232323;',
      '  font-size:14px;',
      '  font-weight:800;',
      '  line-height:1.3;',
      '}',

      p + '.ab-kit-card .ap5-dimensions-row{',
      '  margin:0 0 18px!important;',
      '}',
      p + '.ab-kit-card .ap5-ml-field{',
      '  background:transparent!important;',
      '  border:0!important;',
      '  border-radius:0!important;',
      '  padding:0!important;',
      '}',
      p + '.ab-kit-card .ap5-ml-field .group-name{',
      '  color:#232323!important;',
      '  font-size:13px!important;',
      '  font-weight:800!important;',
      '  margin:0 0 6px!important;',
      '}',
      p + '.ab-kit-card .ap5-ml-field .form-control{',
      '  background-color:transparent!important;',
      '  border:0!important;',
      '  border-bottom:1px solid #026473!important;',
      '  border-radius:0!important;',
      '  box-shadow:none!important;',
      '  font-size:12px!important;',
      '}',

      p + '.ab-kit-card #ap5-product-list > span{',
      '  display:block;',
      '  margin:0 0 6px;',
      '  color:#232323;',
      '  font-size:12.5px;',
      '  font-weight:700;',
      '  line-height:1.35;',
      '}',
      p + '.ab-kit-card #ap5-product-list > span + .product-variants{',
      '  margin-top:0!important;',
      '}',
      p + '.ab-kit-card #ap5-product-list .product-variants{',
      '  display:grid!important;',
      '  grid-template-columns:repeat(3,minmax(0,1fr))!important;',
      '  column-gap:10px!important;',
      '  row-gap:10px!important;',
      '  align-items:end!important;',
      '  border-left:0!important;',
      '  padding:0!important;',
      '  margin:0 0 16px!important;',
      '}',
      p + '.ab-kit-card #ap5-product-list .product-variants .ap5-attributes{',
      '  border-left:0!important;',
      '  padding:0!important;',
      '  margin:0!important;',
      '}',
      p + '.ab-kit-card #ap5-product-list .product-variants-item{',
      '  min-width:0;',
      '  margin:0!important;',
      '}',
      p + '.ab-kit-card #ap5-product-list .product-variants-item .control-label{',
      '  display:block;',
      '  margin:0 4px 4px!important;',
      '  color:#232323!important;',
      '  font-size:13px!important;',
      '  font-weight:400!important;',
      '  line-height:1.2!important;',
      '}',
      p + '.ab-kit-card #ap5-product-list .ap5-attribute-list{',
      '  margin:0!important;',
      '}',

      p + '.ab-kit-card select.ab-kit-pill{',
      '  width:auto!important;',
      '  max-width:100%!important;',
      '  min-width:0!important;',
      '  height:28px!important;',
      '  min-height:28px!important;',
      '  padding:0 28px 0 12px!important;',
      '  color:#232323!important;',
      '  font-size:11.5px!important;',
      '  line-height:26px!important;',
      '  background-color:#E8F4F6!important;',
      '  background-repeat:no-repeat!important;',
      '  background-position:right 8px center!important;',
      '  background-size:14px 14px!important;',
      '  border:1px solid #026473!important;',
      '  border-radius:999px!important;',
      '  box-shadow:none!important;',
      '  -webkit-appearance:none;',
      '  -moz-appearance:none;',
      '  appearance:none;',
      '}',
      p + '.ab-kit-card select.ab-kit-pill:hover{',
      '  background-color:#FFFFFF!important;',
      '}',
      p + '.ab-kit-card select.ab-kit-pill:not(.ab-kit-pill--static){',
      '  background-image:' + CHEVRON + '!important;',
      '  cursor:pointer!important;',
      '}',
      p + '.ab-kit-card select.ab-kit-pill.ab-kit-pill--static{',
      '  background-image:none!important;',
      '  padding-right:12px!important;',
      '  pointer-events:none!important;',
      '  cursor:default!important;',
      '}',

      p + '.ab-kit-card #ap5-product-list .ap5-attribute-list ul{',
      '  display:flex;',
      '  flex-wrap:wrap;',
      '  gap:6px;',
      '  margin:0;',
      '  padding:0;',
      '  list-style:none;',
      '}',
      p + '.ab-kit-card #ap5-product-list .ap5-attribute-list ul li{',
      '  float:none!important;',
      '  margin:0!important;',
      '}',

      '@media (max-width:460px){',
      '  ' + p + '.ab-kit-card{padding:16px 14px 8px;}',
      '  ' + p + '.ab-kit-card__title{font-size:15px;margin-bottom:14px;}',
      '  ' + p + '.ab-kit-card #ap5-product-list .product-variants{column-gap:8px!important;}',
      '  ' + p + '.ab-kit-card select.ab-kit-pill{font-size:11px!important;padding-left:10px!important;}',
      '}'
    ].join('');

    var style = document.createElement('style');
    style.id = STYLE_ID;
    style.appendChild(document.createTextNode(css));
    (document.head || document.documentElement).appendChild(style);
  }

  function getInfo() {
    return (
      document.querySelector('#product .ab-psl__info .product-information') ||
      document.querySelector('#product .product-information')
    );
  }

  function getProductList() {
    var list = document.getElementById('ap5-product-list');
    if (!list) return null;
    if (!list.querySelector('.ap5-attributes')) return null;
    return list;
  }

  function decoratePills(list) {
    if (!list) return;
    list.querySelectorAll('.product-variants-item select').forEach(function (select) {
      select.classList.add('ab-kit-pill');
      if (select.options && select.options.length <= 1) {
        select.classList.add('ab-kit-pill--static');
      } else {
        select.classList.remove('ab-kit-pill--static');
      }
    });
  }

  function isSynced(card, parts) {
    var kids = Array.prototype.filter.call(card.children, function (child) {
      return parts.indexOf(child) !== -1;
    });
    if (kids.length !== parts.length) return false;
    var i;
    for (i = 0; i < parts.length; i++) {
      if (kids[i] !== parts[i]) return false;
    }
    return true;
  }

  function sync() {
    var info = getInfo();
    if (!info) return false;

    var list = getProductList();
    var dims = info.querySelector('.ap5-dimensions-row');
    if (!list && !dims) return false;

    var card = info.querySelector('.ab-kit-card');
    if (!card) {
      card = document.createElement('div');
      card.className = 'ab-kit-card';
      var anchor = dims || list;
      if (!anchor.parentNode) return false;
      anchor.parentNode.insertBefore(card, anchor);
    }

    var title = card.querySelector('.ab-kit-card__title');
    if (!title) {
      title = document.createElement('p');
      title.className = 'ab-kit-card__title';
      title.textContent = TITLE_TEXT;
      card.appendChild(title);
    }

    var subtitle = card.querySelector('.ab-kit-card__subtitle');
    if (!subtitle) {
      subtitle = document.createElement('p');
      subtitle.className = 'ab-kit-card__subtitle';
      subtitle.textContent = SUBTITLE_TEXT;
      card.appendChild(subtitle);
    }
    subtitle.style.display = list ? '' : 'none';

    var parts = [title];
    if (dims) parts.push(dims);
    parts.push(subtitle);
    if (list) parts.push(list);

    if (!isSynced(card, parts)) {
      parts.forEach(function (part) {
        card.appendChild(part);
      });
    }

    decoratePills(list);
    document.documentElement.setAttribute(MARKER, '1');
    return true;
  }

  function watch() {
    if (window.__abKitCardMo || typeof MutationObserver === 'undefined') return;

    var info = getInfo();
    if (!info) return;

    var timer = null;
    window.__abKitCardMo = new MutationObserver(function () {
      if (timer) clearTimeout(timer);
      timer = setTimeout(function () {
        sync();
      }, 80);
    });

    window.__abKitCardMo.observe(info, { childList: true, subtree: true });
  }

  function start() {
    if (!document.body || document.body.id !== 'product') return;
    if (!document.getElementById('ap5-product-list') && !document.querySelector('.ap5-dimensions-row')) return;

    injectStyles();

    var attempts = 0;
    function attempt() {
      if (sync() || attempts > 20) {
        watch();
        return;
      }
      attempts++;
      setTimeout(attempt, 150);
    }
    attempt();
  }

  function run() {
    var tools = getTools();

    if (tools && typeof tools.domLoaded === 'function') {
      tools.domLoaded(start);
    } else if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', start);
    } else {
      start();
    }
  }

  run();
})();
