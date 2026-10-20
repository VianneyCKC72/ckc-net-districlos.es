(function () {
  function fixFeaturedProductSwipers() {
    document.querySelectorAll(".wrapper__swiper__featured-products .swiper__featured-products").forEach(function (el) {
      var swiper = el.swiper;
      if (!swiper) {
        return;
      }
      if (swiper.params.loop) {
        swiper.loopDestroy();
        swiper.params.loop = false;
        swiper.params.watchOverflow = true;
      }
      swiper.update();
    });
  }

  if (document.readyState === "complete") {
    fixFeaturedProductSwipers();
  } else {
    window.addEventListener("load", fixFeaturedProductSwipers);
  }

  if (typeof prestashop !== "undefined" && prestashop.on) {
    prestashop.on("updatedProduct", function () {
      setTimeout(fixFeaturedProductSwipers, 200);
    });
  }
})();
