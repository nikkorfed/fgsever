<?

require_once '../libraries/simple_html_dom.php';
require_once '../libraries/phpQuery.php';

if (isset($_REQUEST['vin']) && isset($_REQUEST['mileage'])) {
  function searchParts() {

    // Сохраняем вводные данные
    $vin = $_REQUEST['vin'];

    // Собираем информацию об автомобиле
    include 'car-info.php';
    $carInfo = requestCarInfo($vin, null);
    
    // Если из cats.parts были успешно получены данные
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
    // $link = 'https://bmwcats.com' . file_get_contents('https://www.bmwcats.com/ajax_vin_bmw.php?vin=' . urlencode($vin));

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://shop.bmw-sto.ru/ajax_search_bmw.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "pn=$vin");
    curl_setopt($ch, CURLOPT_POST, 1);

    $link = curl_exec($ch);
    curl_close ($ch);

    if ($link == 'Такой номер детали отсутствует в каталоге') {
      header('content-type: application/json; charset=UTF-8');
      echo json_encode([ 'error' => 'parts-not-found' ], JSON_UNESCAPED_UNICODE);
      return;
    }

    $link = 'https://shop.bmw-sto.ru' . $link;

    // Переход во вторую группу запчастей
    $serviceLink = substr($link, 0, strrpos($link, '?')) . '02/' . substr($link, strrpos($link, '?'));

    // Поиск ссылки на раздел запчастей для ТО
    foreach (file_get_html($serviceLink)->find('.etk-nodes-list li') as $element) {
      if (strpos($element->plaintext, 'ТО') !== false) { $serviceRelativeLink = $element->find('a', 0)->href; break; }
    }
    $serviceLink = substr($serviceLink, 0, strrpos($serviceLink, '?')) . $serviceRelativeLink;
    $service = file_get_html($serviceLink);

    // Собираем ещё информацию про автомобиль
    // $model = $service->find('.etk-mospid-carinfo-text .div-tr', 0)->find('span', 1)->plaintext;
    $model = $service->find('span[data-original-title="Модель"]', 0)->plaintext;
    $model = preg_replace('/X$/m', ' xDrive', $model);
    if (strpos($model, 'd') !== false) $isDiesel = true;
    if (!$modelCode) {
      preg_match('/\.\s(.+)\./', $service->find('h1', 0), $matches);
      $modelCode = $matches[1];
    }
    // if (!$date) {
    //   $date = $service->find('.etk-mospid-carinfo-text .div-tr', 4)->find('span', 1)->plaintext;
    // }

    // Ищем артикулы нужных нам запчастей
    $oilFilterNumber = $service->find('.tr01 td', 4)->plaintext;
    if ($isDiesel) {
      $fuelFilterNumber = $service->find('.tr02 td', 4)->plaintext;
    } else {
      $sparkPlugNumber = $service->find('.tr02 td', 4)->plaintext;
      $sparkPlugQuantity = $service->find('.tr02 td', 3)->plaintext;
      $sparkPlugQuantity = (int) preg_replace('/\D/', '', $sparkPlugQuantity);
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
      if (strpos($element->plaintext, 'Сервисное обслуживание тормозов') !== false) {
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
        if (
          strpos($optionsElement->getAttribute('data-options'), '((etk.optS3ACA == 0) || (etk.optS3ACA == 1))') !== false ||
          strpos($optionsElement->getAttribute('data-options'), '((etk.optL8AAA == 0) || (etk.optL8AAA == 1))') !== false ||
          strpos($optionsElement->getAttribute('data-options'), '((etk.optS842A == 0) || (etk.optS842A == 1))') !== false
        ) continue;

        // Также пропускаем устаревшие детали
        if ($element->find('.etk-spares-partnr a.etk-spares-partnr-link.disabled', 0)) continue;

        // Передний и задний тормозной диск
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'диск')) {
          if (!isset($frontBrakeDiskNumber) && !isset($rearBrakeDiskNumber)) {
            // echo 'ПЕРЕДНИЙ ТОРМОЗНОЙ ДИСК:<br>' . $element . '<br><br>';
            $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
          } else if (!isset($rearBrakeDiskNumber) && $isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            // echo 'ПЕРЕДНИЙ ТОРМОЗНОЙ ДИСК (ЭКСПОРТ):<br>' . $element . '<br><br>';
            $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
          } else if (!isset($rearBrakeDiskNumber) && isset($frontBrakePadsNumber)) {
            // echo 'ЗАДНИЙ ТОРМОЗНОЙ ДИСК:<br>' . $element . '<br><br>';
            $rearBrakeDiskNumber = $element->find('td', 4)->plaintext;
          } else if (isset($rearBrakeDiskNumber) && $isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            // echo 'ЗАДНИЙ ТОРМОЗНОЙ ДИСК (ЭКСПОРТ):<br>' . $element . '<br><br>';
            $rearBrakeDiskNumber = $element->find('td', 4)->plaintext;
          }
          continue;
        }

        // Комплекты передних и задних тормозных колодок
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'торм.накладок') || strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'емкомплект')) {
          if (!isset($frontBrakePadsNumber) && !isset($rearBrakeDiskNumber)) {
            // echo 'ПЕРЕДНИЕ ТОРМОЗНЫЕ КОЛОДКИ:<br>' . $element . '<br><br>';
            $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
          } else if (!isset($rearBrakeDiskNumber) && $isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            // echo 'ПЕРЕДНИЕ ТОРМОЗНЫЕ КОЛОДКИ (ЭКСПОРТ):<br>' . $element . '<br><br>';
            $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
          } else if (!isset($rearBrakePadsNumber) && isset($rearBrakeDiskNumber)) {
            // echo 'ЗАДНИЕ ТОРМОЗНЫЕ КОЛОДКИ:<br>' . $element . '<br><br>';
            $rearBrakePadsNumber = $element->find('td', 4)->plaintext;
          } else if (isset($rearBrakePadsNumber) && $isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            // echo 'ЗАДНИЕ ТОРМОЗНЫЕ КОЛОДКИ (ЭКСПОРТ):<br>' . $element . '<br><br>';
            $rearBrakePadsNumber = $element->find('td', 4)->plaintext;
          }
          continue;
        }

        // Датчики износа передних и задних тормозных колодок
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'износа тормозных накладок')) {
          if (!isset($frontBrakePadsWearSensorNumber) && !isset($rearBrakeDiskNumber)) {
            // echo 'ПЕРЕДНИЙ ДАТЧИК:<br>' . $element . '<br><br>';
            $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            // echo 'ПЕРЕДНИЙ ДАТЧИК (ЭКСПОРТ):<br>' . $element . '<br><br>';
            $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          } else if (!isset($rearBrakePadsWearSensorNumber) && isset($rearBrakeDiskNumber)) {
            // echo 'ЗАДНИЙ ДАТЧИК:<br>' . $element . '<br><br>';
            $rearBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
          }
          continue;
        }
      }

    // Если же тормоза во второй группе не нашли
    } else {

      // Переходим в 34-ую группу
      $brakesLink = substr($link, 0, strrpos($link, '?')) . '34/' . substr($link, strrpos($link, '?'));

      // Ищем ссылки на разделы передних тормозов
      $frontBrakesTemporaryRelativeLinks = [];
      foreach (file_get_html($brakesLink)->find('.etk-nodes-list li') as $element) {
        if (strpos($element->plaintext, 'Тормозной механизм переднего колеса') !== false) {
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
        if (
          ($optionsElement->getAttribute('data-options') == '((etk.optS3ACA == 0) || (etk.optS3ACA == 1))') ||
          ($optionsElement->getAttribute('data-options') == '((etk.optL8AAA == 0) || (etk.optL8AAA == 1))') ||
          ($optionsElement->getAttribute('data-options') == '((etk.optS842A == 0) || (etk.optS842A == 1))')
        ) continue;

        // Также пропускаем устаревшие детали
        if ($element->find('.etk-spares-partnr a.etk-spares-partnr-link.disabled', 0)) continue;

        // Берем нужные артикулы
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'торм.накладок')) {
          if (!isset($frontBrakePadsNumber)) {
            $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
            continue;
          } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            $frontBrakePadsNumber = $element->find('td', 4)->plaintext;
            continue;
          }
        }
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'диск')) {
          if (!isset($frontBrakeDiskNumber)) {
            $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
            continue;
          } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            $frontBrakeDiskNumber = $element->find('td', 4)->plaintext;
            continue;
          }
        }
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'износа тормозных накладок')) {
          if (!isset($frontBrakePadsWearSensorNumber)) {
            $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
            continue;
          } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            $frontBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
            continue;
          }
        }
      }

      // Ищем ссылки на разделы задних тормозов
      $rearBrakesTemporaryRelativeLinks = [];
      foreach (file_get_html($brakesLink)->find('.etk-nodes-list li') as $element) {
        if (strpos($element->plaintext, 'Тормозной механизм заднего колеса') !== false) {
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

        // Берем нужные артикулы
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'торм.накладок') || strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'накладок')) {
          if (!isset($rearBrakePadsNumber)) {
            $rearBrakePadsNumber = $element->find('td', 4)->plaintext;
            continue;
          } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            $rearBrakePadsNumber = $element->find('td', 4)->plaintext;
            continue;
          }
        }
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'диск')) {
          if (!isset($rearBrakeDiskNumber)) {
            $rearBrakeDiskNumber = $element->find('td', 4)->plaintext;
            continue;
          } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
            $rearBrakeDiskNumber = $element->find('td', 4)->plaintext;
            continue;
          }
        }
        if (strpos($element->find('.etk-spares-name div div', 0)->plaintext, 'износа тормозных накладок')) {
          if (!isset($rearBrakePadsWearSensorNumber)) {
            $rearBrakePadsWearSensorNumber = $element->find('td', 4)->plaintext;
            continue;
          } else if ($isExportBrakesOptionFound && strpos($optionsElement->getAttribute('data-options'), '((etk.optS212A == 0) || (etk.optS212A == 1))') !== false) {
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
    foreach ($capacity->find('.etk-capacity-list .div-tr') as $element) {
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
    if (strpos($service->find('h1', 0)->plaintext, 'F25') !== false) {
      $cabinAirFilterNumber = '64 31 9 312 318';
    }

    // Определение цен на моторное масло
    $originalMotorOilPrice = 1400;
    $motulMotorOilPrice = 900;

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
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, "http://80.78.254.156/work-prices");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $html = curl_exec($ch);
    curl_close($ch);

    $workPrices = json_decode($html, true);

    if (substr($model, 0, 1) == '1' || substr($model, 0, 2) == 'M1') {
      $workPrices = $workPrices['1-series'];
    } else if (substr($model, 0, 1) == '2' || substr($model, 0, 2) == 'M2') {
      $workPrices = $workPrices['2-series'];
    } else if (substr($model, 0, 1) == '3' || substr($model, 0, 2) == 'M3') {
      $workPrices = $workPrices['3-series'];
    } else if (substr($model, 0, 1) == '4' || substr($model, 0, 2) == 'M4') {
      $workPrices = $workPrices['4-series'];
    } else if (substr($model, 0, 1) == '5' || substr($model, 0, 2) == 'M5') {
      $workPrices = $workPrices['5-series'];
    } else if (substr($model, 0, 1) == '6' || substr($model, 0, 2) == 'M6') {
      $workPrices = $workPrices['6-series'];
    } else if (substr($model, 0, 1) == '7') {
      $workPrices = $workPrices['7-series'];
    } else if (substr($model, 0, 1) == '8' || substr($model, 0, 2) == 'M8') {
      $workPrices = $workPrices['8-series'];
    } else if (substr($model, 0, 2) == 'X1') {
      $workPrices = $workPrices['x1'];
    } else if (substr($model, 0, 2) == 'X2') {
      $workPrices = $workPrices['x2'];
    } else if (substr($model, 0, 2) == 'X3') {
      $workPrices = $workPrices['x3'];
    } else if (substr($model, 0, 2) == 'X4') {
      $workPrices = $workPrices['x4'];
    } else if (substr($model, 0, 2) == 'X5') {
      $workPrices = $workPrices['x5'];
    } else if (substr($model, 0, 2) == 'X6') {
      $workPrices = $workPrices['x6'];
    } else if (substr($model, 0, 2) == 'X7') {
      $workPrices = $workPrices['x7'];
    }

    // Подготовка данных к отправке

    // Объединение данных в массив
    $data = [
      'parts' => [
        'motorOil' => [
          'name' => 'Замена моторного масла',
          'quantity' => $motorOilQuantity,
          'quantityLabel' => ' л',
          'options' => $motorOilOptions,
          'work' => $workPrices['motorOil'],
        ],
        'oilFilter' => [
          'name' => 'Замена масляного фильтра',
          'number' => $oilFilterNumber,
          'price' => $oilFilterPrice,
          'work' => $workPrices['oilFilter'],
        ],
        'sparkPlug' => [
          'name' => 'Замена свечей зажигания',
          'quantity' => $sparkPlugQuantity,
          'quantityLabel' => ' шт.',
          'number' => $sparkPlugNumber,
          'price' => $sparkPlugPrice,
          'work' => $workPrices['sparkPlugs'],
        ],
        'fuelFilter' => [
          'name' => 'Замена топливного фильтра',
          'number' => $fuelFilterNumber,
          'price' => $fuelFilterPrice,
          'work' => $workPrices['fuelFilter'],
        ],
        'airFilter' => [
          'name' => 'Замена воздушного фильтра',
          'number' => $airFilterNumber,
          'price' => $airFilterPrice,
          'work' => $workPrices['airFilter'],
        ],
        'cabinAirFilter' => [
          'name' => 'Замена салонного фильтра',
          'number' => $cabinAirFilterNumber,
          'price' => $cabinAirFilterPrice,
          'work' => $workPrices['cabinAirFilter'],
        ],
        'recirculationFilter' => [
          'name' => 'Замена микрофильтра рециркуляции воздуха',
          'number' => $recirculationFilterNumber,
          'price' => $recirculationFilterPrice,
          'work' => $workPrices['recirculationFilter'],
        ],
        'frontBrakeDisk' => [
          'name' => 'Замена передних тормозных дисков',
          'number' => $frontBrakeDiskNumber,
          'quantity' => 2,
          'quantityLabel' => ' шт.',
          'price' => $frontBrakeDiskPrice,
          'work' => $workPrices['frontBrakeDisks'],
        ],
        'frontBrakePads' => [
          'name' => 'Замена передних тормозных колодок',
          'number' => $frontBrakePadsNumber,
          'price' => $frontBrakePadsPrice,
          'work' => $workPrices['frontBrakePads'],
          'initialWork' => $workPrices['frontBrakePads'],
        ],
        'frontBrakePadsWearSensor' => [
          'name' => 'Замена датчика износа передних тормозных колодок',
          'number' => $frontBrakePadsWearSensorNumber,
          'price' => $frontBrakePadsWearSensorPrice,
          'work' => $workPrices['frontBrakePadsWearSensor'],
        ],
        'rearBrakeDisk' => [
          'name' => 'Замена задних тормозных дисков',
          'number' => $rearBrakeDiskNumber,
          'quantity' => 2,
          'quantityLabel' => ' шт.',
          'price' => $rearBrakeDiskPrice,
          'work' => $workPrices['rearBrakeDisks'],
        ],
        'rearBrakePads' => [
          'name' => 'Замена задних тормозных колодок',
          'number' => $rearBrakePadsNumber,
          'price' => $rearBrakePadsPrice,
          'work' => $workPrices['rearBrakePads'],
          'initialWork' => $workPrices['rearBrakePads'],
        ],
        'rearBrakePadsWearSensor' => [
          'name' => 'Замена датчика износа задних тормозных колодок',
          'number' => $rearBrakePadsWearSensorNumber,
          'price' => $rearBrakePadsWearSensorPrice,
          'work' => $workPrices['rearBrakePadsWearSensor'],
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
          'work' => $workPrices['gearBoxOilWithMaterials'],
        ];
        $data['additional']['frontAxleFinalDriveOil'] = [
          'name' => 'Замена масла в переднем редукторе',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => $finalDriveOilPrice,
          'work' => $workPrices['frontAxleFinalDriveOil'],
        ];
        $data['additional']['rearAxleFinalDriveOil'] = [
          'name' => 'Замена масла в заднем редукторе',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => $finalDriveOilPrice,
          'work' => $workPrices['rearAxleFinalDriveOil'],
        ];
        $data['additional']['transferBoxOil'] = [
          'name' => 'Замена масла в раздаточной коробке',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => $transferBoxOilPrice,
          'work' => $workPrices['transferCaseOil'],
        ];
      }

      // Каждые 20 000 км
      if ($mileage % 20000 == 0) {
        $data['additional']['radiatorsWash'] = [
          'name' => 'Мойка радиаторов со снятием и дозаправкой охлаждающей жидкостью',
          'quantity' => 1,
          'quantityLabel' => ' л',
          'price' => 700,
          'work' => $workPrices['radiatorsWash']
        ];
        $data['additional']['brakeFluid'] = [
          'name' => 'Замена тормозной жидкости (Включая расходные материалы)',
          'work' => $workPrices['brakeFluidWithMaterials'],
        ];
      }

      // Каждые 30 000 км
      if ($mileage % 30000 == 0 ) {
        $data['additional']['coolant'] = [
          'name' => 'Замена охлаждающей жидкости (Включая расходные материалы)',
          'work' => $workPrices['coolant'],
          'initialWork' => $workPrices['coolant'],
        ];
      }

      // Каждые 10 000 км (Всегда)
      $data['additional']['carDiagnostics'] = [
        'name' => 'Общая диагностика автомобиля',
        'work' => $workPrices['carDiagnostics'],
        'initialWork' => $workPrices['carDiagnostics'],
      ];
      $data['additional']['computerDiagnostics'] = [
        'name' => 'Компьютерная диагностика',
        'work' => $workPrices['computerDiagnostics'],
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
