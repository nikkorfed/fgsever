$(".special-buttons .close-button").click(function () {
  if ($(".special-buttons").hasClass("hidden")) showSpecialButtons();
  else hideSpecialButtons();
});

if (!$.cookie("special-buttons-closed")) showSpecialButtons();

function showSpecialButtons() {
  $(".special-buttons").removeClass("hidden");
  $(".special-buttons .close-button").removeClass("menu-white");
  $(".special-buttons .close-button").addClass("cross-black");
}

function hideSpecialButtons() {
  $.cookie("special-buttons-closed", true, { expires: 1, path: "/" });
  $(".special-buttons").addClass("hidden");
  $(".special-buttons .close-button").removeClass("cross-black");
  $(".special-buttons .close-button").addClass("menu-white");
}
