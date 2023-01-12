// Глобальное отключение свайпа в fancybox
$.fancybox.defaults.touch = false;

// Просмотр фотографий
$("[data-fancybox]").fancybox({
  infobar: false,
  buttons: ["close"],
  clickContent: false,
  mobile: {
    dblclickContent: false,
    dblclickSlide: false,
  },
});
