<?

function BadSocTraf() {
  // список рефереров которым показывать антибота:
  return preg_match("/(instagram.com|youtube.com|facebook.com|zen.yandex.ru|vk.com|click.my.mail.ru|ok.ru|t.co|bing.com|rambler.ru|msn.com|twitter.com|nova.rambler.ru|sq2.go.mail.ru|duckduckgo.com|ukr.net|yahoo.com)/i", @$_SERVER['HTTP_REFERER']);
}
// в итоге антибот будет покзываться только трафу с плохим реферером, а также закладочному трафику:
if (BadSocTraf() OR @trim($_SERVER['HTTP_REFERER']) == '' OR isset($_COOKIE['lastcid']) OR isset($_POST['antibot'])) {
  require_once($_SERVER['DOCUMENT_ROOT'].'/antibot-secure/system/code/include.php');
}

?>