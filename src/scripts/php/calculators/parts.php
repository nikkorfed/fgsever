<?

require_once '../libraries/simple_html_dom.php';
require_once '../libraries/phpQuery.php';

(function () {
  if (isset($_REQUEST['partNumbers'])) {

    // Сохраняем вводные данные
    $partNumbers = $_REQUEST['partNumbers'];

    // Подготовка массива запчастей
    $parts = [];
    foreach (explode(',', $partNumbers) as $number) {
      $parts[] = ['number' => $number];
    }

    // Авторизация на сайте поставщика
    $login = 'Дерюгин ПС';
    $password = '3306';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'http://sprolf.ru/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/sprolf.cookie');
    curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$login&password=$password&cmdweblogin=");
    curl_setopt($ch, CURLOPT_POST, 1);

    curl_exec($ch);
    curl_close ($ch);

    // Запрос для поиска деталей
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'http://sprolf.ru/index.php?id=137');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/sprolf.cookie');
    curl_setopt($ch, CURLOPT_POSTFIELDS, "articles=$partNumbers&priority=cost&storeid=0&search=%D0%9F%D0%BE%D0%B4%D0%BE%D0%B1%D1%80%D0%B0%D1%82%D1%8C");
    curl_setopt($ch, CURLOPT_POST, 1);

    $html = curl_exec($ch);
    curl_close ($ch);

    // Поиск названия и цены
    foreach (phpQuery::newDocument($html)->find("#multisearch tr") as $row) {
      $row = pq($row);
      $number = str_replace(' ', '', $row->find('td:nth-child(2)')->text());
      foreach ($parts as &$part) {
        if (str_replace(' ', '', $part['number']) == $number && !isset($part['name']) && !isset($part['price'])) {
          $part['name'] = $row->find("td:nth-child(6)")->text();
          $part['price'] = +$row->find("td:nth-child(7)")->text() * 1.3;
        }
      }
    }

    // // Удаление какой-либо детали, если она не была найдена (отсутствует номер, количество, цена детали и работ)
    // foreach ($parts as $key => $element) {
    //   if (empty($element['number']) && empty($element['quantity']) && empty($element['price'])) unset($parts[$key]);
    // }

    // Конвертирование данных в JSON строку и её вывод
    header('content-type: application/json; charset=UTF-8');
    echo json_encode($parts, JSON_UNESCAPED_UNICODE);
  }
})();

?>
