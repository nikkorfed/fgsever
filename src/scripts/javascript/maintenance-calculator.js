// Подбор запчастей при передаче данных в адресе страницы
if (window.location.search && $("#maintenance-calculator").length) {
  let data = window.location.search.substr(1).split("&"),
    result = {},
    keyAndValue;
  data.forEach(function (item) {
    keyAndValue = item.split("=");
    result[keyAndValue[0]] = keyAndValue[1];
  });
  if (result["vin"] != undefined && result["mileage"] != undefined) {
    $("#maintenance-calculator").find("#vin-number").val(result["vin"]);
    $("#maintenance-calculator").find("#mileage").val(result["mileage"]);
    requestCarInfo_MaintenanceCalculator();
  }
}

// Подбор запчастей при ручном вводе VIN-номера
$("#maintenance-calculator")
  .find(".car-data-input")
  .on("submit", function (e) {
    e.preventDefault();
    if (vinCorrect()) {
      requestCarInfo_MaintenanceCalculator();
    }
  });

// Переключение между оригинальными и неоригинальными деталями
$("#maintenance-calculator .calculator-result .original-or-alternative").on("click", ".option:not(.loading)", function (e) {
  $("#maintenance-calculator .calculator-result .original-or-alternative .option").removeClass("selected");
  $(this).addClass("selected");

  // При клике по кнопке "Оригинальные", переключаемся на оригинальные запчасти
  if ($(this).attr("data-name") == "original") {
    $("#maintenance-calculator .calculator-result table")
      .find("tr[class!=table-header] .option:first-child")
      .each(function () {
        chooseOption($(this), e);
      });

    // При клике по кнопке "Аналоги", выбираем первый из аналогов
  } else {
    $("#maintenance-calculator .calculator-result table")
      .find("tr[class!=table-header] .option:nth-child(2)")
      .each(function () {
        chooseOption($(this), e);
      });
  }

  // Учёт спецпредложений и перерасчёт общей цены
  specialConditions();
  calculateCosts();
});

// Выбор необходимых деталей для обслуживания
$("#maintenance-calculator .calculator-result table").on("click", "tr[data-name]", function () {
  $(this).toggleClass("disabled");

  // Учёт необходимых спецпредложений и скидок
  specialConditions();

  // При выборе колодок, автоматический выбор соответствующих датчиков износа
  if ($(this).attr("data-name") == "frontBrakePads" || $(this).attr("data-name") == "rearBrakePads") {
    let brakePadsWearSensor = $(this).attr("data-name").replace(/Pads/g, "PadsWearSensor");
    if (!$(this).hasClass("disabled")) {
      $("#maintenance-calculator .calculator-result")
        .find("[data-name=" + brakePadsWearSensor + "]")
        .removeClass("disabled");
    } else {
      $("#maintenance-calculator .calculator-result")
        .find("[data-name=" + brakePadsWearSensor + "]")
        .addClass("disabled");
    }
  }

  // При выборе датчиков износа, автоматический выбор соответствующих колодок
  if ($(this).attr("data-name") == "frontBrakePadsWearSensor" || $(this).attr("data-name") == "rearBrakePadsWearSensor") {
    let brakePads = $(this)
      .attr("data-name")
      .replace(/PadsWearSensor/g, "Pads");
    if (!$(this).hasClass("disabled")) {
      $("#maintenance-calculator .calculator-result")
        .find("[data-name=" + brakePads + "]")
        .removeClass("disabled");
    }
  }

  // Расчёт общих стоимостей
  calculateCosts();
});

// Выбор запчастей от разных производителей (опций)
$("#maintenance-calculator .calculator-result table").on("click", "tr:not(.disabled) .option", function (e) {
  chooseOption($(this), e);
});

// Фиксация блока с итоговыми стоимостями
if ($("#maintenance-calculator").length) {
  $(window).on("scroll", function () {
    let windowBottom = $(this).height() + $(this).scrollTop();
    let maintenancePartsTop = $("#maintenance-calculator .maintenance-parts").offset().top;
    let maintenancePartsBottom = maintenancePartsTop + $("#maintenance-calculator .maintenance-parts").height();
    if (windowBottom > maintenancePartsTop && windowBottom < maintenancePartsBottom) {
      $("#maintenance-calculator .summary-costs").addClass("summary-costs_fixed");
      $(".overlay-buttons").addClass("overlay-buttons_hidden");
    } else {
      $("#maintenance-calculator .summary-costs").removeClass("summary-costs_fixed");
      $(".overlay-buttons").removeClass("overlay-buttons_hidden");
    }
  });
}

// --- Основные функции --- //

// Проверка правильности ввода VIN
function vinCorrect() {
  $(".error_vin-number").remove();
  if ($("#vin-number").val().length == 7 || $("#vin-number").val().length == 17) {
    return true;
  } else {
    $("#vin-number").after('<div class="input-field__error error_vin-number">Необходимо ввести 7 или 17 знаков</div>');
    return false;
  }
}

// Основная функция, запускающая поиск деталей для обслуживания
function requestCarInfo_MaintenanceCalculator() {
  // Скрытие быстрых кнопок
  hideSpecialButtons();

  // Сбор данных из формы
  let vin = $("#maintenance-calculator").find(".car-data-input #vin-number").val();
  let mileage = $("#maintenance-calculator").find(".car-data-input #mileage").val();

  // Изменение адреса страницы
  history.pushState(null, null, "?vin=" + vin + "&mileage=" + mileage);

  // Открытие всплывающего окна загрузки
  $.fancybox.open({ src: "#calculation", opts: { modal: true } });

  // Запрос информации об автомобиле
  $.ajax({
    url: "/scripts/php/calculators/car-info.php",
    type: "POST",
    data: { vin },
    success: (carInfo) => {
      $.fancybox.close();
      if (!carInfo.error) {
        renderCarInfo_MaintenanceCalculator(carInfo);
        requestParts(carInfo, mileage);
        // requestCarImages(carInfo);
      } else if (carInfo.error == "car-info-not-found") {
        renderCarInfoNotFoundError_MaintenanceCalculator();
      } else if (carInfo.error == "vin-not-found") {
        renderVinNotFoundError_MaintenanceCalculator();
      } else if (carInfo.error == "multiple-cars-founded") {
        renderMultipleCarsFoundedError_MaintenanceCalculator(carInfo);
      }
    },
  });
}

// Отображение ошибки об отсутствии найденной информации
function renderCarInfoNotFoundError_MaintenanceCalculator() {
  setTimeout(() => $.fancybox.open({ src: "#car-info-not-found" }), 300);
}

// Отображение ошибки об отсутствии VIN
function renderVinNotFoundError_MaintenanceCalculator() {
  setTimeout(() => $.fancybox.open({ src: "#vin-not-found" }), 300);
}

// Отображение ошибки о нескольких найденных автомобилях
function renderMultipleCarsFoundedError_MaintenanceCalculator({ cars }) {
  // Заполнение блока найденными автомобилями
  for (let car of cars) {
    $("#multiple-cars-founded .popup__cars").append(
      `<div class="popup__car"><div class="car__model">BMW ${car.model}</div><div class="car__model-code">${car.modelCode}</div><div class="car__vin">${car.vin}</div></div>`
    );
  }

  // Отображение всплывающего окна
  setTimeout(() => $.fancybox.open({ src: "#multiple-cars-founded", afterClose: resetCarsFounded }), 300);

  // Выбор необходимого автомобиля
  $("#multiple-cars-founded").on("click", ".popup__car", function () {
    $(this).addClass("selected").siblings().removeClass("selected");
    $("#multiple-cars-founded [data-action=choose-car]").removeClass("disabled");
  });

  // Запрос информации о выбранном автомобиле при нажатии на кнопку
  $("#multiple-cars-founded").on("click", ":not(.disabled)[data-action=choose-car]", function () {
    // Определение VIN
    let vin = $("#multiple-cars-founded .popup__car.selected .car__vin").text();
    $("#maintenance-calculator").find(".car-data-input #vin-number").val(vin);

    // Закрытие окна, сброс старых и запрос новых данных об автомобиле
    $.fancybox.close();
    setTimeout(() => {
      resetCarsFounded();
      requestCarInfo_MaintenanceCalculator();
    }, 300);
  });

  // Сброс данных о найденных автомобилях
  function resetCarsFounded() {
    $("#multiple-cars-founded .popup__cars").empty();
    $("#multiple-cars-founded").off();
  }
}

function renderPartsNotFoundError_MaintenanceCalculator() {
  $("#maintenance-calculator .calculator-result .maintenance-parts").hide();
  $("#maintenance-calculator .calculator-result .summary-costs").hide();
  setTimeout(() => $.fancybox.open({ src: "#parts-not-found" }), 300);
}

// Отображение информации об автомобиле
function renderCarInfo_MaintenanceCalculator(carInfo) {
  // Очистка от предыдущих результатов
  $("#maintenance-calculator .car-info").off();
  $("#maintenance-calculator .car-info__image").empty();
  $("#maintenance-calculator .car-info__options-factory").empty();
  $("#maintenance-calculator .car-info__options-installed").empty();

  // Отображение блока для вывода информации
  $("#maintenance-calculator .calculator-result").show();

  // Заполнение данных об автомобиле
  $(".car-info__vin .text").text(carInfo["vin"]);
  $(".car-info__model .text").text(carInfo["model"]);
  $(".car-info__model-code .text").text(carInfo["modelCode"]);
  $(".car-info__production-date .text").text(carInfo["productionDate"]);
  $(".car-info__images").append(`<div class="car-info__image"><img src="${carInfo["image"]}"></a>`);

  // Заполнение заводскими и доустановленными опциями
  if (carInfo["options"]) {
    $("#maintenance-calculator .car-info__options").show();
    $("#maintenance-calculator .car-info .button-area").show();
    $("#maintenance-calculator .car-info__options-factory").append('<div class="car-info__title">Заводские опции</div>');
    for (let option in carInfo["options"]["factory"]) {
      $("#maintenance-calculator .car-info__options-factory").append(
        `<div class="car-info__option" data-code="${option}"><span class="option-code">${option}</span>${carInfo["options"]["factory"][option]}</div>`
      );
    }
    $("#maintenance-calculator .car-info__options-installed").append(
      '<div class="car-info__title">Дополнительно установленные опции</div>'
    );
    if (Object.keys(carInfo["options"]["installed"]).length) {
      for (let option in carInfo["options"]["installed"]) {
        $("#maintenance-calculator .car-info__options-installed").append(
          `<div class="car-info__option" data-code="${option}"><span class="option-code">${option}</span>${carInfo["options"]["installed"][option]}</div>`
        );
      }
    } else {
      $("#maintenance-calculator .car-info__options-installed").append(
        `<div class="car-info__no-options">В автомобиль не устанавливались дополнительные опции</div>`
      );
    }
  } else {
    $("#maintenance-calculator .car-info__options").hide();
    $("#maintenance-calculator .car-info .button-area").hide();
  }

  // Отображение/скрытие всех опций
  $("#maintenance-calculator .car-info").on("click", ".toggle-options", function () {
    let offset = $("#maintenance-calculator .maintenance-parts").offset().top - $(window).scrollTop();
    $("#maintenance-calculator .car-info").find(".car-info__options").toggleClass("hidden");
    $(this).toggleClass("active");
    if ($(this).hasClass("active")) {
      $(this).text("Скрыть опции");
    } else {
      $(this).text("Показать все опции");
      $(window).scrollTop($("#maintenance-calculator .maintenance-parts").offset().top - offset);
    }
  });
}

// Запрос изображений автомобиля
function requestCarImages(carInfo) {
  $.ajax({
    url: "/scripts/php/calculators/car-info.php",
    type: "POST",
    data: { vin: carInfo["vin"], data: "images" },
    success: (carImages) => {
      renderCarImages(carImages);
    },
  });

  // Отображение индикации загрузки изображений
  $("#maintenance-calculator .car-info__images").append(
    '<div class="car-info__image"><div class="image-loading-icon"><div class="image-loading"></div></div><div class="image-loading-text">Загружаются дополнительные изображения автомобиля</div></div>'
  );
}

// Отображение изображений автомобиля
function renderCarImages(carImages) {
  $("#maintetnance-calculator .car-info__images").empty();
  $("#maintetnance-calculator .car-info__images").addClass("two");
  $("#maintetnance-calculator .car-info__images").append(
    `<a class="car-info__image" data-fancybox="car-images" data-src="${carImages["exteriorOriginalImage"]}"><img src="${carImages["exteriorImage"]}"></a>`
  );
  $("#maintetnance-calculator .car-info__images").append(
    `<a class="car-info__image" data-fancybox="car-images" data-src="${carImages["interiorOriginalImage"]}"><img src="${carImages["interiorImage"]}"></a>`
  );
}

// Запрос запчастей для обслуживания
function requestParts(carInfo, mileage) {
  $.ajax({
    url: "/scripts/php/calculators/maintenance-parts.php",
    type: "POST",
    data: { vin: carInfo["vin"], mileage },
    success: (result) => {
      if (!result.error) {
        renderOriginalParts(result);
        renderAlternativeParts(result, 0);
        renderAdditionalWorks(result["additional"], mileage);
      } else if (result.error == "parts-not-found") {
        renderPartsNotFoundError_MaintenanceCalculator();
      }
    },
  });

  // Отображение блока для вывода результатов
  $("#maintenance-calculator .calculator-result").show();
  $("#maintenance-calculator .calculator-result .maintenance-parts").show();
  $("#maintenance-calculator .calculator-result .summary-costs").show();

  // Очистка таблицы
  $("#maintenance-calculator .calculator-result table").hide();
  $("#maintenance-calculator .calculator-result table").empty();
  $("#maintenance-calculator .summary-costs .parts-cost .value").text(formatPrice_MaintenanceCalculator(0, false));
  $("#maintenance-calculator .summary-costs .works-cost .value").text(formatPrice_MaintenanceCalculator(0, false));
  $("#maintenance-calculator .summary-costs .total-cost .value").text(formatPrice_MaintenanceCalculator(0, false));

  // Отображение индикатора загрузки деталей
  $("#maintenance-calculator .parts-loading-icon").show();
}

// Отображение оригинальных запчастей
function renderOriginalParts(result) {
  // Скрытие индикатора загрузки деталей
  $("#maintenance-calculator .parts-loading-icon").hide();

  // Подготовка таблицы
  $("#maintenance-calculator .calculator-result table").append(
    '<tr class="table-header"><td>Наименование детали</td><td>Стоимость детали</td><td>Стоимость работ</td></tr>'
  );

  // Заполнение таблицы деталями для обслуживания
  for (let part in result["parts"]) {
    // Добавляем строку
    $("#maintenance-calculator .calculator-result table").append(
      `<tr class="disabled" data-name="${part}"><td><div class="info"><span class="name">${result["parts"][part]["name"]}</span></div><div class="options"></div></div></div></td><td class="part-price"><span class="price"></span></td><td class="work-price"></td></tr>`
    );

    // Номер детали
    if (result["parts"][part]["number"]) {
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "] .info")
        .append('<span class="number">' + result["parts"][part]["number"] + "</span>");
    }

    // Стоимость работ
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "]")
      .attr("data-work-price", result["parts"][part]["work"]);
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "] .work-price")
      .text(formatPrice_MaintenanceCalculator(result["parts"][part]["work"]));
    if (result["parts"][part]["initialWork"] !== undefined)
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "]")
        .attr("data-initial-work-price", result["parts"][part]["initialWork"]);

    // Опции

    // Если указаны в оригинальных деталях, отображаем особые опции
    if (result["parts"][part]["options"]) {
      for (let option in result["parts"][part]["options"]) {
        $("#maintenance-calculator .calculator-result table")
          .find("[data-name=" + part + "] .options")
          .append(
            '<div class="option" data-name="' +
              option +
              '" data-part-price="' +
              result["parts"][part]["options"][option]["price"] +
              '">' +
              result["parts"][part]["options"][option]["name"] +
              "</div>"
          );
      }

      // Если среди оригинальных опций не было, указываем просто "Оригинальный"
    } else {
      let originalName = part == "sparkPlug" ? "Оригинальные" : "Оригинальный";
      let price = result["parts"][part]["price"] == undefined ? "" : result["parts"][part]["price"];
      $("#maintenance-calculator .calculator-result table")
        .find(`[data-name="${part}"] .options`)
        .append(
          `<div class="option" data-name="original" data-number="${result["parts"][part]["number"]}" data-part-price="${price}">${originalName}</div>`
        );
    }

    // Делаем первую опцию активной по-умолчанию и устанавливаем её стоимость
    let firstOptionPrice = $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "] .option:first-child")
      .attr("data-part-price");
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "] .options .option:first-child")
      .addClass("selected");
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "]")
      .attr("data-part-price", firstOptionPrice);
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "] .part-price .price")
      .text(formatPrice_MaintenanceCalculator(firstOptionPrice));

    // Количество
    if (result["parts"][part]["quantity"]) {
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "]")
        .attr("data-quantity", result["parts"][part]["quantity"]);
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "]")
        .attr("data-quantity-label", result["parts"][part]["quantityLabel"]);
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "] .info")
        .append('<span class="quantity"> ' + result["parts"][part]["quantity"] + result["parts"][part]["quantityLabel"] + "</span>");
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "] .part-price")
        .prepend('<span class="quantity">' + result["parts"][part]["quantity"] + " x </span>");
    }

    // Отмечаем детали, которые должны быть отмечены по-умолчанию
    switch (part) {
      case "motorOil":
      case "oilFilter":
      case "sparkPlug":
      case "fuelFilter":
      case "airFilter":
      case "cabinAirFilter":
      case "recirculationCabinAirFilter":
        $("#maintenance-calculator .calculator-result table tr[data-name=" + part + "]").removeClass("disabled");
        break;
    }
  }

  // Отображение таблицы
  setTimeout(() => $("#maintenance-calculator .calculator-result table").fadeIn(300), 0);

  // Отображение индикации загрузки деталей-аналогов
  $("#maintenance-calculator .calculator-result").find(".original-or-alternative .option[data-name=alternative]").addClass("loading");

  // Расчёт итоговых стоимостей
  calculateCosts();
}

// Подбор и отображение деталей-аналогов
function renderAlternativeParts(result, partIndex) {
  if (partIndex >= Object.values(result["parts"]).length) {
    $("#maintenance-calculator .calculator-result").find(".original-or-alternative .option[data-name=alternative]").removeClass("loading");
    return;
  }
  let part = Object.keys(result["parts"])[partIndex];

  // Подбор деталей-аналогов, если есть оригинальный номер
  if (typeof result["parts"][part]["number"] === "string") {
    // Отображение анимации загрузки
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "] .options")
      .addClass("loading");

    // Отправка запроса
    $.ajax({
      url: "/scripts/php/calculators/alternative-parts.php",
      type: "GET",
      data: { number: result["parts"][part]["number"].replace(/\s/g, "") },
      success: (data) => {
        // Обработка результатов
        data = JSON.parse(data.substring(data.indexOf("{"), data.indexOf("</pre>")));
        result["parts"][part]["options"] = data;

        // Если аналогов не найдено, вывод соответствующей надписи
        if (Object.keys(result["parts"][part]["options"])[0] === "no-alternatives") {
          $("#maintenance-calculator .calculator-result table")
            .find("[data-name=" + part + "] .options")
            .addClass("no-alternatives");

          // Если были найдены детали-аналоги
        } else {
          // Добавление опций
          for (let option in result["parts"][part]["options"]) {
            $("#maintenance-calculator .calculator-result table")
              .find("[data-name=" + part + "] .options")
              .append(
                '<div class="option" data-name="' +
                  option +
                  '" data-number="' +
                  result["parts"][part]["options"][option]["number"] +
                  '" data-part-price="' +
                  result["parts"][part]["options"][option]["price"] * 1.3 +
                  '">' +
                  result["parts"][part]["options"][option]["name"] +
                  "</div>"
              );
          }

          // Плавное появление опций
          let options = $("#maintenance-calculator .calculator-result table")
            .find("[data-name=" + part + "]")
            .find(".option:not(:first-child)");
          options.css("opacity", 0);
          options.animate({ opacity: 1 }, 300, function () {
            options.css("opacity", "");
          });
        }

        // Скрытие анимации загрузки
        $("#maintenance-calculator .calculator-result table")
          .find("[data-name=" + part + "] .options")
          .removeClass("loading");

        // Переход к заполнению следующей строки
        renderAlternativeParts(result, ++partIndex);
      },
    });

    // Если же оригинального номера детали нет, то прямой переход к следующей детали
  } else {
    renderAlternativeParts(result, ++partIndex);
  }
}

// Отображение дополнительных работ
function renderAdditionalWorks(additional, mileage) {
  if (Object.keys(additional).length) {
    $("#maintenance-calculator .calculator-result table").append(
      '<tr><td class="section" colspan="3">При пробеге около ' +
        (Math.round(mileage / 10000) * 10000).toLocaleString("ru") +
        " км также рекомендуются следующие работы:</td</tr>"
    );

    for (let item in additional) {
      // Добавляем строку
      $("#maintenance-calculator .calculator-result table").append(
        '<tr class="disabled" data-name=' +
          item +
          '><td><div class="info"><span class="name">' +
          additional[item]["name"] +
          '</span</div></td><td class="part-price"><span class="price"></span></td><td class="work-price"></td></tr>'
      );
      let thisPart = $("#maintenance-calculator .calculator-result table").find("tr[data-name=" + item + "]");

      // Цену детали
      if (additional[item]["price"] !== undefined) {
        thisPart.attr("data-part-price", additional[item]["price"]);
        thisPart.find(".part-price .price").text(formatPrice_MaintenanceCalculator(additional[item]["price"]));
      }
      // } else {
      //   thisPart.attr('data-part-price', 0);
      // }

      // Стоимость работ
      if (additional[item]["work"] !== undefined) {
        thisPart.attr("data-work-price", additional[item]["work"]);
        thisPart.find(".work-price").text(formatPrice_MaintenanceCalculator(additional[item]["work"]));
        if (additional[item]["initialWork"] !== undefined) thisPart.attr("data-initial-work-price", additional[item]["initialWork"]);
      }

      // Количество
      if (additional[item]["quantity"] !== undefined) {
        thisPart.attr("data-quantity", additional[item]["quantity"]);
        thisPart.attr("data-quantity-label", additional[item]["quantityLabel"]);
        thisPart
          .find(".info")
          .append('<span class="quantity">' + additional[item]["quantity"] + additional[item]["quantityLabel"] + "</span>");
        thisPart.find(".part-price").prepend('<span class="quantity">' + additional[item]["quantity"] + " x </span>");
      }
    }

    specialConditions();
    calculateCosts();
  }
}

// Форматирование цены
function formatPrice_MaintenanceCalculator(number, zeroShouldMeanFree) {
  if (number === "") return "Отсутствует";
  else number = +number;
  if (number !== 0 || zeroShouldMeanFree === false) {
    return number.toLocaleString("ru", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
      style: "currency",
      currency: "RUB",
    });
  } else return "Бесплатно";
}

// Переключение опций
function chooseOption(option, e) {
  // Переключение опции
  e.stopPropagation();
  option.parent().find(".option").removeClass("selected");
  option.addClass("selected");

  // Замена номера детали
  if (option.attr("data-number") !== undefined) {
    let newNumber = option.attr("data-number");
    option
      .parent()
      .siblings(".info")
      .find(".number")
      .fadeOut(300, function () {
        $(this).text(newNumber);
        $(this).fadeIn(300);
      });
  }

  // Замена цены детали
  let newPrice = option.attr("data-part-price");
  option.parent().parent().parent().attr("data-part-price", newPrice);
  option
    .parent()
    .parent()
    .parent()
    .find(".part-price .price")
    .fadeOut(300, function () {
      $(this).text(formatPrice_MaintenanceCalculator(newPrice));
      $(this).fadeIn(300);
    });

  // Перерасчёт общих стоимостей
  calculateCosts();
  return;
}

function specialConditions() {
  let isOneMainJobActive = false;

  // Проход по всем позициям и проверка на наличие необходимых спецпредложений
  $("#maintenance-calculator .calculator-result tr[data-name]").each(function () {
    // Проверка выбранности любой из основных работ
    if (
      $(this).attr("data-name") == "motorOil" ||
      $(this).attr("data-name") == "oilFilter" ||
      $(this).attr("data-name") == "sparkPlug" ||
      $(this).attr("data-name") == "fuelFilter" ||
      $(this).attr("data-name") == "airFilter" ||
      $(this).attr("data-name") == "cabinAirFilter" ||
      $(this).attr("data-name") == "recirculationFilter" ||
      $(this).attr("data-name") == "frontBrakeDisk" ||
      $(this).attr("data-name") == "frontBrakePads" ||
      $(this).attr("data-name") == "frontBrakePadsWearSensor" ||
      $(this).attr("data-name") == "rearBrakeDisk" ||
      $(this).attr("data-name") == "rearBrakePads" ||
      $(this).attr("data-name") == "rearBrakePadsWearSensor"
    )
      if (!$(this).hasClass("disabled")) isOneMainJobActive = true;

    // При замене дисков, бесплатная замена соответствующих колодок

    // Поиск дисков
    if ($(this).attr("data-name") == "frontBrakeDisk" || $(this).attr("data-name") == "rearBrakeDisk") {
      // Определение соответствующих тормозных колодок
      let brakePads = $(this).attr("data-name").replace(/Disk/g, "Pads");
      brakePads = $("#maintenance-calculator .calculator-result").find("[data-name=" + brakePads + "]");

      // При выбранных дисках...
      if (!$(this).hasClass("disabled")) {
        // ...и при условии, что у колодок текущая цена работ совпадает с изначальной
        if (brakePads.attr("data-work-price") == brakePads.attr("data-initial-work-price")) {
          // Установка текущей цены равной нулю
          brakePads.attr("data-work-price", 0);
          brakePads.find(".work-price").fadeOut(300, function () {
            $(this).text(formatPrice_MaintenanceCalculator(0));
            $(this).fadeIn(300);
          });
        }

        // При невыбранных дисках...
      } else {
        // ... и при условии, что текущая цена работ равна нулю
        if (brakePads.attr("data-work-price") == 0) {
          // Возвращение изначальной цены
          let initialPrice = Number(brakePads.attr("data-initial-work-price"));
          brakePads.attr("data-work-price", initialPrice);
          brakePads.find(".work-price").fadeOut(300, function () {
            $(this).text(formatPrice_MaintenanceCalculator(initialPrice));
            $(this).fadeIn(300);
          });
        }
      }
    }

    // При выборе мойки радиатора, стоимость замены охлаждающей жидкости составляет 2 100, вместо 3 000

    // Поиск мойки радиаторов
    if ($(this).attr("data-name") == "radiatorsWash") {
      let coolant = $("#maintenance-calculator .calculator-result table").find("tr[data-name=coolant]");

      // При выбранной мойке радиаторов...
      if (!$(this).hasClass("disabled")) {
        // ... и при условии, что текущая цена работ совпадает с изначальной
        if (coolant.attr("data-work-price") == coolant.attr("data-initial-work-price")) {
          // Установка цены равной 2100
          coolant.attr("data-work-price", 2100);
          coolant.find(".work-price").fadeOut(300, function () {
            $(this).text(formatPrice_MaintenanceCalculator(2100));
            $(this).fadeIn(300);
          });
        }
        // При невыбранной мойке радиаторов...
      } else {
        // ... и при условии, что текущая цена работ составляет 2 100
        if (coolant.attr("data-work-price") == 2100) {
          // Возвращение изначальной цены
          let initialPrice = Number(coolant.attr("data-initial-work-price"));
          coolant.attr("data-work-price", initialPrice);
          coolant.find(".work-price").fadeOut(300, function () {
            $(this).text(formatPrice_MaintenanceCalculator(initialPrice));
            $(this).fadeIn(300);
          });
        }
      }
    }
  });

  // При выборе любой основной работы, бесплатная общая диагностика автомобиля
  let carDiagnostics = $("#maintenance-calculator .calculator-result table").find("tr[data-name=carDiagnostics]");

  // Если выбрана любая основная работа
  if (isOneMainJobActive) {
    // ... и текущая цена общей диагностики совпадает с изначальной
    if (carDiagnostics.attr("data-work-price") == carDiagnostics.attr("data-initial-work-price")) {
      // Установка текущей цены равной нулю
      carDiagnostics.attr("data-work-price", 0);
      carDiagnostics.find(".work-price").fadeOut(300, function () {
        $(this).text(formatPrice_MaintenanceCalculator(0));
        $(this).fadeIn(300);
      });
    }

    // Если ни одна основная работа не выбрана
  } else {
    // ... и текущая цена общей диагностики равняется нулю
    if (carDiagnostics.attr("data-work-price") == 0) {
      // Установка текущей цены равной нулю
      let initialPrice = Number(carDiagnostics.attr("data-initial-work-price"));
      carDiagnostics.attr("data-work-price", initialPrice);
      carDiagnostics.find(".work-price").fadeOut(300, function () {
        $(this).text(formatPrice_MaintenanceCalculator(initialPrice));
        $(this).fadeIn(300);
      });
    }
  }
}

// Расчёт итоговых стоимостей деталей и работ
function calculateCosts() {
  // Сброс стоимости всех деталей и работ
  let partsCost = 0,
    worksCost = 0;

  // Расчёт стоимости выбранных деталей и работ
  $("#maintenance-calculator .calculator-result tr[data-name]:not(.disabled)").each(function () {
    let partPrice = $(this).attr("data-part-price");
    let quantity = $(this).attr("data-quantity");
    if (partPrice !== undefined) {
      if (quantity) {
        partsCost += Number($(this).attr("data-part-price")) * Number($(this).attr("data-quantity"));
      } else {
        partsCost += Number($(this).attr("data-part-price"));
      }
    }
    worksCost += Number($(this).attr("data-work-price"));
  });

  // Вывод результатов
  if ($(".summary-costs .parts-cost .value").text() != formatPrice_MaintenanceCalculator(partsCost)) {
    $(".summary-costs .parts-cost .value").fadeOut(300, function () {
      $(this).text(formatPrice_MaintenanceCalculator(partsCost, false));
      $(this).fadeIn(300);
    });
  }
  if ($(".summary-costs .works-cost .value").text() != formatPrice_MaintenanceCalculator(worksCost)) {
    $(".summary-costs .works-cost .value").fadeOut(300, function () {
      $(this).text(formatPrice_MaintenanceCalculator(worksCost, false));
      $(this).fadeIn(300);
    });
  }
  if ($(".summary-costs .total-cost .value").text() != formatPrice_MaintenanceCalculator(partsCost + worksCost)) {
    $(".summary-costs .total-cost .value").fadeOut(300, function () {
      $(this).text(formatPrice_MaintenanceCalculator(partsCost + worksCost, false));
      $(this).fadeIn(300);
    });
  }
}
