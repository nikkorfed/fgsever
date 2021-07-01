<?

include_once 'libraries/phpQuery.php';

// Номера искомых деталей
$reductionGearOil = '83222365987'; // Масло в редукторе
$transferCaseOil = '83222409710'; // Масло в раздаточной коробке

// Подготовка массива артикулов
$numbers = [];
array_push($numbers, $reductionGearOil, $transferCaseOil);

$data = implode(',', $numbers);

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
curl_setopt($ch, CURLOPT_POSTFIELDS, "articles=$data&priority=cost&storeid=0&search=%D0%9F%D0%BE%D0%B4%D0%BE%D0%B1%D1%80%D0%B0%D1%82%D1%8C");
curl_setopt($ch, CURLOPT_POST, 1);

$html = curl_exec($ch);
curl_close ($ch);

// Подготовка данных
$data = [
  'finalDriveOil' => [ 'name' => 'Масло для редукторов' ],
  'transferBoxOil' => [ 'name' => 'Масло для раздаточной коробки' ]
];

// Поиск и сохраннение цен
$prices = [];
foreach (phpQuery::newDocument($html)->find("#multisearch tr") as $row) {
  $row = pq($row);
  $number = $row->find('td:nth-child(2)')->text();
  
  if ($number == $reductionGearOil && !isset($data['finalDriveOil']['price']))
    $data['finalDriveOil']['price'] = round(+$row->find("td:nth-child(7)")->text() * 1.3);
  if ($number == $transferCaseOil && !isset($data['transferBoxOil']['price']))
    $data['transferBoxOil']['price'] = round(+$row->find("td:nth-child(7)")->text() * 1.3);
}

// Сохранение в файл
// header('content-type: application/json; charset=UTF-8');
// echo json_encode($data, JSON_UNESCAPED_UNICODE);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/parts-prices.json', json_encode($data, JSON_UNESCAPED_UNICODE));

?>
