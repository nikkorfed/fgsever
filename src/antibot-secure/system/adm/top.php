<?php
// Last update date: 2022.01.11
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Top queries');

$table = isset($_GET['table']) ? preg_replace("/[^a-z]/","",trim($_GET['table'])) : 'ip';
$status = isset($_GET['status']) ? preg_replace("/[^0-9]/","",trim($_GET['status'])) : '';
$todate = isset($_GET['todate']) ? preg_replace("/[^a-z]/","",trim($_GET['todate'])) : 'today';

// искать до указанной даты включительно
$tl['today'] = date("Ymd", $ab_config['time']); // сегодня
$tl['yesterday'] = date("Ymd", $ab_config['time'] - 86400); // вчера
$tl['lastweek'] = date("Ymd", $ab_config['time'] - (86400*7)); // неделя
$tl['lastmonth'] = date("Ymd", $ab_config['time'] - (86400*30)); // месяц
$tl['lastyear'] = date("Ymd", $ab_config['time'] - (86400*365)); // год

$q = array();

if (isset($tl[$todate])) {
$q[] = 'date >= \''.$tl[$todate].'\'';
}

if ($status != '') {
$q[] = 'passed=\''.$status.'\'';
}

if (count($q) > 0) {
$where = 'WHERE';
} else {
$where = '';
}

$sql = "SELECT count(ROWID) as counter, ".(($table == 'ptr') ? "country, " : '').(($table == 'ip') ? "ptr, country, " : '')." ".$table." FROM hits ".$where." ".implode(' AND ', $q)." GROUP BY ".$table." ORDER BY COUNT(ROWID) DESC LIMIT 200;";
//echo $sql;
$list = $antibot_db->query($sql); 

$content .= '
<form class="form-inline" action="?'.$abw.$abp.'=top" method="get">';
foreach ($abp_get as $k => $v) {
$content .= '<input name="'.$k.'" type="hidden" value="'.$v.'">';
}
$content .= '<input name="'.$abp.'" type="hidden" value="top">
'.abTranslate('status:').'
<select class="form-control mx-sm-3 form-control-sm" name="status">
<option value="">'.abTranslate('any').'</option>
<option value="0" '.(($status == '0') ? 'selected' : '').'>stop</option>
<option value="1" '.(($status == '1') ? 'selected' : '').'>auto</option>
<option value="2" '.(($status == '2') ? 'selected' : '').'>post</option>
<option value="3" '.(($status == '3') ? 'selected' : '').'>local</option>
</select> 
'.abTranslate('table:').'
<select class="form-control mx-sm-3 form-control-sm" name="table">
<option value="ip" '.(($table == 'ip') ? 'selected' : '').'>IP</option>
<option value="ptr" '.(($table == 'ptr') ? 'selected' : '').'>PTR</option>
<option value="useragent" '.(($table == 'useragent') ? 'selected' : '').'>useragent</option>
<option value="uid" '.(($table == 'uid') ? 'selected' : '').'>uid</option>
<option value="cid" '.(($table == 'cid') ? 'selected' : '').'>cid</option>
<option value="country" '.(($table == 'country') ? 'selected' : '').'>country</option>
<option value="referer" '.(($table == 'referer') ? 'selected' : '').'>referer</option>
<option value="page" '.(($table == 'page') ? 'selected' : '').'>page</option>
<option value="lang" '.(($table == 'lang') ? 'selected' : '').'>lang</option>
</select>
<select class="form-control mx-sm-3 form-control-sm" name="todate">
<option value="">'.abTranslate('All time').'</option>
<option value="today" '.(($todate == 'today') ? 'selected' : '').'>'.abTranslate('Today').'</option>
<option value="yesterday" '.(($todate == 'yesterday') ? 'selected' : '').'>'.abTranslate('Yesterday').'</option>
<option value="lastweek" '.(($todate == 'lastweek') ? 'selected' : '').'>'.abTranslate('Last Week').'</option>
<option value="lastmonth" '.(($todate == 'lastmonth') ? 'selected' : '').'>'.abTranslate('Last Month').'</option>
<option value="lastyear" '.(($todate == 'lastyear') ? 'selected' : '').'>'.abTranslate('Last Year').'</option>
</select> 

<input style="cursor:pointer;" class="btn btn-sm btn-primary" type="submit" name="submit" value="'.abTranslate('Search').'">
</form>
<br />
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th>'.$table.'</th>';
if ($table == 'ip') {
$content .= '<th>ptr</th><th>whois</th><th>country</th>';
} elseif ($table == 'ptr') {
$content .= '<th>country</th>';
}
$content .= '<th>'.abTranslate('Amount').'</th>
<th>'.abTranslate('Query Log').'</th>
</tr>
</thead>
<tbody>
';

while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
$content .= '<tr>
<td style="word-break: break-all;">'.$echo[$table].'</td>';
if ($table == 'ip') {
$content .= '<td>'.$echo['ptr'].'</td><td><a href="?'.$abw.$abp.'=ip&ip='.$echo['ip'].'" target="_blank" rel="noopener">whois '.$echo['ip'].'</a></td><td><img src="'.$ab_webdir.'/flags/'.$echo['country'].'.png" class="pngflag" title="'.$echo['country'].'" /> '.$echo['country'].'</td>';
} elseif ($table == 'ptr') {
$content .= '<td><img src="'.$ab_webdir.'/flags/'.$echo['country'].'.png" class="pngflag" title="'.$echo['country'].'" /> '.$echo['country'].'</td>';
}
if ($echo[$table] == '') {$echo[$table] = 'null';}
$content .= '<td>'.$echo['counter'].'</td>
<td><a href="?'.$abw.$abp.'=hits&search='.urlencode($echo[$table]).'&table='.$table.'&status='.$status.'&todate='.$todate.'&operator=equally" title="'.abTranslate('selection by:').' '.$table.'" target="_blank">'.abTranslate('Query Log').'</a></td>
</tr>';
}

$content .= '</tbody>
</table>';
