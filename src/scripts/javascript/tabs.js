// Ручное переключение вкладок
$('.tabs').on('click', '.tabs-item', function() {
  let target = $(this).attr('data-tab');
  let tabsContainer = $(this).parent('.tabs');
  let tabs = tabsContainer.find('.tabs-item');
  let allTargets = tabsContainer.parent().parent().find('[data-target]');
  let currentTargets = allTargets.filter(`[data-target=${target}]`);

  history.pushState(null, null, '#' + target);
  
  tabs.removeClass('active');
  $(this).addClass('active');

  if (target != 'all') {
    // allTargets.fadeOut(300);
    allTargets.removeClass('active');
    // allTargets.promise().done(function() {
    //   currentTargets.fadeIn(300);
    // });
    currentTargets.addClass('active');
  } else {
    // allTargets.fadeIn(300);
    allTargets.addClass('active');
  }
})

// Автоматический выбор вкладки при передаче её id в адресе страницы
if (window.location.hash) {
  $('.tabs').find(`.tabs-item[data-tab='${window.location.hash.substr(1)}']`).click();
}