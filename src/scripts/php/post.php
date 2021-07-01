<?

include '../../scripts/php/libraries/phpQuery.php';

if (isset($_REQUEST['id'])) {

  $url = 'https://drive2.ru/o/b/' . $_REQUEST['id'] . '/';

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $html = curl_exec($ch);
  curl_close($ch);

  $html = phpQuery::newDocument($html);

  $title = $html->find('h1 span:last')->text();

  $date = $html->find('.c-post-meta span:first')->text();

  // $image = $html->find('meta[itemprop="image"]:nth-child(5)')->attr('content');
  $body = $html->find('.c-post__body [itemprop="articleBody"]')->contents();
  $likes = $html->find('.c-like__counter:first')->text();
  $comments = (int)$html->find('.c-comments-counter', 0)->text();

}

?>
