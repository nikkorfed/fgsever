<?

require_once '../libraries/simple_html_dom.php';
require_once '../libraries/phpQuery.php';

if (isset($_REQUEST['vin']) && isset($_REQUEST['mileage'])) {

  // Сохраняем вводные данные
  $vin = $_REQUEST['vin'];

  // Собираем информацию об автомобиле
  include 'car-info.php';
  $carInfo = requestCarInfo($vin);
  
  // Если из AOS были успешно получены данные
  if ($carInfo['vin']) {
    $vin = $carInfo['vin'];
    // $model = $carInfo['model'];
    $modelCode = $carInfo['modelCode'];
    $date = $carInfo['productionDate'];
    $image = $carInfo['image'];
    $options = $carInfo['options'];
    
    // Проверяем, есть ли опции Спортивные тормоза M (2NH) и Оснащение тормозной системой экспорт. (S212A)
    $factoryOptions = array_keys($options['factory']);
    $installedOptions = array_keys($options['installed']);
    $allOptions = array_merge($factoryOptions, $installedOptions);
    foreach ($allOptions as $key => $option) {
      if (strpos($option, '2NH') !== false) $isSportBrakesOptionFound = true;
      if (strpos($option, '212') !== false) $isExportBrakesOptionFound = true;
    }
  }

  // Ищем страницу c запчастями для определенного VIN-номера
  $link = 'https://bmwcats.com' . file_get_contents('https://www.bmwcats.com/ajax_vin_bmw.php?vin=' . urlencode($vin));

  // Переход во вторую группу запчастей
  $serviceLink = substr($link, 0, strrpos($link, '?')) . '02/' . substr($link, strrpos($link, '?'));

  // Поиск ссылки на раздел запчастей для ТО
  foreach (file_get_html($serviceLink)->find('.etk-nodes-list li') as $element) {
    if (strpos($element->plaintext, 'ТО')) { $serviceRelativeLink = $element->find('a', 0)->href; break; }
  }
  $serviceLink = substr($serviceLink, 0, strrpos($serviceLink, '?')) . $serviceRelativeLink;
  $service = file_get_html($serviceLink);

  // Собираем ещё информацию про автомобиль
  $model = $service->find('.etk-mospid-carinfo-text .div-tr', 0)->find('span', 1)->plaintext;
  $model = preg_replace('/X$/m', ' xDrive', $model);
  if (strpos($model, 'd') !== false) $isDiesel = true;
  if (!$modelCode) {
    preg_match('/\.\s(.+)\./', $service->find('h1', 0), $matches);
    $modelCode = $matches[1];
  }
  if (!$date) {
    $date = $service->find('.etk-mospid-carinfo-text .div-tr', 4)->find('span', 1)->plaintext;
  }

  // Ищем артикулы нужных нам запчастей
  $oilFilterNumber = $service->find('.tr01 td', 4)->plaintext;
  if ($isDiesel) {
    $fuelFilterNumber = $service->find('.tr02 td', 4)->plaintext;
  } else {
    $sparkPlugNumber = $service->find('.tr02 td', 4)->plaintext;
    $sparkPlugQuantity = $service->find('.tr02 td', 3)->plaintext;
  }
  // $airFilterNumber = [];
  foreach ($service->find('.tr03') as $element) {
    $optionsElement = $element->parent()->parent()->parent();
    if (($optionsElement->getAttribute('data-options') == '((etk.optL8AAA == 0) || (etk.optL8AAA == 1))') || ($optionsElement->getAttribute('data-options') == '((etk.optS842A == 0) || (etk.optS842A == 1))') || ($element->find('.etk-spares-partnr a.etk-spares-partnr-link.disabled', 0))) {
      continue;
    } else if (isset($airFilterNumber) && ($element->find('td', 4)->plaintext != $airFilterNumber) && ((int)str_replace(' ', '', $element->find('td', 4)->plaintext) - (int)str_replace(' ', '', $airFilterNumber) <= 3)) {
      // array_push($airFilterNumber, $element->find('td', 4)->plaintext);
      $hasTwoAirFilters = true;
    } else {
      // $airFilterNumber[0] = $element->find('td', 4)->plaintext;
      $airFilterNumber = $element->find('td', 4)->plaintext;
    }
  }
  foreach ($service->find('.tr04') as $element) {
    if ($element->find('.etk-spares-partnr a.etk-spares-partnr-link.disabled', 0)) {
      continue;
    } else if (strpos($element->find('.etk-spares-name div div', 0), 'углем')) {
      $cabinAirFilterNumber = $element->find('td', 4)->plaintext;
      break;
    } else if (empty($cabinAirFilterNumber)) {
      $cabinAirFilterNumber = $element->find('td', 4)->plaintext;
    }
  }
  if (strpos($service->find('.tr05 .etk-spares-name div div', 0)->plaintext, 'рециркуляции воздуха')) {
    $recirculationFilterNumber = $service->find('.tr05 td', 4)->plaintext;
  }

  // Теперь ищем страницу с деталями тормозной системы

  // Переход во вторую группу запчастей
  $brakesLink = substr($link, 0, strrpos($link, '?')) . '02/' . substr($link, strrpos($link, '?'));

  // Ищем ссылки на разделы тормозов
  $brakesTemporaryRelativeLinks = [];
  foreach (file_get_html($brakesLink)->find('.etk-nodes-list li') as $element) {
    if (strpos($element->plaintext, 'Сервисное обслуживание тормозов')) {
      array_push($brakesTemporaryRelativeLinks, $element->find('a', 0)->href);
      $isBrakesFoundInSecondGroup = true;
    }
  }

  // Если нашли тормоза во второй группе
  if ($isBrakesFoundInSecondGroup) {

    // Ищем страницу с обычными тормозами (не спортивными)
    foreach ($brakesTemporaryRelativeLinks as $element) {
      $brakesTemporaryLink = substr($brakesLink, 0, strrpos($brakesLink, '?')) . $element;
      $temporaryBrakes = file_get_html($brakesTemporaryLink);
      if (strpos($temporaryBrakes->find('.etk-spares-option-off', 0)->plaintext, 'S2NHA')) {
        $brakes = $temporaryBrakes;
      } else if (!isset($brakes)) {
        $brakes = $temporaryBrakes;
      }
    }

    // Если в комплектации присутствуют спортивные тормоза, ищем страницу с ними
    if ($isSportBrakesOptionFound) {
      foreach ($brakesTemporaryRelativeLinks as $element) {
        $brakesTemporaryLink = substr($brakesLink, 0, strrpos($brakesLink, '?')) . $element;
        $temporaryBrakes = file_get_html($brakesTemporaryLink);
        if (strpos($temporaryBrakes->find('.etk-spares-option-on', 0)->plaintext, 'S2NHA')) $brakes = $temporaryBrakes;
      }
    }

    // Ищем необходимые детали
    foreach ($brakes->find('tr') as $element) {
      // echo $element->find('.etk-spares-name div div', 0)->plaintext . '<br><br>';

      // Пропускаем строку без номера
      if ($element->getAttribute('class') == 'tr--') continue;

      // Пропускаем детали с неподходящими опциями (фаркопом (S3ACA), исполнением для Китая (L8AAA) и еще чем-то (S842A))
      $optionsElement = $element->parent()->parent()->parent();
      if (strpos($optionsElement->getAttribute('data-options'), '((etk.optS3ACA == 0) || (etk.optS3ACA == 1))') || strpos($optionsElement->getAttribute('data-options'), '((etk.optL8AAA == 0) || (etk.optL8AAA == 1))') || strpos($optionsElement->getAttribute('data-options'), '((etk.optS842A == 0) || (etk.optS842A == 1))')) continue;

      // Также пропускаем устаревшие детали
      if ($element->find('.etk-spares-partnr a.etk-spares-partnr-link.disabled', 0)) continue;

      // Передний и задний тормозной диск
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'диск')) {
        if (!isset($frontBrakeDiskNumber)) {
          // echo 'ПЕРЕДНИЙ ТОРМОЗНОЙ ДИСК:<br>' . $element . '<br><br>';
          $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          // echo 'ПЕРЕДНИЙ ТОРМОЗНОЙ ДИСК (ЭКСПОРТ):<br>' . $element . '<br><br>';
          $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if (!isset($rearBrakeDiskNumber) && isset($frontBrakePadsNumber)) {
          // echo 'ЗАДНИЙ ТОРМОЗНОЙ ДИСК:<br>' . $element . '<br><br>';
          $rearBrakeDiskNumber = $element->find('td', 4)->plaintext;
          continue;
        } else {
          continue;
        }
      }

      // Комплекты передних и задних тормозных колодок
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'торм.накладок') || strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'емкомплект')) {
        // echo '<b>Нашли позицию со словами "торм.накладок" или "накладок".</b><br><br>';
        if (!isset($frontBrakePadsNumber) && !isset($rearBrakeDiskNumber)) {
          // echo 'ПЕРЕДНИЕ ТОРМОЗНЫЕ КОЛОДКИ:<br>' . $element . '<br><br>';
          $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          // echo 'ПЕРЕДНИЕ ТОРМОЗНЫЕ КОЛОДКИ (ЭКСПОРТ):<br>' . $element . '<br><br>';
          $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if (!isset($rearBrakePadsNumber) && isset($rearBrakeDiskNumber)) {
          // echo 'ЗАДНИЕ ТОРМОЗНЫЕ КОЛОДКИ:<br>' . $element . '<br><br>';
          $rearBrakePadsNumber = $element->find('td', 4)->plaintext;
          continue;
        } else {
          continue;
        }
      }

      // Датчики износа передних и задних тормозных колодок
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'износа')) {
        // echo '<b>Нашли позицию со словом "износ"</b><br><br>';
        if (!isset($frontBrakePadsWearSensorNumber) && !isset($rearBrakeDiskNumber)) {
          // echo 'ПЕРЕДНИЙ ДАТЧИК:<br>' . $element . '<br><br>';
          $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          // echo 'ПЕРЕДНИЙ ДАТЧИК (ЭКСПОРТ):<br>' . $element . '<br><br>';
          $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if (!isset($rearBrakePadsWearSensorNumber) && isset($rearBrakeDiskNumber)) {
          // echo 'ЗАДНИЙ ДАТЧИК:<br>' . $element . '<br><br>';
          $rearBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          continue;
        } else {
          continue;
        }
      }
    }

  // Если же тормоза во второй группе не нашли
  } else {

    // Переходим в 34-ую группу
    $brakesLink = substr($link, 0, strrpos($link, '?')) . '34/' . substr($link, strrpos($link, '?'));

    // Ищем ссылки на разделы передних тормозов
    $frontBrakesTemporaryRelativeLinks = [];
    foreach (file_get_html($brakesLink)->find('.etk-nodes-list li') as $element) {
      if (strpos($element->plaintext, 'Тормозной механизм переднего колеса')) {
        array_push($frontBrakesTemporaryRelativeLinks, $element->find('a', 0)->href);
        // $isFrontBrakesFoundIn34thGroup = true; Заложено на всякий случай, если вдруг где-то в 34-группе тормоза будут выдаваться по-другому
      }
    }

    // Ищем страницу с обычными передними тормозами (не спортивными)
    foreach ($frontBrakesTemporaryRelativeLinks as $element) {
      $frontBrakesTemporaryLink = substr($brakesLink, 0, strrpos($brakesLink, '?')) . $element;
      $temporaryFrontBrakes = file_get_html($frontBrakesTemporaryLink);
      if (strpos($temporaryFrontBrakes->find('.etk-spares-option-off', 0)->plaintext, 'S2NHA')) {
        $frontBrakes = $temporaryFrontBrakes;
      } else if (!isset($frontBrakes)) {
        $frontBrakes = $temporaryFrontBrakes;
      }
    }

    // Если в комплектации присутствуют спортивные тормоза, ищем страницу с соответствующими передними
    if ($isSportBrakesOptionFound) {
      foreach ($frontBrakesTemporaryRelativeLinks as $element) {
        $frontBrakesTemporaryLink = substr($brakesLink, 0, strrpos($brakesLink, '?')) . $element;
        $temporaryFrontBrakes = file_get_html($frontBrakesTemporaryLink);
        if (strpos($temporaryFrontBrakes->find('.etk-spares-option-on', 0)->plaintext, 'S2NHA')) $frontBrakes = $temporaryFrontBrakes;
      }
    }

    // Ищем артикулы деталей передней тормозной системы
    foreach ($frontBrakes->find('tr') as $element) {

      // Пропускаем детали с неподходящими опциями (фаркопом (S3ACA), исполнением для Китая (L8AAA) и еще чем-то (S842A))
      $optionsElement = $element->parent()->parent()->parent();
      if (($optionsElement->getAttribute('data-options') == '((etk.optS3ACA == 0) || (etk.optS3ACA == 1))') || ($optionsElement->getAttribute('data-options') == '((etk.optL8AAA == 0) || (etk.optL8AAA == 1))') || ($optionsElement->getAttribute('data-options') == '((etk.optS842A == 0) || (etk.optS842A == 1))')) continue;

      // Также пропускаем устаревшие детали
      if ($element->find('.etk-spares-partnr a.etk-spares-partnr-link.disabled', 0)) continue;

      // Берем нужные артикулы
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'торм.накладок')) {
        if (!isset($frontBrakePadsNumber)) {
          $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
          continue;
        }
      }
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'диск')) {
        if (!isset($frontBrakeDiskNumber)) {
          $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
          continue;
        }
      }
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'износа')) {
        if (!isset($frontBrakePadsWearSensorNumber)) {
          $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          continue;
        }
      }
    }

    // Ищем ссылки на разделы задних тормозов
    $rearBrakesTemporaryRelativeLinks = [];
    foreach (file_get_html($brakesLink)->find('.etk-nodes-list li') as $element) {
      if (strpos($element->plaintext, 'Тормозной механизм заднего колеса')) {
        array_push($rearBrakesTemporaryRelativeLinks, $element->find('a', 0)->href);
        // $isrearBrakesFoundIn34thGroup = true; Заложено на всякий случай, если вдруг где-то в 34-группе тормоза будут выдаваться по-другому
      }
    }

    // Ищем страницу с обычными задними тормозами (не спортивными)
    foreach ($rearBrakesTemporaryRelativeLinks as $element) {
      $rearBrakesTemporaryLink = substr($brakesLink, 0, strrpos($brakesLink, '?')) . $element;
      $temporaryRearBrakes = file_get_html($rearBrakesTemporaryLink);
      if (strpos($temporaryRearBrakes->find('.etk-spares-comment', 0)->plaintext, 'нет')) {
        $rearBrakes = $temporaryRearBrakes;
      } else if (!isset($rearBrakes)) {
        $rearBrakes = $temporaryRearBrakes;
      }
    }

    // Если в комплектации присутствуют спортивные тормоза, ищем страницу с соответствующими задними
    if ($isSportBrakesOptionFound) {
      foreach ($rearBrakesTemporaryRelativeLinks as $element) {
        $rearBrakesTemporaryLink = substr($brakesLink, 0, strrpos($brakesLink, '?')) . $element;
        $temporaryRearBrakes = file_get_html($rearBrakesTemporaryLink);
        if (strpos($temporaryRearBrakes->find('.etk-spares-option-on', 0)->plaintext, 'S2NHA')) $rearBrakes = $temporaryRearBrakes;
      }
    }

    // Ищем артикулы деталей задней тормозной системы
    foreach ($rearBrakes->find('tr') as $element) {

      // Пропускаем детали с неподходящими опциями (фаркопом (S3ACA), исполнением для Китая (L8AAA) и еще чем-то (S842A))
      $optionsElement = $element->parent()->parent()->parent();
      if (($optionsElement->getAttribute('data-options') == '((etk.optS3ACA == 0) || (etk.optS3ACA == 1))') || ($optionsElement->getAttribute('data-options') == '((etk.optL8AAA == 0) || (etk.optL8AAA == 1))') || ($optionsElement->getAttribute('data-options') == '((etk.optS842A == 0) || (etk.optS842A == 1))')) continue;

      // Также пропускаем устаревшие детали
      if ($element->find('.etk-spares-partnr a.etk-spares-partnr-link.disabled', 0)) continue;

      // Если в комплектации присутствуют экспортные тормоза, пропускаем обычные
      // if ($isExportBrakesOptionFound) {
      //   if (!strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) continue;
      // }

      // Берем нужные артикулы
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'торм.накладок') || strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'накладок')) {
        if (!isset($rearBrakePadsNumber)) {
          $rearBrakePadsNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          $rearBrakePadsNumber = $element->find('td', 4)->plaintext;
          continue;
        }
      }
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'диск')) {
        if (!isset($rearBrakeDiskNumber)) {
          $rearBrakeDiskNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          $rearBrakeDiskNumber = $element->find('td', 4)->plaintext;
          continue;
        }
      }
      if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'износа')) {
        if (!isset($rearBrakePadsWearSensorNumber)) {
          $rearBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          continue;
        } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))')) {
          $rearBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          continue;
        }
      }


    }

  }

  // Ищем страницу с информацией о заправочных емкостях
  $capacityLink = substr($link, 0, strrpos($link, '?')) . 'capacity/' . substr($link, strrpos($link, '?'));
  $capacity = file_get_html($capacityLink);

  // Выясняем необходимый объем жидкостей
  foreach ($capacity->find('.etk-capacity-row') as $element) {
    if (strpos($element->find('.etk-capacity-name', 0)->plaintext, 'Масло в двигатель') !== false) {
      $motorOilQuantity = (int)$element->find('.etk-capacity-data', 0)->plaintext;
    } else if (strpos($element->find('.etk-capacity-name', 0)->plaintext, 'Масло в КПП') !== false) {
      $gearBoxOilQuantity = (int)$element->find('.etk-capacity-data', 0)->plaintext;
    } else if (strpos($element->find('.etk-capacity-name', 0)->plaintext, 'Масло для заднего моста') !== false) {
      $rearAxleFinalDriveQuantity = (int)$element->find('.etk-capacity-data', 0)->plaintext;
    } else if (strpos($element->find('.etk-capacity-name', 0)->plaintext, 'Рекомендации') !== false) {
      if (preg_match('/Transfer box \(0,44 l\)/', $element->find('.etk-capacity-data', 0)->plaintext, $matches)) {
        // var_dump($matches);
      }
    }
  }

  // Исправление ошибок

  // Неправильный фильтр на X3 F25
  if (strpos($service->find('h1', 0)->plaintext, 'F25')) {
    $cabinAirFilterNumber = '64 31 9 312 318';
  }

  // Определение цен на моторное масло
  $originalMotorOilPrice = 900;
  $motulMotorOilPrice = 700;

  // Запрос цен единым списком с авторизацией на сайте parts.major-auto.ru

  // Подготовка массива артикулов
  $numbers = [];
  if ($oilFilterNumber) array_push($numbers, $oilFilterNumber);
  if ($sparkPlugNumber) array_push($numbers, $sparkPlugNumber);
  if ($fuelFilterNumber) array_push($numbers, $fuelFilterNumber);
  // foreach ($airFilterNumber as $element) array_push($numbers, $element);
  if ($airFilterNumber) array_push($numbers, $airFilterNumber);
  if ($cabinAirFilterNumber) array_push($numbers, $cabinAirFilterNumber);
  if ($recirculationFilterNumber) array_push($numbers, $recirculationFilterNumber);
  array_push($numbers, $frontBrakeDiskNumber);
  array_push($numbers, $frontBrakePadsNumber);
  array_push($numbers, $frontBrakePadsWearSensorNumber);
  array_push($numbers, $rearBrakeDiskNumber);
  array_push($numbers, $rearBrakePadsNumber);
  array_push($numbers, $rearBrakePadsWearSensorNumber);

  $data = implode('
  ', $numbers);

  // Первичная авторизация на сайте и сохранение Cookie для дальнейшего использования
  $ch = curl_init();
 
  curl_setopt($ch, CURLOPT_URL, 'https://parts.major-auto.ru/Account/LogOn');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'UserName' => '+7(903)976-00-45',
    'Password' => 'Bsever',
    'btnLogOn' => 'Вход',
  ]);

  $test = curl_exec($ch);
  curl_close($ch);

  // Запрос на страницу для поиска деталей
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, 'https://parts.major-auto.ru/SearchNew/ByList');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'searchElements' => $data,
    'SearchByList' => 'Поиск',
  ]);

  $html = curl_exec($ch);
  curl_close($ch);

  // Поиск цен и сохранение их в массив
  $prices = [];
  $html = phpQuery::newDocument($html);

  for ($i = 0; $i < count($numbers); $i++) {
    $price = $html->find('#priceItemLbl_' . $i)->text();
    // Удаление пробелов из числа
    $price = str_replace(' ', '', $price);
    // Замена запятой на точку
    $price = str_replace(',', '.', $price);
    array_push($prices, +$price);
  }

  // Проведение наценки на детали
  foreach ($prices as &$value) {
    $value = $value * 1.3;
  }

  // Сохранение цен в отдельные переменные
  $i = 0;
  if ($oilFilterNumber) $oilFilterPrice = $prices[$i]; $i++;
  if ($sparkPlugNumber) { $sparkPlugPrice = $prices[$i]; $i++; }
  if ($fuelFilterNumber) { $fuelFilterPrice = $prices[$i]; $i++; }
  // $airFilterPrice = []; $cabinAirFilterPrice = [];
  // foreach ($airFilterNumber as $element) { array_push($airFilterPrice, $prices[$i]); $i++; }
  if ($airFilterNumber) $airFilterPrice = $prices[$i]; $i++;
  if ($cabinAirFilterNumber) $cabinAirFilterPrice = $prices[$i]; $i++;
  if ($recirculationFilterNumber) { $recirculationFilterPrice = $prices[$i]; $i++; }
  $frontBrakeDiskPrice = $prices[$i]; $i++;
  $frontBrakePadsPrice = $prices[$i]; $i++;
  $frontBrakePadsWearSensorPrice = $prices[$i]; $i++;
  $rearBrakeDiskPrice = $prices[$i]; $i++;
  $rearBrakePadsPrice = $prices[$i]; $i++;
  $rearBrakePadsWearSensorPrice = $prices[$i];

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
  
  // Отображение фразы "Отсутствует", если номер какой-либо тормозной детали не найден
  foreach ($data['parts'] as $key => $element) {
    if (empty($element['number']) && ($key == 'frontBrakeDisk' || $key == 'frontBrakePads' || $key == 'frontBrakePadsWearSensor' || $key ==  'rearBrakeDisk' || $key == 'rearBrakePads' || $key == 'rearBrakePadsWearSensor')) {
      $data[$key]['number'] = 'Отсутствует';
      $data[$key]['price'] = 'Отсутствует';
    }
  }

  // Конвертирование данных в JSON строку и её вывод
  header('content-type: application/json; charset=UTF-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

?>
