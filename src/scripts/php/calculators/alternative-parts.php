<?

// Подбор аналогов при передаче номера детали
if (isset($_REQUEST['number'])) {
  $parts = searchAlternativeParts($_REQUEST['number']);
  header('content-type: application/json; charset=UTF-8');
  echo json_encode($parts, JSON_UNESCAPED_UNICODE);
}

// Запрос данных об аналогах
function searchAlternativeParts($number, $onlyFavorites = true) {
  $url = "http://80.78.254.156/alternative-parts/$number?onlyFavorites=$onlyFavorites";
  
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