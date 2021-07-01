<?

include_once 'libraries/simple_html_dom.php';
include_once 'libraries/phpQuery.php';

$data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/data/posts.json'), true);

echo
  '<?xml version="1.0" encoding="UTF-8"?>
    <rss xmlns:yandex="http://news.yandex.ru" xmlns:media="http://search.yahoo.com/mrss/" xmlns:turbo="http://turbo.yandex.ru" version="2.0">
      <channel>
        <title>Блог | Автосервис BMW «FGSEVER»</title>
        <link>https://fgsever.ru</link>
        <description>Новости и видео с интересными работами и проектами автосервиса «FGSEVER» в Москве</description>
        <language>ru</language>';

foreach ($data['posts'] as $id => $post) {

  $originalLink = 'https://drive2.ru/o/b/' . $id;
  $link = 'https://fgsever.ru/blog/id/?id=' . $id;
  $image = $post['image'];

  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $originalLink);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $html = curl_exec($ch);
  curl_close($ch);

  $html = phpQuery::newDocument($html);

  $title = $html->find('h1 span:last')->text();

  $date = $html->find('.c-post-meta span:first')->text();
  $replaceFrom = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря', 'в&nbsp;'];
  $replaceTo = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', ''];
  $date = str_replace($replaceFrom, $replaceTo, htmlentities($date));
  $date = date('r', strtotime($date));

  $body = $html->find('.c-post__body [itemprop="articleBody"]')->contents();
  $likes = $html->find('.c-like__counter:first')->text();
  $comments = (int)$html->find('.c-comments-counter', 0)->text();
  
  $content = '';
  foreach ($body as $element) {

    if (pq($element)->is('div.c-post__pic')) {
      $imageUrl = pq($element)->find('a')->attr('href');
      $imageMin = pq($element)->find('img')->attr('src');
      $desc = pq($element)->find('.c-post__desc')->text();

      if (empty($desc)) {
        $content .= '<img src="' . $imageMin . '">';
      } else {
        $content .=
          '<figure>
            <img src="' . $imageMin . '">
            <figcaption>' . $desc . '</figcaption>
          </figure>';
      }
    } else if (preg_replace('/\s/', '', pq($element)->html()) != '') {
      $content .= '<p>' . pq($element)->html() . '</p>';
    }
  }

  echo
    '<item turbo="true">
      <link>' . $link . '</link>
      <turbo:source></turbo:source>
      <turbo:topic>' . $title . '</turbo:topic>
      <pubDate>' . $date . '</pubDate>
      <category>Новость</category>
      <yandex:related></yandex:related>
      <turbo:content>
          <![CDATA[
            <header>
              <h1>' . $title . '</h1>
              <menu>
                <a href="https://fgsever.ru">Главная</a>
                <a href="https://fgsever.ru/services/">Услуги</a>
                <a href="https://fgsever.ru/blog/">Блог</a>
                <a href="https://fgsever.ru/about/">О нас</a>
                <a href="https://fgsever.ru/contacts/">Контакты</a>
              </menu>
            </header>
            ' . $content . '
          ]]>
      </turbo:content>
    </item>';
}

echo
  '</channel>
  </rss>';

?>
