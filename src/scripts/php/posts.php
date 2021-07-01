<?

include_once 'libraries/simple_html_dom.php';

$url = 'https://www.drive2.ru/o/FGSEVER/blog';

$html = file_get_html($url);
$data = [
  'url' => $url,
  'posts' => []
];

foreach ($html->find('.c-block[data-id]') as $element) {

  $id = $element->getAttribute('data-id');
  $link = 'https://drive2.ru/o/b/' . $id;
  $image = $element->find('.c-preview-pic img', 0)->src;
  $title = $element->find('.c-post-preview__title a', 0)->plaintext;
  $likes = trim($element->find('.c-like', 0)->plaintext); if ($likes == '') $likes = 0;
  $comments = trim($element->find('.c-comments-counter', 0)->plaintext); if ($comments == '') $comments = 0;
  $date = trim($element->find('.c-author__date', 0)->plaintext);

  $data['posts'][$id] = [
    'link' => $link,
    'image' => $image,
    'title' => $title,
    'likes' => $likes,
    'comments' => $comments,
    'date' => $date,
  ];

}

// Считаем общее количество записей в профиле
$numberOfPages = (int)array_pop($html->find('.c-pager__page'))->plaintext;
$numberOfPostsYet = ($numberOfPages - 1) * 20;
$lastPageLink = 'https://drive2.ru' . array_pop($html->find('.c-pager__page'))->href;
$lastPageNumberOfPosts = (int)count(file_get_html($lastPageLink)->find('.c-block[data-id]'));
$totalNumberOfPosts = $numberOfPostsYet + $lastPageNumberOfPosts;
$data['numberOfPosts'] = addBlogDrive2Suffix($totalNumberOfPosts);

// header('content-type: application/json; charset=UTF-8');
// echo json_encode($data, JSON_UNESCAPED_UNICODE);
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/posts.json', json_encode($data, JSON_UNESCAPED_UNICODE));

// Основные и воспомогательные функции

function addBlogDrive2Suffix ($numberOfPosts) {

  switch ((int)$numberOfPosts % 10) {
    case 1:
      $numberOfPosts = $numberOfPosts . ' запись';
      break;
    case 2:
    case 3:
    case 4:
      $numberOfPosts = $numberOfPosts . ' записи';
      break;
    case 5:
    case 6:
    case 7:
    case 8:
    case 9:
    case 0:
      $numberOfPosts = $numberOfPosts . ' записей';
      break;
  };

  return $numberOfPosts;

}

?>
