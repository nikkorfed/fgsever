<?

include_once 'libraries/simple_html_dom.php';

$url = 'https://yadi.sk/d/GnnO7eAU3XETgh';
$result = file_get_html($url);


preg_match_all('/Road Map Europe (?:Next East|East Next) ([\d-]+)/i', $result->plaintext, $matches);
$nextEastVersion = array_pop(array_pop($matches));

preg_match_all('/Road Map Europe (?:Next West|West Next) ([\d-]+)/i', $result->plaintext, $matches);
$nextWestVersion = array_pop(array_pop($matches));

preg_match_all('/Road Map Europe EVO ([\d-]+)/i', $result->plaintext, $matches);
$evoVersion = array_pop(array_pop($matches));

$data = [
  'nextEastVersion' => $nextEastVersion,
  'nextWestVersion' => $nextWestVersion,
  'evoVersion' => $evoVersion
];

if (!$updateData) {
	header('content-type: application/json; charset=UTF-8');
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else echo '<p>Версии карт успешно обновлены!</p>';
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/data/maps-version.json', json_encode($data, JSON_UNESCAPED_UNICODE));

?>