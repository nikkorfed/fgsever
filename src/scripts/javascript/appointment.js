// Открытие всплывающего окна о переезде
$(document).ready(function () {
  let expired = Date.now() > 1673427600000; // После 1 января 2023 года
  if ($.cookie("we-moved-closed") || expired) return;
  $.fancybox.open({
    src: "#we-moved",
    opts: { touch: false, afterClose: () => $.cookie("we-moved-closed", true, { expires: 7, path: "/" }) },
  });
});

// Открытие всплывающего окна о работе в праздники
$(document).ready(function () {
  let expired = Date.now() > 1641513600000; // После 7 января 2022 года
  if ($.cookie("holidays-warning-closed") || expired) return;
  $.fancybox.open({
    src: "#holidays-warning",
    opts: { touch: false, afterClose: () => $.cookie("holidays-warning-closed", true, { expires: 1, path: "/" }) },
  });
});

// Подстановка +7 в поле для телефона
$("body").on("focus", '.appointment input[name="phone"]', function () {
  if (!$(this).val()) $(this).val("+7 (");
});

// При отправке формы
$(".appointment form").on("submit", function (e) {
  e.preventDefault();

  // Сбор данных
  let data = {
    name: $(this).find("input[name=name]").val(),
    phone: $(this).find("input[name=phone]").val(),
  };

  // Определение услуги
  if ($("h1").length == 1) {
    data["service"] = $("h1").text();
  } else {
    switch ($(".appointment").attr("id")) {
      case "maintenance-appointment":
        data["service"] = "Техническое обслуживание";
        break;
      case "upgrade-appointment":
        data["service"] = "Дооснащение";
        break;
      case "remont-appointment":
        data["service"] = "Ремонт";
        break;
      case "diagnostics-appointment":
        data["service"] = "Диагностика";
        break;
      case "gearbox-appointment":
        data["service"] = "Ремонт АКПП и раздаточных коробок";
        break;
      case "engine-appointment":
        data["service"] = "Ремонт двигателя";
        break;
      case "injector-appointment":
        data["service"] = "Ремонт форсунок";
        break;
      case "steering-appointment":
        data["service"] = "Ремонт ГУР";
        break;
      case "headlights-appointment":
        data["service"] = "Ремонт ГУР";
        break;
      case "suspension-appointment":
        data["service"] = "Диагностика и ремонт подвески";
        break;
      case "intake-appointment":
        data["service"] = "Чистка впускного коллектора";
        break;
      case "abs-appointment":
        data["service"] = "Ремонт АБС";
        break;
      case "ecu-appointment":
        data["service"] = "Ремонт электронных блоков";
        break;
      case "coding-appointment":
        data["service"] = "Кодирование опций";
        break;
      case "body-repair-appointment":
        data["service"] = "Кузовной ремонт";
        break;
      case "update-appointment":
        data["service"] = "Обновление ПО";
        break;
    }
  }

  // Добавление кодируемых опций
  if ($(".coding").length) {
    data["car"] = $(".coding").attr("data-car");
    data["codingOptions"] = [];
    $(".coding .coding-item:not(.disabled)").each(function () {
      let codingOption = $(this).find(".coding-item__title").text();
      data["codingOptions"].push(codingOption);
    });
  }

  // Добавление данных из калькулятора ТО
  if ($(".maintenance-parts").length) {
    data["car"] = [];
    $(".calculator-result .car-spec").each(function () {
      data["car"].push([$(this).find(".label").text(), $(this).find(".text").text()]);
    });

    data["maintenanceParts"] = [];

    $(".calculator-result .maintenance-parts tr[data-name]:not(.disabled)").each(function () {
      let part = {
        name: $(this).find(".name").text(),
      };
      if ($(this).find(".option.selected").length) {
        part["brand"] = $(this).find(".option.selected .part .text").text();
      }
      if ($(this).find(".option.selected").attr("data-number") !== undefined) {
        part["number"] = $(this).find(".option.selected").attr("data-number");
        part["from"] = $(this).find(".option.selected").attr("data-from");
      }
      if ($(this).attr("data-quantity") !== undefined) {
        part["quantity"] = $(this).attr("data-quantity");
        part["quantityLabel"] = $(this).attr("data-quantity-label");
      }
      if ($(this).attr("data-part-price") !== undefined) {
        part["partPrice"] = $(this).attr("data-part-price");
      }
      if ($(this).attr("data-work-price") !== undefined) {
        part["workPrice"] = $(this).attr("data-work-price");
      }

      data["maintenanceParts"].push(part);
    });
  }

  // Добавление данных из поиска запчастей
  if ($(".search-parts").length) {
    data["orderParts"] = [];

    $("#search-parts .result .parts tr[data-number]:not(.disabled)").each(function () {
      let part = {
        name: $(this).find(".name").text(),
      };
      if ($(this).find(".option.selected").length) {
        part["brand"] = $(this).find(".option.selected .part .text").text();
      }
      if ($(this).find(".option.selected").attr("data-number") !== undefined) {
        part["number"] = $(this).find(".option.selected").attr("data-number");
        part["from"] = $(this).find(".option.selected").attr("data-from");
      }
      if ($(this).attr("data-part-price") !== undefined) {
        part["partPrice"] = $(this).attr("data-part-price");
      }

      data["orderParts"].push(part);
    });
  }

  // Добавление данных из калькулятора дооснащения
  if ($(".upgrade-options").length) {
    data["car"] = [];
    $(".calculator-result .car-spec").each(function () {
      data["car"].push([$(this).find(".label").text(), $(this).find(".text").text()]);
    });

    data["upgradeOptions"] = [];

    $(".calculator-result .upgrade-options .upgrade-option.selected").each(function () {
      data["upgradeOptions"].push({
        code: $(this).find(".upgrade-option__code").text(),
        name: $(this).find("h3").text(),
        price: +$(this).attr("data-price"),
        shownPrice: $(this).find(".upgrade-option__price").text(),
      });
    });
  }

  // Отправка запроса
  $.ajax({
    url: "/scripts/php/appointment.php",
    type: "POST",
    data: data,
    success: function () {
      $(".appointment form input").val("");
      $(".appointment form textarea").val("");
      $.fancybox.close();
      $.fancybox.open({
        src: "#appointment-success",
        opts: { touch: false },
      });
    },
  });
});
