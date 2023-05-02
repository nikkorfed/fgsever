<?

$url = 'https://www.cbr.ru/scripts/XML_daily.asp';
$response = file_get_contents($url);

header("content-type: application/xml; charset=windows-1251");
echo $response;

?>