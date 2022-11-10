<?

function main () {

  // Проверка на превышение лимита
  if (!isset($_REQUEST['admin'])) {
    $requests = json_decode(file_get_contents('../../../data/requests.json'), true);
    $ip = $_SERVER['REMOTE_ADDR'];

    $lastTime = $requests[$ip]['time']; $currentTime = time();
    if ($currentTime - $lastTime >= 60 * 60) {
      unset($requests[$ip]);
      $requests[$ip]['time'] = $currentTime;
    }
    
    if (isset($requests[$ip]['counter']) && $requests[$ip]['counter'] == 5) {
      header('content-type: application/json; charset=UTF-8');
      echo json_encode([ 'error' => 'limit-exceeded' ], JSON_UNESCAPED_UNICODE);
      return;
    } else if (isset($requests[$ip]['counter'])) {
      $requests[$ip]['counter'] += 1;
    } else if (!isset($requests[$ip]['counter'])) {
      $requests[$ip]['counter'] = 1;
    }
    
    file_put_contents('../../../data/requests.json', json_encode($requests, JSON_UNESCAPED_UNICODE+JSON_PRETTY_PRINT));
  }

  $carModel = $_REQUEST['model'];
  $modelCode = $_REQUEST['modelCode'];
  $productionDate = strtotime($_REQUEST['productionDate']);
  // $productionDate = strtotime('05.06.2014');
  $currentOptions = $_REQUEST['options'];

  // Определение серии и модели
  switch ($modelCode) {
    case 'F20':
    case 'F21':
    case 'F48':
      $series = 'f-series';
      $model = '3-series';
      break;
    case 'F22':
    case 'F23':
    case 'F87':
    case 'F45':
    case 'F46':
      $series = 'f-series';
      $model = '3-series';
      break;
    case 'F30':
    case 'F31':
    case 'F34':
    case 'F80':
      $series = 'f-series';
      $model = '3-series';
      break;
    case 'F32':
    case 'F33':
    case 'F36':
    case 'F82':
    case 'F83':
      $series = 'f-series';
      $model = '3-series';
      break;
    case 'F10':
    case 'F11':
    case 'F07':
      $series = 'f-series';
      $model = '5-series';
      break;
    case 'F06':
    case 'F12':
    case 'F13':
      $series = 'f-series';
      $model = '5-series';
      break;
    case 'F01':
    case 'F02':
      $series = 'f-series';
      $model = '5-series';
      break;
    case 'F25':
    case 'F26':
      $series = 'f-series';
      $model = 'x3';
      break;
    case 'F15':
    case 'F16':
      $series = 'f-series';
      $model = 'x5';
      break;
    case 'F40':
    case 'F44':
      $series = 'g-series';
      $model = '2-series';
      break;
    case 'G20':
    case 'G21':
    case 'G22':
    case 'G23':
    case 'G26':
    case 'G80':
    case 'G81':
    case 'G82':
    case 'G83':
      $series = 'g-series';
      $model = '3-series';
      break;
    case 'G30':
    case 'G31':
    case 'F90':
      $series = 'g-series';
      $model = '5-series';
      break;
    case 'G32':
      $series = 'g-series';
      $model = '6-series';
      break;
    case 'G11':
    case 'G12':
      $series = 'g-series';
      $model = '7-series';
      break;
    case 'G14':
    case 'G15':
    case 'G16':
      $series = 'g-series';
      $model = '8-series';
      break;
    case 'G01':
    case 'F97':
      $series = 'g-series';
      $model = 'x3';
      break;
    case 'G02':
    case 'F98':
      $series = 'g-series';
      $model = 'x4';
      break;
    case 'G05':
    case 'F95':
      $series = 'g-series';
      $model = 'x5';
      break;
    case 'G06':
    case 'F96':
      $series = 'g-series';
      $model = 'x6';
      break;
    case 'G07':
      $series = 'g-series';
      $model = 'x7';
      break;
  }

  // Проверка подходит ли данная модель автомобиля
  if (stripos($modelCode, 'F') === false && stripos($modelCode, 'G') === false) {
    header('content-type: application/json; charset=UTF-8');
    echo json_encode([ 'error' => 'car-is-not-supported' ], JSON_UNESCAPED_UNICODE);
    return;
  } else if ($productionDate < strtotime('01.03.2013') && ($modelCode == 'F06' || $modelCode == 'F10' || $modelCode == 'F12' || $modelCode == 'F13' || $modelCode == 'F25')) {
    header('content-type: application/json; charset=UTF-8');
    echo json_encode([ 'error' => 'individual-calculation', 'series' => $series, 'model' => $model ], JSON_UNESCAPED_UNICODE);
    return;
  } 

  // Подготовка массива опций
  if (!isset($currentOptions)) {
    $currentOptionsCodes = [];
  } else if ($currentOptions['installed']) {
    $currentOptionsCodes = array_merge(array_keys($currentOptions['factory']), array_keys($currentOptions['installed']));
  } else {
    $currentOptionsCodes = array_keys($currentOptions['factory']);
  }

  // $currentOptionsCodes = [];

  // Запрос данных из таблицы
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, "http://185.20.226.75:3000/upgrade-calculator/");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'modelCode' => $modelCode,
    'productionDate' => $_REQUEST['productionDate'],
    'currentOptions' => $currentOptionsCodes,
  ]));

  $result = curl_exec($ch);
  curl_close($ch);

  $upgradeOptions = json_decode($result, true);

  header('content-type: application/json; charset=UTF-8');
  echo json_encode([ 'series' => $series, 'model' => $model, 'currentOptions' => $currentOptionsCodes, 'upgradeOptions' => $upgradeOptions ], JSON_UNESCAPED_UNICODE);
}

main();

// --- Основные и вспомогательные функции --- //

// Проверка наличия в автомобиле определенной опции
function hasOption ($upgradeOptionCode, $currentOptionsCodes) {
  foreach ($currentOptionsCodes as $key => $currentOptionCode) {
    if (stripos($currentOptionCode, $upgradeOptionCode) !== false) {
      return true;
    }
  }
  return false;
}

// Удаление опции, требуемых и включенных в неё
function deleteOption ($deleteOptionName, $upgradeOptions) {
  // echo 'Удаляется опция: ' . $deleteOptionName;
  $deleteOption = $upgradeOptions[$deleteOptionName];
  
  // Удаление найденной опции
  unset($upgradeOptions[$deleteOptionName]);
  
  // Удаление требуемых для неё опций
  if ($deleteOption['required']) {
    foreach ($deleteOption['required'] as $requiredOption) {
      if (gettype($requiredOption) == 'array') {
        if ($requiredOption == ['nbt', 'nbt-evo']) {
          // $upgradeOptions = deleteOption('nbt', $upgradeOptions);
          // $upgradeOptions = deleteOption('nbt-evo', $upgradeOptions);
        }
        foreach ($requiredOption as $requiredOption) {
          // $upgradeOptions = deleteOption($requiredOption, $upgradeOptions);
        }
      } else {
        $upgradeOptions = deleteOption($requiredOption, $upgradeOptions);
      }
    }
  }

  // Удаление содержащихся в ней опций (заменяются)
  if ($deleteOption['contained']) {
    foreach ($deleteOption['contained'] as $containedOption) {
      $upgradeOptions = deleteOption($containedOption, $upgradeOptions);
    }
  }

  // Удаление включенных в неё опций (добавляются и считаются)
  if ($deleteOption['included']) {
    foreach ($deleteOption['included'] as $includedOption) {
      $upgradeOptions = deleteOption($includedOption, $upgradeOptions);
    }
  }

  // Удаление требований этой опции в других
  foreach ($upgradeOptions as $upgradeOptionName => $upgradeOption) {
    if ($upgradeOption['required']) {
      foreach ($upgradeOption['required'] as $requiredOptionNumber => $requiredOption) {

        // Если требуется одна из нескольких опций, удаляем их все
        if (gettype($requiredOption) == 'array') {
          foreach ($requiredOption as $requiredOption) {
            if ($requiredOption == $deleteOptionName) {
              unset($upgradeOptions[$upgradeOptionName]['required'][$requiredOptionNumber]);
              break;
            }
          }

        // Или же удаляем требование отдельной опции
        } else {
          if ($requiredOption == $deleteOptionName) {
            unset($upgradeOptions[$upgradeOptionName]['required'][$requiredOptionNumber]);
          }
        }
        
      }

      // Перенумерование массива
      $upgradeOptions[$upgradeOptionName]['required'] = array_values($upgradeOptions[$upgradeOptionName]['required']);
      
      // Удаление пустого массива с требуемыми опциями
      if (empty($upgradeOptions[$upgradeOptionName]['required'])) {
        unset($upgradeOptions[$upgradeOptionName]['required']);
      }
    }
  }

  // Удаление включенной этой опции в других и замена на требуемую
  foreach ($upgradeOptions as $upgradeOptionName => $upgradeOption) {
    if ($upgradeOption['included']) {
      // echo 'Опция: ' . $upgradeOptionName;
      $includedOptionDeleted = false;
      foreach ($upgradeOption['included'] as $includedOptionNumber => $includedOption) {
        if ($includedOption == $deleteOptionName) {
          unset($upgradeOptions[$upgradeOptionName]['included'][$includedOptionNumber]);
          $includedOptionDeleted = true;
          break;
        }
      }

      // if ($includedOptionDeleted) {
      //   // echo "Удалили опцию $deleteOptionName";
      //   // echo "Работаем с requuired and included опции $upgradeOptionName";
      //   if ($upgradeOptions[$upgradeOptionName]['required']) {
      //     $upgradeOptions[$upgradeOptionName]['required'] = array_merge($upgradeOptions[$upgradeOptionName]['required'], $upgradeOptions[$upgradeOptionName]['included']);
      //   } else {
      //     $upgradeOptions[$upgradeOptionName]['required'] = $upgradeOptions[$upgradeOptionName]['included'];
      //   }
      //   unset($upgradeOptions[$upgradeOptionName]['included']);
      // }

      // Перенумерование массива
      $upgradeOptions[$upgradeOptionName]['included'] = array_values($upgradeOptions[$upgradeOptionName]['included']);
      
      // Удаление пустого массива с включенными опциями
      if (empty($upgradeOptions[$upgradeOptionName]['included'])) {
        unset($upgradeOptions[$upgradeOptionName]['included']);
      }
    }
  }

  // Удаление рекомендаций этой опции в других
  foreach ($upgradeOptions as $upgradeOptionName => $upgradeOption) {
    if ($upgradeOption['recommended']) {
      foreach ($upgradeOption['recommended'] as $recommendedOptionNumber => $recommendedOption) {

        // Если рекомендуется одна из нескольких опций, удаляем их все
        if (gettype($recommendedOption) == 'array') {
          foreach ($recommendedOption as $recommendedOption) {
            if ($recommendedOption == $deleteOptionName) {
              unset($upgradeOptions[$upgradeOptionName]['recommended'][$recommendedOptionNumber]);
              break;
            }
          }

        // Или же удаляем рекомендацию отдельной опции
        } else {
          if ($recommendedOption == $deleteOptionName) {
            unset($upgradeOptions[$upgradeOptionName]['recommended'][$recommendedOptionNumber]);
          }
        }
        
      }

      // Перенумерование массива
      $upgradeOptions[$upgradeOptionName]['recommended'] = array_values($upgradeOptions[$upgradeOptionName]['recommended']);
      
      // Удаление пустого массива с рекомендуемыми опциями
      if (empty($upgradeOptions[$upgradeOptionName]['recommended'])) {
        unset($upgradeOptions[$upgradeOptionName]['recommended']);
      }
    }
  }

  return $upgradeOptions;
}

?>