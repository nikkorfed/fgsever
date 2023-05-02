// Слайдер на главной страницее
$(".slider-container").owlCarousel({
  items: 1,
  loop: true,
  autoplay: true,
  autoplayHoverPause: true,
  smartSpeed: 500,
  autoplayTimeout: 8000,
  stageOuterClass: "slider-stage-outer",
  stageClass: "slider-stage",
  itemElement: 'div class="slider-item-wrapper"',
  nav: false,
  navContainerClass: "slider-arrows",
  navElement: "div",
  navClass: ["slider-arrows__left", "slider-arrows__right"],
  dotsClass: "slider-dots",
  dotClass: "slider-dots__item",
  onTranslated: resetTimer,
});

// Функция сброса таймера для перелистывания слайдера
function resetTimer(event) {
  $(event.target).trigger("stop.owl.autoplay");
  $(event.target).trigger("play.owl.autoplay", [0, 500]);
}

// Слайдер отзывов
$(".reviews-container").owlCarousel({
  loop: true,
  // autoplay: true,
  autoplayHoverPause: true,
  smartSpeed: 500,
  stageOuterClass: "reviews-stage-outer",
  stageClass: "reviews-stage",
  itemElement: 'div class="reviews-item-wrapper"',
  dots: false,
  nav: true,
  navContainerClass: "reviews-arrows",
  navElement: "div",
  navClass: ["reviews-arrows__left icon chevron-left-black", "reviews-arrows__right icon chevron-right-black"],
  navText: ["", ""],
  dotsClass: "reviews-dots",
  dotClass: "reviews-dots__item",
  responsive: {
    0: {
      items: 1,
    },
    768: {
      items: 2,
    },
    992: {
      items: 3,
    },
  },
  onInitialized: reviewsHeightCalculation,
  onTranslated: reviewsHeightCalculation,
  onResized: reviewsHeightCalculation,
});

// Функция проверки попадания слайдера в поле зрения
function isSliderInSight(element) {
  let windowTop = $(window).scrollTop();
  let windowBottom = windowTop + $(window).height();

  let elementTop = $(element).offset().top;
  let elementBottom = elementTop + $(element).height();

  return elementTop >= windowTop && elementBottom <= windowBottom;
}

// Запуск и остановка прокрутки отзывов
$(".reviews-container").each(function () {
  let reviewsContainer = $(this);
  let reviews = reviewsContainer.parent();
  let isAnimationStarted = false;
  $(window).on("scroll", function () {
    if (isSliderInSight($(reviews)) && !isAnimationStarted) {
      $(reviewsContainer).trigger("play.owl.autoplay", [0, 500]);
      isAnimationStarted = true;
    } else if (!isSliderInSight($(reviews)) && isAnimationStarted) {
      $(reviewsContainer).trigger("stop.owl.autoplay");
      isAnimationStarted = false;
    }
  });
});

// Функция расчёта высоты отзывов
function reviewsHeightCalculation(event) {
  let heights = [],
    maxHeight,
    reviewsContainer = $(event.target);
  reviewsContainer.find(".reviews-item-wrapper.active").each(function () {
    if ($(this).find("div").is(".reviews-item__drive2-link")) {
      heights.push(
        $(this).find(".reviews-item__head").outerHeight(true) +
          $(this).find(".reviews-item__text").outerHeight(true) +
          $(this).find(".reviews-item__drive2-link").outerHeight(true)
      );
    } else {
      heights.push($(this).find(".reviews-item__head").outerHeight(true) + $(this).find(".reviews-item__text").outerHeight(true));
    }
  });
  maxHeight = Math.max.apply(null, heights);
  console.log(maxHeight);
  reviewsContainer.find(".reviews-item").height(maxHeight);
}
