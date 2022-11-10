<? 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = sendSurvey($_REQUEST);
  header('content-type: application/json; charset=UTF-8');
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

// Отправка анкеты на сервер
function sendSurvey($request) {
  $name = $_GET["name"];
  $url = "http://185.20.226.75:3000/survey/?name=$name";
  
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded; charset=UTF-8']);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
  
  $html = curl_exec($ch);
  curl_close($ch);

  return json_decode($html, true);
}

?>