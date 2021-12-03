const memory = {};

$("section.advanced-survey form").on("click", "input", function (e) {
  if (e.target.name === "Оценка условий труда") {
    const target = $(e.target).parents(".answers").find(".clarify");
    target.removeClass("hidden");
    if (+e.target.value < 5) {
      if (memory["Почему меньше пяти"]) {
        $(e.target).parents(".answers").append(memory["Почему меньше пяти"]);
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
      if (!memory["Желаете официально трудоустроиться (другое)"])
        memory["Желаете официально трудоустроиться (другое)"] = target.detach();
    }
  }

  if (e.target.name === "Где провести новогодний корпоратив") {
    const target = $(e.target)
      .parents(".answers")
      .find(`textarea[name="Где провести новогодний корпоратив (свой ответ)"]`);
    console.log(target);
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

$("section.advanced-survey form").on("submit", function (e) {
  e.preventDefault();

  let data = $(this).serialize();
  $(".results").text(decodeURI(data));
});
