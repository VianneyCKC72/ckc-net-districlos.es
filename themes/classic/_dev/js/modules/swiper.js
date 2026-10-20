import "swiper/css/bundle";
import Swiper from "swiper/bundle";

function openProductImageLightbox(imageUrl, alt) {
  const existing = document.getElementById("product-image-lightbox");
  if (existing) {
    existing.parentNode.removeChild(existing);
  }

  const overlay = document.createElement("div");
  overlay.id = "product-image-lightbox";
  overlay.setAttribute("role", "dialog");
  overlay.setAttribute("aria-modal", "true");
  overlay.style.cssText =
    "position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.85);display:flex;align-items:center;justify-content:center;padding:24px;box-sizing:border-box;";

  const closeBtn = document.createElement("button");
  closeBtn.type = "button";
  closeBtn.setAttribute("aria-label", "Fermer");
  closeBtn.innerHTML = "&times;";
  closeBtn.style.cssText =
    "position:absolute;top:16px;right:20px;border:0;background:transparent;color:#fff;font-size:36px;line-height:1;cursor:pointer;padding:8px;";

  const img = document.createElement("img");
  img.src = imageUrl;
  img.alt = alt || "";
  img.style.cssText =
    "max-width:min(92vw,1100px);max-height:90vh;width:auto;height:auto;object-fit:contain;border-radius:4px;background:#fff;";

  function close() {
    if (overlay.parentNode) {
      overlay.parentNode.removeChild(overlay);
    }
    document.removeEventListener("keydown", onKey);
  }

  function onKey(e) {
    if (e.key === "Escape") {
      close();
    }
  }

  closeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    close();
  });
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      close();
    }
  });
  document.addEventListener("keydown", onKey);

  overlay.appendChild(closeBtn);
  overlay.appendChild(img);
  document.body.appendChild(overlay);
}

document.addEventListener("click", (e) => {
  const link = e.target.closest("a.js-product-image-zoom");
  if (!link) {
    return;
  }

  e.preventDefault();
  e.stopPropagation();

  const img = link.querySelector("img");
  openProductImageLightbox(link.getAttribute("href"), img ? img.getAttribute("alt") : "");
});

function initFeaturedProductSwipers() {
  document.querySelectorAll(".wrapper__swiper__featured-products").forEach((wrapper) => {
    const el = wrapper.querySelector(".swiper__featured-products");
    if (!el || el.swiper) {
      if (el && el.swiper) {
        el.swiper.update();
      }
      return;
    }

    new Swiper(el, {
      // CKC Vianney — loop cassé en EN (titres longs / Skeepers) : clones superposés
      loop: false,
      watchOverflow: true,
      spaceBetween: 3,
      slidesPerView: 2,
      breakpoints: {
        768: { slidesPerView: 3, spaceBetween: 24 },
        1024: { slidesPerView: 4, spaceBetween: 24 },
      },
      observer: true,
      observeParents: true,
      pagination: {
        el: wrapper.querySelector(".swiper-pagination"),
        clickable: true,
      },
      navigation: {
        nextEl: wrapper.querySelector(".swiper-button-next"),
        prevEl: wrapper.querySelector(".swiper-button-prev"),
      },
    });
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initFeaturedProductSwipers);
} else {
  initFeaturedProductSwipers();
}

window.addEventListener("load", initFeaturedProductSwipers);
if (typeof prestashop !== "undefined") {
  prestashop.on("updatedProduct", () => {
    setTimeout(initFeaturedProductSwipers, 200);
  });
}
