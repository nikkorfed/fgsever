<?

$api_url = 'https://maps.googleapis.com/maps/api/place/details';
$api_key = 'AIzaSyBYGNsDWBEmW0k-CCwBTTrKz2s3C6SSIKA';

$place_id = 'ChIJhb7bSl03tUYRohJdftyKX14';

$details = file_get_contents("$api_url/json?key=$api_key&place_id=$place_id");
$details = json_decode($details, true);

$data = [
  'url' => $channel_url,
  'details' => $details,
];

// foreach ($videos as $video) {
//   $id = $video['id']['videoId'];
//   $url = "https://www.youtube.com/watch?v=$id";
//   $image = $video['snippet']['thumbnails']['high']['url'];
//   $title = $video['snippet']['title'];

//   $date = $video['snippet']['publishedAt'];
//   $date = format_date_2($date);
//   // $date = format_date($date);

//   $video = json_decode(file_get_contents("$api_url/videos?part=snippet,statistics&id=$id&key=$api_key"), true);
//   $views = $video['items'][0]['statistics']['viewCount'];

//   $views = format_views($views);

//   $data['videos'][] = [
//     'url' => $url,
//     'image' => $image,
//     'title' => $title,
//     'date' => $date,
//     'views' => $views
//   ];
// }

header('content-type: application/json; charset=UTF-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/videos.json', json_encode($data, JSON_UNESCAPED_UNICODE));

// Вспомогательные функции

function format_date($date) {
  $date = strtotime($date);
  $date = strftime_rus_videos('%e %B2 %Y', $date);
  return $date;
}

function format_date_2($date) {
  $date = strtotime($date);
  $currentDate = time();
  
  if ($currentDate - $date < 60 * 60 * 24) $date = format_hours(round(($currentDate - $date) / (60 * 60)));
  else if ($currentDate - $date < 60 * 60 * 24 * 7) $date = format_days(round(($currentDate - $date) / (60 * 60 * 24)));
  else if ($currentDate - $date < 60 * 60 * 24 * 31) $date = format_weeks(round(($currentDate - $date) / (60 * 60 * 24 * 7)));
  else if ($currentDate - $date < 60 * 60 * 24 * 31 * 12) $date = format_months(round(($currentDate - $date) / (60 * 60 * 24 * 31)));
  else $date = format_years(round(($currentDate - $date) / (60 * 60 * 24 * 31 * 12)));

  return $date;
}

function format_views($views) {

  if ($views > 1000000) $views = number_format($views/1000000, 1, ',', ' ') . ' млн просмотров';
  else if ($views > 1000) $views = number_format($views/1000, 1, ',', ' ') . ' тыс. просмотров';
  else $views = number_format($views, 0, ',', ' ') . ' просмотров';

  return $views;

}

function strftime_rus_videos($format, $date = false) {

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

function format_hours($number) {
  return format($number, 'час', 'часа', 'часов');
}

function format_days($number) {
  return format($number, 'день', 'дня', 'дней');
}

function format_weeks($number) {
  return format($number, 'неделю', 'недели', 'недель');
}

function format_months($number) {
  return format($number, 'месяц', 'месяца', 'месяцев');
}

function format_years($number) {
  return format($number, 'год', 'года', 'лет');
}

function format($number, $firstForm, $secondForm, $thirdForm) {

  if ($number % 100 == 11 || $number % 100 == 12 || $number % 100 == 13 || $number % 100 == 14) return "$number $thirdForm назад";

  switch ($number % 10) {
    case '1':
      return "$number $firstForm назад";
    case '2':
    case '3':
    case '4':
      return "$number $secondForm назад";
    case '5':
    case '6':
    case '7':
    case '8':
    case '9':
    case '10':
      return "$number $thirdForm назад";
  }

}

?>
