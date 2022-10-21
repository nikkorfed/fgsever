<?

require_once '../libraries/simple_html_dom.php';
require_once '../libraries/phpQuery.php';

// Поиск оригиналов при передаче номеров деталей
if (isset($_REQUEST['partNumbers'])) {
  $parts = searchOriginalParts($_REQUEST['partNumbers']);
  header('content-type: application/json; charset=UTF-8');
  echo json_encode($parts, JSON_UNESCAPED_UNICODE);
}

// Запрос оригинальных запчастей
function searchOriginalParts($partNumbers) {
  $url = "http://194.58.98.247:3000/parts/original/$partNumbers";
  
  $ch = curl_init();
  
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  
  $html = curl_exec($ch);
  curl_close($ch);

  return json_decode($html, true);
}

?>
