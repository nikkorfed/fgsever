<?

include_once 'libraries/phpQuery.php';

$businessId = '230081050482';

setlocale(LC_TIME, 'ru_RU.UTF-8');

$url = 'https://yandex.ru/maps/org/' . $businessId . '/reviews';
$html = phpQuery::newDocument(file_get_contents($url));

$stars = $html->find('.business-summary-rating .business-rating-badge-view__stars')->html();
$rating = $html->find('.business-summary-rating-badge-view__rating')->text();
$quantity = $html->find('.business-summary-rating .business-rating-amount-view')->text();
$addReviewUrl = 'https://yandex.ru/maps/org/' . $businessId . '/?add-review=true';

$reviews = $html->find('.business-reviews-card-view__reviews-container .business-reviews-card-view__review');

$data = [
	'url' => $url,
	'quantity' => $quantity,
	'stars' => $stars,
	'rating' => str_replace('Рейтинг ', '', str_replace(',', '.', $rating)),
	'addReviewUrl' => $addReviewUrl,
	'reviews' => []
];

foreach ($reviews as $element) {

	$user = pq($element)->find('.business-review-view__author span')->text();
	$image = pq($element)->find('.business-review-view__user-icon')->html();
	$image = str_replace(['<a', '</a>'], ['<div', '</div>'], $image);
	$url = pq($element)->find('.business-review-view__author a')->attr('href');
	$date = pq($element)->find('.business-review-view__date span')->text();
	$text = pq($element)->find('.business-review-view__body-text')->text();
	
	$data['reviews'][] = [
		'url' => $url,
		'user' => $user,
		'image' => $image,
		'date' => $date,
		'text' => $text,
	];
	
}

usort($data['reviews'], 'dateCompare');

if (!$updateData) {
	header('content-type: application/json; charset=UTF-8');
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else echo '<p>Отзывы с Яндекса успешно обновлены!</p>';

file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/reviews-yandex.json', json_encode($data, JSON_UNESCAPED_UNICODE));


// Вспомогательные функции

function strftime_rus($format, $date = false) {

	if (!$date) { $timestamp = time(); }
	else if (!is_numeric($date)) { $timestamp = strtotime($date); }
	else $timestamp = $date;

	if (strpos($format, '%B2') === false) return strftime($format, $timestamp);

	$month_number = date('n', $timestamp);

	switch ($month_number) {
		case 1: $rus = 'января'; break;
		case 2: $rus = 'февраля'; break;
		case 3: $rus = 'марта'; break;
		case 4: $rus = 'апреля'; break;
		case 5: $rus = 'мая'; break;
		case 6: $rus = 'июня'; break;
		case 7: $rus = 'июля'; break;
		case 8: $rus = 'августа'; break;
		case 9: $rus = 'сентября'; break;
		case 10: $rus = 'октября'; break;
		case 11: $rus = 'ноября'; break;
		case 12: $rus = 'декабря'; break;
	}

	$rusformat = str_replace('%B2', $rus, $format);
	return strftime($rusformat, $timestamp);

}

function dateCompare ($a, $b) {

	$from = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
	$to = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

	$a = str_replace($from, $to, $a);
	$b = str_replace($from, $to, $b);

	return strcmp(strtotime($b['date']), strtotime($a['date']));

}

?>
