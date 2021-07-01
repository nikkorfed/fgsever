<?

include_once 'libraries/simple_html_dom.php';

$requestCommon = file_get_html('https://www.google.ru/maps/preview/place?authuser=0&hl=ru&gl=ru&pb=!1m17!1s0x46b5375d4adbbe85%3A0x5e5f8adc7e5d12a2!3m12!1m3!1d69903.48144971453!2d60.5913088!3d56.803328!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m2!3d55.88934!4d37.5715591!12m4!2m3!1i360!2i120!4i8!13m57!2m2!1i203!2i100!3m2!2i4!5b1!6m6!1m2!1i86!2i86!1m2!1i408!2i240!7m42!1m3!1e1!2b0!3e3!1m3!1e2!2b1!3e2!1m3!1e2!2b0!3e3!1m3!1e3!2b0!3e3!1m3!1e8!2b0!3e3!1m3!1e3!2b1!3e2!1m3!1e9!2b1!3e2!1m3!1e10!2b0!3e3!1m3!1e10!2b1!3e2!1m3!1e10!2b0!3e4!2b1!4b1!9b0!14m2!1sqCRyXvanD4mkmwX4gI34Bg!7e81!15m45!1m12!13m6!2b1!3b1!4b1!6i1!8b1!9b1!18m4!3b1!4b1!5b1!6b1!2b1!5m5!2b1!3b1!5b1!6b1!7b1!10m1!8e3!14m1!3b1!17b1!20m2!1e3!1e6!24b1!25b1!26b1!30m1!2b1!43b1!52b1!55b1!56m2!1b1!3b1!65m5!3m4!1m3!1m2!1i224!2i298!21m28!1m6!1m2!1i0!2i0!2m2!1i458!2i768!1m6!1m2!1i974!2i0!2m2!1i1024!2i768!1m6!1m2!1i0!2i0!2m2!1i1024!2i20!1m6!1m2!1i0!2i748!2m2!1i1024!2i768!22m1!1e81!29m0!30m1!3b1&q=fgsever');

$requestFirst = file_get_html('https://www.google.ru/maps/preview/review/listentitiesreviews?authuser=0&hl=ru&gl=ru&pb=!1m2!1y5095039427266985605!2y6800306641970205346!2m2!1i0!2i10!3e2!4m5!3b1!4b1!5b1!6b1!7b1!5m2!1s2iVyXuvfCoHjmwXfoomICg!7e81');

$requestSecond = file_get_html('https://www.google.ru/maps/preview/review/listentitiesreviews?authuser=0&hl=ru&gl=ru&pb=!1m2!1y5095039427266985605!2y6800306641970205346!2m2!1i10!2i10!3e2!4m5!3b1!4b1!5b1!6b1!7b1!5m2!1s2iVyXuvfCoHjmwXfoomICg!7e81');

$requestThird = file_get_html('https://www.google.ru/maps/preview/review/listentitiesreviews?authuser=0&hl=ru&gl=ru&pb=!1m2!1y5095039427266985605!2y6800306641970205346!2m2!1i20!2i10!3e2!4m5!3b1!4b1!5b1!6b1!7b1!5m2!1s2iVyXuvfCoHjmwXfoomICg!7e81');

$common = json_decode(substr($requestCommon, strpos($requestCommon, '[')), true);
$quantity = addGoogleSuffix($common[6][4][8]);

$rating = round((float)$common[6][4][7], 1);
if (strpos((string)$rating, '.') === false) $rating = $rating . '.0';
$stars = round($rating * 2) * 10;
$url = 'https://www.google.com/maps/place/FG-Sever/@55.88934,37.5693704,17z/data=!3m1!4b1!4m5!3m4!1s0x46b5375d4adbbe85:0x5e5f8adc7e5d12a2!8m2!3d55.88934!4d37.5715591';
$addReviewUrl = 'https://www.google.ru/search?newwindow=1&client=safari&sxsrf=ALeKk01ze3OZlKNv8YFgaVH9oCuTgOEXeA%3A1584528225048&source=hp&ei=YftxXs0Wx4hpiYKBQA&q=fgsever&oq=fgsever&gs_l=psy-ab.3..35i39j0i10i30.999.1753..3144...1.0..0.104.679.5j2......0....1..gws-wiz.......35i39i19j0i131j0j0i10i1j0i1j0i10.tmW4_r_jmj0&ved=0ahUKEwiNy_PP66PoAhVHRBoKHQlBAAgQ4dUDCAk&uact=5#lrd=0x46b5375d4adbbe85:0x5e5f8adc7e5d12a2,3,,,';

$reviews = array_merge(
  json_decode(substr($requestFirst, strpos($requestFirst, '[')), true)[2],
  json_decode(substr($requestSecond, strpos($requestSecond, '[')), true)[2],
  json_decode(substr($requestThird, strpos($requestThird, '[')), true)[2]
);

$data = [
  'url' => $url,
  'quantity' => $quantity,
  'stars' => $stars,
  'rating' => $rating,
  'addReviewUrl' => $addReviewUrl,
  'reviews' => []
];

foreach ($reviews as $element) {

  $user = $element[0][1];
  $image = $element[0][2];
  $date = $element[1];
  $text = $element[3];
  if (empty($text)) continue;

  $data['reviews'][] = [
    'user' => $user,
    'image' => $image,
    'date' => $date,
    'text' => $text,
  ];

}

if (!$updateData) {
	header('content-type: application/json; charset=UTF-8');
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else echo '<p>Отзывы с Google успешно обновлены!</p>';

file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/reviews-google.json', json_encode($data, JSON_UNESCAPED_UNICODE));

// Вспомогательные функции

function addGoogleSuffix ($quantity) {

  $quantity = (int)$quantity; 

  if ($quantity == 11 || $quantity == 12) return $quantity . ' отзывов';

  switch ($quantity % 10) {
    case 1:
      $quantity = $quantity . ' отзыв';
      break;
    case 2:
    case 3:
    case 4:
      $quantity = $quantity . ' отзыва';
      break;
    case 5:
    case 6:
    case 7:
    case 8:
    case 9:
    case 0:
      $quantity = $quantity . ' отзывов';
      break;
  };

  return $quantity;

}

?>
