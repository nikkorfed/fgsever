// Смена состояния шапок при прокрутке
var tempScroll = 0,
  currentScroll;
$(window).on("scroll", function () {
  currentScroll = $(this).scrollTop();
  var activateThreshold = ($(window).width() > 1251 && 90) || ($(window).width() < 992 && 100) || 122;
  // if ($(document).width() < 768) {
  if (currentScroll > tempScroll) {
    if (currentScroll > 0) {
      $(".header-mobile").addClass("white");
    }
    if (currentScroll > activateThreshold) {
      $(".header-mobile").addClass("hidden");
      $(".header-mobile__menu").removeClass("active");
      $(".overlay").removeClass("active");
      $(".header-mobile__menu-switcher").prop("checked", "");
      $(".header").addClass("active");
    }
    if (currentScroll > 150) {
      $(".header").addClass("hidden");
    }
  } else if (currentScroll < tempScroll) {
    if (currentScroll == 0) {
      $(".header-mobile").removeClass("active white");
    }
    if (currentScroll > 90) {
      $(".header-mobile").removeClass("hidden");
    } else {
      $(".header").removeClass("active");
    }
    if (currentScroll > 150) {
      $(".header").removeClass("hidden");
    }
  }
  tempScroll = currentScroll;
  // } else {
  //   if (currentScroll > tempScroll) {
  //     if (currentScroll > 90) {
  //       $('.header').addClass('active');
  //       if (currentScroll > 150) {
  //         $('.header').addClass('hidden');
  //       }
  //     }
  //     tempScroll = currentScroll;
  //   } else if (currentScroll < tempScroll) {
  //     if (currentScroll > 90) {
  //       if (currentScroll > 150) {
  //         $('.header').removeClass('hidden');
  //       }
  //     } else {
  //       $('.header').removeClass('active');
  //     }
  //     tempScroll = currentScroll;
  //   }
  // }
});

// Изменение цвета десктопной шапки при наведении на пункт меню
// $('.header .menu__item').hover(
//   function() {
//     $('.header').addClass('white');
//   }, function() {
//     $('.header').removeClass('white');
//   }
// )

// Открытие/закрытие мобильного меню при клике на кнопку
$(".header-mobile__menu-switcher").on("change", function () {
  if ($(this).prop("checked")) {
    $(".header-mobile").addClass("white");
    $(".header-mobile__menu").addClass("active");
    $(".overlay").addClass("active");
  } else {
    if ($(window).scrollTop() == 0) {
      $(".header-mobile").removeClass("white");
    }
    $(".header-mobile__menu").removeClass("active");
    $(".overlay").removeClass("active");
  }
});

// Закрытие мобильного меню при клике по фону
$(".overlay").on("click", function () {
  $(".header-mobile").removeClass("white");
  $(".header-mobile__menu").removeClass("active");
  $(".overlay").removeClass("active");
  $(".header-mobile__menu-switcher").prop("checked", "");
});

// Закрытие мобильного меню при изменении размеров окна
$(window).on("resize", function () {
  if ($(window).width() >= 768) {
    $(".header-mobile__menu").removeClass("active");
    $(".overlay").removeClass("active");
    $(".header-mobile__menu-switcher").prop("checked", "");
  }
});

// Открытие/закрытие подменю при клике по пункту мобильного меню
$(".header-mobile__menu .menu__item > a").on("click", function (e) {
  if ($(this).next().hasClass("submenu")) {
    e.preventDefault();
    if ($(this).parent().hasClass("active")) {
      $(this).parent().removeClass("active");
      $(this).next().children("div").hide();
    } else {
      $(this).parent().addClass("active");
      $(this).next().children("div").show();
    }
  }
});

// Обновление страницы при смене window.location.hash
