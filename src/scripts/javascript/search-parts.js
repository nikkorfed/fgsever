(function () {
  // Подбор запчастей при передаче данных в адресе страницы
  if (window.location.search && $("#search-parts").length) {
    let partNumbers = new URLSearchParams(location.search).get("partNumbers");

    if (partNumbers) {
      $("#search-parts").find("#part-numbers").val(partNumbers);
      searchParts();
    }
  }

  // Подбор запчастей при ручном вводе VIN-номера
  $("#search-parts")
    .find(".part-numbers-input")
    .on("submit", function (e) {
      e.preventDefault();
      if (partNumberCorrect()) {
        searchParts();
      }
    });

  // Переключение между оригинальными и неоригинальными деталями
  $("#search-parts .result .original-or-alternative").on("click", ".option:not(.loading)", function (e) {
    $("#search-parts .result .original-or-alternative .option").removeClass("selected");
    $(this).addClass("selected");

    // При клике по кнопке "Оригинальные", переключаемся на оригинальные запчасти
    if ($(this).attr("data-name") == "original") {
      $("#search-parts .result table")
        .find("tr[class!=table-header] .option:first-child")
        .each(function () {
          chooseOption($(this), e);
        });

      // При клике по кнопке "Аналоги", выбираем первый из аналогов
    } else {
      $("#search-parts .result table")
        .find("tr[class!=table-header] .option:nth-child(2)")
        .each(function () {
          chooseOption($(this), e);
        });
    }

    // Перерасчёт стоимости деталей
    calculatePartsCost();
  });

  // Выбор необходимых деталей для обслуживания
  $("#search-parts .result table").on("click", "tr[data-number]", function () {
    $(this).toggleClass("disabled");

    // Расчёт стоимости деталей
    calculatePartsCost();
  });

  // Выбор запчастей от разных производителей (опций)
  $("#search-parts .result table").on("click", "tr:not(.disabled) .option", function (e) {
    chooseOption($(this), e);
  });

  // Фиксация блока с итоговыми стоимостями
  if ($("#search-parts").length) {
    $(window).on("scroll", function () {
      let windowBottom = $(this).height() + $(this).scrollTop();
      let maintenancePartsTop = $("#search-parts .parts").offset().top;
      let maintenancePartsBottom = maintenancePartsTop + $("#search-parts .parts").height();
      if (windowBottom > maintenancePartsTop && windowBottom < maintenancePartsBottom) {
        $("#search-parts .summary-costs").addClass("summary-costs_fixed");
        $(".overlay-buttons").addClass("overlay-buttons_hidden");
      } else {
        $("#search-parts .summary-costs").removeClass("summary-costs_fixed");
        $(".overlay-buttons").removeClass("overlay-buttons_hidden");
      }
    });
  }

  // --- Основные функции --- //

  // Проверка правильности ввода номера запчасти
  function partNumberCorrect() {
    $(".error_part-number").remove();
    if ($("#part-numbers").val().length >= 11) {
      let filtered = $("#part-numbers").val().replace(/\s/g, "");
      $("#part-numbers").val(filtered);
      return true;
    } else {
      $("#part-numbers").after('<div class="input-field__error error_part-number">Необходимо ввести 11 знаков</div>');
      return false;
    }
  }

  // Основная функция, запускающая поиск запчастей
  function searchParts() {
    // Скрытие быстрых кнопок
    hideSpecialButtons();

    // Сбор данных из формы
    let partNumbers = $("#search-parts").find(".part-numbers-input #part-numbers").val();

    // Изменение адреса страницы
    history.pushState(null, null, "?partNumbers=" + partNumbers);

    // Открытие всплывающего окна загрузки
    $.fancybox.open({ src: "#calculation", opts: { modal: true } });

    // Запрос информации о запчастях
    requestParts(partNumbers);
  }

  // Отображение ошибки о том, что ни одной детали не было найдено
  function renderPartsNotFoundError() {
    $("#search-parts .result .parts").hide();
    $("#search-parts .result .summary-costs").hide();
    setTimeout(() => $.fancybox.open({ src: "#parts-not-found" }), 300);
  }

  // Запрос запчастей
  function requestParts(partNumbers) {
    $.ajax({
      url: "/scripts/php/calculators/parts.php",
      type: "POST",
      data: { partNumbers },
      success: (result) => {
        if (!result.error) {
          $.fancybox.close();
          renderOriginalParts(result);
          renderAlternativeParts(result, 0);
        } else if (result.error == "parts-not-found") {
          renderPartsNotFoundError();
        }
      },
    });

    // Отображение блока для вывода результатов
    $("#search-parts .result").show();
    $("#search-parts .result .parts").show();
    $("#search-parts .result .summary-costs").show();

    // Очистка таблицы
    $("#search-parts .result table").hide();
    $("#search-parts .result table").empty();
    $("#search-parts .summary-costs .parts-cost .value").text(formatPrice(0, false));
    $("#search-parts .summary-costs .works-cost .value").text(formatPrice(0, false));
    $("#search-parts .summary-costs .total-cost .value").text(formatPrice(0, false));

    // Отображение индикатора загрузки деталей
    $("#search-parts .parts-loading-icon").show();
  }

  // Отображение оригинальных запчастей
  function renderOriginalParts(parts) {
    // Скрытие индикатора загрузки деталей
    $("#search-parts .parts-loading-icon").hide();

    // Подготовка таблицы
    $("#search-parts .result table").append('<tr class="table-header"><td>Наименование детали</td><td>Стоимость детали</td></tr>');

    // Заполнение таблицы деталями для обслуживания
    for (let part of parts) {
      // Добавляем строку
      let partName = part["name"] ?? "Неизвестная запчасть";
      $("#search-parts .result table").append(
        `<tr data-number="${part["number"]}"><td colspan="2"><div class="info"><span class="name">${partName}</span><span class="number">${part["number"]}</span></div><div class="options"></div></div></div></td></tr>`
      );

      // Отключено, так как цены от основного постащика временно неактуальны

      // // Оригинал (от основного поставщика)
      // let partPrice = part["price"] ?? "";
      // $("#search-parts .result table")
      //   .find(`[data-number="${part["number"]}"] .options`)
      //   .append(`<div class="option" data-name="original" data-number="${part["number"]}" data-part-price="${partPrice}"><span class="part">Оригинал</span><span class="price">${formatPrice(partPrice)}</span></div>`);

      // // Делаем первую опцию активной по-умолчанию и устанавливаем её стоимость
      // let firstOptionPrice = $("#search-parts .result table")
      //   .find("[data-number=" + part["number"] + "] .options .option:first-child")
      //   .addClass("selected")
      //   .attr("data-part-price");
      // $("#search-parts .result table")
      //   .find("[data-number=" + part["number"] + "]")
      //   .attr("data-part-price", firstOptionPrice);
      // $("#search-parts .result table")
      //   .find("[data-number=" + part["number"] + "] .part-price .price")
      //   .text(formatPrice(firstOptionPrice));
    }

    // Отображение таблицы
    setTimeout(() => $("#search-parts .result table").fadeIn(300), 0);

    // Отображение индикации загрузки деталей-аналогов
    $("#search-parts .result").find(".original-or-alternative .option[data-name=alternative]").addClass("loading");

    // Расчёт итоговых стоимостей
    calculatePartsCost();
  }

  // Подбор и отображение деталей-аналогов
  function renderAlternativeParts(parts, index) {
    if (index >= parts.length) {
      $("#search-parts .result").find(".original-or-alternative .option[data-name=alternative]").removeClass("loading");
      return;
    }

    // Подбор деталей-аналогов, если есть оригинальный номер
    if (parts[index]["number"]) {
      // Отображение анимации загрузки
      $("#search-parts .result table")
        .find("[data-number=" + parts[index]["number"] + "] .options")
        .addClass("loading");

      // Отправка запроса
      $.ajax({
        url: "/scripts/php/calculators/alternative-parts.php",
        type: "GET",
        data: { number: parts[index]["number"].replace(/\s/g, "") },
        success: (data) => {
          // Обработка результатов
          parts[index]["options"] = data;

          // Если аналогов не найдено, вывод соответствующей надписи
          if (parts[index]["options"].error === "no-alternatives") {
            $("#search-parts .result table")
              .find("[data-number=" + parts[index]["number"] + "] .options")
              .addClass("no-alternatives");

            // Если были найдены детали-аналоги
          } else {
            // Если не найден оригинал, взять имя из первого аналога
            let originalPartName = $("#search-parts .result table").find("[data-number=" + parts[index]["number"] + "] .name");
            if (originalPartName.text() == "Неизвестная запчасть") {
              originalPartName.text(Object.values(parts[index]["options"])[0].description);
            }

            // Добавление опций
            for (let option in parts[index]["options"]) {
              $("#search-parts .result table")
                .find("[data-number=" + parts[index]["number"] + "] .options")
                .append(
                  `<div class="option" data-name="${option}" data-description="${
                    parts[index]["options"][option]["description"]
                  }" data-number="${parts[index]["options"][option]["number"]}" data-part-price="${
                    parts[index]["options"][option]["price"]
                  }" data-from="${parts[index]["options"][option]["from"]}"><span class="part"><div class="text">${
                    parts[index]["options"][option]["name"]
                  }</div><div class="info-button"></div><div class="info">Поставщик: ${
                    parts[index]["options"][option]["from"]
                  }</div></span><span class="price">${formatPrice(parts[index]["options"][option]["price"])}</span></div>`
                );
            }

            // Делаем первую опцию активной по-умолчанию и устанавливаем её стоимость
            let firstOptionPrice = $("#search-parts .result table")
              .find("[data-number=" + parts[index]["number"] + "] .options .option:first-child")
              .addClass("selected")
              .attr("data-part-price");
            $("#search-parts .result table")
              .find("[data-number=" + parts[index]["number"] + "]")
              .attr("data-part-price", firstOptionPrice);
            $("#search-parts .result table")
              .find("[data-number=" + parts[index]["number"] + "] .part-price .price")
              .text(formatPrice(firstOptionPrice));

            // Расчёт итоговых стоимостей
            calculatePartsCost();

            // Плавное появление опций
            let options = $("#search-parts .result table")
              .find("[data-number=" + parts[index]["number"] + "]")
              .find(".option:not(:first-child)");
            options.css("opacity", 0);
            options.animate({ opacity: 1 }, 300, function () {
              options.css("opacity", "");
            });
          }

          // Скрытие анимации загрузки
          $("#search-parts .result table")
            .find("[data-number=" + parts[index]["number"] + "] .options")
            .removeClass("loading");

          // Переход к заполнению следующей строки
          renderAlternativeParts(parts, ++index);
        },
      });

      // Если же оригинального номера детали нет, то прямой переход к следующей детали
    } else {
      renderAlternativeParts(parts, ++index);
    }
  }

  // Форматирование цены
  function formatPrice(number, zeroShouldMeanFree) {
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

    // Замена названия детали
    if (option.attr("data-description") !== undefined) {
      let newName = option.attr("data-description");
      option
        .parent()
        .siblings(".info")
        .find(".name")
        .fadeOut(300, function () {
          $(this).text(newName);
          $(this).fadeIn(300);
        });
    }

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
    calculatePartsCost();
    return;
  }

  // Расчёт итоговых стоимостей деталей
  function calculatePartsCost() {
    // Сброс стоимости всех деталей
    let partsCost = 0;

    // Расчёт стоимости выбранных деталей
    $("#search-parts .result tr[data-number]:not(.disabled)").each(function () {
      let partPrice = $(this).attr("data-part-price");
      let quantity = $(this).attr("data-quantity");
      if (partPrice !== undefined) {
        if (quantity) {
          partsCost += Number($(this).attr("data-part-price")) * Number($(this).attr("data-quantity"));
        } else {
          partsCost += Number($(this).attr("data-part-price"));
        }
      }
    });

    // Вывод результатов
    if ($(".summary-costs .parts-cost .value").text() != formatPrice(partsCost)) {
      $(".summary-costs .parts-cost .value").fadeOut(300, function () {
        $(this).text(formatPrice(partsCost, false));
        $(this).fadeIn(300);
      });
    }
  }
})();
