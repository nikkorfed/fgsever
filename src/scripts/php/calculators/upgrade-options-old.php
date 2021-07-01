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
      $series = 'g-series';
      $model = 'x3';
      break;
    case 'G02':
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
  if ($currentOptions['installed']) {
    $currentOptionsCodes = array_merge(array_keys($currentOptions['factory']), array_keys($currentOptions['installed']));
  } else {
    $currentOptionsCodes = array_keys($currentOptions['factory']);
  }

  // $currentOptionsCodes = [];

  $allUpgradeOptions = json_decode(file_get_contents('../../../data/upgrade-options.json'), true);
  $upgradeOptions = $allUpgradeOptions = $allUpgradeOptions[$series][$model];

  // Специальные условия

  // F серия

  if ($series == 'f-series') {

    // 1, 2 серия (F20/F21, F22/F23)
    if ($modelCode == 'F20' || $modelCode == 'F21' || $modelCode == 'F22' || $modelCode == 'F23') {

      // Отображение опции 534 Автоматический климат-контроль
      unset($upgradeOptions['automatic-climate-control']['status']);

      // // До 07.2012
      // if ($productionDate < strtotime('01.07.2012')) {

        // Изменение стоимости опции 609 Мультимедийная система NBT/NBT EVO
        // при отсутствии опций 606, 609, 663
        if (
          !hasOption('606', $currentOptionsCodes) &&
          !hasOption('609', $currentOptionsCodes) &&
          !hasOption('663', $currentOptionsCodes)
        ) {
          $upgradeOptions['nbt']['price'] += 13000;
        }

      // }
      
    }

    // 1, 2, 3, 4 серия (F20/F21, F22/F23, F87, F45/F46, F30/F31/F34, F80, F32/F33/F36, F82/F83)
    if (
      $modelCode == 'F20' || $modelCode == 'F21' || $modelCode == 'F22' || $modelCode == 'F23' || $modelCode == 'F87' ||
      $modelCode == 'F45' || $modelCode == 'F46' || $modelCode == 'F30' || $modelCode == 'F31' || $modelCode == 'F34' ||
      $modelCode == 'F80' ||
      $modelCode == 'F32' || $modelCode == 'F33' || $modelCode == 'F36' || $modelCode == 'F82' || $modelCode == 'F83'
    ) {

      // До 07.2012
      if ($productionDate < strtotime('01.07.2012')) {
        
        // Увеличение стоимости опции 609 Мультимедийная система NBT/NBT EVO
        // при наличии одной из опций 6VA, 6NK, 6NL
        if (
          hasOption('6VA', $currentOptionsCodes) ||
          hasOption('6NK', $currentOptionsCodes) || 
          hasOption('6NL', $currentOptionsCodes)
        ) {
          $upgradeOptions['nbt']['price'] += 10000;
          $upgradeOptions['nbt-evo']['price'] += 10000;
        }

      }

      // До 07.2013
      if ($productionDate < strtotime('01.07.2013')) {

        $upgradeOptions['tire-pressure-indicator']['price'] = '45000';

      // После 07.2013
      } else if ($productionDate >= strtotime('01.07.2013')) {

        $upgradeOptions['tire-pressure-indicator']['price'] = '28000';

      }

      // До 07.2014
      if ($productionDate < strtotime('01.07.2014')) {
        
        // Рекомендации поддержки Bluetooth и USB-порта
        // при отсутствии опций 6NK, 6NL, 6NS
        if (
          !hasOption('6NK', $currentOptionsCodes) &&
          !hasOption('6NL', $currentOptionsCodes) &&
          !hasOption('6NS', $currentOptionsCodes)
        ) {
          if (!isset($upgradeOptions['nbt']['recommended'])) $upgradeOptions['nbt']['recommended'] = [];
          array_push($upgradeOptions['nbt']['recommended'], 'bluetooth-support', 'usb-port');
          if (!isset($upgradeOptions['nbt-evo']['recommended'])) $upgradeOptions['nbt-evo']['recommended'] = [];
          array_push($upgradeOptions['nbt-evo']['recommended'], 'bluetooth-support', 'usb-port');
        }

        // Предложение установки опции 6AE BMW TeleService
        // if (!isset($upgradeOptions['nbt']['recommended'])) $upgradeOptions['nbt']['recommended'] = [];
        // array_push($upgradeOptions['nbt']['recommended'], 'bmw-teleservice');
        // if (!isset($upgradeOptions['nbt-evo']['recommended'])) $upgradeOptions['nbt-evo']['recommended'] = [];
        // array_push($upgradeOptions['nbt-evo']['recommended'], 'bmw-teleservice');

        // Требование установки блока ATM
        // при установке опции 609 Мультимедийная системы NBT EVO
        if (!isset($upgradeOptions['nbt-evo']['required'])) $upgradeOptions['nbt-evo']['required'] = [];
        array_push($upgradeOptions['nbt-evo']['required'], 'atm-installation');

        // Установка стоимости опции 5DA Выключатель пассажирской подушки безопасности
        $upgradeOptions['passenger-airbag-deactivation']['recommended'] = ['ceiling-light-replacement'];

      }

      // После 07.2014
      if ($productionDate >= strtotime('01.07.2014')) {

        // Удаление опции Головное устройство Entrynav
        $upgradeOptions = deleteOption('entrynav-head-unit', $upgradeOptions);

        // Удаление требований опций NBT/NBT EVO для опции 6AE BMW Teleservice
        unset($upgradeOptions['bmw-teleservice']['required']);

        // Установка стоимости NBT EVO
        // $upgradeOptions['nbt-evo']['price'] = 70000;

      }

      // С 07.2014 и до 07.2016
      if ($productionDate >= strtotime('01.07.2014') && $productionDate < strtotime('01.07.2016')) {

        // Требование замены блока ТСВ на ATM
        // при установке опции 609 Мультимедийная система NBT EVO
        if (hasOption('6AE', $currentOptionsCodes)) {
          if (!isset($upgradeOptions['nbt-evo']['required'])) $upgradeOptions['nbt-evo']['required'] = [];
          array_push($upgradeOptions['nbt-evo']['required'], 'tsw-to-atm-replacement');
        }

      }

      // C 07.2014 и до 07.2015
      if ($productionDate >= strtotime('01.07.2014') && $productionDate < strtotime('01.07.2015')) {

        // Пометка о целесообразоности установки опции Мультимедийная система NBT
        $upgradeOptions['nbt']['label'] = ['Рекомендуем', 'В автомобили, произведённые в промежутке между июлем 2014 и 2015 года, наиболее целесообразным является установка мультимедийной системы NBT.'];

      }

      // С 07.2015 и до 07.2016
      if ($productionDate >= strtotime('01.07.2015') && $productionDate < strtotime('01.07.2067')) {

        // Пометка о целесообразоности установки опции Мультимедийная система NBT
        $upgradeOptions['nbt']['label'] = ['Рекомендуем', 'В автомобили, произведённые в промежутке между июлем 2015 и 2016 года, устанавливалась переходная версия мультимедийной системы NBT EVO с блоком управления ТСВ, для которого целесообразным вариантом дооснащения является обычная мультимедийная система NBT.'];

        // Удаление опции 6AE BMW TeleService
        // при наличии опции 609 NBT/NBT EVO
        if (hasOption('609', $currentOptionsCodes)) {
          $upgradeOptions = deleteOption('bmw-teleservice', $upgradeOptions);
        }
        
      }

      // После 07.2015
      if ($productionDate >= strtotime('01.07.2015')) {

        // Удаление опции Подстаканники от LCI с закрывающейся шторкой
        $upgradeOptions = deleteOption('cup-holders', $upgradeOptions);

      }

      // После 07.2016
      if ($productionDate >= strtotime('01.07.2016')) {

        // Требование замены блока АТМ на ТСВ
        // при установке опции 609 Мультимедийная система NBT
        if (!isset($upgradeOptions['nbt']['required'])) $upgradeOptions['nbt']['required'] = [];
        array_push($upgradeOptions['nbt']['required'], 'atm-to-tsw-replacement');

        // Пометка о целесообразоности установки опции Мультимедийная система NBT EVO
        $upgradeOptions['nbt-evo']['label'] = ['Рекомендуем', 'В автомобили, произведённые после июля 2016 года, устанавливались блоки АТМ , подходящие для мультимедийной системы NBT EVO. Поэтому наиболее правильным и целесообразным вариантом дооснащения является мультимедийная система NBT EVO.'];

        // Предложение установки блока распознавания рукописного ввода, и экрана от F15
        // if (!isset($upgradeOptions['nbt']['recommended'])) $upgradeOptions['nbt']['recommended'] = [];
        // array_push($upgradeOptions['nbt']['recommended'], 'handwriting-recognition');
        // array_push($upgradeOptions['nbt']['recommended'], 'nbt-f15-screen');
        // if (!isset($upgradeOptions['nbt-evo']['recommended'])) $upgradeOptions['nbt-evo']['recommended'] = [];
        // array_push($upgradeOptions['nbt-evo']['recommended'], 'handwriting-recognition');

        // // Предложение установки сенсорных экранов в автомобили до 07.17
        // if ($productionDate < '01.07.2017') {
        //   array_push($upgradeOptions['nbt-evo']['recommended'], 'nbt-evo-touch-screen');
        // }

        // Удаление опции 6AE BMW TeleService
        $upgradeOptions = deleteOption('bmw-teleservice', $upgradeOptions);

      }

      // После 07.2017
      if ($productionDate >= strtotime('01.07.2017')) {

        // Удаление опции Головное устройство Entryevo
        $upgradeOptions = deleteOption('entryevo-head-unit', $upgradeOptions);

      }

      // Для любой даты выпуска

      // Изменение стоимости установки опции 5DL Система кругового обзора
      // при наличии опции 3AG Камера заднего вида
      if (hasOption('3AG', $currentOptionsCodes)) {
        $upgradeOptions['surround-view']['types']['three-cameras']['price'] -= 20000;
        $upgradeOptions['surround-view']['types']['five-cameras']['price'] -= 20000;
      }

      // Удаление опций замены головного устройства
      // при наличии опции 609 Мультимедийная система NBT/NBT EVO
      if (hasOption('609', $currentOptionsCodes)) {
        $upgradeOptions = deleteOption('entrynav-head-unit', $upgradeOptions);
        $upgradeOptions = deleteOption('entryevo-head-unit', $upgradeOptions);
      }

      // Удаление опции Виброгенератор рулевого колеса
      // при наличии одной из опций 710, 5AS или 5AD
      if (
        hasOption('710', $currentOptionsCodes) ||
        hasOption('5AS', $currentOptionsCodes) ||
        hasOption('5AD', $currentOptionsCodes)
      ) {
        $upgradeOptions = deleteOption('vibro-steering-wheel', $upgradeOptions);
      }

      // Удаление опции Лобовое стекло под камеру KAFAS
      // при наличии одной из опций 8TH или 5AS
      if (
        hasOption('8TH', $currentOptionsCodes) ||
        hasOption('5AS', $currentOptionsCodes)
      ) {
        $upgradeOptions = deleteOption('kafas-windshield', $upgradeOptions);
      }

      // Требование проверки блока FEM
      // при установке опции 563 Пакет освещения снаружи и в салоне
      if (!hasOption('7AC', $currentOptionsCodes)) $upgradeOptions['lighting']['label'] = [ "Требуется проверка блока FEM", "Для установки данной опции необходима проверка блока FEM непосредственно в автосервисе. По результатам данной проверки, стоимость дооснащения может увеличиться"];

    }

    // 5, 6, 7 серия (F10/F11/F07, F06/F12/F13, F01/F01)
    if (
      $modelCode == 'F10' || $modelCode == 'F11' || $modelCode == 'F07' ||
      $modelCode == 'F06' || $modelCode == 'F12' || $modelCode == 'F13' ||
      $modelCode == 'F01' || $modelCode == 'F02' 
    ) {

      // До 07.2014
      if ($productionDate < strtotime('01.07.2014')) {

        // Требование установки блока ATM
        // при установке опции 609 Мультимедийная системы NBT EVO
        if (!isset($upgradeOptions['nbt-evo']['required'])) $upgradeOptions['nbt-evo']['required'] = [];
        array_push($upgradeOptions['nbt-evo']['required'], 'atm-installation');

      }

      // После 07.2014
      if ($productionDate >= strtotime('01.07.2014')) {

        // Удаление требований опций NBT/NBT EVO для опции 6AE BMW Teleservice
        unset($upgradeOptions['bmw-teleservice']['required']);
        
      }

      // С 07.2014 и до 07.2016
      if ($productionDate >= strtotime('01.07.2014') && $productionDate < strtotime('01.07.2016')) {

        // Требование замены блока ТСВ на ATM
        // при установке опции 609 Мультимедийная система NBT EVO
        if (hasOption('6AE', $currentOptionsCodes)) {
          if (!isset($upgradeOptions['nbt-evo']['required'])) $upgradeOptions['nbt-evo']['required'] = [];
          array_push($upgradeOptions['nbt-evo']['required'], 'tsw-to-atm-replacement');
        }

      }

      // После 07.2016
      if ($productionDate >= strtotime('01.07.2016')) {

        // Удаление опций NBT/NBT EVO
        $upgradeOptions = deleteOption('nbt-evo', $upgradeOptions);

        // Удаление опции замены мультимедийной системы
        $upgradeOptions = deleteOption('multimedia-replacement', $upgradeOptions);

        // Требование замены блока АТМ на ТСВ
        // при установке опции 609 Мультимедийная система NBT
        if (!isset($upgradeOptions['nbt']['required'])) $upgradeOptions['nbt']['required'] = [];
        array_push($upgradeOptions['nbt']['required'], 'atm-to-tsw-replacement');
        
      }

    }

    // X3, X4 (F25, F26)
    if ($modelCode == 'F25' || $modelCode == 'F26') {

      // Удаление опции 6WA Расширенная комбинация приборов
      // при наличии опции 610 Проекицонный дисплей
      if (hasOption('610', $currentOptionsCodes)) $upgradeOptions = deleteOption('extended-cockpit-black-panel', $upgradeOptions);

      // После 04.2014
      if ($productionDate >= strtotime('01.04.2014')) {

        // Удаление опции 6WA Расширенная комбинация приборов
        $upgradeOptions['head-up-display']['required'] = [['extended-cockpit-black-panel', 'led-cockpit']];
        $upgradeOptions = deleteOption('extended-cockpit', $upgradeOptions);
      }

    }

    // X4 (F26)
    if ($modelCode == 'F26') {

      // Удаление опции 316 Автоматический привод багажника
      $upgradeOptions = deleteOption('automatic-trunk', $upgradeOptions);

    }

    // X5, X6 (F15, F16)
    if ($modelCode == 'F15' || $modelCode == 'F16') {

      // До 07.2014
      if ($productionDate < strtotime('01.07.2014')) {

        // Требование установки блока ATM
        // при установке опции 609 Мультимедийная системы NBT EVO
        if (!isset($upgradeOptions['nbt-evo']['required'])) $upgradeOptions['nbt-evo']['required'] = [];
        array_push($upgradeOptions['nbt-evo']['required'], 'atm-installation');

      }

      // После 07.2014
      if ($productionDate >= strtotime('01.07.2014')) {

        // Удаление требований опций NBT/NBT EVO для опции 6AE BMW Teleservice
        unset($upgradeOptions['bmw-teleservice']['required']);
        
      }

      // С 07.2014 и до 07.2016
      if ($productionDate >= strtotime('01.07.2014') && $productionDate < strtotime('01.07.2016')) {

        // Требование замены блока ТСВ на ATM
        // при установке опции 609 Мультимедийная система NBT EVO
        if (hasOption('6AE', $currentOptionsCodes)) {
          if (!isset($upgradeOptions['nbt-evo']['required'])) $upgradeOptions['nbt-evo']['required'] = [];
          array_push($upgradeOptions['nbt-evo']['required'], 'tsw-to-atm-replacement');
        }

      }

      // После 07.2016
      if ($productionDate >= strtotime('01.07.2016')) {

        // Удаление опций NBT/NBT EVO
        $upgradeOptions = deleteOption('nbt-evo', $upgradeOptions);

        // Удаление опции замены мультимедийной системы
        $upgradeOptions = deleteOption('multimedia-replacement', $upgradeOptions);

        // Требование замены блока АТМ на ТСВ
        // при установке опции 609 Мультимедийная система NBT
        if (!isset($upgradeOptions['nbt']['required'])) $upgradeOptions['nbt']['required'] = [];
        array_push($upgradeOptions['nbt']['required'], 'atm-to-tsw-replacement');
        
      }

    }

  }

  // G серия
  if ($series == 'g-series') {

    // 1, 2 серия (F40/F44)
    if ($modelCode == 'F40' || $modelCode == 'F44') {

      if (hasOption('5AC', $currentOptionsCodes)) {
        $upgradeOptions['head-up-display']['price'] = '160000';
      }

    }

    // 3 серия (G20/G21)
    if ($modelCode == 'G20' || $modelCode == 'G21') {

      if (
        hasOption('6U3', $currentOptionsCodes) ||
        hasOption('6UC', $currentOptionsCodes)
      ) $upgradeOptions['head-up-display']['price'] = '220000';

    }

    // 5, 6, M5, 7, X3, X4, X3M, X4M (G30/G31, G32, F90, G11/G12, G01, G02, F97, F98)
    if (
      $modelCode == 'G30' || $modelCode == 'G31' || $modelCode == 'G32' ||
      $modelCode == 'F90' || $modelCode == 'G11' || $modelCode == 'G12' ||
      $modelCode == 'G01' || $modelCode == 'G02' || $modelCode == 'F97' || $modelCode == 'F98'
    ) {

      // До 07.2019
      if ($productionDate < strtotime('01.07.2019')) {

        // Невозможность установки опции Активация Android Auto
        $upgradeOptions = deleteOption('android-auto', $upgradeOptions);

        // Требование замены головного устройства
        // при отсутствии опций 688 Аудиосистема Harman/Kardon, 6F1 Аудиосистема Bowers & Wilkins и 6FH Задняя развлекательная система
        // и установке опции 601 Телевидение
        if (
          !hasOption('688', $currentOptionsCodes) &&
          !hasOption('6F1', $currentOptionsCodes) &&
          !hasOption('6FH', $currentOptionsCodes)
        ) {
          if (!isset($upgradeOptions['tv-tuner']['required'])) $upgradeOptions['tv-tuner']['required'] = [];
          array_push($upgradeOptions['tv-tuner']['required'], 'evo-replacement');
        }

      }
      
      // После 07.2019
      if ($productionDate >= strtotime('01.07.2019')) {

        // Невозможность установки опции 6CP Активация CarPlay
        $upgradeOptions['apple-carplay']['price'] = '';

      }

      // Требование замены головного устройства
      // при наличии опции 6U3 Live Cockpit Professional или после 07.2019
      // и установке опции 6FH Развлекательная система для задних пассажиров
      if (isset($upgradeOptions['rear-seats-entertainment'])) {
        $upgradeOptions['rear-seats-entertainment']['price'] = '240000';

        if (hasOption('6U3', $currentOptionsCodes) || $productionDate >= strtotime('01.07.2019')) {
          if (!isset($upgradeOptions['rear-seats-entertainment']['required'])) $upgradeOptions['rear-seats-entertainment']['required'] = [];
          array_push($upgradeOptions['rear-seats-entertainment']['required'], 'head-unit-replacement');
        }
      }

      // Рекомендация замены головного устройства
      // при наличии опции 6U3 Live Cockpit Professional или после 07.2019
      // и установке опции 6UK Система ночного видения
      if (isset($upgradeOptions['night-vision'])) {
        if (hasOption('6U3', $currentOptionsCodes) || $productionDate >= strtotime('01.07.2019')) {
          array_push($upgradeOptions['night-vision']['recommended'], 'head-unit-replacement');
        }
      }

      // Изменение стоимости опции 5AT Ассистент вождения Plus
      // при полном отсутствии включенных опций
      // if (
      //   !hasOption('5AV', $currentOptionsCodes) &&
      //   !hasOption('5AS', $currentOptionsCodes) && 
      //   !hasOption('5DF', $currentOptionsCodes) &&
      //   !hasOption('5AT', $currentOptionsCodes)
      // ) {
      //   if ($upgradeOptions['driving-assistant-plus']) $upgradeOptions['driving-assistant-plus']['price'] = '380000';
      //   if ($upgradeOptions['driving-assistant-professional']) $upgradeOptions['driving-assistant-professional']['price'] = '380000';
      // }

      // Изменение стоимости опций ассистентов вождения
      // при наличии опции 5AV Active Guard
      if (
        hasOption('5AV', $currentOptionsCodes)
      ) {
        if ($upgradeOptions['driving-assistant']) $upgradeOptions['driving-assistant']['price'] = '170000';
        if ($upgradeOptions['driving-assistant-plus']) $upgradeOptions['driving-assistant-plus']['price'] = '280000';
        if ($upgradeOptions['driving-assistant-professional']) $upgradeOptions['driving-assistant-professional']['price'] = '280000';
      }

      // Изменение стоимости опций ассистентов вождения
      // при наличии опции 5DF Активный круиз-контроль
      if (
        hasOption('5DF', $currentOptionsCodes)
      ) {
        if ($upgradeOptions['driving-assistant']) $upgradeOptions['driving-assistant']['price'] = '120000';
        if ($upgradeOptions['driving-assistant-plus']) $upgradeOptions['driving-assistant-plus']['price'] = '140000';
        if ($upgradeOptions['driving-assistant-professional']) $upgradeOptions['driving-assistant-professional']['price'] = '140000';
      }

      // Изменение стоимости опции 5AT Ассистент вождения Plus
      // при наличии опций 5AS Ассистент вождения
      if (hasOption('5AS', $currentOptionsCodes)) {
        if ($upgradeOptions['driving-assistant-plus']) $upgradeOptions['driving-assistant-plus']['price'] = '120000';
        if ($upgradeOptions['driving-assistant-professional']) $upgradeOptions['driving-assistant-professional']['price'] = '120000';
      }

      // Изменение стоимости опций 4NM и 536 при наличии одной из них
      if (hasOption('4NM', $currentOptionsCodes)) $upgradeOptions['autonomous-heating']['price'] = '85000';
      if (hasOption('536', $currentOptionsCodes)) $upgradeOptions['ambient-air']['price'] = '65000';

    }

    // 5, 6 серия, M5 (G30/G31, G32, F90)
    if ($modelCode == 'G30' || $modelCode == 'G31' || $modelCode == 'G32' || $modelCode == 'F90') {

      // Изменение стоимости установки опции 6FH Задняя развлекательная система
      // при наличии опции 6U3 Live Cockpit Professional
      if (hasOption('6U3', $currentOptionsCodes)) $upgradeOptions['rear-seats-entertainment']['price'] = "350000";

    }

    // 7 серия (G11/G12)
    if ($modelCode == 'G11' || $modelCode == 'G12') {

      // До 07.19
      if ($productionDate < strtotime('01.07.2019')) {

        // Удаление 5AU Driving Assistant Professional
        $upgradeOptions = deleteOption('driving-assistant-professional', $upgradeOptions);

      }

      // После 07.19
      if ($productionDate >= strtotime('01.07.2019')) {

        // Удаление 5AT Driving Assistant Plus
        $upgradeOptions = deleteOption('driving-assistant-plus', $upgradeOptions);

      }

      // Удаление опций, доступных только для G12
      if ($modelCode !== 'G12') {
        $upgradeOptions = deleteOption('executive-lounge-rear-seats', $upgradeOptions);
        $upgradeOptions = deleteOption('executive-lounge-console', $upgradeOptions);
        $upgradeOptions = deleteOption('extended-heating-package', $upgradeOptions);
        $upgradeOptions = deleteOption('active-rear-seats-ventilation', $upgradeOptions);
        $upgradeOptions = deleteOption('massage-rear-seats', $upgradeOptions);
        $upgradeOptions = deleteOption('rear-sunshades', $upgradeOptions);
        $upgradeOptions = deleteOption('panoramic-moonroof', $upgradeOptions);
        $upgradeOptions = deleteOption('sky-lounge-panoramic-moonroof', $upgradeOptions);
      }

      // Изменение стоимости опции 407 Панорамная стекляннная крыша Sky Lounge
      // при наличии опции 402 Панорамная стеклянная крыша
      if (hasOption('402', $currentOptionsCodes)) {
        $upgradeOptions['sky-lounge-panoramic-moonroof']['price'] = '250000';
      }

    }

    // 8 серия (G14/G15/G16)
    if ($modelCode == 'G14' || $modelCode == 'G15' || $modelCode == 'G16') {

      // Кабриолет (G14)
      if ($modelCode == 'G14') {
        $upgradeOptions = deleteOption('carbon-roof', $upgradeOptions);
        $upgradeOptions = deleteOption('panoramic-moonroof', $upgradeOptions);
      }

      // Купе (G15)
      if ($modelCode == 'G15') {
        $upgradeOptions = deleteOption('panoramic-moonroof', $upgradeOptions);
      }

    }

    // M5, M8 (F90, F91/F92/F93)
    if ($modelCode == 'F90' || $modelCode == 'F91' || $modelCode == 'F92' || $modelCode == 'F93') {

      // Удаление опции 2NH Тормозная система M Sport
      // и добавление опции 2NK Карбоно-керамическая тормозная система M
      $upgradeOptions = deleteOption('m-sport-brakes', $upgradeOptions);
      unset($upgradeOptions['carbon-ceramic-brakes']['status']);

      // Удаление опций 2VH Интегральное активное рулевое управление и 2VA Adaptive Drive
      if (isset($upgradeOptions['integral-active-steering'])) $upgradeOptions = deleteOption('integral-active-steering', $upgradeOptions);
      if (isset($upgradeOptions['adaptive-drive'])) $upgradeOptions = deleteOption('adaptive-drive', $upgradeOptions);

      // Отсутствие рекомендации установки дополнительного аккумулятора
      // при установке опции 536 Автономная система отопления
      if (isset($upgradeOptions['autonomous-heating']['recommended'])) unset($upgradeOptions['autonomous-heating']['recommended']);

      // Изменение цены опции 536 Автономная система отопления
      $upgradeOptions['autonomous-heating']['price'] += 20000;

    }

    // X3, X4, X3M, X4M (G01, G02, F97, F98)
    if ($modelCode == 'G01' || $modelCode == 'G02' || $modelCode == 'F97' || $modelCode == 'F98') {

      // Отсутствие необходимости заменять комбинацию приборов
      // при отсутствии опции 6U3 Live Cockpit Professional
      if (!hasOption('6U3', $currentOptionsCodes)) {
        $upgradeOptions = deleteOption('cockpit-replacement', $upgradeOptions);
      }

    }

    // X5, X6, X7, X5M, X6M (G05, G06, G07, F95, F96)
    if ($modelCode == 'G05' || $modelCode == 'G06' || $modelCode == 'G07' || $modelCode == 'F95' || $modelCode == 'F96') {

      // Изменение стоимости опции чтения дорожных знаков
      // при наличии опции 5AV Active Guard
      if (hasOption('5AV', $currentOptionsCodes)) {
        $upgradeOptions['road-signs-detection']['price'] = '45000';
      }

      // Изменение стоимости опций 5AS Ассистент вождения и 5DF Активный круиз-контроль
      // при наличии опции 5AQ Active Guard Plus
      if (hasOption('5AQ', $currentOptionsCodes)) {
        $upgradeOptions['driving-assistant']['price'] = '150000';
        $upgradeOptions['active-cruise-control']['price'] = '60000';
      }

      // Изменение стоимости опции 5AU Ассистент вождения Professional
      // при наличии одной из опций 5AV Active Guard, 5AS Ассистент вождения или 5DF Активный круиз-контроль c функцией Stop&Go
      if (
        hasOption('5AV', $currentOptionsCodes) ||
        hasOption('5AS', $currentOptionsCodes) ||
        hasOption('5DF', $currentOptionsCodes)
      ) {
        $upgradeOptions['driving-assistant-professional']['price'] = '680000';
      }

      // Изменение стоимости опции 407 Панорамная стекляннная крыша Sky Lounge
      // при наличии опции 402 Панорамная стеклянная крыша
      if (hasOption('402', $currentOptionsCodes)) {
        $upgradeOptions['sky-lounge-panoramic-moonroof']['price'] = '250000';
      }

    }

    // X5M, X6M (F95, F96)
    if ($modelCode == 'F95' || $modelCode == 'F96') {

      // Удаление опций, отсутствующих на M моделях
      $upgradeOptions = deleteOption('aluminium-running-boards', $upgradeOptions);
      $upgradeOptions = deleteOption('glass-controls', $upgradeOptions);
      $upgradeOptions = deleteOption('m-steering-wheel', $upgradeOptions);
      $upgradeOptions = deleteOption('sensatec-dashboard', $upgradeOptions);
      $upgradeOptions = deleteOption('comfort-front-seats', $upgradeOptions);
      $upgradeOptions = deleteOption('travel-comfort-system', $upgradeOptions);
      $upgradeOptions = deleteOption('m-sport-differential', $upgradeOptions);
      $upgradeOptions = deleteOption('m-sport-brakes', $upgradeOptions);

    }

    // Для всех моделей
    
    // Отсутствие необходимости замены мультимедийной системы при отсутствии опции 609
    if (
      !hasOption('609', $currentOptionsCodes) &&
      !hasOption('6U3', $currentOptionsCodes)
    ) {
      $upgradeOptions = deleteOption('multimedia-replacement', $upgradeOptions);
    }

    // Отсутствие необходимости установки блока SAS и замены блока DSC
    // при наличии одной из опций 5DM Ассистент парковки и 5DN Ассистент парковки Plus
    if (
      hasOption('5DM', $currentOptionsCodes) ||
      hasOption('5DN', $currentOptionsCodes)
    ) {
      $upgradeOptions = deleteOption('sas-and-dsc-blocks-replacement', $upgradeOptions);
    }

    // Отсутствие необходимости установки дополнительного аккумулятора
    // при наличии опции 536 Автономная система отопления
    if (hasOption('536', $currentOptionsCodes)) {
      $upgradeOptions = deleteOption('additional-battery', $upgradeOptions);
    }

  }

  // Удаление опций, которые уже имеются в автомобиле
  foreach ($upgradeOptions as $upgradeOptionName => $upgradeOption) {
    if (hasOption($upgradeOption['code'], $currentOptionsCodes)) {
      $upgradeOptions = deleteOption($upgradeOptionName, $upgradeOptions);
    }
  }

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