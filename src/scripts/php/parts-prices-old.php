<?

include_once 'libraries/phpQuery.php';

// Номера искомых деталей
$reductionGearOil = '83222365987'; // Масло в редукторе
$transferCaseOil = '83222409710'; // Масло в раздаточной коробке

// Подготовка массива артикулов
$numbers = [];
array_push($numbers, $reductionGearOil, $transferCaseOil);

$data = implode('
', $numbers);

// Первичная авторизация на сайте и сохранение cookie для дальнейшего использования
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://parts.major-auto.ru/Account/LogOn');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/parts-prices.cookie');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
  'UserName' => '+7(903)976-00-45',
  'Password' => 'Bsever',
  'btnLogOn' => 'Вход',
]);

$test = curl_exec($ch);
curl_close($ch);

// Основной запрос на страницу для поиска детали
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://parts.major-auto.ru/SearchNew/ByList');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/parts-prices.cookie');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
  'searchElements' => $data,
  'SearchByList' => 'Поиск',
]);

$html = curl_exec($ch);
echo $html;
curl_close($ch);

// Поиск цен и сохранение их в массив
$prices = [];
$html = phpQuery::newDocument($html);

for ($i = 0; $i < count($numbers); $i++) {
  $price = $html->find('#priceItemLbl_' . $i)->text();
  // Удаление пробелов из числа
  $price = str_replace(' ', '', $price);
  // Замена запятой на точку
  $price = str_replace(',', '.', $price);
  array_push($prices, +$price);
}

// Проведение наценки на детали
foreach ($prices as &$value) $value = round($value * 1.3, 2);

// Объединение цен
$data = [
  'finalDriveOil' => [
    'name' => 'Масло для редукторов',
    'price' => $prices[0],
  ],
  'transferBoxOil' => [
    'name' => 'Масло для раздаточной коробки',
    'price' => $prices[1]
  ]
];

// Сохранение в файл
// header('content-type: application/json; charset=UTF-8');
// echo json_encode($data, JSON_UNESCAPED_UNICODE);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/parts-prices.json', json_encode($data, JSON_UNESCAPED_UNICODE));

?>
