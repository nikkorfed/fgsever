import { hideSpecialButtons } from "./footer.js";

// Подбор опций при передаче VIN в адресе страницы
if (window.location.search && $("#upgrade-calculator").length) {
  let data = window.location.search.substr(1).split("&"),
    result = {},
    keyAndValue;
  data.forEach(function (item) {
    keyAndValue = item.split("=");
    result[keyAndValue[0]] = keyAndValue[1];
  });
  if (result["vin"] !== undefined) {
    $("#upgrade-calculator").find("#vin-number").val(result["vin"]);
    requestCarInfo();
  }
}

// Подбор опций при ручном вводе VIN-номера
$("#upgrade-calculator")
  .find(".car-data-input")
  .on("submit", function (e) {
    e.preventDefault();
    if (vinCorrect()) {
      requestCarInfo();
    }
  });

// Переход на страницу с подробным описанием опции
$("#services-options .services-item__meta .button-simple").on("click", (e) => {
  e.stopPropagation();
  e.preventDefault();
  window.open(e.target.href, "_blank").focus();
});

// Отображение предупреждения при выборе примерных опций
$("#services-options .services-item").on("click", () => $.fancybox.open({ src: "#use-calculator" }));
$(".popup").on("click", "[data-action=go-to-calculator]", () => {
  $.fancybox.close();
  $("html,body")
    .stop()
    .animate({ scrollTop: $("#upgrade-calculator").offset().top - 60 });
});

// --- Основные функции --- //

function vinCorrect() {
  $(".error_vin-number").remove();
  if ($("#vin-number").val().length == 7 || $("#vin-number").val().length == 17) {
    return true;
  } else {
    $("#vin-number").after('<div class="input-field__error error_vin-number">Необходимо ввести 7 или 17 знаков</div>');
    return false;
  }
}

// Запрос информации об автомобиле
function requestCarInfo() {
  // Скрытие быстрых кнопок
  hideSpecialButtons();

  // Взятие VIN из формы
  let vin = $("#upgrade-calculator").find(".car-data-input #vin-number").val();
  let aos = $("#upgrade-calculator").hasClass("aos");

  // Изменение адреса страницы
  history.pushState(null, null, "?vin=" + vin);

  // Открытие всплывающего окна загрузки
  $.fancybox.open({ src: "#calculation", opts: { modal: true } });

  // Очистка от предыдущих результатов
  $("#upgrade-calculator .calculator-result .category .tabs").empty();
  $("#upgrade-calculator .calculator-result .upgrade-options").empty();

  // Запрос информации об автомобиле
  $.ajax({
    url: "/scripts/php/calculators/car-info.php",
    type: "POST",
    data: { vin, from: aos ? "aos" : undefined },
    success: (carInfo) => {
      $.fancybox.close();
      if (!carInfo.error) {
        renderCarInfo(carInfo);
        requestUpgradeOptions(carInfo);
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
    $("#upgrade-calculator").find(".car-data-input #vin-number").val(vin);

    // Закрытие окна, сброс старых и запрос новых данных об автомобиле
    $.fancybox.close();
    setTimeout(() => {
      resetCarsFounded();
      requestCarInfo();
    }, 300);
  });

  // Сброс данных о найденных автомобилях
  function resetCarsFounded() {
    $("#multiple-cars-founded .popup__cars").empty();
    $("#multiple-cars-founded").off();
  }
}

// Отображение информации об автомобиле
function renderCarInfo(carInfo) {
  // Очистка от предыдущих результатов
  $("#upgrade-calculator .car-info").off();
  $("#upgrade-calculator .car-info__images").empty();
  $("#upgrade-calculator .car-info__options-factory").empty();
  $("#upgrade-calculator .car-info__options-installed").empty();

  // Отображение блока для вывода информации
  $("#upgrade-calculator .calculator-result").show();

  // Заполнение данных об автомобиле
  $(".car-info__vin .text").text(carInfo["vin"]);
  $(".car-info__model .text").text(carInfo["model"]);
  $(".car-info__model-code .text").text(carInfo["modelCode"]);
  $(".car-info__production-date .text").text(carInfo["productionDate"]);
  $(".car-info__images").append(`<div class="car-info__image"><img src="${carInfo["image"]}"></a>`);

  // Заполнение заводскими и доустановленными опциями
  $("#upgrade-calculator .car-info__options-factory").append('<div class="car-info__title">Заводские опции</div>');
  for (let option in carInfo["options"]["factory"]) {
    $("#upgrade-calculator .car-info__options-factory").append(
      `<div class="car-info__option" data-code="${option}"><span class="option-code">${option}</span>${carInfo["options"]["factory"][option]}</div>`
    );
  }
  $("#upgrade-calculator .car-info__options-installed").append('<div class="car-info__title">Дополнительно установленные опции</div>');
  if (Object.keys(carInfo["options"]["installed"]).length) {
    for (let option in carInfo["options"]["installed"]) {
      $("#upgrade-calculator .car-info__options-installed").append(
        `<div class="car-info__option" data-code="${option}"><span class="option-code">${option}</span>${carInfo["options"]["installed"][option]}</div>`
      );
    }
  } else {
    $("#upgrade-calculator .car-info__options-installed").append(
      `<div class="car-info__no-options">В автомобиль не устанавливались дополнительные опции</div>`
    );
  }

  // Отображение/скрытие всех опций
  $("#upgrade-calculator .car-info").on("click", ".toggle-options", function () {
    let offset = $("#upgrade-calculator .upgrade-options").offset().top - $(window).scrollTop();
    $("#upgrade-calculator .car-info").find(".car-info__options").toggleClass("hidden");
    $(this).toggleClass("active");
    if ($(this).hasClass("active")) {
      $(this).text("Скрыть опции");
    } else {
      $(this).text("Показать все опции");
      $(window).scrollTop($("#upgrade-calculator .upgrade-options").offset().top - offset);
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
      else if (carImages.error == "images-not-found") $("#upgrade-calculator .car-info__images .image-loading-icon").parent().remove();
    },
  });
  $("#upgrade-calculator .car-info__images").append(
    '<div class="car-info__image"><div class="image-loading-icon"><div class="image-loading"></div></div><div class="image-loading-text">Загружаются дополнительные изображения автомобиля</div></div>'
  );
}

// Отображение изображений автомобиля
function renderCarImages(carImages) {
  $("#upgrade-calculator .car-info__images").empty();
  $("#upgrade-calculator .car-info__images").addClass("two");
  $("#upgrade-calculator .car-info__images").append(
    `<a class="car-info__image" data-fancybox="car-images" data-src="${carImages["exteriorOriginalImage"]}"><img src="${carImages["exteriorImage"]}"></a>`
  );
  $("#upgrade-calculator .car-info__images").append(
    `<a class="car-info__image" data-fancybox="car-images" data-src="${carImages["interiorOriginalImage"]}"><img src="${carImages["interiorImage"]}"></a>`
  );
  $("a.car-info__image").fancybox({
    infobar: false,
    buttons: ["close"],
    clickContent: false,
    mobile: { dblclickContent: false, dblclickSlide: false },
  });
}

// Запрос опций для дооснащения
function requestUpgradeOptions(carInfo) {
  if ($("#upgrade-calculator").hasClass("no-limit")) carInfo.admin = true;
  $("#upgrade-calculator .loading-icon_new").parent().show();
  $.ajax({
    url: "/scripts/php/calculators/upgrade-options.php",
    type: "POST",
    data: carInfo,
    success: (result) => {
      if (!result.error) {
        renderUpgradeOptions(result);
      } else if (result.error == "limit-exceeded") {
        $("#upgrade-calculator .calculator-result .categories").hide();
        $("#upgrade-calculator .calculator-result .upgrade-options").hide();
        $("#upgrade-calculator .calculator-result .summary-costs").hide();
        setTimeout(() => $.fancybox.open({ src: "#limit-exceeded", opts: { modal: true } }), 300);
        return;
      } else if (result.error == "car-is-not-supported") {
        $("#upgrade-calculator .calculator-result .categories").hide();
        $("#upgrade-calculator .calculator-result .upgrade-options").hide();
        $("#upgrade-calculator .calculator-result .summary-costs").hide();
        setTimeout(() => $.fancybox.open({ src: "#car-is-not-supported", opts: { modal: true } }), 300);
        return;
      } else if (result.error == "individual-calculation") {
        $("#upgrade-calculator .calculator-result .categories").hide();
        $("#upgrade-calculator .calculator-result .upgrade-options").hide();
        $("#upgrade-calculator .calculator-result .summary-costs").hide();
        $("#individual-calculation .link").attr("href", `/services/upgrade/${result.series}/${result.model}/#services-options`);
        setTimeout(() => $.fancybox.open({ src: "#individual-calculation", opts: { modal: true } }), 300);
        return;
      }
    },
  });
}

// Отображение опций для дооснащения
function renderUpgradeOptions(upgradeOptions) {
  console.log("Вывод опций для дооснащения:", upgradeOptions);

  // Очистка от предыдущих данных и отображение результатов
  $("#upgrade-calculator .tabs").empty();
  $("#upgrade-calculator .upgrade-options").empty();
  $("#upgrade-calculator .calculator-result .categories").show();
  $("#upgrade-calculator .calculator-result .upgrade-options").show();
  $("#upgrade-calculator .calculator-result .summary-costs").show();

  // Скрытие индикатора загрузки
  $("#upgrade-calculator .loading-icon_new").parent().hide();

  // Определение поколения, модели, массива категорий и опций
  let series = upgradeOptions["series"];
  let model = upgradeOptions["model"];
  let categories = {};
  upgradeOptions = upgradeOptions["upgradeOptions"];

  for (let option in upgradeOptions) {
    // Заполнение массива с категориями опций
    categories[upgradeOptions[option]["category"][0]] = upgradeOptions[option]["category"][1];

    // Добавление опции
    $("#upgrade-calculator .upgrade-options").append(
      `<div class="upgrade-option col-sm-6 col-lg-4 col-xl-3 active" data-target="${upgradeOptions[option]["category"][0]}" data-name="${option}"><div class="upgrade-option__block"><img src="/images/services/upgrade/${series}/${model}/${option}-min.jpg?11" srcset="/images/services/upgrade/${series}/${model}/${option}-min@2x.jpg?11 1.5x"><div class="upgrade-option__content"><div class="upgrade-option__text"><div class="upgrade-option__title"><h3>${upgradeOptions[option]["name"]}</h3></div><div class="subtitle upgrade-option__subtitle">${upgradeOptions[option]["description"]}</div></div></div><div class="upgrade-option__overlay"></div></div></div>`
    );
    let optionContainer = $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`);

    // Добавление стоимости опции
    if (upgradeOptions[option]["parts"] !== undefined) {
      let partsPrices = [];
      for (let part in upgradeOptions[option]["parts"]) partsPrices.push(upgradeOptions[option]["parts"][part]["price"]);
      let minPrice = +Math.min(...partsPrices);
      optionContainer.attr("data-price", "");
      optionContainer.attr("data-selected-parts", "");
      optionContainer.find(".upgrade-option__content").append(`<div class="upgrade-option__price">от ${formatPrice(minPrice)}</div>`);
    } else if (upgradeOptions[option]["types"] !== undefined) {
      let typesPrices = [];
      for (let type in upgradeOptions[option]["types"]) typesPrices.push(upgradeOptions[option]["types"][type]["price"]);
      let minPrice = +Math.min(...typesPrices);
      let allPricesSame = typesPrices.filter((price) => price == minPrice).length == typesPrices.length ? true : false;
      let prfx = allPricesSame ? "" : "от ";
      console.log(`prefix опции ${option}`, prfx);
      optionContainer.attr("data-price", "");
      optionContainer.attr("data-option-type", "");
      optionContainer.find(".upgrade-option__content").append(`<div class="upgrade-option__price">${prfx}${formatPrice(minPrice)}</div>`);
    } else if (upgradeOptions[option]["price"] !== "") {
      let formattedPrice = formatPrice(upgradeOptions[option]["price"]);
      optionContainer.attr("data-price", upgradeOptions[option]["price"]);
      optionContainer.find(".upgrade-option__content").append(`<div class="upgrade-option__price">${formattedPrice}</div>`);
    } else {
      optionContainer.find(".upgrade-option__content").append(`<div class="upgrade-option__price unknown">Недоступно</div>`);
    }

    // Добавление кнопки «подробнее»
    if (upgradeOptions[option]["page"]) {
      let link = upgradeOptions[option]["page"] === true ? option : upgradeOptions[option]["page"];
      optionContainer.find(".upgrade-option__price").wrap('<div class="upgrade-option__meta"></div>');
      optionContainer
        .find(".upgrade-option__price")
        .after(`<a class="button-simple" href="/services/upgrade/${series}/${link}" target="_blank">Подробнее</a>`);
    }
    optionContainer.find(".button-simple").click((e) => e.stopPropagation());

    // Добавление кода опции
    if (upgradeOptions[option]["code"] !== undefined)
      optionContainer.find(".upgrade-option__title").prepend(`<span class="upgrade-option__code">${upgradeOptions[option]["code"]}</span>`);

    // Добавление пометки
    if (upgradeOptions[option]["label"] !== undefined)
      optionContainer
        .find(".upgrade-option__title")
        .after(
          `<div class="upgrade-option__label"><div class="upgrade-option__label-name">${upgradeOptions[option]["label"][0]}</div><div class="upgrade-option__label-text">${upgradeOptions[option]["label"][1]}</div></div>`
        );

    // Скрытие опции при необходимости
    if (upgradeOptions[option]["hidden"]) optionContainer.addClass("hidden");

    // Возможность выбирать опции
    optionContainer.on("click", function () {
      if (optionContainer.attr("data-price") !== undefined && !optionContainer.hasClass("disabled")) selectOption($(this), upgradeOptions);
    });
  }

  // Добавление вкладок для переключения категорий
  $("#upgrade-calculator .tabs").append(`<div class="tabs-item active" data-tab="all">Все опции</div>`);
  for (let category in categories) {
    $("#upgrade-calculator .tabs").append(`<div class="tabs-item" data-tab="${category}">${categories[category]}</div>`);
  }

  summaryHandlers(upgradeOptions);
  calculateSummary({ upgradeOptions });
}

// Выбор необходимых опций для дооснащения
function selectOption(optionContainer, upgradeOptions) {
  let option = optionContainer.attr("data-name"),
    action;

  // Определение действия
  if (!optionContainer.hasClass("selected")) action = "select";
  else action = "deselect";

  // Вычисление необходимых и отображаемых изменений
  let changes = neededChanges({
    option,
    upgradeOptions,
    initialOption: option,
    action,
  });
  // console.log("Необходимые изменения:", changes);
  let shownChanges = JSON.parse(JSON.stringify(changes));
  delete shownChanges["included"];
  delete shownChanges["notIncluded"];

  // Простое применение изменений, если отображаемые отсутствуют
  if (noChanges(shownChanges)) {
    applyChanges(option, upgradeOptions, changes);

    // Отображение отображаемых изменений, если они есть
  } else {
    showChanges(shownChanges, option, upgradeOptions);

    // Применение изменений при нажатии на кнопку
    $("#upgrade-options-changes").on("click", ":not(.disabled)[data-action=apply]", function () {
      applyChanges(option, upgradeOptions, changes);
    });

    // Отмена изменений при нажатии на кнопку
    // $("#upgrade-options-changes").on("click", "[data-action=cancel]", function () {
    //   cancelChanges();
    // });
  }
}

// Проверка необходимости изменений в оснащении автомобиля
function neededChanges({ option, upgradeOptions, initialOption, initialAction, action, previousChanges }) {
  // console.log("Проверяются необходимые изменения для опции:", option);

  // Подготовка данных
  let changes = {
    selectOptions: [],
    addOptions: [],
    removeOptions: [],
    recommendedOptions: [],
    additional: {},
    included: {},
    notIncluded: {},
    parts: [],
    types: [],
  };
  if (option == initialOption) {
    if (initialAction === undefined) initialAction = action;
    else return changes;
  }
  if (previousChanges === undefined) previousChanges = JSON.parse(JSON.stringify(changes));

  // Опция была выбрана

  if (action == "select") {
    // Проверка требуемых опций
    if (upgradeOptions[option]["required"] !== undefined) {
      upgradeOptions[option]["required"].forEach((requiredOption) => {
        // Если требуется одна из нескольких опций
        if (typeof requiredOption == "object") {
          let selectOptions = [];
          let already = false;

          // Добавляем их, если они не выбраны и ещё не добавлены
          requiredOption.forEach((requiredOption) => {
            if (
              optionSelected(requiredOption) ||
              optionAlreadyChanged(requiredOption, previousChanges) ||
              (initialOption == requiredOption && initialAction == "select")
            ) {
              already = true;
            } else {
              selectOptions.push(requiredOption);
            }
          });
          if (!already) {
            changes["selectOptions"].push(selectOptions);
          }

          // Если требуется определенная опция
        } else {
          // Добавляем её, если она не выбрана и ещё не добавлена
          if (
            optionSelected(requiredOption) ||
            optionAlreadyChanged(requiredOption, previousChanges) ||
            (initialOption == requiredOption && initialAction == "select")
          )
            return;
          changes["addOptions"].push(requiredOption);
        }
      });
    }

    // Проверка содержащихся опций
    if (upgradeOptions[option]["contained"] !== undefined) {
      upgradeOptions[option]["contained"].forEach((containedOption) => {
        // Убираем их, если они выбраны
        if (optionSelected(containedOption)) {
          changes["removeOptions"].push(containedOption);
        }
      });
    }

    // Проверка включенных опций
    changes = enableIncludedOptions(option, upgradeOptions, changes);
    function enableIncludedOptions(option, upgradeOptions, changes) {
      if (upgradeOptions[option]["included"] !== undefined) {
        changes["included"][option] = [];
        upgradeOptions[option]["included"].forEach((includedOption) => {
          changes = enableIncludedOptions(includedOption, upgradeOptions, changes);
          changes["included"][option].push(includedOption);
        });
      }
      return changes;
    }

    // Проверка рекомендуемых опций
    if (upgradeOptions[option]["recommended"] !== undefined) {
      upgradeOptions[option]["recommended"].forEach((recommendedOption) => {
        // Если рекомендуется одна из нескольких опций
        if (typeof recommendedOption == "object") {
          let selectOptions = [],
            alreadySelected = false;

          // Добавляем их, если они не выбраны
          recommendedOption.forEach((recommendedOption) => {
            if (optionSelected(recommendedOption)) {
              alreadySelected = true;
            } else {
              selectOptions.push(recommendedOption);
            }
          });
          if (!alreadySelected) {
            changes["recommendedOptions"].push(selectOptions);
          }
        } else {
          // Если рекомендуется определенная опция
          if (!recommendedOption.includes(".")) {
            // Добавляем опцию, если она не выбрана
            if (
              optionSelected(recommendedOption) ||
              optionAlreadyChanged(recommendedOption, previousChanges) ||
              (initialOption == recommendedOption && initialAction == "select")
            )
              return;
            changes["recommendedOptions"].push(recommendedOption);

            // Если рекомендуется часть опции
          } else {
            changes["recommendedOptions"].push(recommendedOption);
          }
        }
      });
    }

    // Проверка частей опции
    if (upgradeOptions[option]["parts"] !== undefined) {
      for (let part in upgradeOptions[option]["parts"]) {
        changes["parts"].push(part);
      }
    }

    // Проверка видов опции
    if (upgradeOptions[option]["types"] !== undefined) {
      for (let part in upgradeOptions[option]["types"]) {
        changes["types"].push(part);
      }
    }

    // Проверка содержания в другой опции
    for (let otherOption in upgradeOptions) {
      if (upgradeOptions[otherOption]["contained"] !== undefined) {
        upgradeOptions[otherOption]["contained"].forEach((containedOption) => {
          // Убираем другую опцию, если она выбрана
          if (containedOption == option && optionSelected(otherOption)) {
            changes["removeOptions"].push(otherOption);
          }
        });
      }
    }

    // Выбор опции отменён
  } else if (action == "deselect") {
    // Проверка включенных опций
    changes = disableIncludedOptions(option, upgradeOptions, changes);
    function disableIncludedOptions(option, upgradeOptions, changes) {
      if (upgradeOptions[option]["included"] !== undefined) {
        changes["notIncluded"][option] = [];
        upgradeOptions[option]["included"].forEach((includedOption) => {
          // Снимаем обозначения опций, включенных в отмененную
          changes["notIncluded"][option].push(includedOption);
          // changes = disableIncludedOptions(includedOption, upgradeOptions, changes);
        });
      }
      return changes;
    }

    // Проверка необходимости для другой опции
    for (let otherOption in upgradeOptions) {
      if (upgradeOptions[otherOption]["required"] !== undefined) {
        upgradeOptions[otherOption]["required"].forEach((requiredOption) => {
          // В качестве одной из нескольких
          if (typeof requiredOption == "object") {
            let requiredAsSelect = false;
            requiredOption.forEach((requiredOption) => {
              if (requiredOption == option) requiredAsSelect = true;
            });
            if (requiredAsSelect) {
              let alternativeFound = false;
              requiredOption.forEach((requiredOption) => {
                if (requiredOption == initialOption && initialAction == "select") alternativeFound = true;
              });
              if (!alternativeFound) {
                if (optionSelected(otherOption)) changes["removeOptions"].push(otherOption);
              }
            }

            // В качестве отдельной опции
          } else {
            if (requiredOption == option) {
              // Убираем другую опцию, если она выбрана
              if (optionSelected(otherOption)) changes["removeOptions"].push(otherOption);
            }
          }
        });
      }
    }

    // Проверка включенности в другую опцию
    for (let otherOption in upgradeOptions) {
      if (upgradeOptions[otherOption]["included"] !== undefined) {
        upgradeOptions[otherOption]["included"].forEach((includedOption) => {
          if (includedOption == option) {
            // Убираем другую опцию, если она выбрана
            if (optionSelected(otherOption)) {
              changes["removeOptions"].push(otherOption);
              changes = disableIncludedOptions(otherOption, upgradeOptions, changes);
            }
          }
        });
      }
    }

    // Сброс выбранных частей опции
    if (upgradeOptions[option]["parts"] !== undefined) changes["resetParts"] = true;
  }

  // console.log(`Промежуточные изменения`, changes);

  // Подготовка массива всех уже выявленных изменений
  let allChanges = {};

  allChanges["selectOptions"] = [...previousChanges["selectOptions"], ...changes["selectOptions"]];
  allChanges["addOptions"] = [...previousChanges["addOptions"], ...changes["addOptions"]];
  allChanges["removeOptions"] = [...previousChanges["removeOptions"], ...changes["removeOptions"]];
  allChanges["recommendedOptions"] = [...previousChanges["recommendedOptions"], ...changes["recommendedOptions"]];
  allChanges["included"] = { ...previousChanges["included"], ...changes["included"] };
  allChanges["notIncluded"] = { ...previousChanges["notIncluded"], ...changes["notIncluded"] };

  // console.log(`allChanges перед повторными проверками`, allChanges);

  // Повторная проверка необходимых изменений для добавленных опций
  let addedOptions = changes["addOptions"];
  // let addedOptions = changes["addOptions"].filter((change) => !previousChanges.includes(change));
  for (let parentOption in changes["included"]) addedOptions = addedOptions.concat(changes["included"][parentOption]);

  addedOptions.forEach((option) => {
    let newChanges = neededChanges({
      option,
      upgradeOptions,
      initialOption,
      initialAction,
      action: "select",
      previousChanges: allChanges,
    });
    if (!noChanges(newChanges)) {
      changes["selectOptions"] = [...changes["selectOptions"], ...newChanges["selectOptions"]];
      changes["addOptions"] = [...changes["addOptions"], ...newChanges["addOptions"]];
      changes["removeOptions"] = [...changes["removeOptions"], ...newChanges["removeOptions"]];
      changes["recommendedOptions"] = [...changes["recommendedOptions"], ...newChanges["recommendedOptions"]];
      changes["included"] = { ...changes["included"], ...newChanges["included"] };
      changes["notIncluded"] = { ...changes["notIncluded"], ...newChanges["notIncluded"] };
    }
  });

  // ... и то же самое для удаленных опций
  let removedOptions = changes["removeOptions"];
  removedOptions.forEach((option) => {
    let newChanges = neededChanges({
      option,
      upgradeOptions,
      initialOption,
      initialAction,
      action: "deselect",
    });
    if (!noChanges(newChanges)) {
      changes["selectOptions"] = [...changes["selectOptions"], ...newChanges["selectOptions"]];
      changes["addOptions"] = [...changes["addOptions"], ...newChanges["addOptions"]];
      changes["removeOptions"] = [...changes["removeOptions"], ...newChanges["removeOptions"]];
      changes["recommendedOptions"] = [...changes["recommendedOptions"], ...newChanges["recommendedOptions"]];
      changes["included"] = { ...changes["included"], ...newChanges["included"] };
      changes["notIncluded"] = { ...changes["notIncluded"], ...newChanges["notIncluded"] };
    }
  });

  // Проверка необходимых изменений для опций по выбору
  let recommendedOptions = changes["recommendedOptions"].flat().filter((option) => !option.includes("."));
  let additionalOptions = changes["selectOptions"].flat().concat(recommendedOptions);

  // console.log("additionalOptions", additionalOptions);
  // console.log("previousChanges", previousChanges);

  additionalOptions.forEach((option) => {
    // if (previousChanges.includes(option)) return;
    let newChanges = neededChanges({
      option,
      upgradeOptions,
      initialOption,
      initialAction,
      action: "select",
      previousChanges: allChanges,
    });
    if (!noChanges(newChanges)) changes["additional"][option] = { ...newChanges };
  });

  // Удаление дубликатов и начальной опции
  if (initialOption == option) {
    // Добавляемых опций
    let tempAddOptions = {};
    changes["addOptions"].forEach((option) => (tempAddOptions[option] = ""));
    changes["addOptions"] = Object.keys(tempAddOptions);

    // Удаляемых опций
    let tempRemoveOptions = {};
    changes["removeOptions"].forEach((option) => (tempRemoveOptions[option] = ""));
    changes["removeOptions"] = Object.keys(tempRemoveOptions);

    // Вариантов выбора
    let tempSelectOptions = [];
    changes["selectOptions"].forEach((selectOptions) => {
      let choiceFound = false;
      tempSelectOptions.forEach((tempSelectOptions) => {
        if (JSON.stringify(selectOptions) == JSON.stringify(tempSelectOptions)) choiceFound = true;
      });
      if (!choiceFound) tempSelectOptions.push(selectOptions);
    });
    changes["selectOptions"] = tempSelectOptions;

    // Добавляемые (в том числе по выбору) и рекомендуемые опции, которые дублируют включенные
    let tempIncludedOptions = [];
    for (let parentOption in changes["included"]) tempIncludedOptions = tempIncludedOptions.concat(changes["included"][parentOption]);
    tempIncludedOptions.forEach((includedOption) => {
      let index = changes["addOptions"].indexOf(includedOption);
      if (changes["addOptions"].includes(includedOption)) delete changes["addOptions"][index];
      changes["selectOptions"].forEach((selectOptions) => {
        let index = changes["selectOptions"].indexOf(selectOptions);
        if (selectOptions.includes(includedOption)) delete changes["selectOptions"][index];
      });
      changes["recommendedOptions"].forEach((recommendedOption) => {
        let index = changes["recommendedOptions"].indexOf(recommendedOption);
        if (recommendedOption == includedOption) delete changes["recommendedOptions"][index];
      });
    });
    changes["addOptions"] = Object.values(changes["addOptions"]);
    changes["recommendedOptions"] = Object.values(changes["recommendedOptions"]);
    changes["selectOptions"] = Object.values(changes["selectOptions"]);

    // Удаление начальной опции из дополнительных опций
    for (let option in changes["additional"]) {
      if (changes["additional"][option]["addOptions"].includes(initialOption)) {
        changes["additional"][option]["addOptions"] = changes["additional"][option]["addOptions"].filter(
          (option) => option != initialOption
        );
      }
      if (changes["additional"][option]["recommendedOptions"].includes(initialOption)) {
        changes["additional"][option]["recommendedOptions"] = changes["additional"][option]["recommendedOptions"].filter(
          (option) => option != initialOption
        );
      }
    }
  }

  // Вывод итоговых изменений
  if (initialOption == option) console.log(`Итоговые изменения для опции ${option}:`, changes);

  // Возврат необходимых изменений
  changes["addOptions"] = sortOptions(changes["addOptions"], upgradeOptions);
  changes["removeOptions"] = sortOptions(changes["removeOptions"], upgradeOptions);
  return changes;
}

// Проверка на отсутствие изменений
function noChanges(changes) {
  if (
    !changes["selectOptions"].length &&
    !changes["addOptions"].length &&
    !changes["removeOptions"].length &&
    !changes["recommendedOptions"].length &&
    !changes["parts"].length &&
    !changes["types"].length &&
    !Object.keys(changes["additional"]).length
  ) {
    return true;
  } else {
    return false;
  }
}

// Уплощение объекта
function flattenObject(object) {
  let toReturn = {};

  for (let i in object) {
    if (!object.hasOwnProperty(i)) continue;

    if (typeof object[i] == "object") {
      let flatObject = flattenObject(object[i]);
      for (let x in flatObject) if (flatObject.hasOwnProperty(x)) toReturn[i + "." + x] = flatObject[x];
    } else toReturn[i] = object[i];
  }

  return toReturn;
}

// Проверка на наличие данного изменения
function optionAlreadyChanged(option, changes) {
  changes = Object.values(flattenObject(changes));
  return changes.includes(option);
}

// Отображение всплывающего окна
function showChanges(changes, option, upgradeOptions) {
  let optionContainer = $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`);

  // Подготовка текста во всплывающем окне
  if (!optionContainer.hasClass("selected")) {
    $("#upgrade-options-changes .required-changes .popup__upgrade-changes").append(
      "<p>Для установки данного дооснащения необходимы следующие изменения:</p>"
    );
  } else {
    $("#upgrade-options-changes .required-changes .popup__upgrade-changes").append(
      "<p>Для отмены данного дооснащения необходимы следующие изменения:</p>"
    );
  }

  // Сброс состояния кнопки "Применить"
  $("#upgrade-options-changes").find("[data-action=apply]").removeClass("disabled");

  // Отображение изменений
  renderChanges({ changes, upgradeOptions, option });

  // Установка состояния блоков требуемых и рекомендуемых изменений, а также кнопки "Применить"
  setShownChangesState();
  setApplyButtonState();

  // Отображение всплывающего окна
  $.fancybox.open({
    src: "#upgrade-options-changes",
    afterClose: cancelChanges,
  });

  // Возможность выбора опций по выбору
  $("#upgrade-options-changes").on("click", ".popup__select-options .popup__option", function () {
    let option = $(this).attr("data-name");
    // let additionalChangesAnchor = $(this).parent();
    let additionalChangesAnchor;
    if ($(this).parent().parent().is(".popup__upgrade-changes")) {
      additionalChangesAnchor = $(".popup__upgrade-changes");
    } else if ($(this).parent().parent().is(".popup__additional-changes.active")) {
      let anchorName = $(this).parent().parent().attr("data-condition");
      additionalChangesAnchor = $(`.popup__additional-changes.active[data-condition=${anchorName}`);
    }
    let cancelledOptions = [];

    // Отображение сделанного выбора
    $(this).siblings(".popup__option").removeClass("selected");
    $(this)
      .siblings(".popup__option")
      .each(function () {
        cancelledOptions.push($(this).attr("data-name"));
      });
    $(this).addClass("selected");
    $(this).parent(".popup__select-options").attr("data-name", $(this).attr("data-name"));

    // Отображение дополнительных изменений
    cancelledOptions.forEach((option) => {
      additionalChangesAnchor.children(`[data-condition=${option}]`).removeClass("active");
      additionalChangesAnchor.children(`[data-condition=${option}]`).find(`.popup__option`).removeClass("selected");
      additionalChangesAnchor
        .children(`[data-condition=${option}]`)
        .find(".popup__select-options, .popup__option-parts, .popup__option-types")
        .removeAttr("data-name");
    });
    additionalChangesAnchor.children(`[data-condition=${option}]`).addClass("active");

    setShownChangesState();
    setApplyButtonState();
  });

  // Возможность выбора рекомендуемых опций
  $("#upgrade-options-changes").on("click", ".popup__recommended-options .popup__option", function () {
    let option = $(this).attr("data-name");
    // let additionalChanges = $(this).parent().siblings(`[data-condition=${option}]`);
    let additionalChangesAnchor;
    if ($(this).parent().parent().is(".popup__upgrade-changes")) {
      additionalChangesAnchor = $(".popup__upgrade-changes");
    } else if ($(this).parent().parent().is(".popup__additional-changes.active")) {
      let anchorName = $(this).parent().parent().attr("data-condition");
      additionalChangesAnchor = $(`.popup__additional-changes.active[data-condition=${anchorName}`);
    }

    // Выбор опции
    if (!$(this).hasClass("selected")) {
      $(this).addClass("selected");

      // Отображение дополнительных изменений
      additionalChangesAnchor.children(`[data-condition=${option}]`).addClass("active");

      // Отмена выбора опции
    } else {
      $(this).removeClass("selected");

      additionalChangesAnchor.children(`[data-condition=${option}]`).removeClass("active");
      additionalChangesAnchor.children(`[data-condition=${option}]`).find(".popup__option").removeClass("selected");
      additionalChangesAnchor
        .children(`[data-condition=${option}]`)
        .find(".popup__select-options, .popup__option-parts, .popup__option-types")
        .removeAttr("data-name");
    }

    setShownChangesState();
    setApplyButtonState();
  });

  // Возможность выбора рекомендуемых опций по выбору
  $("#upgrade-options-changes").on("click", ".popup__recommended-select-options .popup__option", function () {
    let option = $(this).attr("data-name");
    // let additionalChangesAnchor = $(this).parent();
    let additionalChangesAnchor;
    if ($(this).parent().parent().is(".popup__upgrade-changes")) {
      additionalChangesAnchor = $(".popup__upgrade-changes");
    } else if ($(this).parent().parent().is(".popup__additional-changes.active")) {
      let anchorName = $(this).parent().parent().attr("data-condition");
      additionalChangesAnchor = $(`.popup__additional-changes.active[data-condition=${anchorName}`);
    }
    let cancelledOptions = [];

    // Отображение сделанного выбора
    $(this).siblings(".popup__option").removeClass("selected");
    $(this)
      .siblings(".popup__option")
      .each(function () {
        cancelledOptions.push($(this).attr("data-name"));
      });
    $(this).addClass("selected");
    $(this).parent(".popup__select-options").attr("data-name", $(this).attr("data-name"));

    // Отображение дополнительных изменений
    cancelledOptions.forEach((option) => {
      additionalChangesAnchor.children(`[data-condition=${option}]`).removeClass("active");
      additionalChangesAnchor.children(`[data-condition=${option}]`).find(".popup__option").removeClass("selected");
      additionalChangesAnchor
        .children(`[data-condition=${option}]`)
        .find(".popup__select-options, .popup__option-parts, .popup__option-types")
        .removeAttr("data-name");
    });
    additionalChangesAnchor.children(`[data-condition=${option}]`).addClass("active");

    setShownChangesState();
    setApplyButtonState();
  });

  // Возможность выбора частей опции
  $("#upgrade-options-changes").on("click", ".popup__option-parts .popup__option", function () {
    $(this).toggleClass("selected");
    $(this).parent(".popup__option-parts").attr("data-name", $(this).attr("data-name"));
    if ($(this).parent().children(".selected").length == 0) $(this).parent(".popup__option-parts").removeAttr("data-name");
    setShownChangesState();
    setApplyButtonState();
  });

  // Возможность выбора видов опции
  $("#upgrade-options-changes").on("click", ".popup__option-types .popup__option", function () {
    $(this).siblings(".popup__option").removeClass("selected");
    $(this).addClass("selected");
    $(this).parent(".popup__option-types").attr("data-name", $(this).attr("data-name"));
    setShownChangesState();
    setApplyButtonState();
  });
}

// Отображение необходимых изменений
function renderChanges({ changes, upgradeOptions, originalContainer, originalRecommendedContainer, option }) {
  // console.log('Функция renderChanges!');
  // console.log('Отображение изменений:', changes);
  // console.log('Для опции:', option);

  let container,
    recommendedContainer,
    specialPrices = getSpecialPrices({ upgradeOptions, initialOption: option });

  // Определение контейнера для отображения изменений
  if (originalContainer === undefined && originalRecommendedContainer === undefined) {
    container = $("#upgrade-options-changes .required-changes .popup__upgrade-changes");
    recommendedContainer = $("#upgrade-options-changes .recommended-changes .popup__upgrade-changes");
  } else {
    container = originalContainer;
    recommendedContainer = originalRecommendedContainer;
  }

  // Добавление опций с выбором
  if (changes["selectOptions"].length) {
    changes["selectOptions"].forEach((selectOptions) => {
      let number = changes["selectOptions"].indexOf(selectOptions) + 1;

      // Подпись
      container.append(
        `<div class="popup__select-options select-group-${number}"><div class="popup__group-name">Добавить одну из опций <span class="red">(обязательно)</span></div></div>`
      );

      selectOptions.forEach((option) => {
        // Опция
        container
          .children(".popup__select-options")
          .append(
            `<div class="popup__option" data-name="${option}"><div class="option__title">${
              upgradeOptions[option]["name"]
            }</div><div class="option__price">${formatPrice(upgradeOptions[option]["price"])}</div></div>`
          );

        // Код опции
        if (upgradeOptions[option]["code"] !== undefined) {
          container
            .find(`[data-name="${option}"] .option__title`)
            .prepend(`<span class="option__code">${upgradeOptions[option]["code"]}</span>`);
        }
      });
    });

    // Отключение кнопки "Применить"
    // $('#upgrade-options-changes').find('[data-action=apply]').addClass('disabled');
  }

  // Добавление отдельных опций
  if (changes["addOptions"].length) {
    // Подпись
    container.append(
      `<div class="popup__add-options"><div class="popup__group-name">Добавить опции <span class="red">(обязательно)</span></div></div>`
    );

    changes["addOptions"].forEach((option) => {
      // Опция
      container
        .children(".popup__add-options")
        .append(
          `<div class="popup__option" data-name="${option}"><div class="option__title">${
            upgradeOptions[option]["name"]
          }</div><div class="option__price">${formatPrice(upgradeOptions[option]["price"])}</div></div>`
        );

      // Код опции
      if (upgradeOptions[option]["code"] !== undefined) {
        container
          .find(`[data-name="${option}"] .option__title`)
          .prepend(`<span class="option__code">${upgradeOptions[option]["code"]}</span>`);
      }
    });
  }

  // Удаление опций
  if (changes["removeOptions"].length) {
    // Подпись
    container.append(
      `<div class="popup__remove-options"><div class="popup__group-name">Удалить опции <span class="red">(обязательно)</span></div></div>`
    );

    changes["removeOptions"].forEach((option) => {
      // Опция
      container
        .children(".popup__remove-options")
        .append(
          `<div class="popup__option" data-name="${option}"><div class="option__title">${
            upgradeOptions[option]["name"]
          }</div><div class="option__price">${formatPrice(upgradeOptions[option]["price"])}</div></div>`
        );

      // Код опции
      if (upgradeOptions[option]["code"] !== undefined) {
        container
          .find(`[data-name="${option}"] .option__title`)
          .prepend(`<span class="option__code">${upgradeOptions[option]["code"]}</span>`);
      }
    });
  }

  // Добавление рекомендуемых опций
  if (changes["recommendedOptions"].length) {
    changes["recommendedOptions"].forEach((option) => {
      // Если рекомендуется одна из нескольких
      if (typeof option == "object") {
        let number = changes["recommendedOptions"].indexOf(option) + 1;

        // Подпись
        recommendedContainer.append(
          `<div class="popup__recommended-select-options recommended-group-${number}"><div class="popup__group-name">Добавить одну из опций <span class="grey">(необязательно)</span></div></div>`
        );

        option.forEach((option) => {
          // Опция
          recommendedContainer
            .children(`.popup__recommended-select-options.recommended-group-${number}`)
            .append(
              `<div class="popup__option" data-name="${option}"><div class="option__title">${upgradeOptions[option]["name"]}</div></div>`
            );

          // Код опции
          if (upgradeOptions[option]["code"] !== undefined) {
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .prepend(`<span class="option__code">${upgradeOptions[option]["code"]}</span>`);
          }

          // Цена опции
          if (upgradeOptions[option]["parts"] !== undefined) {
            let partsPrices = [];
            for (let part in upgradeOptions[option]["parts"]) partsPrices.push(upgradeOptions[option]["parts"][part]["price"]);
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .after(`<div class="option__price">от ${formatPrice(Math.min(...partsPrices))}</div>`);
          } else if (upgradeOptions[option]["types"] !== undefined) {
            let typesPrices = [];
            for (let type in upgradeOptions[option]["types"]) typesPrices.push(upgradeOptions[option]["types"][type]["price"]);
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .after(`<div class="option__price">от ${formatPrice(Math.min(...typesPrices))}</div>`);
          } else
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .after(`<div class="option__price">${formatPrice(upgradeOptions[option]["price"])}</div>`);
        });

        // Если рекомендуется конкретная опция или её часть
      } else {
        // Подпись
        if (!recommendedContainer.children(".popup__recommended-options").length)
          recommendedContainer.append(
            `<div class="popup__recommended-options"><div class="popup__group-name">Добавить опции <span class="grey">(необязательно)</span></div></div>`
          );

        // Рекомендуется опция
        if (!option.includes(".")) {
          // Опция
          recommendedContainer
            .children(".popup__recommended-options")
            .append(
              `<div class="popup__option" data-name="${option}"><div class="option__title">${upgradeOptions[option]["name"]}</div></div>`
            );

          // Код опции
          if (upgradeOptions[option]["code"] !== undefined) {
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .prepend(`<span class="option__code">${upgradeOptions[option]["code"]}</span>`);
          }

          // Цена опции
          if (upgradeOptions[option]["parts"] !== undefined) {
            let partsPrices = [];
            for (let part in upgradeOptions[option]["parts"]) partsPrices.push(upgradeOptions[option]["parts"][part]["price"]);
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .after(`<div class="option__price">от ${formatPrice(Math.min(...partsPrices))}</div>`);
          } else if (upgradeOptions[option]["types"] !== undefined) {
            let typesPrices = [];
            for (let type in upgradeOptions[option]["types"]) typesPrices.push(upgradeOptions[option]["types"][type]["price"]);
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .after(`<div class="option__price">от ${formatPrice(Math.min(...typesPrices))}</div>`);
          } else
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .after(`<div class="option__price">${formatPrice(upgradeOptions[option]["price"])}</div>`);

          // Рекомендуется часть опции
        } else {
          let part = option.split(".")[1];
          option = option.split(".")[0];

          // Часть опции
          recommendedContainer
            .children(".popup__recommended-options")
            .append(
              `<div class="popup__option" data-name="${option}.${part}"><div class="option__title">${
                upgradeOptions[option]["parts"][part]["name"]
              }</div><div class="option__price">${formatPrice(upgradeOptions[option]["parts"][part]["price"])}</div></div>`
            );

          // Код опции
          if (upgradeOptions[option]["code"] !== undefined) {
            recommendedContainer
              .find(`[data-name="${option}"] .option__title`)
              .prepend(`<span class="option__code">${upgradeOptions[option]["code"]}</span>`);
          }
        }
      }
    });
  }

  // Добавление частей опции
  if (changes["parts"].length) {
    changes["parts"].forEach((part) => {
      // Подпись
      if (!container.children(".popup__option-parts").length)
        container.append(
          `<div class="popup__option-parts"><div class="popup__group-name">Выбрать части опции <span class="red">(обязательно)</span></div></div>`
        );

      // Часть опции
      container
        .children(".popup__option-parts")
        .append(
          `<div class="popup__option" data-name="${option}.${part}"><div class="option__title">${
            upgradeOptions[option]["parts"][part]["name"]
          }</div><div class="option__price">${formatPrice(upgradeOptions[option]["parts"][part]["price"])}</div></div>`
        );
    });
  }

  // Добавление видов опции
  if (changes["types"].length) {
    changes["types"].forEach((part) => {
      // Подпись
      if (!container.children(".popup__option-types").length)
        container.append(
          `<div class="popup__option-types"><div class="popup__group-name">Выбрать вид опции <span class="red">(обязательно)</span></div></div>`
        );

      // Часть опции
      container
        .children(".popup__option-types")
        .append(
          `<div class="popup__option" data-name="${option}.${part}"><div class="option__title">${
            upgradeOptions[option]["types"][part]["name"]
          }</div><div class="option__price">${formatPrice(upgradeOptions[option]["types"][part]["price"])}</div></div>`
        );
    });

    // Отключение кнопки "Применить"
    if (!container.is(".popup__additional-changes")) {
      // $('#upgrade-options-changes').find('[data-action=apply]').addClass('disabled');
    }
  }

  // Добавление возможных дополнительных изменений
  if (changes["additional"]) {
    for (let option in changes["additional"]) {
      let additionalChanges = changes["additional"][option];
      // let additionalChangesContainer;

      // if (originalContainer === undefined) {
      //   let optionContainer = $(".popup__upgrade-changes").children().children(`.popup__option[data-name=${option}]`);
      //   if (optionContainer.parent().parent().parent().is(".required-changes")) additionalChangesContainer = container;
      //   else if (optionContainer.parent().parent().parent().is(".recommended-changes")) additionalChangesContainer = recommendedContainer;
      // } else {
      //   additionalChangesContainer = originalContainer;
      // }

      // additionalChangesContainer.append(`<div class="popup__additional-changes" data-condition="${option}"></div>`);

      container.append(`<div class="popup__additional-changes" data-condition="${option}"></div>`);
      recommendedContainer.append(`<div class="popup__additional-changes" data-condition="${option}"></div>`);

      renderChanges({
        changes: additionalChanges,
        upgradeOptions,
        originalContainer: container.children(`.popup__additional-changes[data-condition=${option}]`),
        originalRecommendedContainer: recommendedContainer.children(`.popup__additional-changes[data-condition=${option}]`),
        option,
      });
    }
  }

  // Применение специальных цен
  // console.log('Применение специальных цен:', specialPrices);
  for (let option in specialPrices) {
    let price;
    if (specialPrices[option] == "initial") continue;
    else if (specialPrices[option] == "Входит в комплект") price = specialPrices[option];
    else price = formatPrice(specialPrices[option]);
    $("#upgrade-options-changes").find(`[data-name="${option}"]`).find(".option__price").text(price);
  }
}

// Установка состояния блоков требуемых и рекомендуемых изменений
function setShownChangesState() {
  let requiredIsActive = false,
    recommendedIsActive = false;
  let requiredChanges = $("#upgrade-options-changes").find(".required-changes");
  let recommendedChanges = $("#upgrade-options-changes").find(".recommended-changes");

  if (
    $("#upgrade-options-changes .required-changes .popup__upgrade-changes").children().children(".popup__option").length ||
    $("#upgrade-options-changes .required-changes").find(".popup__additional-changes.active").length
  )
    requiredIsActive = true;

  if (
    $("#upgrade-options-changes .recommended-changes .popup__upgrade-changes").children().children(".popup__option").length ||
    ($("#upgrade-options-changes .recommended-changes .popup__upgrade-changes").children(".popup__additional-changes.active").length &&
      $("#upgrade-options-changes .recommended-changes .popup__upgrade-changes")
        .children(".popup__additional-changes.active")
        .children()
        .children(".popup__option").length)
  )
    recommendedIsActive = true;

  requiredIsActive ? requiredChanges.addClass("active") : requiredChanges.removeClass("active");
  recommendedIsActive ? recommendedChanges.addClass("active") : recommendedChanges.removeClass("active");
}

// Установка состояния кнопки "Применить"
function setApplyButtonState() {
  let disabled = false;
  let applyButton = $("#upgrade-options-changes").find("[data-action=apply]");

  $(".popup__select-options, .popup__option-parts, .popup__option-types").each(function () {
    if ($(this).parent().is(".popup__additional-changes") && !$(this).parent(".popup__additional-changes").hasClass("active")) return;
    if ($(this).attr("data-name") === undefined) disabled = true;
  });

  disabled ? applyButton.addClass("disabled") : applyButton.removeClass("disabled");
}

// Применение необходимых изменений в оснащении автомобиля
function applyChanges(option, upgradeOptions, changes) {
  console.log("Применяем изменения: ", changes);

  // Удаление обработчика
  $("#upgrade-options-changes").off("click");

  // Переключение кликнутой опции
  $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).toggleClass("selected");

  // Выбор нужных опций
  changes["addOptions"].forEach((option) => {
    $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).addClass("selected");
  });

  // Отмена выбора ненужных опций
  changes["removeOptions"].forEach((option) => {
    $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).removeClass("selected");
  });

  // Выбор выбранных опций и дополнительных к ним
  $("#upgrade-options-changes")
    .find(".popup__select-options")
    .each(function () {
      // Выбор выбранной опции
      let selectedOption = $(this).attr("data-name");
      $("#upgrade-calculator .upgrade-options").find(`[data-name=${selectedOption}]`).addClass("selected");

      // Выбор дополнительных опций, если они имеются
      if (changes["additional"][selectedOption]) {
        changes["additional"][selectedOption]["addOptions"].forEach((option) => {
          $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).addClass("selected");
        });
        changes["additional"][selectedOption]["removeOptions"].forEach((option) => {
          $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).removeClass("selected");
        });
      }
    });

  // Выбор рекомендуемых опций
  $("#upgrade-options-changes")
    .find(".popup__recommended-options .popup__option.selected, .popup__recommended-select-options .popup__option.selected")
    .each(function () {
      // Выбор опции
      let recommendedOption = $(this).attr("data-name");
      if (!recommendedOption.includes(".")) {
        $("#upgrade-calculator .upgrade-options").find(`[data-name=${recommendedOption}]`).addClass("selected");

        // Выбор дополнительных опций, если они имеются
        if (changes["additional"][recommendedOption]) {
          changes["additional"][recommendedOption]["addOptions"].forEach((option) => {
            $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).addClass("selected");
          });
          changes["additional"][recommendedOption]["removeOptions"].forEach((option) => {
            $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).removeClass("selected");
          });
          changes["additional"][recommendedOption]["parts"].forEach((part) => {});
          changes["additional"][recommendedOption]["types"].forEach((type) => {});
        }

        // Выбор части опции
      } else {
        let option = recommendedOption.split(".")[0];
        let part = recommendedOption.split(".")[1];
        let selectedParts = [];
        selectedParts.push(`${option}.${part}`);
        $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).addClass("selected");
        $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).attr("data-selected-parts", JSON.stringify(selectedParts));
      }
    });

  // Обозначение опций, включенных в другие
  for (let parentOption in changes["included"]) {
    let parentOptionName = $("#upgrade-calculator .upgrade-options").find(`[data-name=${parentOption}] h3`).text();
    changes["included"][parentOption].forEach((includedOption) => {
      let includedOptionContainer = $("#upgrade-calculator .upgrade-options").find(`[data-name=${includedOption}]`);
      includedOptionContainer.addClass("selected");
      includedOptionContainer.find(".upgrade-option__overlay").addClass("active");
      includedOptionContainer
        .find(".upgrade-option__overlay")
        .html(`Опция входит в <span class="upgrade-option__parent-option">${parentOptionName}</span></div>`);
    });
  }

  // Снятие обозначений опций, включенных в другие
  for (let parentOption in changes["notIncluded"]) {
    changes["notIncluded"][parentOption].forEach((includedOption) => {
      let includedOptionContainer = $("#upgrade-calculator .upgrade-options").find(`[data-name=${includedOption}]`);
      includedOptionContainer.find(".upgrade-option__overlay").removeClass("active");
      includedOptionContainer.find(".upgrade-option__overlay").html("");
    });
  }

  // Выбор частей опции
  let optionPartsContainer = $("#upgrade-options-changes .popup__upgrade-changes").children(".popup__option-parts"),
    selectedParts = [];
  if (optionPartsContainer.length) {
    optionPartsContainer.find(".popup__option.selected").each(function () {
      selectedParts.push($(this).attr("data-name"));
    });
    if (selectedParts.length) {
      $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).attr("data-selected-parts", JSON.stringify(selectedParts));
    } else {
      $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).removeClass("selected");
      $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).attr("data-selected-parts");
    }
  }

  // Выбор вида опции
  let optionTypesContainer = $("#upgrade-options-changes .popup__upgrade-changes").find(".popup__option-types"),
    selectedType;
  if (optionTypesContainer.length) {
    selectedType = optionTypesContainer.find(".popup__option.selected").attr("data-name");
    if (selectedType !== undefined) {
      $("#upgrade-calculator .upgrade-options")
        .find(`[data-name=${selectedType.split(".")[0]}]`)
        .attr("data-option-type", selectedType);
    } else {
      let option = optionTypesContainer.find(".popup__option").attr("data-name").split(".")[0];
      $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).removeClass("selected");
      $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).attr("data-option-type");
    }
  }

  // Сброс выбранных частей опции
  if (changes["resetParts"]) $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`).attr("data-selected-parts", "");

  // Закрытие всплывающего окна
  $.fancybox.close({ src: "#upgrade-options-changes" });

  // Расчёт стоимости
  calculateSummary({ upgradeOptions, lastOptions: [option] });
}

// Очистка всплывающего окна
function cancelChanges() {
  // Удаление обработчика
  $("#upgrade-options-changes").off("click");

  // Очистка
  $("#upgrade-options-changes .popup__upgrade-changes").empty();
}

// Проверка, выбрана ли уже данная опция
function optionSelected(option) {
  // Сбор выбранных опций
  let selectedOptions = [];
  $("#upgrade-calculator .upgrade-option.selected").each(function () {
    selectedOptions.push($(this).attr("data-name"));
  });

  return selectedOptions.indexOf(option) !== -1;
}

// Сортировка опций
function sortOptions(options, upgradeOptions) {
  let sortedOptions = [];
  Object.keys(upgradeOptions).forEach((upgradeOption) => {
    options.forEach((option) => {
      if (upgradeOption == option) sortedOptions.push(option);
    });
  });
  return sortedOptions;
}

// Форматирование цены
function formatPrice(number, zeroShouldMeanFree) {
  if (number !== 0 || zeroShouldMeanFree === false) {
    return Number(number).toLocaleString("ru", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
      style: "currency",
      currency: "RUB",
    });
  } else return "Бесплатно";
}

// Расчёт количества, итоговой стоимости и списка выбранных опций
function calculateSummary({ upgradeOptions, lastOptions }) {
  // Проверка на необходимость установки специальных цен
  let specialPrices = getSpecialPrices({ upgradeOptions, lastOptions });
  console.log("Вызов getSpecialPrices() в calculateSummary", specialPrices);

  // Установка специальных цен
  setPrices(specialPrices, upgradeOptions);

  // Расчёт количества, общей стоимости и списка опций
  let optionsNumber = 0,
    optionsList = [],
    partsCost = 0;
  $("#upgrade-calculator .upgrade-options .upgrade-option.selected").each(function () {
    optionsNumber++;
    optionsList.push({
      name: $(this).attr("data-name"),
      code: $(this).find(".upgrade-option__code").text(),
      title: $(this).find(".upgrade-option__title h3").text(),
      price: $(this).find(".upgrade-option__price").text(),
    });
    partsCost += Number($(this).attr("data-price"));
  });

  // Вывод количества опций
  $(".summary-costs .options-number").hide();
  if (optionsNumber == 0) {
    $(".summary-costs .options-number").removeClass("active");
    $(".summary-costs .options-number").text(`Нет опций`);
    $(".summary-costs__options-list").removeClass("active");
  } else {
    $(".summary-costs .options-number").addClass("active");
    if (optionsNumber == 11 || optionsNumber == 12) {
      $(".summary-costs .options-number").text(`${optionsNumber} опций`);
    } else {
      switch (optionsNumber % 10) {
        case 1:
          $(".summary-costs .options-number").text(`${optionsNumber} опция`);
          break;
        case 2:
        case 3:
        case 4:
          $(".summary-costs .options-number").text(`${optionsNumber} опции`);
          break;
        case 5:
        case 6:
        case 7:
        case 8:
        case 9:
        case 0:
          $(".summary-costs .options-number").text(`${optionsNumber} опций`);
          break;
      }
    }
  }
  $(".summary-costs .options-number").fadeIn(600);

  // Вывод списка опций
  $(".summary-costs__options-list .list").empty();
  optionsList.forEach((option) => {
    $(".summary-costs__options-list .list").append(
      `<div class="list-item" data-name="${option["name"]}"><div class="list-item__left"><div class="list-item__code">${option["code"]}</div><div class="list-item__title">${option["title"]}</div></div><div class="list-item__right"><div class="list-item__price">${option["price"]}</div><div class="list-item__remove-button icon cross-grey"></div></div></div>`
    );
  });

  // Вывод общей стоимости
  if ($(".summary-costs .total-cost .value").text() != formatPrice(partsCost)) {
    $(".summary-costs .total-cost .value").hide();
    $(".summary-costs .total-cost .value").text(formatPrice(partsCost, false));
    $(".summary-costs .total-cost .value").fadeIn(600);
  }
}

// Проверка на необходимость специальных цен
function getSpecialPrices({ upgradeOptions, initialOption, lastOptions }) {
  let specialPrices = {};
  let initialOptionContainer = $("#upgrade-calculator .upgrade-options").find(`[data-name=${initialOption}]`);
  // console.log('Проверка перед функцией getSpecialPrices!');
  // console.log('initialOption', initialOption);
  // console.log('lastOptions', lastOptions);

  // // Учет специальных цен на опции 4NM и 536
  // if (lastOptions) {
  //   lastOptions.forEach(lastOption => {
  //     if (lastOption == 'ambient-air' || lastOption == 'autonomous-heating') {
  //       delete upgradeOptions['ambient-air']['specialPrices'][1]['ignore'];
  //       delete upgradeOptions['autonomous-heating']['specialPrices'][1]['ignore'];
  //       upgradeOptions[lastOption]['specialPrices'][1]['ignore'] = true;
  //     }
  //   })
  // }

  // console.log('upgradeOptions:', upgradeOptions);

  // Проход по всем опциям
  for (let option in upgradeOptions) {
    // Проход по всем специальным ценам этой опции и определение необходимой цены
    if (upgradeOptions[option]["specialPrices"]) {
      upgradeOptions[option]["specialPrices"].forEach((specialPrice) => {
        if (specialPrice["ignore"] == true) return;

        if (specialPrice["condition"] !== "allParts") {
          // Проверка на полное соответствие условию этой цены
          let conditionMatched = true;
          specialPrice["condition"].forEach((condition) => {
            if (initialOption == condition && !initialOptionContainer.hasClass("selected")) return;
            if (!optionSelected(condition)) conditionMatched = false;
          });

          // Сохранение необходимой цены
          if (conditionMatched) {
            specialPrices[option] = specialPrice["price"];
          } else if (specialPrices[option] === undefined) {
            specialPrices[option] = "initial";
          }
        }
      });
    }

    // Проход по всем частям этой опции
    if (upgradeOptions[option]["parts"]) {
      for (let part in upgradeOptions[option]["parts"]) {
        if (upgradeOptions[option]["parts"][part]["specialPrices"]) {
          upgradeOptions[option]["parts"][part]["specialPrices"].forEach((specialPrice) => {
            // Проверка на соответствие условию этой цены
            let conditionMatched = specialPrice["condition"].every((condition) => {
              if (initialOption == condition && !optionSelected(condition)) return true;
              return optionSelected(condition);
            });
            if (conditionMatched) specialPrices[`${option}.${part}`] = specialPrice["price"];
            else specialPrices[`${option}.${part}`] = "initial";
          });
        }
      }
    }
  }

  // console.log(`Итоговые специальные цены для опции ${initialOption}:`, specialPrices);
  return specialPrices;
}

// Установка цен
function setPrices(specialPrices, upgradeOptions) {
  console.log("Рассчитываем и устанавливаем цены для всех опциий!");

  $("#upgrade-calculator .upgrade-option").each(function () {
    let optionContainer = $(this),
      option = optionContainer.attr("data-name"),
      price = 0,
      formattedPrice,
      priceChanged = false;

    // Вычисление специальных цен
    if (specialPrices[option] !== undefined) {
      priceChanged = true;

      // Если определена специальная цена
      if (specialPrices[option] !== "initial") {
        // Входит в комплект
        if (specialPrices[option] == "Входит в комплект") {
          price = 0;
          formattedPrice = specialPrices[option];

          // Специальная цена
        } else {
          price = specialPrices[option];
          formattedPrice = formatPrice(price);
        }

        // Если цена должна стать обычной
      } else {
        price = upgradeOptions[option]["price"];
        formattedPrice = formatPrice(upgradeOptions[option]["price"]);
      }
    }

    // Вычисление цен опций с частями
    if (optionContainer.attr("data-selected-parts") !== undefined) {
      priceChanged = true;

      // Если опция выбрана
      if (optionSelected(option)) {
        let selectedParts = JSON.parse($(this).attr("data-selected-parts"));
        selectedParts.forEach((selectedPart) => {
          let [option, part] = selectedPart.split(".");
          let partPrice = +upgradeOptions[option]["parts"][part]["price"];
          if (specialPrices[`${option}.${part}`] !== "initial" && specialPrices[`${option}.${part}`] !== undefined)
            partPrice = +specialPrices[`${option}.${part}`];
          price += partPrice;
        });
        if (selectedParts.length == Object.keys(upgradeOptions[option]["parts"]).length && upgradeOptions[option]["price"])
          price = upgradeOptions[option]["price"];
        formattedPrice = formatPrice(price);

        // Если опция не выбрана
      } else {
        let partsPrices = [];
        for (let part in upgradeOptions[option]["parts"]) {
          let partPrice = upgradeOptions[option]["parts"][part]["price"];
          if (specialPrices[`${option}.${part}`] !== "initial" && specialPrices[`${option}.${part}`] !== undefined)
            partPrice = +specialPrices[`${option}.${part}`];
          partsPrices.push(partPrice);
        }
        price = Math.min(...partsPrices);
        formattedPrice = "от " + formatPrice(price);
        price = "";
      }
    }

    // Вычисление цен опций с видами
    if (optionContainer.attr("data-option-type") !== undefined) {
      priceChanged = true;

      // Если опция выбрана
      if (optionSelected(option)) {
        let [option, type] = $(this).attr("data-option-type").split(".");
        price = +upgradeOptions[option]["types"][type]["price"];
        formattedPrice = formatPrice(price);

        // Если опция не выбрана
      } else {
        let typesPrices = [];

        for (let type in upgradeOptions[option]["types"]) typesPrices.push(upgradeOptions[option]["types"][type]["price"]);
        let minPrice = +Math.min(...typesPrices);

        let allPricesSame = typesPrices.filter((price) => price == minPrice).length == typesPrices.length ? true : false;
        let prfx = allPricesSame ? "" : "от ";

        formattedPrice = prfx + formatPrice(minPrice);
        price = "";
      }
    }

    // Установка рассчитанных цен
    let priceContainer = optionContainer.find(".upgrade-option__price");
    if (priceChanged) {
      if (priceContainer.text() !== formattedPrice) {
        optionContainer.attr("data-price", price);
        priceContainer.hide();
        priceContainer.text(formattedPrice);
        priceContainer.fadeIn(600);
      }
      if (formattedPrice.includes("Входит")) priceContainer.addClass("upgrade-option__price_included");
      else priceContainer.removeClass("upgrade-option__price_included");
    }
  });
}

// Установка обработчиков событий для блока с итоговыми стоимостями
function summaryHandlers(upgradeOptions) {
  // Фиксация блока при прокрутке
  if ($("#upgrade-calculator").length) {
    $(window).on("scroll", function () {
      let windowBottom = $(this).height() + $(this).scrollTop();
      let optionsTop = $("#upgrade-calculator .upgrade-options").offset().top;
      let optionsBottom = optionsTop + $("#upgrade-calculator .upgrade-options").height();
      if (windowBottom > optionsTop && windowBottom < optionsBottom) {
        $("#upgrade-calculator .summary-costs").addClass("summary-costs_fixed");
        $(".overlay-buttons").addClass("overlay-buttons_hidden");
      } else {
        $("#upgrade-calculator .summary-costs").removeClass("summary-costs_fixed");
        $(".overlay-buttons").removeClass("overlay-buttons_hidden");
      }
    });
  }

  // Отображение кнопки для прокрутки вверх
  $(window).on("scroll", function () {
    let windowTop = $(window).scrollTop();
    let windowBottom = windowTop + $(window).height();
    let optionsTop = $(".upgrade-options").offset().top;
    let optionsBottom = optionsTop + $(".upgrade-options").height();

    if (windowBottom > optionsTop && windowBottom < optionsBottom) $(".to-top-button").fadeIn(300);
    else $(".to-top-button").fadeOut(300);
  });
  $(".to-top-button").on("click", () =>
    $("html,body")
      .stop()
      .animate({ scrollTop: $(".car-info").offset().top - 60 })
  );

  // Отображение списка выбранных опций при наведении
  $(document).on("mouseover", ".summary-costs .options-number.active, .summary-costs__options-list", function () {
    $(".summary-costs__options-list").addClass("active");
  });
  $(document).on("mouseout", ".summary-costs .options-number.active, .summary-costs__options-list", function () {
    $(".summary-costs__options-list").removeClass("active");
  });

  // Удаление отдельной опции при клике по крестику
  $(document).on("click", ".list-item__remove-button", function () {
    let option = $(this).parents(".list-item").attr("data-name");
    let optionContainer = $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`);
    selectOption(optionContainer, upgradeOptions);
  });

  // Удаление всех опций при клике по кнопке
  $(document).on("click", ".summary-costs .remove-all", function () {
    for (let option in upgradeOptions) {
      if (upgradeOptions[option]["parts"] !== undefined) {
        let optionContainer = $("#upgrade-calculator .upgrade-options").find(`[data-name=${option}]`);
        if (optionSelected(option)) {
          let initialPrice = optionContainer.attr("data-initial-price");
          optionContainer.attr("data-price", "");
          optionContainer.find(".upgrade-option__price").text(initialPrice);
          optionContainer.find(".upgrade-option__price").fadeIn(600);
        }
      }
    }
    $("#upgrade-calculator .upgrade-options").find(`.upgrade-option.selected`).removeClass("selected");
    $("#upgrade-calculator .upgrade-options").find(`.upgrade-option`).find(".upgrade-option__overlay").removeClass("active");
    $("#upgrade-calculator .upgrade-options").find(`.upgrade-option`).find(".upgrade-option__overlay").html("");
    calculateSummary({ upgradeOptions });
  });
}
