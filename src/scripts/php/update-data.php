<?

if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/data')) {
  mkdir($_SERVER['DOCUMENT_ROOT'] . '/data');
}

$updateData = true;

include('reviews-drive2.php');
include('reviews-yandex.php');
include('reviews-google.php');
include('parts-prices.php');
include('maps-version.php');
include('drive2-car.php');
include('posts.php');
include('videos.php');

?>