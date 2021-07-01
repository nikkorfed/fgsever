<?

// Подбор аналогов в случае прямого обращения к скрипту и передачи номера детали в строке запроса
if (isset($_REQUEST['number'])) {
  $parts = searchAlternativeParts($_REQUEST['number']);
  echo '<pre>' . json_encode($parts, JSON_PRETTY_PRINT+JSON_UNESCAPED_UNICODE) . '</pre>';
}

// Основная функция, выполняющая поиск и подбор аналогов
function searchAlternativeParts ($number) {

  // Авторизация
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, 'https://shate-m.ru/Account/Login');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_COOKIEJAR, 'alternative-parts.cookie');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'login' => 'MIKANIA',
    'password' => '4996383577',
    'rememberMe' => true
  ]);

  curl_exec($ch);
  curl_close($ch);

  // Запрос информации об оригинальной запчасти
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, 'https://shate-m.ru/api/SearchPart/PartsByNumber?number=' . $number);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_COOKIEFILE, 'alternative-parts.cookie');

  $data = json_decode(curl_exec($ch), true);
  // var_dump($data);
  curl_close($ch);

  // Узнаём ID запчасти для BMW
  foreach ($data as $element) {
    if ($element['tradeMarkName'] == 'BMW') $id = $element['id'];
  }

  // echo 'id детали: ' . $id;

  // Запрос аналогов c собственных складов shate-m
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, 'https://shate-m.ru/api/searchPart/GetAnalogsInternalPrices?sortAscending=true&sortField=&showPurchasePrice=0&selectedAdress=%D0%941&partId=' . $id);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_COOKIEFILE, 'alternative-parts.cookie');

  $data = json_decode(curl_exec($ch), true);
  // print_r($data);
  curl_close($ch);

  // Отбор запчастей с собственных складов shate-m от подходящих проиводителей
  $internalAnalogs = $data['data']['items'];
  $temporaryParts = filterBrands($internalAnalogs);
  $parts = filterPrices($temporaryParts);

  // Запрос аналогов у сторонних поставщиков
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, 'https://shate-m.ru/api/searchPart/GetAnalogsExternalPrices?sortAscending=true&sortField=&showPurchasePrice=0&selectedAdress=%D0%941&partId=' . $id);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_COOKIEFILE, 'alternative-parts.cookie');

  $data = json_decode(curl_exec($ch), true);
  curl_close($ch);

  // Дополнительный поиск деталей-аналогов у сторонних поставщиков
  $externalAnalogs = $data['data']['items'];
  $temporaryParts = filterBrands($externalAnalogs, false);
  $externalParts = filterPrices($temporaryParts);

  // Добавление деталей от сторонних поставщиков в выдачу, если там ещё нет такого производителя
  foreach ($externalParts as $part => $partValue) {
    $partAlreadyExists = false;
    foreach ($parts as $key => $value) { if (str_replace('coal-', '', $part) === str_replace('coal-', '', $key)) $partAlreadyExists = true; }
    if ($partAlreadyExists === false) $parts[$part] = $partValue;
  }

  if (empty($parts)) return showNoAlternatives();
  return $parts;

}

// Функция для отбора запчастей от подходящих производителей
function filterBrands ($data, $findBMWInText = true) {
  $temporaryParts = [];
  foreach ($data as $element) {
    if ($findBMWInText === false || $findBMWInText === true && mb_stripos($element['partInfo']['itemComment'], 'BMW') !== false) {
      if (mb_stripos($element['partInfo']['description'], 'Фильтр воздушный') !== false || mb_stripos($element['partInfo']['description'], 'Фильтр салона') !== false) {
        switch ($element['partInfo']['tradeMarkName']) {
          case 'KNECHT':
          case 'MANN':
          case 'MANN-FILTER':
          case 'BOSCH':
          case 'CORTECO':
          case 'MAHLE':
            array_push($temporaryParts, $element);
            break;
        }
      } else if (mb_stripos($element['partInfo']['description'], 'Фильтр') !== false) {
        switch ($element['partInfo']['tradeMarkName']) {
          case 'KNECHT':
          case 'MANN':
          case 'MAHLE':
            array_push($temporaryParts, $element);
            break;
        }
      } else if (mb_stripos($element['partInfo']['description'], 'Свеча') !== false) {
        switch ($element['partInfo']['tradeMarkName']) {
          case 'CHAMPION':
          case 'BOSCH':
          case 'NGK':
            array_push($temporaryParts, $element);
            break;
        }
      } else if (mb_stripos($element['partInfo']['description'], 'тормоз') !== false || mb_stripos($element['partInfo']['description'], 'датчик') !== false || mb_stripos($element['partInfo']['description'], 'диск') !== false || mb_stripos($element['partInfo']['description'], 'disc') !== false) {
        switch ($element['partInfo']['tradeMarkName']) {
          case 'ATE':
          case 'BOSCH':
          case 'BREMBO':
          case 'TEXTAR':
          case 'TRW':
            array_push($temporaryParts, $element);
            break;
        }
      }
    }
  }
  return $temporaryParts;
}

// Функция, для отбора цен с подходящих складов
function filterPrices ($temporaryParts) {
  $parts = [];
  foreach ($temporaryParts as $element) {
    $description = $element['partInfo']['description'];
    $name = $element['partInfo']['tradeMarkName'];
    $number = $element['partInfo']['article'];
    $part = strtolower($name);
    if (stripos($element['partInfo']['itemComment'], 'угольный') !== false) {
      $part = 'coal-' . $part;
      $name .= ', угольный';
    }
    foreach ($element['prices'] as $element) {
      if (($element['city'] == 'Подольск' && $element['locationColor'] == 'D4FFB8') || ($element['city'] == 'Минск' && $element['locationColor'] == 'FFEDC1')) {
        $parts[$part] = [
          'description' => $description,
          'name' => $name,
          'number' => $number,
          'price' => $element['price']
        ];
        break;
      } else if ($element['city'] == 'Екатеринбург' && $element['locationColor'] == 'F7DFFF') {
        $parts[$part] = [
          'description' => $description,
          'name' => $name . ' (Доставка ' . $element['deliveryDate'] . ')',
          'number' => $number,
          'price' => $element['price']
        ];
        break;
      } else {
        $parts[$part] = [
          'description' => $description,
          'name' => $name . ' (Доставка ' . $element['deliveryDate'] . ')',
          'number' => $number,
          'price' => $element['price'],
          'comment' => 'От сторонних поставщиков'
        ];
        break;
      }
    }
  }
  return $parts;
}

// Функция, на случай если детали не найдены
function showNoAlternatives () {
  $parts['no-alternatives'] = [];
  return $parts;
}

?>