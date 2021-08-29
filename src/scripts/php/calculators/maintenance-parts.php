<?

require_once '../libraries/simple_html_dom.php';
require_once '../libraries/phpQuery.php';

if (isset($_REQUEST['vin']) && isset($_REQUEST['mileage'])) {
  function searchParts() {

    // Сохраняем вводные данные
    $vin = $_REQUEST['vin'];

    // Собираем информацию об автомобиле
    include 'car-info.php';
    $carInfo = requestCarInfo($vin);
    
    // Если не удалось найти данные в AOS
    if (!isset($carInfo['vin'])) {
      header('content-type: application/json; charset=UTF-8');
      echo json_encode([ 'error' => 'parts-not-found' ], JSON_UNESCAPED_UNICODE);
      return;
    }
    
    // Если из AOS были успешно получены данные
    $vin = $carInfo['vin'];
    $model = $carInfo['model'];
    $modelCode = $carInfo['modelCode'];
    $date = $carInfo['productionDate'];
    $image = $carInfo['image'];
    $options = $carInfo['options'];

    // Запрашиваем данные о запчастях для ТО
    $maintenanceLink = "http://194.58.98.247/maintenance/$vin";
    $maintenance = json_decode(file_get_html($maintenanceLink), true);

    if ($maintenance['motorOil']) $motorOilQuantity = $maintenance['motorOil']['quantity'];
    if ($maintenance['oilFilter']) $oilFilterNumber = $maintenance['oilFilter']['partNumber'];
    if ($maintenance['sparkPlug']) {
      $sparkPlugNumber = $maintenance['sparkPlug']['partNumber'];
      $sparkPlugQuantity = $maintenance['sparkPlug']['quantity'];
    }
    if ($maintenance['fuelFilter']) $fuelFilterNumber = $maintenance['fuelFilter']['partNumber'];
    if ($maintenance['airFilter']) $airFilterNumber = $maintenance['airFilter']['partNumber'];
    if ($maintenance['cabinAirFilter']) $cabinAirFilterNumber = $maintenance['cabinAirFilter']['partNumber'];
    if ($maintenance['recirculationFilter']) $recirculationFilterNumber = $maintenance['recirculationFilter']['partNumber'];
    if ($maintenance['frontBrakeDisk']) $frontBrakeDiskNumber = $maintenance['frontBrakeDisk']['partNumber'];
    if ($maintenance['frontBrakePads']) $frontBrakePadsNumber = $maintenance['frontBrakePads']['partNumber'];
    if ($maintenance['frontBrakePadsWearSensor']) $frontBrakePadsWearSensorNumber = $maintenance['frontBrakePadsWearSensor']['partNumber'];
    if ($maintenance['rearBrakeDisk']) $rearBrakeDiskNumber = $maintenance['rearBrakeDisk']['partNumber'];
    if ($maintenance['rearBrakePads']) $rearBrakePadsNumber = $maintenance['rearBrakePads']['partNumber'];
    if ($maintenance['rearBrakePadsWearSensor']) $rearBrakePadsWearSensorNumber = $maintenance['rearBrakePadsWearSensor']['partNumber'];

    // Исправление ошибок

    // Неправильный фильтр на X3 F25
    if (strpos($modelCode, 'F25') !== false) {
      $cabinAirFilterNumber = '64 31 9 312 318';
    }

    // Определение цен на моторное масло
    $originalMotorOilPrice = 600;
    $motulMotorOilPrice = 500;

    // Подготовка массива запчастей
    $parts = [];
    if ($oilFilterNumber) $parts['oil-filter'] = ['number' => $oilFilterNumber];
    if ($sparkPlugNumber) $parts['spark-plug'] = ['number' => $sparkPlugNumber];
    if ($fuelFilterNumber) $parts['fuel-filter'] = ['number' => $fuelFilterNumber];
    if ($airFilterNumber) $parts['air-filter'] = ['number' => $airFilterNumber];
    if ($cabinAirFilterNumber) $parts['cabin-air-filter'] = ['number' => $cabinAirFilterNumber];
    if ($recirculationFilterNumber) $parts['recirculation-filter'] = ['number' => $recirculationFilterNumber];
    if ($frontBrakeDiskNumber) $parts['front-brake-disk'] = ['number' => $frontBrakeDiskNumber];
    if ($frontBrakePadsNumber) $parts['front-brake-pads'] = ['number' => $frontBrakePadsNumber];
    if ($frontBrakePadsWearSensorNumber) $parts['front-brake-pads-wear-sensor'] = ['number' => $frontBrakePadsWearSensorNumber];
    if ($rearBrakeDiskNumber) $parts['rear-brake-disk'] = ['number' => $rearBrakeDiskNumber];
    if ($rearBrakePadsNumber) $parts['rear-brake-pads'] = ['number' => $rearBrakePadsNumber];
    if ($rearBrakePadsWearSensorNumber) $parts['rear-brake-pads-wear-sensor'] = ['number' => $rearBrakePadsWearSensorNumber];

    $numbers = [];
    foreach ($parts as $part) $numbers[] = $part['number'];
    $data = implode(',', $numbers);

    // Авторизация на сайте поставщика
    $login = 'Дерюгин ПС';
    $password = '3306';

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'http://sprolf.ru/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/sprolf.cookie');
    curl_setopt($ch, CURLOPT_POSTFIELDS, "username=$login&password=$password&cmdweblogin=");
    curl_setopt($ch, CURLOPT_POST, 1);

    curl_exec($ch);
    curl_close ($ch);

    // Запрос для поиска деталей
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'http://sprolf.ru/index.php?id=137');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/sprolf.cookie');
    curl_setopt($ch, CURLOPT_POSTFIELDS, "articles=$data&priority=cost&storeid=0&search=%D0%9F%D0%BE%D0%B4%D0%BE%D0%B1%D1%80%D0%B0%D1%82%D1%8C");
    curl_setopt($ch, CURLOPT_POST, 1);

    $html = curl_exec($ch);
    curl_close ($ch);

    // Поиск и сохраннение цен
    foreach (phpQuery::newDocument($html)->find("#multisearch tr") as $row) {
      $row = pq($row);
      $number = str_replace(' ', '', $row->find('td:nth-child(2)')->text());
      foreach ($parts as &$part) {
        if (str_replace(' ', '', $part['number']) == $number && !isset($part['price'])) {
          $part['price'] = +$row->find("td:nth-child(7)")->text() * 1.3;
        }
      }
    }

    // Сохранение цен в отдельные переменные
    if ($parts['oil-filter']['price']) $oilFilterPrice = $parts['oil-filter']['price'];
    if ($parts['spark-plug']['price']) $sparkPlugPrice = $parts['spark-plug']['price'];
    if ($parts['fuel-filter']['price']) $fuelFilterPrice = $parts['fuel-filter']['price'];
    if ($parts['air-filter']['price']) $airFilterPrice = $parts['air-filter']['price'];
    if ($parts['cabin-air-filter']['price']) $cabinAirFilterPrice = $parts['cabin-air-filter']['price'];
    if ($parts['recirculation-filter']['price']) $recirculationFilterPrice = $parts['recirculation-filter']['price'];
    if ($parts['front-brake-disk']['price']) $frontBrakeDiskPrice = $parts['front-brake-disk']['price'];
    if ($parts['front-brake-pads']['price']) $frontBrakePadsPrice = $parts['front-brake-pads']['price'];
    if ($parts['front-brake-pads-wear-sensor']['price']) $frontBrakePadsWearSensorPrice = $parts['front-brake-pads-wear-sensor']['price'];
    if ($parts['rear-brake-disk']['price']) $rearBrakeDiskPrice = $parts['rear-brake-disk']['price'];
    if ($parts['rear-brake-pads']['price']) $rearBrakePadsPrice = $parts['rear-brake-pads']['price'];
    if ($parts['rear-brake-pads-wear-sensor']['price']) $rearBrakePadsWearSensorPrice = $parts['rear-brake-pads-wear-sensor']['price'];

    // Альтернативные варианты (опции) некоторых деталей
    $motorOilOptions = [
      'original' => [
        'name' => 'Оригинальное BMW 5W30',
        'price' => $originalMotorOilPrice,
      ],
      'motul' => [
        'name' => 'Motul 5W40',
        'price' => $motulMotorOilPrice,
      ],
    ];

    // Определение цен на работы
    if (substr($model, 0, 1) == '1' || substr($model, 0, 1) == '2' || substr($model, 0, 2) == 'M1' || substr($model, 0, 2) == 'M2') {
      $motorOilWork = 400;
      $oilFilterWork = 400;
      $sparkPlugWork = 1800;
      $fuelFilterWork = 1200;
      $airFilterWork = 400;
      $cabinAirFilterWork = 1000;
      $frontBrakeDiskWork = 1800;
      $frontBrakePadsWork = 1000;
      $rearBrakeDiskWork = 2500;
      $rearBrakePadsWork = 1500;
    } else if (substr($model, 0, 1) == '3' || substr($model, 0, 1) == '4' || substr($model, 0, 2) == 'M3' || substr($model, 0, 2) == 'M4') {
      $motorOilWork = 400;
      $oilFilterWork = 400;
      $sparkPlugWork = 1800;
      $fuelFilterWork = 1300;
      $airFilterWork = 400;
      $cabinAirFilterWork = 1000;
      $frontBrakeDiskWork = 1800;
      $frontBrakePadsWork = 1000;
      $rearBrakeDiskWork = 2500;
      $rearBrakePadsWork = 1500;
    } else if (substr($model, 0, 1) == '5' || substr($model, 0, 1) == '6' || substr($model, 0, 2) == 'M5' || substr($model, 0, 2) == 'M6') {
      $motorOilWork = 450;
      $oilFilterWork = 450;
      $sparkPlugWork = 1900;
      $fuelFilterWork = 1300;
      $airFilterWork = 400;
      $cabinAirFilterWork = 1000;
      $frontBrakeDiskWork = 1800;
      $frontBrakePadsWork = 1500;
      $rearBrakeDiskWork = 2500;
      $rearBrakePadsWork = 2000;
    } else if (substr($model, 0, 1) == '7' || substr($model, 0, 1) == '8' || substr($model, 0, 2) == 'M8') {
      $motorOilWork = 450;
      $oilFilterWork = 450;
      $sparkPlugWork = 2000;
      $fuelFilterWork = 1400;
      $airFilterWork = 400;
      $cabinAirFilterWork = 1200;
      $frontBrakeDiskWork = 1800;
      $frontBrakePadsWork = 2000;
      $rearBrakeDiskWork = 2500;
      $rearBrakePadsWork = 2000;
    } else if (substr($model, 0, 2) == 'X1' || substr($model, 0, 2) == 'X2' || substr($model, 0, 2) == 'X3' || substr($model, 0, 2) == 'X4') {
      $motorOilWork = 450;
      $oilFilterWork = 450;
      $sparkPlugWork = 1800;
      $fuelFilterWork = 1400;
      $airFilterWork = 400;
      $cabinAirFilterWork = 1200;
      $frontBrakeDiskWork = 1800;
      $frontBrakePadsWork = 2000;
      $rearBrakeDiskWork = 2500;
      $rearBrakePadsWork = 2500;
    } else if (substr($model, 0, 2) == 'X5' || substr($model, 0, 2) == 'X6' || substr($model, 0, 2) == 'X7') {
      $motorOilWork = 450;
      $oilFilterWork = 450;
      $sparkPlugWork = 2400;
      $fuelFilterWork = 1500;
      $airFilterWork = 400;
      $cabinAirFilterWork = 1200;
      $frontBrakeDiskWork = 2800;
      $frontBrakePadsWork = 2000;
      $rearBrakeDiskWork = 3000;
      $rearBrakePadsWork = 2600;
    }
    $recirculationFilterWork = 1000;
    $frontBrakePadsWearSensorWork = 0;
    $rearBrakePadsWearSensorWork = 0;

    // Подготовка данных к отправке

    // Объединение данных в массив
    $data = [
      'parts' => [
        'motorOil' => [
          'name' => 'Замена моторного масла',
          'quantity' => $motorOilQuantity,
          'quantityLabel' => ' л',
          'options' => $motorOilOptions,
          'work' => $motorOilWork,
        ],
        'oilFilter' => [
          'name' => 'Замена масляного фильтра',
          'number' => $oilFilterNumber,
          'price' => $oilFilterPrice,
          'work' => $oilFilterWork,
        ],
        'sparkPlug' => [
          'name' => 'Замена свечей зажигания',
          'quantity' => $sparkPlugQuantity,
          'quantityLabel' => ' шт.',
          'number' => $sparkPlugNumber,
          'price' => $sparkPlugPrice,
          'work' => $sparkPlugWork,
        ],
        'fuelFilter' => [
          'name' => 'Замена топливного фильтра',
          'number' => $fuelFilterNumber,
          'price' => $fuelFilterPrice,
          'work' => $fuelFilterWork,
        ],
        'airFilter' => [
          'name' => 'Замена воздушного фильтра',
          'number' => $airFilterNumber,
          'price' => $airFilterPrice,
          'work' => $airFilterWork,
        ],
        'cabinAirFilter' => [
          'name' => 'Замена салонного фильтра',
          'number' => $cabinAirFilterNumber,
          'price' => $cabinAirFilterPrice,
          'work' => $cabinAirFilterWork,
        ],
        'recirculationFilter' => [
          'name' => 'Замена микрофильтра рециркуляции воздуха',
          'number' => $recirculationFilterNumber,
          'price' => $recirculationFilterPrice,
          'work' => $recirculationFilterWork,
        ],
        'frontBrakeDisk' => [
          'name' => 'Замена передних тормозных дисков',
          'number' => $frontBrakeDiskNumber,
          'quantity' => 2,
          'quantityLabel' => ' шт.',
          'price' => $frontBrakeDiskPrice,
          'work' => $frontBrakeDiskWork,
        ],
        'frontBrakePads' => [
          'name' => 'Замена передних тормозных колодок',
          'number' => $frontBrakePadsNumber,
          'price' => $frontBrakePadsPrice,
          'work' => $frontBrakePadsWork,
          'initialWork' => $frontBrakePadsWork,
        ],
        'frontBrakePadsWearSensor' => [
          'name' => 'Замена датчика износа передних тормозных колодок',
          'number' => $frontBrakePadsWearSensorNumber,
          'price' => $frontBrakePadsWearSensorPrice,
          'work' => $frontBrakePadsWearSensorWork,
        ],
        'rearBrakeDisk' => [
          'name' => 'Замена задних тормозных дисков',
          'number' => $rearBrakeDiskNumber,
          'quantity' => 2,
          'quantityLabel' => ' шт.',
          'price' => $rearBrakeDiskPrice,
          'work' => $rearBrakeDiskWork,
        ],
        'rearBrakePads' => [
          'name' => 'Замена задних тормозных колодок',
          'number' => $rearBrakePadsNumber,
          'price' => $rearBrakePadsPrice,
          'work' => $rearBrakePadsWork,
          'initialWork' => $rearBrakePadsWork,
        ],
        'rearBrakePadsWearSensor' => [
          'name' => 'Замена датчика износа задних тормозных колодок',
          'number' => $rearBrakePadsWearSensorNumber,
          'price' => $rearBrakePadsWearSensorPrice,
          'work' => $rearBrakePadsWearSensorWork,
        ],
      ]
    ];

    // Удаление какой-либо детали, если она не была найдена (отсутствует номер, количество, цена детали и работ)
    foreach ($data['parts'] as $key => $element) {
      if (empty($element['number']) && empty($element['quantity']) && empty($element['price'])) unset($data['parts'][$key]);
    }

    // Указание количества воздушных фильтров равного двум при необходимости
    if ($hasTwoAirFilters) {
      $data['parts']['airFilter']['quantity'] = 2;
      $data['parts']['airFilter']['quantityLabel'] = ' шт.';
    }

    // Добавляем дополнительные услуги, в зависимости от пробега
    $data['additional'] = [];

    if ($_REQUEST['mileage'] >= 15000) {

      // Округление и сохранение пробега
      $mileage = round($_REQUEST['mileage'], -4);

      // Каждые 60 000 км
      if ($mileage % 60000 == 0) {
        $prices = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/data/parts-prices.json'), true);
        $finalDriveOilPrice = $prices['finalDriveOil']['price'];
        $transferBoxOilPrice = $prices['transferBoxOil']['price'];
        $data['additional']['gearBoxOil'] = [
          'name' => 'Замена масла в АКПП (Включая расходные материалы)',
          'work' => 20000,
        ];
        $data['additional']['frontAxleFinalDriveOil'] = [
          'name' => 'Замена масла в переднем редукторе',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => $finalDriveOilPrice,
          'work' => 800,
        ];
        $data['additional']['rearAxleFinalDriveOil'] = [
          'name' => 'Замена масла в заднем редукторе',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => $finalDriveOilPrice,
          'work' => 800,
        ];
        $data['additional']['transferBoxOil'] = [
          'name' => 'Замена масла в раздаточной коробке',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => $transferBoxOilPrice,
          'work' => 1200,
        ];
      }

      // Каждые 20 000 км
      if ($mileage % 20000 == 0) {
        $data['additional']['radiatorsWash'] = [
          'name' => 'Мойка радиаторов со снятием и дозаправкой охлаждающей жидкостью',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => 700,
        ];
        if (substr($model, 0, 1) == '1' || substr($model, 0, 1) == '2' || substr($model, 0, 2) == 'M2' || substr($model, 0, 1) == '3' || substr($model, 0, 1) == '4' || substr($model, 0, 2) == 'M3' || substr($model, 0, 2) == 'M4') {
          $data['additional']['radiatorsWash']['work'] = 6000;
        } else if (substr($model, 0, 1) == '5' || substr($model, 0, 2) == 'M5' || substr($model, 0, 1) == '6' || substr($model, 0, 2) == 'M6') {
          $data['additional']['radiatorsWash']['work'] = 7000;
        } else if (substr($model, 0, 1) == '7' || substr($model, 0, 1) == '8' || substr($model, 0, 1) == 'X') {
          $data['additional']['radiatorsWash']['work'] = 8000;
        }
        $data['additional']['brakeFluid'] = [
          'name' => 'Замена тормозной жидкости (Включая расходные материалы)',
          'work' => 2500,
        ];
      }

      // Каждые 30 000 км
      if ($mileage % 30000 == 0 ) {
        $data['additional']['coolant'] = [
          'name' => 'Замена охлаждающей жидкости (Включая расходные материалы)',
          'work' => 3000,
          'initialWork' => 3000,
        ];
      }

      // Каждые 10 000 км (Всегда)
      $data['additional']['carDiagnostics'] = [
        'name' => 'Общая диагностика автомобиля',
        'work' => 2100,
        'initialWork' => 2100,
      ];
      $data['additional']['computerDiagnostics'] = [
        'name' => 'Компьютерная диагностика',
        'work' => 1500,
      ];
    }
    
    // Отображение фразы "Отсутствует"
    foreach ($data['parts'] as $key => $element) {

      // Если номер какой-либо тормозной детали не найден
      if (empty($element['number']) && ($key == 'frontBrakeDisk' || $key == 'frontBrakePads' || $key == 'frontBrakePadsWearSensor' || $key ==  'rearBrakeDisk' || $key == 'rearBrakePads' || $key == 'rearBrakePadsWearSensor')) {
        unset($data['parts'][$key]);
        // $data['parts'][$key]['number'] = 'Отсутствует';
        // $data['parts'][$key]['price'] = 'Отсутствует';
      }

      // Если цена какой-либо детали не найдена
      if (isset($element['number']) && $element['price'] == null) unset($data['parts'][$key]['price']);
    }

    // Конвертирование данных в JSON строку и её вывод
    header('content-type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
  };
  searchParts();
}

?>
