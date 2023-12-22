<?

define('BOT_TOKEN', '1204592494:AAFQwGuvtIygZ_4gpu2QZsMxDYPrZgkJvug');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Список администраторов
$admins = [ '426923021', '1287582209', '1013059892' ];

// Основное меню бота
$menu = [
  'keyboard' => [ ['', ''] ],
  'resize_keyboard' => true
];

// Получение обновлений
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update['message'])) {
  $from = $update['message']['from']['id'];
  $text = $update['message']['text'];
} else if (isset($update['callback_query'])) {
  $from = $update['callback_query']['from']['id'];
  $data = $update['callback_query']['data'];
}

// Установка Webhook
if (isset($_REQUEST['setWebhook'])) {
  echo '<p>Установили Webhook!</p>';
  echo apiRequest('setWebhook', [ 'url' => 'https://fgsever.ru/scripts/php/bot.php' ]);
}

// Получение Webhook
if (isset($_REQUEST['getWebhook'])) {
  echo json_encode(apiRequest('getWebhookInfo', []), JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT);
}

// Начало работы с ботом
if ($text == '/start') {

  $users = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/data/bot-users.json'), true);

  $users[$from] = $update['message']['from']['first_name'];
  if (!empty($update['message']['from']['last_name'])) $users[$from] .= ' ' . $update['message']['from']['last_name'];

  sendMessage($from, "Добро пожаловать в бот автосервиса FGSEVER!\n\nЕсли вы являетесь администратором данного бота, вы будете получать уведомления о заявках и обращениях с сайтов FGSEVER и M72SEVER.", ['parse_mode' => 'Markdown']);

  file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/bot-users.json', json_encode($users, JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT));

} else if (isset($text)) {

  sendMessage($from, "Если вы являетесь администратором данного бота, вы будете получать уведомления о заявках и обращениях с сайтов FGSEVER и M72SEVER.");

}

// Отправка уведомлений администраторам
if (isset($_REQUEST['notifyAdmins']) && $_REQUEST['token'] == BOT_TOKEN) {
  $text = $_REQUEST['text'];
  $parameters = $_REQUEST['parameters'];

  foreach ($admins as $to) sendMessage($to, $text, $parameters);
}

// Вспомогательные функции

// Осуществление cURL запроса
function exec_curl_request($handle) {
  $response = curl_exec($handle);

  if ($response === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    error_log("Curl returned error $errno: $error\n");
    curl_close($handle);
    return false;
  }

  $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
  curl_close($handle);

  if ($http_code >= 500) {

    // Защищаемся от DDOS, если что-то пошло не так
    sleep(10);
    return false;

  } else if ($http_code != 200) {

    $response = json_decode($response, true);
    error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
    if ($http_code == 401) {
      throw new Exception('Invalid access token provided');
    }
    return false;

  } else {

    $response = json_decode($response, true);
    if (isset($response['description'])) {
      error_log("Request was successful: {$response['description']}\n");
    }
    $response = $response['result'];

  }

  return $response;
}

// Основная функция запроса к Telegram API
function apiRequest($method, $parameters) {

  if (!is_string($method)) {
    error_log("Метод должен передаваться в виде строки.\n");
    return false;
  }

  if (!$parameters) {
    $parameters = array();
  } else if (!is_array($parameters)) {
    error_log("Параметры должны передаваться в виде массива.\n");
    return false;
  }

  foreach ($parameters as $key => &$val) {
    // Кодирование в строку JSON тех параметров, которые являются массивами. Например, reply_markup.
    if (!is_numeric($val) && !is_string($val)) {
      $val = json_encode($val);
    }
  }

  $url = API_URL.$method.'?'.http_build_query($parameters);

  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  return exec_curl_request($handle);
}

function sendMessage($to, $text, $options = []) {
  $parameters = [ 'chat_id' => $to, 'text' => $text ];
  if (!empty($options)) $parameters = array_merge($parameters, $options);
  apiRequest('sendMessage', $parameters);
}

?>
