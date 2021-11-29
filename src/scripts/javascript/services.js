$("section.services:not(.options)").on("click", ".services-item", function () {
  location.href = $(this).find(".button-simple").attr("href");
});
