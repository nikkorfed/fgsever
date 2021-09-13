// Подстановка +7 в поле для телефона
$("body").on("focus", '.form-claim input[name="form-claim-phone"]', function () {
  if (!$(this).val()) $(this).val("+7 (");
});

// Отправка претензии
$("#form-claim-images").on("change", function () {
  let text = `Выбрано ${$(this).prop("files").length} фотографий`;
  $("label[for=form-claim-images]").text(text).removeClass("photo-black").addClass("tick-color");
});
$(".form-claim").on("submit", function (e) {
  e.preventDefault();
  var data = new FormData(this);
  $.ajax({
    url: "/scripts/php/claim.php",
    type: "POST",
    data: data,
    processData: false,
    contentType: false,
    success: function () {
      $(".form-claim input").val("");
      $(".form-claim textarea").val("");
      $.fancybox.close();
      $.fancybox.open({
        src: "#form-claim-success",
        opts: {
          touch: false,
        },
      });
    },
  });
});
