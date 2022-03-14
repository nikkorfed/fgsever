<?php
// Last update date: 2021.02.27
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Add rule');

if (isset($_POST['submit'])) {
$_POST['search'] = isset($_POST['search']) ? trim(strip_tags($_POST['search'])) : die('search');
$_POST['comment'] = isset($_POST['comment']) ? trim(strip_tags($_POST['comment'])) : '';
$_POST['comment'] = $antibot_db->escapeString($_POST['comment'].' / '.date("Y.m.d H:i", $ab_config['time']));
$_POST['rule'] = isset($_POST['rule']) ? trim(strip_tags($_POST['rule'])) : '';
if ($_POST['rule'] != 'white' AND $_POST['rule'] != 'black') die('rule');
if ($_POST['rule'] != '') {
$add = @$antibot_db->exec("INSERT INTO rules (search, rule, comment) VALUES ('".$_POST['search']."', '".$_POST['rule']."', '".$_POST['comment']."');");
}
}

echo '<script>document.location.href="?'.$abw.$abp.'=rules";</script>';
