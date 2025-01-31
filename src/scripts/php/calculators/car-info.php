<? 

include_once '../libraries/simple_html_dom.php';
include_once 'car-images.php';

// Выдача данных в формате JSON при передаче VIN
if (isset($_REQUEST['vin']) && !isset($_REQUEST['mileage'])) {
  if (isset($_REQUEST['data']) && $_REQUEST['data'] == 'images') $result = requestCarImages($_REQUEST['vin']);
  else $result = requestCarInfo($_REQUEST['vin'], $_REQUEST['from']);

  header('content-type: application/json; charset=UTF-8');
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

// Запрос данных об автомобиле
function requestCarInfo($vin, $from) {
  $url = "http://185.20.226.75:3000/aos-parser/?vin=$vin";
  if ($from) $url .= "&from=$from";
  
  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  
  $html = curl_exec($ch);
  curl_close($ch);

  $html = json_decode($html, true);

  if (isset($html['image']) && mb_strpos($html['image'], '/aos-parser/images') !== false) {
    $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];

    $fullVin = $html['vin'];
    $html['image'] = $protocol . $host . "/scripts/php/calculators/car-images.php?vin=$fullVin&image=image";
  }

  return $html;
}

// Запрос изображений автомобиля
function requestCarImages($vin) {
  $url = "http://185.20.226.75:3000/aos-parser/images/?vin=$vin";

  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  
  $html = curl_exec($ch);
  curl_close($ch);

  $html = json_decode($html, true);

  if (isset($html['error'])) return [ 'error' => 'images-not-found' ];

  $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
  $host = $_SERVER['HTTP_HOST'];

  foreach ($html as $image => $url) {
    $html[$image] = $protocol . $host . "/scripts/php/calculators/car-images.php?vin=$vin&image=$image";
  }

  return $html;
}

?>