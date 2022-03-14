<?php
/*
очистка таблицы хитов по крону.
в crontab поставить команду вида:
0 1 * * * /usr/bin/php -q /home/user/antibot.cloud/antibot/code/clear_old_hits.php lastyear > /dev/null 2>&1
суть которой: 
0 1 * * * - это значит запускать в час ночи каждый день.
/home/user/antibot.cloud/antibot/code/clear_old_hits.php путь к этому скрипту относительно корня сервера.
lastyear - вместо этого указать за какой период удалять логи, варианты:
yesterday - удалять старше суток
lastweek - удалять старше недели
lastmonth - - удалять старше месяца
lastyear - удалить старше года
all - удалить все логи
*/

if (php_sapi_name() != 'cli') die('no cli');

$antibot_db = new SQLite3(__DIR__.'/../data/sqlite.db'); 
$antibot_db->busyTimeout(5000);
$antibot_db->exec("PRAGMA journal_mode = WAL;");

$todate = isset($_SERVER['argv'][1]) ? preg_replace("/[^a-z]/","",trim($_SERVER['argv'][1])) : die('date not set');

// искать до указанной даты включительно
$tl['yesterday'] = date("Ymd", time() - 86400); // старше суток
$tl['lastweek'] = date("Ymd", time() - (86400*7)); // неделя
$tl['lastmonth'] = date("Ymd", time() - (86400*30)); // месяц
$tl['lastyear'] = date("Ymd", time() - (86400*365)); // год

if (isset($tl[$todate])) {
$datelimit = 'WHERE date < \''.$tl[$todate].'\'';
} else {
$datelimit = '';
}

$del = $antibot_db->exec("DELETE FROM hits ".$datelimit.";");
$vacuum = $antibot_db->exec("VACUUM;");

echo "ok\n";
