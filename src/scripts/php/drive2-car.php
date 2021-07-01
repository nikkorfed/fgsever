<?

include_once 'libraries/phpQuery.php';

$url = 'https://www.drive2.ru/r/bmw/5_series/495453684444954989/';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$html = curl_exec($ch);
curl_close($ch);

$html = phpQuery::newDocument($html);

$image = $html->find('.c-slideshow__pic:first .c-slideshow__hd img')->attr('src');
$title = $html->find('.c-car-info__caption')->text();
$owner = $html->find('.c-username:first')->text();
$date = $html->find('.c-car-desc__modify-date span:first')->text();
$likes = (int)$html->find('.c-like__counter:first')->text();
$comments = (int)$html->find('.c-comments-counter:first')->text();
$drive = (int)$html->find('.c-car-info__nums .c-round-num-block:first')->text();
$followers = (int)$html->find('.c-car-info__nums .c-round-num-block:eq(1)')->text();
$posts = addPostsSuffix((int)$html->find('.c-car-info__nums .c-round-num-block:eq(2)')->text());

$data = [
	'url' => $url,
	'image' => $image,
	'title' => $title,
	'owner' => $owner,
	'date' => $date,
	'likes' => $likes,
	'comments' => $comments,
	'drive' => $drive,
	'followers' => $followers,
	'posts' => $posts
];

// header('content-type: application/json; charset=UTF-8');
// echo json_encode($data, JSON_UNESCAPED_UNICODE);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/drive2-car.json', json_encode($data, JSON_UNESCAPED_UNICODE));

// Воспомогательные фунции

function addPostsSuffix ($reviewsNumber) {

	switch ($reviewsNumber % 10) {
		case 1:
			$reviewsNumber = (string)$reviewsNumber . ' <span>запись</span>';
			break;
		case 2:
		case 3:
		case 4:
			$reviewsNumber = (string)$reviewsNumber . ' <span>записи</span>';
			break;
		case 5:
		case 6:
		case 7:
		case 8:
		case 9:
		case 0:
			$reviewsNumber = (string)$reviewsNumber . ' <span>записей</span>';
			break;
	}

	return $reviewsNumber;

}

?>
