// Подбор запчастей при передаче данных в адресе страницы
if (window.location.search && $("#maintenance-calculator").length) {
  let params = new URL(window.location.href).searchParams;
  if (params.get("vin") != undefined && params.get("mileage") != undefined) {
    $("#maintenance-calculator").find("#vin-number").val(params.get("vin"));
    $("#maintenance-calculator").find("#mileage").val(params.get("mileage"));
    if (vinCorrect()) requestCarInfo(params.get("admin") === "true");
  }
}

// Подбор запчастей при ручном вводе VIN-номера
$("#maintenance-calculator")
  .find(".car-data-input")
  .on("submit", function (e) {
    e.preventDefault();
    let params = new URL(window.location.href).searchParams;
    if (vinCorrect()) requestCarInfo(params.get("admin") === "true");
  });

// Переключение между оригинальными и неоригинальными деталями
$("#maintenance-calculator .calculator-result .original-or-alternative").on("click", ".option:not(.loading)", function (e) {
  $("#maintenance-calculator .calculator-result .original-or-alternative .option").removeClass("selected");
  $(this).addClass("selected");

  // При клике по кнопке "Оригинальные", скрываем лишние аналоги и переключаемся на оригинальные запчасти
  if ($(this).attr("data-name") == "original") {
    $("#maintenance-calculator .calculator-result table").find("tr .option:not([data-favorite=true])").addClass("hidden");
    $("#maintenance-calculator .calculator-result table").find("tr .option:first-child").each(chooseOption);

    // При клике по кнопке "Рекомендуемые аналоги", скрываем лишние аналоги и выбираем первый из рекомендуемых аналогов
  } else if ($(this).attr("data-name") == "alternative") {
    $("#maintenance-calculator .calculator-result table").find("tr .option:not([data-favorite=true])").addClass("hidden");
    $("#maintenance-calculator .calculator-result table")
      .find("tr")
      .has(".options")
      .each(function () {
        $(this).find(".option[data-favorite=true]:not([data-name=original])").first().each(chooseOption);
      });

    // При клике по кнопке "Все аналоги", показываем все аналоги и выбираем первый из аналогов
  } else if ($(this).attr("data-name") == "all") {
    $("#maintenance-calculator .calculator-result table").find("tr .option").removeClass("hidden");
    $("#maintenance-calculator .calculator-result table").find("tr .option:nth-child(2)").each(chooseOption);
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

  // При выборе замены масла, автоматический выбор замены масляного фильтра

  if ($(this).attr("data-name") == "motorOil")
    if (!$(this).hasClass("disabled"))
      $("#maintenance-calculator .calculator-result").find('[data-name="oilFilter"]').removeClass("disabled");

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
$("#maintenance-calculator .calculator-result table").on("click", "tr:not(.disabled) .option", chooseOption);

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

  let vin = $("#vin-number").val();
  let properLength = vin.length == 7 || vin.length == 17;
  let latinLetters = vin.match(/^[A-Za-z\d]+$/);

  if (!properLength) {
    $("#vin-number").after('<div class="input-field__error error_vin-number">Необходимо ввести 7 или 17 знаков</div>');
    return false;
  } else if (!latinLetters) {
    $("#vin-number").after('<div class="input-field__error error_vin-number">Необходимо использовать латинские буквы</div>');
    return false;
  } else {
    return true;
  }
}

// Основная функция, запускающая поиск деталей для обслуживания
function requestCarInfo(admin = false) {
  // Применение режима админа
  if (admin) $("#maintenance-calculator").addClass("with-numbers");

  // Скрытие быстрых кнопок
  hideSpecialButtons();

  // Сбор данных из формы
  let vin = $("#maintenance-calculator").find(".car-data-input #vin-number").val();
  let mileage = $("#maintenance-calculator").find(".car-data-input #mileage").val();
  let aos = $("#maintenance-calculator").hasClass("aos");

  // Изменение адреса страницы
  history.pushState(null, null, "?vin=" + vin + "&mileage=" + mileage + (admin ? "&admin=true" : ""));

  // Открытие всплывающего окна загрузки
  $.fancybox.open({ src: "#calculation", opts: { modal: true } });

  // Запрос информации об автомобиле
  $.ajax({
    url: "/scripts/php/calculators/car-info.php",
    type: "POST",
    data: { vin, from: aos ? "aos" : undefined },
    success: (carInfo) => {
      $.fancybox.close();
      if (!carInfo.error) {
        renderCarInfo(carInfo);
        requestParts(carInfo, mileage);
        requestCarImages(carInfo);
      } else if (carInfo.error == "car-info-not-found") {
        renderCarInfoNotFoundError();
      } else if (carInfo.error == "vin-not-found") {
        renderVinNotFoundError();
      } else if (carInfo.error == "multiple-cars-founded") {
        renderMultipleCarsFoundedError(carInfo);
      }
    },
  });
}

// Отображение ошибки об отсутствии найденной информации
function renderCarInfoNotFoundError() {
  setTimeout(() => $.fancybox.open({ src: "#car-info-not-found" }), 300);
}

// Отображение ошибки об отсутствии VIN
function renderVinNotFoundError() {
  setTimeout(() => $.fancybox.open({ src: "#vin-not-found" }), 300);
}

// Отображение ошибки о нескольких найденных автомобилях
function renderMultipleCarsFoundedError({ cars }) {
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
    let params = new URL(window.location.href).searchParams;
    setTimeout(() => {
      resetCarsFounded();
      requestCarInfo(params.get("admin") === "true");
    }, 300);
  });

  // Сброс данных о найденных автомобилях
  function resetCarsFounded() {
    $("#multiple-cars-founded .popup__cars").empty();
    $("#multiple-cars-founded").off();
  }
}

function renderPartsNotFoundError() {
  $("#maintenance-calculator .calculator-result .maintenance-parts").hide();
  $("#maintenance-calculator .calculator-result .summary-costs").hide();
  setTimeout(() => $.fancybox.open({ src: "#parts-not-found" }), 300);
}

// Отображение информации об автомобиле
function renderCarInfo(carInfo) {
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
      if (!carImages.error) renderCarImages(carImages);
      else if (carImages.error == "images-not-found") $("#maintenance-calculator .car-info__images .image-loading-icon").parent().remove();
    },
  });

  // Отображение индикации загрузки изображений
  $("#maintenance-calculator .car-info__images").append(
    '<div class="car-info__image"><div class="image-loading-icon"><div class="image-loading"></div></div><div class="image-loading-text">Загружаются дополнительные изображения автомобиля</div></div>'
  );
}

// Отображение изображений автомобиля
function renderCarImages(carImages) {
  $("#maintenance-calculator .car-info__images").empty();
  $("#maintenance-calculator .car-info__images").addClass("two");
  $("#maintenance-calculator .car-info__images").append(
    `<a class="car-info__image" data-fancybox="car-images" data-src="${carImages["exteriorOriginalImage"]}"><img src="${carImages["exteriorImage"]}"></a>`
  );
  $("#maintenance-calculator .car-info__images").append(
    `<a class="car-info__image" data-fancybox="car-images" data-src="${carImages["interiorOriginalImage"]}"><img src="${carImages["interiorImage"]}"></a>`
  );
}

// Запрос запчастей для обслуживания
function requestParts(carInfo, mileage) {
  let aos = $("#maintenance-calculator").hasClass("aos");
  let from = aos ? "aos" : "cats";
  $.ajax({
    url: `/scripts/php/calculators/maintenance-parts-${from}.php`,
    type: "POST",
    data: { vin: carInfo["vin"], mileage },
    success: (result) => {
      if (!result.error) {
        renderOriginalParts(result);
        renderAlternativeParts(result, 0);
        renderAdditionalWorks(result["additional"], mileage);
      } else if (result.error == "parts-not-found") {
        renderPartsNotFoundError();
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
  $("#maintenance-calculator .summary-costs .parts-cost .value").text(formatPrice(0, false));
  $("#maintenance-calculator .summary-costs .works-cost .value").text(formatPrice(0, false));
  $("#maintenance-calculator .summary-costs .total-cost .value").text(formatPrice(0, false));

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
      `<tr class="disabled" data-name="${part}"><td colspan="2"><div class="info"><span class="name">${result["parts"][part]["name"]}</span></div><div class="options"></div></div></div></td><td class="work-price"></td></tr>`
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
      .text(formatPrice(result["parts"][part]["work"]));
    if (result["parts"][part]["initialWork"] !== undefined)
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "]")
        .attr("data-initial-work-price", result["parts"][part]["initialWork"]);

    // Опции

    // Если указаны в оригинальных деталях, отображаем особые опции
    if (result["parts"][part]["options"]) {
      for (let optionKey in result["parts"][part]["options"]) {
        let option = result["parts"][part]["options"][optionKey];
        let { name, price } = option;
        let formattedPrice = formatPrice(price);
        $("#maintenance-calculator .calculator-result table")
          .find("[data-name=" + part + "] .options")
          .append(
            `<div class="option" data-name="${optionKey}" data-part-price="${price}" data-favorite="true"><span class="part">${name}</span><span class="price">${formattedPrice}</span></div>`
          );
      }

      // Если среди оригинальных опций не было, указываем просто "Оригинал"
    } else {
      let originalName = "Оригинал";
      let { number, from } = result["parts"][part];
      let price = result["parts"][part]["price"] == undefined ? "" : result["parts"][part]["price"];
      let formattedPrice = formatPrice(price);
      $("#maintenance-calculator .calculator-result table")
        .find(`[data-name="${part}"] .options`)
        .append(
          `<div class="option" data-name="original" data-number="${number}" data-part-price="${price}" data-from="${from}" data-favorite="true"><span class="part"><div class="text">${originalName}</div><div class="info-button"></div><div class="info">Поставщик: ${from}</div></span><span class="price">${formattedPrice}</span></div>`
        );
    }

    // Делаем первую опцию активной по-умолчанию и устанавливаем её стоимость
    let firstOptionPrice = $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "] .options .option:first-child")
      .addClass("selected")
      .attr("data-part-price");
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "]")
      .attr("data-part-price", firstOptionPrice);
    $("#maintenance-calculator .calculator-result table")
      .find("[data-name=" + part + "] .part-price .price")
      .text(formatPrice(firstOptionPrice));

    // Количество
    if (result["parts"][part]["quantity"]) {
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "]")
        .attr("data-quantity", result["parts"][part]["quantity"]);
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "]")
        .attr("data-quantity-label", result["parts"][part]["quantityLabel"]);
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "] td > .info")
        .append('<span class="quantity"> ' + result["parts"][part]["quantity"] + result["parts"][part]["quantityLabel"] + "</span>");
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "] .option .price")
        .prepend('<span class="quantity">' + result["parts"][part]["quantity"] + " x </span>");
    }

    // Дополнения
    if (result["parts"][part]["additional"]?.length) {
      let additionalText = result["parts"][part]["additional"].map(({ name, price }) => `${name} (${formatPrice(price)})`).join(", ");
      let additionalPrice = result["parts"][part]["additional"].reduce((result, { price }) => (result += price), 0);

      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "] .options")
        .after(`<div class="additional">+ ${additionalText}</div>`);
      $("#maintenance-calculator .calculator-result table")
        .find("[data-name=" + part + "] ")
        .attr("data-additional-price", additionalPrice);
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
  $("#maintenance-calculator .calculator-result").find(".original-or-alternative .option[data-name=all]").addClass("loading");

  // Расчёт итоговых стоимостей
  specialConditions();
  calculateCosts();
}

// Подбор и отображение деталей-аналогов
function renderAlternativeParts(result, partIndex) {
  if (partIndex >= Object.values(result["parts"]).length) {
    $("#maintenance-calculator .calculator-result").find(".original-or-alternative .option[data-name=alternative]").removeClass("loading");
    $("#maintenance-calculator .calculator-result").find(".original-or-alternative .option[data-name=all]").removeClass("loading");
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
      data: {
        number: result["parts"][part]["number"].replace(/\s/g, ""),
        // onlyFavorites: true,
      },
      success: (data) => {
        // Обработка результатов
        result["parts"][part]["options"] = data;

        // Если аналогов не найдено, вывод соответствующей надписи
        if (result["parts"][part]["options"].error === "no-parts") {
          $("#maintenance-calculator .calculator-result table")
            .find("[data-name=" + part + "] .options")
            .addClass("no-alternatives");

          // Если были найдены детали-аналоги
        } else {
          // Добавление опций
          for (let optionKey in result["parts"][part]["options"]) {
            let option = result["parts"][part]["options"][optionKey];
            let { name, number, price, from, favorite } = option;
            let favoriteAttr = favorite ? `data-favorite="${favorite}"` : "";
            let formattedPrice = formatPrice(price);

            $("#maintenance-calculator .calculator-result table")
              .find("[data-name=" + part + "] .options")
              .append(
                `<div class="option" data-name="${optionKey}" data-number="${number}" data-part-price="${price}" data-from="${from}" ${favoriteAttr}><span class="part"><div class="text">${name}</div><div class="info-button"></div><div class="info">Поставщик: ${from}</div></span><span class="price">${formattedPrice}</span></div>`
              );

            // Скрытие лишних аналогов
            if (!favorite)
              $("#maintenance-calculator .calculator-result table")
                .find(`[data-name="${part}"] .option[data-name="${optionKey}"]`)
                .addClass("hidden");

            // Указание количества
            if (result["parts"][part]["quantity"])
              $("#maintenance-calculator .calculator-result table")
                .find("[data-name=" + part + "] .option[data-name=" + optionKey + "] .price")
                .prepend('<span class="quantity">' + result["parts"][part]["quantity"] + " x </span>");
          }

          // Расчёт итоговых стоимостей
          calculateCosts();

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
        thisPart.find(".part-price .price").text(formatPrice(additional[item]["price"]));
      }
      // } else {
      //   thisPart.attr('data-part-price', 0);
      // }

      // Стоимость работ
      if (additional[item]["work"] !== undefined) {
        thisPart.attr("data-work-price", additional[item]["work"]);
        thisPart.find(".work-price").text(formatPrice(additional[item]["work"]));
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
function formatPrice(number, zeroShouldMeanFree) {
  if (number === "" || number == null) return "Отсутствует";
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
function chooseOption(e) {
  let option = $(this);

  // Переключение опции
  e.stopPropagation?.();
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
      $(this).text(formatPrice(newPrice));
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

    // При замене масла, бесплатная замена масляного фильтра

    if ($(this).attr("data-name") == "motorOil") {
      let oilFilter = $('#maintenance-calculator .calculator-result [data-name="oilFilter"]');

      if (!$(this).hasClass("disabled") && oilFilter.attr("data-work-price") == oilFilter.attr("data-initial-work-price")) {
        oilFilter.attr("data-work-price", 0);
        oilFilter.find(".work-price").fadeOut(300, function () {
          $(this).text(formatPrice(0));
          $(this).fadeIn(300);
        });
      } else if ($(this).hasClass("disabled") && oilFilter.attr("data-work-price") == 0) {
        let initialPrice = Number(oilFilter.attr("data-initial-work-price"));
        oilFilter.attr("data-work-price", initialPrice);
        oilFilter.find(".work-price").fadeOut(300, function () {
          $(this).text(formatPrice(initialPrice));
          $(this).fadeIn(300);
        });
      }
    }

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
            $(this).text(formatPrice(0));
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
            $(this).text(formatPrice(initialPrice));
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
            $(this).text(formatPrice(2100));
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
            $(this).text(formatPrice(initialPrice));
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
        $(this).text(formatPrice(0));
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
        $(this).text(formatPrice(initialPrice));
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
    let additionalPrice = $(this).attr("data-additional-price");
    let quantity = $(this).attr("data-quantity");

    if (partPrice !== undefined) partsCost += +partPrice * (quantity ? +quantity : 1);
    if (additionalPrice !== undefined) partsCost += +additionalPrice;
    worksCost += +$(this).attr("data-work-price");
  });

  // Вывод результатов
  if ($(".summary-costs .parts-cost .value").text() != formatPrice(partsCost)) {
    $(".summary-costs .parts-cost .value").fadeOut(300, function () {
      $(this).text(formatPrice(partsCost, false));
      $(this).fadeIn(300);
    });
  }
  if ($(".summary-costs .works-cost .value").text() != formatPrice(worksCost)) {
    $(".summary-costs .works-cost .value").fadeOut(300, function () {
      $(this).text(formatPrice(worksCost, false));
      $(this).fadeIn(300);
    });
  }
  if ($(".summary-costs .total-cost .value").text() != formatPrice(partsCost + worksCost)) {
    $(".summary-costs .total-cost .value").fadeOut(300, function () {
      $(this).text(formatPrice(partsCost + worksCost, false));
      $(this).fadeIn(300);
    });
  }

  // Обновление текстового расчёта
  calculateMaintenancePartsText();
}

// Выичсление текстового расчёта
function calculateMaintenancePartsText() {
  let text = "Расчёт стоимости техобслуживания:\n\n",
    originalPartsCost = 0,
    alternativePartsCost = 0,
    worksCost = 0;

  $("#maintenance-calculator .calculator-result tr[data-name]:not(.disabled)").each(function () {
    let name = $(this).find(".info .name").text();
    let quantity = $(this).attr("data-quantity");
    let workPrice = $(this).attr("data-work-price");
    let originalPartPrice = $(this).find('.options .option[data-name="original"]').attr("data-part-price");

    let alternativePartPrice;
    $(this)
      .find(".options .option")
      .each(function () {
        let price = $(this).attr("data-part-price");
        if ($(this).attr("data-name") === "original") return;
        if (alternativePartPrice == undefined || Number(price) < alternativePartPrice) alternativePartPrice = Number(price);
      });

    let formattedOriginalPartPrice = formatPrice(originalPartPrice).toLowerCase();
    let formattedAlternativePartPrice = formatPrice(alternativePartPrice).toLowerCase();
    let formattedWorkPrice = formatPrice(workPrice).toLowerCase();

    quantity && (name += "," + $(this).find(".info .quantity").text());
    quantity && (formattedOriginalPartPrice = `${quantity} x ` + formattedOriginalPartPrice);
    quantity && (formattedAlternativePartPrice = `${quantity} x ` + formattedAlternativePartPrice);

    text += `• ${name}. Оригинал — ${formattedOriginalPartPrice}, `.replace(/\.+/g, ".");
    if (alternativePartPrice) text += `аналог — ${formattedAlternativePartPrice}, `;
    text += `работы — ${formattedWorkPrice}\n`;

    if (originalPartPrice) originalPartsCost += quantity ? Number(quantity) * Number(originalPartPrice) : Number(originalPartPrice);
    if (alternativePartPrice)
      alternativePartsCost += quantity ? Number(quantity) * Number(alternativePartPrice) : Number(alternativePartPrice);

    worksCost += Number(workPrice);
  });
  text += "\n";

  const formattedOriginalPartsCost = formatPrice(originalPartsCost, false);
  const formattedAlternativePartsCost = formatPrice(alternativePartsCost, false);
  const formattedWorksCost = formatPrice(worksCost, false);

  text += `Оригинальные запчасти — ${formattedOriginalPartsCost}\n`;
  text += `Аналоги — ${formattedAlternativePartsCost}\n\n`;
  text += `Работы — ${formattedWorksCost}`;

  $(".popup#maintenance-parts-text .content").fadeOut(300, function () {
    $(this).text(text);
    $(this).fadeIn(300);
  });
}

$(".popup#maintenance-parts-text").on("click", '[data-action="copy-maintenance-parts-text"]', async function () {
  let text = $(".popup#maintenance-parts-text .content").text();
  await navigator.clipboard.writeText(text);
  $.fancybox.close();
});
