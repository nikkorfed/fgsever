<?php
// удаление из правил записей many_buttons_ban
// Last update date: 2021.02.27
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Empty the records');

if (isset($_POST['submit'])) {
$del = $antibot_db->exec("DELETE FROM rules WHERE comment LIKE 'many_buttons_ban%';");
//$vacuum = $antibot_db->exec("VACUUM;");
}

echo '<script>document.location.href="?'.$abw.$abp.'=rules";</script>';
