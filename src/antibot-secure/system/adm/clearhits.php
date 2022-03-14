<?php
// очистка таблицы хитов
// Last update date: 2021.03.02
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Empty the records');

if (isset($_POST['submit'])) {

$todate = isset($_POST['todate']) ? preg_replace("/[^a-z]/","",trim($_POST['todate'])) : '';

// искать до указанной даты включительно
$tl['yesterday'] = date("Ymd", $ab_config['time'] - 86400); // старше суток
$tl['lastweek'] = date("Ymd", $ab_config['time'] - (86400*7)); // неделя
$tl['lastmonth'] = date("Ymd", $ab_config['time'] - (86400*30)); // месяц
$tl['lastyear'] = date("Ymd", $ab_config['time'] - (86400*365)); // год

if (isset($tl[$todate])) {
$datelimit = 'WHERE date < \''.$tl[$todate].'\'';
} else {
$datelimit = '';
}

$del = $antibot_db->exec("DELETE FROM hits ".$datelimit.";");
$vacuum = $antibot_db->exec("VACUUM;");
}

echo '<script>document.location.href="?'.$abw.$abp.'=hits";</script>';
