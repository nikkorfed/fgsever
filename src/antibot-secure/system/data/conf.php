<?php
// большинство настроек, которые тут указаны - это рекомендуемые настройки.
// можно скопировать этот конфиг к себе, вместо конфига с англоязычными описаниями.

//error_reporting(E_ALL); // 0 or E_ALL
//ini_set('display_errors', 'on'); // off or on
//ini_set('error_log', __DIR__.'/errorlog.txt');

// отключить антибот: 1 (если нужно временно отключить антибот, не удаляя код).
$ab_config['disable'] = 0;

// логин для доступа в админку (если cloud версия, то email от antibot.cloud).
$ab_config['email'] = 'nikkorfed@yandex.ru';

// пароль для доступа в админку (если cloud версия, то пароль от antibot.cloud).
$ab_config['pass'] = 'iuJHj3cc4Ww5RY7ha';

// соль, изменить для сброса cookie всем посетителям.
$ab_config['salt'] = 'xyz';

// для подключения облачной проверки - это значение должно быть пустым: $ab_config['check_url'] = '';
$ab_config['check_url'] = '';

// если сайт на Bitrix CMS и появляется зацикленный редирект или не до конца загружается заглушка, то поставьте = 1;
$ab_config['is_bitrix'] = 0;

// задержка перед началом проверки (в секундах, чем больше - тем лучше защита).
$ab_config['timer'] = 2;

// кол-во хитов юзера на сайте, после чего выдавать по новой проверку антибота.
$ab_config['hits_per_user'] = 5;

// запретить на странице проверки доступ посетителям с пустым реферером.
// 0 - не запрещать доступ, 1 - запретить доступ.
$ab_config['stop_noreferer'] = 0;

// запретить на странице проверки доступ посетителям с пустым HTTP_ACCEPT_LANGUAGE.
// после того, как отловите всех нужных вам нестандартных белых ботов - поставьте 1.
// 0 - не запрещать доступ, 1 - запретить доступ.
$ab_config['stop_nolang'] = 0;

// отключить возможность зайти на сайт по нажатию кнопки (если не прошел автоматическую проверку).
// 0 - не отключать кнопку, 1 - отключить кнопку.
$ab_config['input_button'] = 0;

// строго задать язык текстов страницы проверки (языки из папки: antibot/lang/tpl/): 
// $ab_config['tpl_lang'] = 'ru'; 
// если отображать тексты страницы проверки на языке браузера посетителя, то оставьте поле пустым.
$ab_config['tpl_lang'] = '';

// для посетителей, которые не прошли автоматически облачную проверку.
// включить множество кнопок разных цветов, вместо одной кнопки входа на сайт:
// (пользователь должен будет сделать выбор правильного цвета)
// 1 - включить, 0 - использовать одну кнопку.
$ab_config['many_buttons'] = 1;
// при неправильном разгадывании цвета и клике не на ту кнопку.
// 1 - заносить ip в черный список, 0 - давать бесконечное число шансов для клика по правильной кнопке.
$ab_config['one_chance'] = 0;

// включить reCAPTCHA v3 фильтр (при облачной проверке). 0 - выключить, 1 - включить.
// посетители из Китая не пройдут, google.com у них не доступен.
$ab_config['re_check'] = 1;

// включить Hosting фильтр (при облачной проверке). 0 - выключить, 1 - включить.
// блокировка автоматического прохода пользователей с ip, принадлежащих хостингам и TOR.
$ab_config['ho_check'] = 1;

// если сайт работает на https c поддержкой http/2.0
// 1 - пускать только юзеров, поддерживающих http2.
// 0 - пускать всех прошедших проверку cookie.
$ab_config['http2only'] = 0;

// сохранять в белый список ip хороших ботов по маске /24 для ipv4 и по маске /64 для ipv6.
// 1 - сокращенная запись (рекомендуется), 0 - полный ip.
$ab_config['short_mask'] = 1;

// если зашел фейкбот (с юзерагентом как у хорошего бота):
// 1 - остановить выполнение скрипта (рекомендуется)
// 0 - разрешить пройти проверку как человеку.
$ab_config['stop_fake'] = 1; 

// передавать на сайт гет переменную utm_referrer с реальным реферером, чтобы не ставить яндекс метрику в заглушку антибота.
// проверьте, чтобы на страницах был мета тег rel="canonical", чтобы исключить дубли страниц после добавления utm меток.
// 1 - включить, 0 - отключить.
$ab_config['utm_referrer'] = 1; 

// ---------------------------------------------------------------------

// ЛОГИ (1 - включить лог, 0 - не вести лог).

// лог посетителей попавших на страницу проверки.
$ab_config['antibot_log_tests'] = 1;

// лог посетителей прошедших страницу проверки.
$ab_config['antibot_log_users'] = 1;

// лог фейковых ботов (с юзерагентом как у хорошего бота, но с не правильным PTR).
$ab_config['antibot_log_fakes'] = 1;

// ---------------------------------------------------------------------

// счетчики статистики в мемкешед. 1 - включить, 0 - отключить.
$ab_config['memcached_counter'] = 0;

$ab_config['memcached_host'] = 'localhost';
$ab_config['memcached_port'] = 11211;

// расширенная статистика по ботам: yandex, google, mailru, bing. 1 - включить, 0 - отключить:
$ab_config['extended_bot_stat'] = 0;

// ---------------------------------------------------------------------

// код ответа сервера для заблокированных в правилах пользователей. доступные варианты:
// варианты: 200, 400, 403, 404, 410, 451, 500, 502, 503, 504.
// описание статусов: https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
$ab_config['header_error_code'] = 403;

// контент показываемый заблокированным пользователям:
// 0 - системное сообщение в зависимости от кода.
// 1 - свой контент из antibot/data/error.txt
$ab_config['custom_error_page'] = 0;

// разрешать доступ только посетителям с указанных рефереров. проверяется только на заглушке.
// 1 - пускать только по белому списку рефереров.
// 0 - не проверять реферер и пускать на заглушку всех.
// с реферером не из белого списка посетитель будет видеть страницу ошибки.
$ab_config['check_ref_traf'] = 0;

// эти слова искать в хост реферера для разрешения доступа к заглушке антибота:
$ab_config['allow_ref_only'] = array('yandex', 'google');

// если посетитель попал под какое либо из правил блокировки и получил страницу блокировки, 
// то также ему устанавливается cookie с именем stop на 10 дней.
// 1 - блокировать этих посетителей в дальнейшем, даже если они больше не подпадают под правила блокировки.
// 0 - не блокировать.
$ab_config['block_stop_cookie'] = 0;

// ---------------------------------------------------------------------

// Список белых ботов в формате: сигнатура (признак) из User-Agent => массив PTR записей:
// если PTR запись пустая или неинформативная, то указывать array('.');
// тогда все боты с этим юзерагентом будут пропускаться как белые боты,
// но ip в базу белых ботов добавляться не будут.
// если бот ходит из малого количества подсетей, то можно указать часть ip адреса.

$ab_se['Googlebot'] = array('.googlebot.com'); // GoogleBot (main indexer)
$ab_se['yandex.com'] = array('yandex.ru', 'yandex.net', 'yandex.com'); // All Yandex bots
$ab_se['Mail.RU_Bot'] = array('mail.ru', 'smailru.net'); // All Bots Mail.RU Indexers
$ab_se['bingbot'] = array('search.msn.com'); // Bing.com indexer
//$ab_se['Baiduspider'] = array('crawl.baidu.com'); // Baidu indexer
$ab_se['msnbot'] = array('search.msn.com'); // Additional Indexer Bing.com
$ab_se['Google-Site-Verification'] = array('googlebot.com', 'google.com'); // Check for Google Search Console
$ab_se['vkShare'] = array('.', '.vk.com', '.vkontakte.ru', '.go.mail.ru', '.userapi.ru'); // vkontakte
$ab_se['facebookexternalhit'] = array('.fbsv.net', '66.220.149.', '31.13.', '2a03:2880:'); // Facebook
$ab_se['OdklBot'] = array('.odnoklassniki.ru'); // Однокласники
$ab_se['MailRuConnect'] = array('.smailru.net'); // Мой мир (mail.ru)
$ab_se['TelegramBot'] = array('149.154.161'); // Telegram
$ab_se['Twitterbot'] = array('.twttr.com', '199.16.15'); // Twitter
$ab_se['WhatsApp/'] = array('.');
$ab_se['googleweblight'] = array('google.com'); // 
$ab_se['BingPreview'] = array('search.msn.com'); // Check Bing Mobile Page Adaptation
//$ab_se['uptimerobot'] = array('uptimerobot.com');
//$ab_se['pingdom'] = array('pingdom.com');
//$ab_se['HostTracker'] = array('.'); //
$ab_se['Yahoo! Slurp'] = array('.yahoo.net'); // Yahoo Bots
//$ab_se['SeznamBot'] = array('.seznam.cz'); // seznam.cz
$ab_se['Pinterestbot'] = array('.pinterest.com'); // 
$ab_se['Mediapartners'] = array('googlebot.com', 'google.com'); // AdSense bot
$ab_se['Mediapartners-Google'] = array('googlebot.com', 'google.com'); // AdSense bot
$ab_se['AdsBot-Google'] = array('google.com'); // Adwords bot
$ab_se['Google-Adwords'] = array('google.com'); // Adwords bot (Google-Adwords-Instant и Google-AdWords-Express
$ab_se['Google-Ads'] = array('google.com'); // Adwords bot (Google-Ads-Creatives-Assistant)
$ab_se['Google Favicon'] = array('google.com');
$ab_se['FeedFetcher-Google'] = array('google.com'); // google news
$ab_se['Applebot'] = array('applebot.apple.com'); // see http://www.apple.com/go/applebot
$ab_se['Chrome-Lighthouse'] = array('.google.com'); // PageSpeed Insights
//$ab_se['w3.org'] = array('.w3.org');
$ab_se['Google-Structured-Data-Testing-Tool'] = array('.google.com');
//$ab_se['AhrefsBot'] = array('ahrefs.com');
//$ab_se['SemrushBot'] = array('semrush.com');

// ---------------------------------------------------------------------

// Если сайт (php) находится за прокси (apache за nginx или cloudflare и т.п.)
// укажите подсеть ip прокси серверов и значение $_SERVER переменной из которой 
// брать реальный ip посетителя. поддерживаются только ipv4.

// CloudFlare:
// $ab_proxy['173.245.48.0/20'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['103.21.244.0/22'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['103.22.200.0/22'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['103.31.4.0/22'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['141.101.64.0/18'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['108.162.192.0/18'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['190.93.240.0/20'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['188.114.96.0/20'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['197.234.240.0/22'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['198.41.128.0/17'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['162.158.0.0/15'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['172.64.0.0/13'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['131.0.72.0/22'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['104.16.0.0/13'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['104.24.0.0/14'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['104.16.0.0/12'] = 'HTTP_CF_CONNECTING_IP';
// $ab_proxy['104.21.80.0/20'] = 'HTTP_CF_CONNECTING_IP';
//$ab_proxy['127.0.0.0/24'] = 'HTTP_X_REAL_IP';

// ---------------------------------------------------------------------

// Настройки безопасности!
// для файлов: conf.php, counter.txt, tpl.txt, error.txt
// запретить редактировать файлы через админку. 1 - запретить, 0 - разрешить.
$ab_config['disable_editing'] = 1;

// Дополнительные настройки

$ab_config['check_get_ref'] = 1; // 0 - не проверять, 1 - проверять
$ab_config['bad_get_ref'] = array('q', 'text');
$ab_config['stop_nouseragent'] = 0; // 0 - не запрещать, 1 - запретить.
$ab_config['utm_noindex'] = 1; // 1 - запретить индексацию, 0 - не запрещать.