<?

// Запрос цен на работы
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'http://185.20.226.75:3000/work-prices');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$html = curl_exec($ch);
curl_close ($ch);

// Сохранение в файл
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/work-prices.json', $html);

?>
