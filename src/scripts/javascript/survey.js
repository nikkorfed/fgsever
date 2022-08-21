const memory = {};

$("section.survey form").on("click", ".button:not([type=submit])", function () {
  // Валидация данных
  const step = $(this).parents(".step");
  const answers = step.find(".answers");

  let allValid = true;
  answers.each(function () {
    let radioValid = true;
    const radioInputs = $(this).find("input[type=radio]");
    if (radioInputs.length > 0) {
      radioValid = false;
      for (let input of radioInputs) if (input.checked) radioValid = true;
      if (!radioValid) {
        if (!$(this).prev(".required").length) $(this).before('<p class="required">Пожалуйста выберите один из вариантов.</p>');
      }
    }

    let checkboxValid = true;
    const checkboxInputs = $(this).find("input[type=checkbox]");
    if (checkboxInputs.length > 0) {
      checkboxValid = false;
      for (let input of checkboxInputs) if (input.checked) checkboxValid = true;
      if (!checkboxValid) {
        if (!$(this).prev(".required").length) $(this).before('<p class="required">Пожалуйста выберите один или несколько вариантов.</p>');
      }
    }

    let textValid = true;
    const textInputs = $(this).find("textarea.input-required:not(.hidden)");
    if (textInputs.length > 0) {
      for (let input of textInputs) {
        if (!$(input).val()) textValid = false;
        if (!$(input).val() && !$(input).prev(".required").length)
          $(input).before('<p class="required">Пожалуйста введите ответ в поле ниже.</p>');
      }
    }

    if (!radioValid || !checkboxValid || !textValid) allValid = false;
  });

  if (allValid === false) return;

  // Переключение вопроса
  if ($(this).hasClass("next")) {
    $(this).parents(".step").removeClass("active");
    $(this).parents(".step").next().addClass("active");
  } else if ($(this).hasClass("back")) {
    $(this).parents(".step").removeClass("active");
    $(this).parents(".step").prev().addClass("active");
  }
});

$("section.survey form").on("change", "textarea", function () {
  if ($(this).val()) $(this).prev(".required").remove();
});

$("section.survey form").on("click", "input", function (e) {
  if ($(e.target).attr("type") === "radio" || $(e.target).attr("type") === "checkbox") {
    $(e.target).parents(".answers").prev(".required").remove();
  }

  if (e.target.name === "Оценка условий труда") {
    const target = $(e.target).parents(".answers").next(".clarify");
    target.removeClass("hidden");
    if (+e.target.value < 5) {
      if (memory["Почему меньше пяти"]) {
        $(e.target).parents(".answers").after(memory["Почему меньше пяти"]);
        delete memory["Почему меньше пяти"];
      }
    } else {
      if (!memory["Почему меньше пяти"]) memory["Почему меньше пяти"] = target.detach();
    }
  }

  if (e.target.name === "Улучшение рабочих условий") {
    const conditions = [
      "Закупка дополнительного инструмента",
      "Дополнительные сотрудники",
      "Расширение предоставляемых услуг сервисом",
      "Другое",
    ];
    const selector = conditions.map((condition) => `[value="${condition}"]:checked`).join(",");
    const elements = $(e.target).parents(".answers").find(selector);

    let shouldClarify = false;
    elements.each(function () {
      if (conditions.includes(this.value)) shouldClarify = true;
    });

    const target = $(e.target).parents(".answers").find(`textarea[name="Улучшение рабочих условий (детали)"]`);

    target.removeClass("hidden");
    if (shouldClarify) {
      if (memory["Улучшение рабочих условий (детали)"]) {
        $(e.target).parents(".answers").append(memory["Улучшение рабочих условий (детали)"]);
        delete memory["Улучшение рабочих условий (детали)"];
      }
    } else {
      if (!memory["Улучшение рабочих условий (детали)"]) memory["Улучшение рабочих условий (детали)"] = target.detach();
    }
  }

  if (e.target.name === "Желаете официально трудоустроиться") {
    const target = $(e.target).parents(".answers").find(`textarea[name="Желаете официально трудоустроиться (другое)"]`);
    target.removeClass("hidden");
    if (e.target.value === "Другой ответ") {
      if (memory["Желаете официально трудоустроиться (другое)"]) {
        $(e.target).parents(".answers").append(memory["Желаете официально трудоустроиться (другое)"]);
        delete memory["Желаете официально трудоустроиться (другое)"];
      }
    } else {
      if (!memory["Желаете официально трудоустроиться (другое)"]) memory["Желаете официально трудоустроиться (другое)"] = target.detach();
    }
  }

  if (e.target.name === "Где провести новогодний корпоратив") {
    const target = $(e.target).parents(".answers").find(`textarea[name="Где провести новогодний корпоратив (свой ответ)"]`);
    target.removeClass("hidden");
    if (e.target.value === "Свой ответ") {
      if (memory["Где провести новогодний корпоратив (свой ответ)"]) {
        $(e.target).parents(".answers").append(memory["Где провести новогодний корпоратив (свой ответ)"]);
        delete memory["Где провести новогодний корпоратив (свой ответ)"];
      }
    } else {
      if (!memory["Где провести новогодний корпоратив (свой ответ)"])
        memory["Где провести новогодний корпоратив (свой ответ)"] = target.detach();
    }
  }
});

$("section.survey form").on("submit", function (e) {
  e.preventDefault();

  let name = $(this).attr("name");
  let data = $(this).serialize();

  $.ajax({
    url: `/scripts/php/survey.php?name=${name}`,
    method: "POST",
    data,
    success: function () {
      $.fancybox.open({ src: "#survey-success", opts: { touch: false } });
    },
  });
});
