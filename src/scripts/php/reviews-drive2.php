<?

include_once 'libraries/simple_html_dom.php';

$url = 'https://www.drive2.ru/o/FGSever/reviews';

$html = file_get_html($url);

$quantity = $html->find('.c-cp-feedbacks__filters .c-toggle .c-counter', 0)->plaintext;
$quantity = addDrive2Suffix($quantity);

$data = [
  'url' => $url,
  'quantity' => $quantity,
  'reviews' => []
];

foreach ($html->find('[data-role=comfeedback] .c-block') as $element) {

  $url = 'https://drive2.ru' . $element->find('.c-post-preview__title a', 0)->href;
  $positive = mb_stripos($element->find('.c-post-rating', 0)->plaintext, 'доволен') !== false ? true : false;
  $user = trim($element->find('.c-username', 0)->plaintext);
  $image = $element->find('.c-car-card__pic img', 0)->src;
  $car = $element->find('.c-car-title', 0)->plaintext;
  $date = trim($element->find('.c-author__date', 0)->plaintext);
  $carImage = $element->find('.c-preview-pic img', 0)->src;
  $title = trim($element->find('.c-post-preview__title', 0)->plaintext);
  $text = trim(str_replace('&nbsp;Читать дальше', '', $element->find('.c-post-preview__lead', 0)->plaintext));

  $data['reviews'][] = [
    'url' => $url,
    'positive' => $positive,
    'user' => $user,
    'image' => $image,
    'car' => $car,
    'date' => $date,
    'carImage' => $carImage,
    'title' => $title,
    'text' => $text,
  ];

}

if (!$updateData) {
	header('content-type: application/json; charset=UTF-8');
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else echo '<p>Отзывы с DRIVE2 успешно обновлены!</p>';

file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/reviews-drive2.json', json_encode($data, JSON_UNESCAPED_UNICODE));

// Вспомогательные функции

function addDrive2Suffix ($quantity) {

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
