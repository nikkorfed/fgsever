<? 

include_once '../libraries/simple_html_dom.php';

// Выдача данных в формате JSON при передаче VIN
if (isset($_REQUEST['vin']) && !isset($_REQUEST['mileage'])) {
  $result = requestCarInfo($_REQUEST['vin']);
  header('content-type: application/json; charset=UTF-8');
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
}

// Основная функция, собирающая данные об автомобиле
function requestCarInfo($vin) {
  
  // do {
    // if ($error) include 'aos-auth.php';
    // include 'aos-auth.php';
    
    // Запрос первоначальной страницы с машиной
    $url = 'https://myair-bdr.bmwgroup.com/air/faces/xhtml/fahrzeug/FahrzeugSingleView.xhtml?vin=' . $vin;
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'aos-bmwgroup.cookie');
    curl_setopt($ch, CURLOPT_HEADER, 1);
    
    $html = curl_exec($ch);
    curl_close($ch);
    echo $html;
    
    preg_match('/403 Forbidden/', $html, $matches);
    $error = $matches[0];
    
  // } while ($error);

  // Подготовка массива для хранения найденных данных
  $result = [];

  // Считывание данных
  $html = str_get_html($html);
  if ($html->find('.air-accordion-table-row')) {

    $result['image'] = $html->find('.air-cosy-frame img', 0)->getAttribute('src');
    foreach ($html->find('.air-accordion-table-row') as $info) {
      if (mb_strpos($info->find('.air-accordion-table-label', 0)->plaintext, 'Модель') !== false) {
        $model = trim($info->find('.air-accordion-table-text', 0)->plaintext);
        $result['model'] = str_replace([' a', 'xdriv', 'xdr'], ['', 'xDrive', 'xDrive'], mb_strtolower($model));
      } else if (mb_strpos($info->find('.air-accordion-table-label', 0)->plaintext, 'VIN (17-значный)') !== false) {
        $result['vin'] = trim($info->find('.air-accordion-table-text', 0)->plaintext);
      } else if (mb_strpos($info->find('.air-accordion-table-label', 0)->plaintext, 'Внутризаводское обозначение серии') !== false) {
        $result['modelCode'] = trim($info->find('.air-accordion-table-text', 0)->plaintext);
      } else if (mb_strpos($info->find('.air-accordion-table-label', 0)->plaintext, 'Дата изготовления') !== false) {
        $result['productionDate'] = trim($info->find('.air-accordion-table-text', 0)->plaintext);
      }
    }
  
    // Подготовка данных для запроса списка опций
    $data = $html->find('.air-sonderausstattungen-table', 0)->parent()->next_sibling()->innertext;
    
    preg_match('/\(({[\s\S][^;]+})\)/', $data, $matches);
    $data = $matches[1];
    // echo $data;
    
    preg_match('/s:"([^"]+)"/', $data, $matches);
    $source = $matches[1];
    
    preg_match('/f:"([^"]+)"/', $data, $matches);
    $form = $matches[1];
    
    preg_match('/u:"([^"]+)"/', $data, $matches);
    $render = $matches[1];

    $viewState = $html->find('input[name=javax.faces.ViewState]', 0)->getAttribute('value');
    
    // Запрос списка опций
    $url = 'https://myair-bdr.bmwgroup.com/air/faces/xhtml/fahrzeug/FahrzeugSingleView.xhtml?vin=' . $vin;

    $data = [
      'javax.faces.partial.ajax' => 'true',
      // 'javax.faces.source' => 'contentForm:vehicleAccordion:j_idt891',
      'javax.faces.source' => $source,
      'javax.faces.partial.execute' => '@all',
      // 'javax.faces.partial.render' => 'contentForm:vehicleAccordion:sonderausstattungen',
      'javax.faces.partial.render' => $render,
      // 'contentForm:vehicleAccordion:j_idt891' => 'contentForm:vehicleAccordion:j_idt891',
      $source => $source,
      // 'contentForm' => 'contentForm',
      'contentForm' => $form,
      'contentForm:vehicleAccordion_active' => '0,1,2,3,4,5,7,8,9,10,11,12,13,14,15,16,17,18,19',
      'javax.faces.ViewState' => $viewState,
    ];
    // print_r($data);

    // $ch = curl_init();

    // curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    // curl_setopt($ch, CURLOPT_COOKIEFILE, 'aos-bmwgroup.cookie');

    // $html = curl_exec($ch);
    // curl_close($ch);
    // // echo $html;
    
    preg_match('/<table.*>([\s\S]+)<\/table>/', $html, $matches);
    $html = str_get_html($matches[0]);

    $result['options'] = [
      'factory' => [],
      'installed' => []
    ];

    foreach ($html->find('.air-sonderausstattungen-table-data td', 0)->find('.air-sonderausstattung') as $option) {
      $result['options']['factory'][$option->find('.air-sonderausstattung-code', 0)->plaintext] = $option->find('.air-sonderausstattung-text', 0)->plaintext;
    }

    foreach ($html->find('.air-sonderausstattungen-table-data td', 2)->find('.air-sonderausstattung') as $option) {
      $result['options']['installed'][$option->find('.air-sonderausstattung-code', 0)->plaintext] = $option->find('.air-sonderausstattung-text', 0)->plaintext;
    }

    return $result;
  }
}

// function requestCarImages($vin) {

//   $url = "https://etk-b2i.bmwgroup.com/";

//   $ch = curl_init();
    
//   curl_setopt($ch, CURLOPT_URL, $url);
//   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//   curl_setopt($ch, CURLOPT_COOKIEFILE, 'aos-bmwgroup.cookie');
//   curl_setopt($ch, CURLOPT_HEADER, 1);
  
//   $html = curl_exec($ch);
//   curl_close($ch);
//   // echo $html;
  
// }

// function checkForTwoCars() {

//   $ch = curl_init();

//   curl_setopt($ch, CURLOPT_URL, 'https://myair-bdr.bmwgroup.com/air/faces/xhtml/Start.xhtml');
//   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//   curl_setopt($ch, CURLOPT_POST, 1);
//   curl_setopt($ch, CURLOPT_POSTFIELDS, "javax.faces.partial.ajax=true&javax.faces.source=j_idt308%3Avin-search-form%3Avin-search-button&javax.faces.partial.execute=j_idt308%3Avin-search-form&javax.faces.partial.render=j_idt308%3Avin-search-form+vehicleSelectionPopup%3Acontent&j_idt308%3Avin-search-form%3Avin-search-button=j_idt308%3Avin-search-form%3Avin-search-button&j_idt308%3Avin-search-form=j_idt308%3Avin-search-form&j_idt308%3Avin-search-form%3AvinSearchInputFieldNoKeyboard=FH12354&javax.faces.ViewState=-8233034842694662248%3A-4302667095917705564");
//   curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
//   curl_setopt($ch, CURLOPT_HEADER, 1);
  
//   $headers = array();
//   $headers[] = 'Connection: keep-alive';
//   $headers[] = 'Accept: application/xml, text/xml, */*; q=0.01';
//   $headers[] = 'X-Requested-With: XMLHttpRequest';
//   $headers[] = 'Faces-Request: partial/ajax';
//   $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';
//   $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
//   $headers[] = 'Origin: https://myair-bdr.bmwgroup.com';
//   $headers[] = 'Sec-Fetch-Site: same-origin';
//   $headers[] = 'Sec-Fetch-Mode: cors';
//   $headers[] = 'Sec-Fetch-Dest: empty';
//   $headers[] = 'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
//   $headers[] = 'Cookie: AIR_PERSISTENT=eyJ2IjoxLCJsIjoicnVfUlUiLCJnIjpmYWxzZX0=; AIR_TRANSIENT=eyJ2IjoxLCJsIjoicnVfUlUiLCJkYyI6bnVsbCwidmkiOm51bGwsInAiOm51bGwsInZrIjpmYWxzZSwibiI6IlNUQVJUUEFHRSJ9; JSESSIONID=d04b55e3fec3bb90f53d8874d76d.0; s_fid=1E00AD0E8D30C30F-0C7D74FAA448AED9; origin=Internet; SM_UID=b2iu10021694; SM_USER=riverdale@inbox.ru; SMSESSION=+cXC4MndAAxUNzIp+wYYykw9ozI+cs3YrFbt6EDRvTYu7lD3/Y2XUb9FI+fK5xu0gBFJkIsxih3/HXbNiMbg1dAX71d4z8O6b2KH3A2f0vmMGczrRRcNQEPbEjCHyi8b6ZiC2l+o4Bi1KinyJrgcDRd2wxWHrRWfu/xKBbo7MKkAA8d1X7FHaRqR8fqNuHs5VmflYsiZBzHA/TI2EiE1mKguvzeCxltc/fzXIYE18lm+g1Oa/OuHtgK5BHFDLErz2gQYIz4vnaryB8sAT+vJ1Oi/QkRsarJtdqi3obpJ2g/gsQlgR8PJjnixbWAts+wR2do6gm5D2TasEQ25uNf1xRWRUTnz7tVwCNvc5SvH7dz9sM8MO85Bhjy+Q/odXPOLwnaXPV7YN6tzfPrSCGzSTTt9fxYNuOExNh0Xbsu/kLpB63449RGTMIvxceuXRbQ5upkFmiAakGJwx2pvwcsZsCILvMu3aab9AkPNX/+7WQDrBy9vlqYN8Ppbp8j9+EKwqYWZn/2afAbkyLVUoSpCyR88nttjkcN1Y4JNS0vROAFKxWM7/dLmMkzSCdv4kAF8FKSsyeYo8yndK9O6e7CbCKdYWY7V8Geym5DfUJlt/XKh9t826jHieMQnW5Uy7euRwL9AuzHFcXYrHCtiSkYlHce6p7Teb0OjvNJAm2ta3buZG6V/LacbcrzOewf1UE5IeYfmA5HrJ867/L5L7Kxe1ffAvntCQjji4d5QNnU39zxb4JtiBjLE28Ikq7w2zRfU1KACqT7edpWsGa/C7/8UeR7LsQpcvM80as533aTrv0sHKNlgb1jPOIBZ4BsUKPOcvGqxdhCwm/N1dpTz3NlLxA4gMz3r5HFRTh1a3IkNdBFQBcuwXM7NHJiqROq6o5LZHPWT2dFrOExzhAc2eobq9dYO/uRRzVpuWiH6Mg77U0wbDM7ni+c3wz6vr4ue7vsC6USb/TZHyh0uIKHaKH1GCAPgn00uHGIe5wuCtMQLtXRQlur5nlfMkCw/ysoOQzPPehFXfFVheKZhVDlJDvXRP3B4QBEK/opsHMpCD+o1Icgl8gA3q32ow1A7BYkb8CtnTjoUN/Xust6e4+4MKvznG5QCQbvA+EUQKAdF7LLHJmUGA8VCZ0If2hmfeb+azOL8L3HpHZF3/6C0E/JeKTW3DJG2FiVkYiAx';
//   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  
//   $result = curl_exec($ch);
//   if (curl_errno($ch)) {
//       echo 'Error:' . curl_error($ch);
//   }
//   curl_close($ch);

// }

?>