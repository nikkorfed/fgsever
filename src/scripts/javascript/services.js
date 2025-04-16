$("section.services:not(.options)").on("click", ".services-item", function () {
  location.href = $(this).find("a[class^=button]").attr("href");
});
