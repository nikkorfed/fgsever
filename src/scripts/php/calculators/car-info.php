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
  $url = "http://localhost:3002/aos-parser/?vin=$vin";
  if ($from) $url .= "&from=$from";
  
  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  
  $html = curl_exec($ch);
  curl_close($ch);

  return json_decode($html, true);
}

// Запрос изображений автомобиля
function requestCarImages($vin) {
  $url = "http://80.78.254.156/aos-parser/images/?vin=$vin";

  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  
  $html = curl_exec($ch);
  curl_close($ch);

  $html = json_decode($html, true);

  // Если изображений нет, то выдавать { error: "images-not-found" }

  $protocol = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
  $host = $_SERVER['HTTP_HOST'];

  foreach ($html as $image => $url) {
    $html[$image] = $protocol . $host . "/scripts/php/calculators/car-images.php?vin=$vin&image=$image";
  }

  return $html;
}

?>