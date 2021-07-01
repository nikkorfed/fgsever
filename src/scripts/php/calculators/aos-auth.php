<?

include_once '../libraries/simple_html_dom.php';

// Первоначальный заход на портал AOS и поиск ссылки для авторизации

// $url = 'https://aos.bmwgroup.com/group/oss/start';

// $ch = curl_init();

// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_HEADER, 1);

// $html = curl_exec($ch);
// curl_close($ch);
// // echo $html;

// preg_match('/Location: (.+)/', $html, $matches);
// // echo $url = $matches[1];

// Запрос на адрес для первичной авторизации
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://aos.bmwgroup.com/login/login_www.fcc?TYPE=33619969&REALMOID=06-19247b09-132c-4935-b7ea-99d11699c8f0&GUID=&SMAUTHREASON=0&METHOD=GET&SMAGENTNAME=qRUXAdb5oDAyTkz72zYEcO7nYIMKL3e2UEqDqz3ykn7czo61oiYoZObpQrmw3EZl&TARGET=SMhttps%3a%2f%2faos%2ebmwgroup%2ecom%2fgroup%2foss%2fstart");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "target=https%3A%2F%2Faos.bmwgroup.com%2Fgroup%2Foss%2Fstart&smauthreason=0&smagentname=qRUXAdb5oDAyTkz72zYEcO7nYIMKL3e2UEqDqz3ykn7czo61oiYoZObpQrmw3EZl&smtryno=&USER=komandir2c3%40yandex.ru&PASSWORD=comandF30");
curl_setopt($ch, CURLOPT_COOKIEJAR, 'aos-bmwgroup.cookie');
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$headers = array();
$headers[] = 'Connection: keep-alive';
$headers[] = 'Cache-Control: max-age=0';
$headers[] = 'Upgrade-Insecure-Requests: 1';
$headers[] = 'Origin: https://aos.bmwgroup.com';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
$headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.106 Safari/537.36';
$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
$headers[] = 'Sec-Fetch-Site: same-origin';
$headers[] = 'Sec-Fetch-Mode: navigate';
$headers[] = 'Sec-Fetch-User: ?1';
$headers[] = 'Sec-Fetch-Dest: document';
$headers[] = 'Referer: https://aos.bmwgroup.com/login/login_www.fcc?TYPE=33619969&REALMOID=06-19247b09-132c-4935-b7ea-99d11699c8f0&GUID=&SMAUTHREASON=0&METHOD=GET&SMAGENTNAME=qRUXAdb5oDAyTkz72zYEcO7nYIMKL3e2UEqDqz3ykn7czo61oiYoZObpQrmw3EZl&TARGET=SMhttps%3a%2f%2faos%2ebmwgroup%2ecom%2fgroup%2foss%2fstart';
$headers[] = 'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
$headers[] = 'Cookie: COOKIE_SUPPORT=true; CookiesConfirmed=true; smisalreadylogin=1; s_fid=1E00AD0E8D30C30F-0C7D74FAA448AED9; LFR_SESSION_STATE_1389190=1592837787713; SMSESSION=LOGGEDOFF; JSESSIONID=c8833193d1c56576b9f570a6c1a9.1; LFR_SESSION_STATE_20120=1592837817765';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

echo $result = curl_exec($ch);
curl_close($ch);


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://aos.bmwgroup.com/group/oss/start');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$headers = array();
$headers[] = 'Connection: keep-alive';
$headers[] = 'Content-Length: 208';
$headers[] = 'Cache-Control: max-age=0';
$headers[] = 'Upgrade-Insecure-Requests: 1';
$headers[] = 'Origin: https://aos.bmwgroup.com';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
$headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.106 Safari/537.36';
$headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
$headers[] = 'Sec-Fetch-Site: same-origin';
$headers[] = 'Sec-Fetch-Mode: navigate';
$headers[] = 'Sec-Fetch-User: ?1';
$headers[] = 'Sec-Fetch-Dest: document';
$headers[] = 'Referer: https://aos.bmwgroup.com/login/login_www.fcc?TYPE=33619969&REALMOID=06-19247b09-132c-4935-b7ea-99d11699c8f0&GUID=&SMAUTHREASON=0&METHOD=GET&SMAGENTNAME=qRUXAdb5oDAyTkz72zYEcO7nYIMKL3e2UEqDqz3ykn7czo61oiYoZObpQrmw3EZl&TARGET=SMhttps%3a%2f%2faos%2ebmwgroup%2ecom%2fgroup%2foss%2fstart';
$headers[] = 'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
$headers[] = 'Cookie: COOKIE_SUPPORT=true; CookiesConfirmed=true; smisalreadylogin=1; s_fid=1E00AD0E8D30C30F-0C7D74FAA448AED9; LFR_SESSION_STATE_1389190=1592838587852; SMSESSION=LOGGEDOFF; JSESSIONID=c9512f728fb91a8c2d2d0b9f98dd.1; LFR_SESSION_STATE_20120=1592838657584';
curl_setopt($ch, CURLOPT_COOKIEFILE, 'aos-bmwgroup.cookie');
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

echo $result = curl_exec($ch);
curl_close($ch);


// $ch = curl_init();

// curl_setopt($ch, CURLOPT_URL, "https://aos.bmwgroup.com/login/login_www.fcc?TYPE=33619969&REALMOID=06-19247b09-132c-4935-b7ea-99d11699c8f0&GUID=&SMAUTHREASON=0&METHOD=GET&SMAGENTNAME=SMGs9iN2ssBvYK6%2fPxrYpYSqXEGfm9srDGng8OK9HrFzP8c6zEtWXqg7sLUmbu4n52&TARGET=SMhttps%3a%2f%2faos%2ebmwgroup%2ecom%2fgroup%2foss%2fstart");
// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_HEADER, 1);
// curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_POSTFIELDS, "target=https%3A%2F%2Faos.bmwgroup.com%2Fgroup%2Foss%2Fstart&smauthreason=0&smagentname=Gs9iN2ssBvYK6%2FPxrYpYSqXEGfm9srDGng8OK9HrFzP8c6zEtWXqg7sLUmbu4n52&smtryno=&USER=komandir2c3%40yandex.ru&PASSWORD=comandF30");
// curl_setopt($ch, CURLOPT_COOKIEJAR, 'aos-bmwgroup.cookie');
// curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

// $headers = array();
// $headers[] = 'Connection: keep-alive';
// $headers[] = 'Cache-Control: max-age=0';
// $headers[] = 'Upgrade-Insecure-Requests: 1';
// $headers[] = 'Origin: https://aos.bmwgroup.com';
// $headers[] = 'Content-Type: application/x-www-form-urlencoded';
// $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36';
// $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
// $headers[] = 'Sec-Fetch-Site: same-origin';
// $headers[] = 'Sec-Fetch-Mode: navigate';
// $headers[] = 'Sec-Fetch-User: ?1';
// $headers[] = 'Sec-Fetch-Dest: document';
// $headers[] = 'Referer: https://aos.bmwgroup.com/login/login_www.fcc?TYPE=33619969&REALMOID=06-19247b09-132c-4935-b7ea-99d11699c8f0&GUID=&SMAUTHREASON=0&METHOD=GET&SMAGENTNAME=SMGs9iN2ssBvYK6%2fPxrYpYSqXEGfm9srDGng8OK9HrFzP8c6zEtWXqg7sLUmbu4n52&TARGET=SMhttps%3a%2f%2faos%2ebmwgroup%2ecom%2fgroup%2foss%2fstart';
// $headers[] = 'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
// $headers[] = 'Cookie: COOKIE_SUPPORT=true; CookiesConfirmed=true; smisalreadylogin=1; s_fid=1E00AD0E8D30C30F-0C7D74FAA448AED9; origin=Internet; SM_UID=b2iu10021694; SM_USER=riverdale@inbox.ru; LFR_SESSION_STATE_87542=1591629098305; LFR_SESSION_STATE_1389190=1591790966300; SMSESSION=LOGGEDOFF; JSESSIONID=e22b2fc2e7990a0c393f254a9a4b.1; LFR_SESSION_STATE_20120=1591790974437';
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// $result = curl_exec($ch);
// curl_close($ch);
// // echo $result;

// $ch = curl_init();

// curl_setopt($ch, CURLOPT_URL, 'https://aos.bmwgroup.com/group/oss/start');
// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_POST, 1);
// curl_setopt($ch, CURLOPT_POSTFIELDS, "target=https%3A%2F%2Faos.bmwgroup.com%2Fgroup%2Foss%2Fstart&smauthreason=0&smagentname=2r0C4EYeARQckIWZdfssQVA8t%2ByARvuEnWFhXleuC2MRzTGTd93NM1qfaVfHuYbH&smtryno=&USER=riverdale%40inbox.ru&PASSWORD=comandG30");
// curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
// curl_setopt($ch, CURLOPT_COOKIEJAR, 'aos-bmwgroup.cookie');

// curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

// $headers = array();
// $headers[] = 'Connection: keep-alive';
// $headers[] = 'Cache-Control: max-age=0';
// $headers[] = 'Upgrade-Insecure-Requests: 1';
// $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36';
// $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9';
// $headers[] = 'Sec-Fetch-Site: same-origin';
// $headers[] = 'Sec-Fetch-Mode: navigate';
// $headers[] = 'Sec-Fetch-User: ?1';
// $headers[] = 'Sec-Fetch-Dest: document';
// $headers[] = 'Referer: https://aos.bmwgroup.com/login/login_www.fcc?TYPE=33619969&REALMOID=06-19247b09-132c-4935-b7ea-99d11699c8f0&GUID=&SMAUTHREASON=0&METHOD=GET&SMAGENTNAME=SMMl4nUsJoGDeFQoBXrMZRrrb7oMLsnY09j12jXuTL2rhO3j4kU1vbelRrG0X%2fRzNy&TARGET=SMhttps%3a%2f%2faos%2ebmwgroup%2ecom%2fgroup%2foss%2fstart';
// $headers[] = 'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
// $headers[] = 'Cookie: smisalreadylogin=1; COOKIE_SUPPORT=true; LFR_SESSION_STATE_87542=1590923356443; JSESSIONID=a6bfec81473cfa6b98b5f4fa65f6.1; LFR_SESSION_STATE_20120=1590923362954; SMSESSION=hzZ36rPAp+3wVxdxS+fxagltGGoR5KAvXP98HqnzjAfvuuNIz5zkYkGR1+SrorQl1PNSOxuKCmU3uTm4eDSb3x1hX0uqOgOQO8QibO6D37UrKFaJG93TH/qXMb0zIODhLl+KXPfoLoXgHpVWh85b76HUPZJSEwmFglM4eabep6rZniluh/uwKS6FsZavLzCVSHCL/VSOf2NdTmd97O2zxkdPOAeVRoFH8kh2SkfRSCX+QDO0ynDGN6g3LsrQp310hGLyGlK7jSBNG0tGo05Q2D7RbkFGASjKq2K06ccilDsl0EYdZ7xG1VJcD1Sy31xpdB8RsCd42Fvaafs6rANlXGcQVO1WWvKqhNrg/BMHhm99Wjasbfou9HgRIDMWAgTyz5w9Ic0jA5VozktSKqf9z1Ll7xtriASn2vXjOGSLcO7eoNgY/e2ZSy8E3IIcHOezcIj3GYJ/t1QWqZfAmxMoHDVjoZjaHEJQw6Bg5xfJhYKq6Kr7MDaGTRxjDVRdyNdGoeqGZaOwgFWGMT75gInhs+E0iijULQm1+coLP8WPuOLPP7coH6BrKw1tyMNHRcNbGGf/awkJKwz3W1XZhbKQqr51hK6p3Wx9H3tkzzntvLqi2fUfKBd5xeJrm0Kqs96Je8D5Ggrc5a1IZ2zVYspWoYOAATJuf9XrXf4An7BJDFG+YmR4geyFAmdx0Sm8LxbU+sa2DZPTFJhCF9haX58fWUJE9kvLMobLXWYQjFCqKTJ+o9gwH6zBW9ccpMbBspFCaOU3O0mnke1in4K1g4P003zdCTjF7M6gD4pnhRMKbqnj6oshewiH+bukVlX9pojqoKx71V2cItOdJIQyfgYsg+Qu/FNlWFAGHKsEgUEwRYGHIC3Moi61zqx9kWulnVIB4dYTZDc3zqGkmDdaMmrnDQMQ+ADT7OiJ+MJL3cUsmVAKCZGJzdklz83PI6ywKdEe0MTPqmE9+OGA8DIbOx46SwYm8NpajEXVSXqhn8e6SrMEhB/Si6TIDSl0/HYSdin87A74LfAfpol9BzxpRd+cMvNqDUEa8bpJvMAuBVPka8hqgRIMBP0Do0325zs3/Q5/sfaUQbqgPWS4pNrHI50ogWJ5XzMlmpxq8aqefiNOxsnR2wVhnxdMe1Q0DwNkpq2Al2wnc358vHP6INL9sWPnJTCtxI793yVD';
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// curl_setopt($ch, CURLOPT_HEADER, 1);

// $result = curl_exec($ch);
// curl_close ($ch);
// // echo $result;

// Запрос на страницу приложения Air для основной авторизации

// // $url = 'https://aos.bmwgroup.com/group/oss/apps/air';
// $url = 'https://onl-osmc-b2i.bmwgroup.com/osmc/b2i/air/start.html?navigation=true&langLong=ru-RU';

// $ch = curl_init();

// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_COOKIEFILE, 'aos-bmwgroup.cookie');
// curl_setopt($ch, CURLOPT_HEADER, 1);

// $result = curl_exec($ch);
// curl_close($ch);
// // echo $result;

// $html = str_get_html($result);
// $startLink = $html->find('#startlink', 0)->href;
// // echo $startLink;

// // Следующий запрос

// $ch = curl_init();

// curl_setopt($ch, CURLOPT_URL, $startLink);
// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_COOKIEFILE, 'aos-bmwgroup.cookie');
// curl_setopt($ch, CURLOPT_COOKIEJAR, 'aos-bmwgroup.cookie');

// curl_setopt($ch, CURLOPT_HEADER, 1);

// $result = curl_exec($ch);
// curl_close($ch);
// // echo $result;

?>