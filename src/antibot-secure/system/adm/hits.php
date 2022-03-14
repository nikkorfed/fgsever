<?php
// Last update date: 2022.01.08
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Query Log');

// номер страницы пагинации:
$n = isset($_GET['n']) ? preg_replace("/[^0-9]/","",trim($_GET['n'])) : 0;

$search = isset($_GET['search']) ? trim(strip_tags($_GET['search'])) : '';
$search = $antibot_db->escapeString($search);
$table = isset($_GET['table']) ? preg_replace("/[^a-z]/","",trim($_GET['table'])) : '';
$status = isset($_GET['status']) ? preg_replace("/[^0-9]/","",trim($_GET['status'])) : '';
$operator = isset($_GET['operator']) ? preg_replace("/[^a-z]/","",trim($_GET['operator'])) : '';
$todate = isset($_GET['todate']) ? preg_replace("/[^a-z]/","",trim($_GET['todate'])) : '';
$delete = isset($_GET['delete']) ? preg_replace("/[^0-9]/","",trim($_GET['delete'])) : '0';

// искать до указанной даты включительно
$tl['today'] = date("Ymd", $ab_config['time']); // сегодня
$tl['yesterday'] = date("Ymd", $ab_config['time'] - 86400); // вчера
$tl['lastweek'] = date("Ymd", $ab_config['time'] - (86400*7)); // неделя
$tl['lastmonth'] = date("Ymd", $ab_config['time'] - (86400*30)); // месяц
$tl['lastyear'] = date("Ymd", $ab_config['time'] - (86400*365)); // год

if (isset($tl[$todate])) {
$datelimit = 'date >= \''.$tl[$todate].'\' AND';
} else {
$datelimit = '';
}

$search_count = '';

if ($search != '') {
if ($operator == 'equally') {
if ($search == 'null') {
$q = "=''";
} else {
$q = "='".$search."'";
}
} else {$q = "LIKE '%".$search."%'";}

if ($delete == 1) {
$sql = "DELETE FROM hits WHERE ".$datelimit." ".(($status != '') ? "passed='".$status."' AND" : '')." ".$table." ".$q.";";
$list = $antibot_db->query($sql); 
$content .= '<div class="alert alert-success" role="alert">'.abTranslate('The selection has been deleted.').'</div>';
}

$sql = "SELECT rowid, * FROM hits WHERE ".$datelimit." ".(($status != '') ? "passed='".$status."' AND" : '')." ".$table." ".$q." ORDER BY rowid DESC LIMIT ".$n.", 100;";
//echo '<br />'.$sql;
$list = $antibot_db->query($sql); 

// колво результатов выборки:
$size = round(filesize(__DIR__.'/../data/sqlite.db') / 1024 / 1024, 2);
if ($size < 300) {
$search_count = $antibot_db->querySingle("SELECT count(rowid) FROM hits WHERE ".$datelimit." ".(($status != '') ? "passed='".$status."' AND" : '')." ".$table." ".$q.";");
$search_count = (string)$search_count;
$search_count = abTranslate('Found matches:').' '.$search_count;
}

} else {
$sql = "SELECT rowid, * FROM hits ".(($status != '') ? "WHERE ".$datelimit." passed='".$status."'" : '')." ORDER BY rowid DESC LIMIT ".$n.", 100;";
$list = $antibot_db->query($sql); 
}

if ($list === false) {
    var_dump($antibot_db->lastErrorCode());
    var_dump($antibot_db->lastErrorMsg());
die();
}

$content .= '
<form class="form-inline" action="?'.$abw.$abp.'=hits" method="get">';
foreach ($abp_get as $k => $v) {
$content .= '<input name="'.$k.'" type="hidden" value="'.$v.'">';
}
$content .= '<input name="'.$abp.'" type="hidden" value="hits">
'.abTranslate('Search:').' <input class="form-control mx-sm-3 form-control-sm" name="search" type="text" value="'.(($search != '') ? $search : '').'">
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
'.abTranslate('operator:').' 
<select class="form-control mx-sm-3 form-control-sm" name="operator">
<option value="equally" '.(($operator == 'equally') ? 'selected' : '').'>'.abTranslate('Strictly equal').'</option>
<option value="contains" '.(($operator == 'contains') ? 'selected' : '').'>'.abTranslate('Contains').'</option>
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
<p>'.$search_count;
if ($delete != 1 AND $search != '') {
$content .= ' <a href="'.$ab_config['uri'].'&delete=1" class="btn btn-sm btn-danger">'.abTranslate('Delete this selection').'</a>';
}
$content .= '</p><table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th>'.abTranslate('Status').'</th>
<th>IP (PTR) & User Agent & Accept Language</th>
<th>Referer & Page & UID</th>
</tr>
</thead>
<tbody>
';
$i = 0;
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
if ($echo['passed'] == 0) {$passed = '<span style="color:red;">stop</span>';} elseif ($echo['passed'] == 1) {$passed = '<span style="color:green;">auto</span>';} elseif ($echo['passed'] == 2) {$passed = '<span style="color:teal;">post</span>';} elseif ($echo['passed'] == 3) {$passed = '<span style="color:black;">local</span>';}
$content .= '<tr>
<td>'.date("Y.m.d", strtotime($echo['date'])).' '.$echo['time'].'<br />
'.$passed.' '.round($echo['generation'], 3);
if (isset($ab_config['recaptcha_secret'])) {
$content .= '<br /><span class="text-secondary">
'.$echo['js_w'].'x'.$echo['js_h'].'<br />
'.$echo['js_cw'].'x'.$echo['js_ch'].'<br />
'.$echo['js_co'].' '.$echo['js_pi'].'<br />
RE score: '.$echo['recaptcha'].'<br />
'.$echo['proto'].'
</span>
';
}
$content .= '</td>
<td style="word-break: break-all;"><img src="'.$ab_webdir.'/flags/'.$echo['country'].'.png" class="pngflag" title="'.$echo['country'].'" /> <strong>'.$echo['country'].'</strong> <a href="?'.$abw.$abp.'=hits&search='.$echo['ip'].'&table=ip&operator=equally" title="'.abTranslate('selection by:').' IP">'.$echo['ip'].'</a> ('.$echo['ptr'].') <a href="?'.$abw.$abp.'=ip&ip='.$echo['ip'].'" target="_blank" rel="noopener">whois</a><br />
<small>'.$echo['useragent'].'</small><br /><em>'.wordwrap($echo['lang'], 10, " ", true).'</em>
</td>
<td style="word-break: break-all;"><small>
R: <a href="'.$echo['referer'].'" target="_blank" rel="noopener noreferrer" title="Referer">'.mb_strimwidth($echo['referer'], 0, 60, '...', 'utf-8').'</a><br />
P: <a href="'.$echo['page'].'" target="_blank" rel="noopener" title="Page">'.mb_strimwidth($echo['page'], 0, 60, '...', 'utf-8').'</a><br />
uid: <a href="?'.$abw.$abp.'=hits&search='.$echo['uid'].'&table=uid&operator=equally" title="'.abTranslate('selection by:').' UID">'.$echo['uid'].'</a> <br />
cid: '.$echo['cid'].'</small></td>
</tr>';
$i++;
}
$content .= '</tbody>
</table>';
if ($i == 100) {
$content .= '<center><a href="?'.$abw.$abp.'=hits&n='.($n+100).'&status='.$status.'&search='.urlencode($search).'&table='.$table.'&operator='.$operator.'&todate='.$todate.'" class="btn btn-outline-info">'.abTranslate('Show more').'</a><br /><small><a href="?'.$abw.$abp.'=hits">'.abTranslate('To the begining').'</a></small></center>';
} else {
$content .= '<center><small><a href="?page=hits">'.abTranslate('To the begining').'</a></small></center>';
}
$content .= '<p><span style="color:red;">stop</span> - '.abTranslate('request to the AntiBot check page, check failed, the visitor remains on the check page.').'<br />
<span style="color:green;">auto</span> - '.abTranslate('request to the AntiBot check page, the check was completed automatically, the visitor received a redirect to the full page.').'<br />
<span style="color:teal;">post</span> - '.abTranslate('the request to the AntiBot check page, the check did not pass automatically, the visitor clicked on the website login button, the visitor received a redirect to the full page.').'<br />
<span style="color:black;">local</span> - '.abTranslate('request to a full page of the website, the visitor already had permission to access the website.').'<br />
'.abTranslate('The following is the time taken to generate the AntiBot check software.').'
</p>
<p>
<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;">
<input name="'.$abp.'" type="hidden" value="hits">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="submit" value="'.abTranslate('Empty the records').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="yesterday">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="submit" value="'.abTranslate('Older than a day').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="lastweek">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="submit" value="'.abTranslate('Older than a week').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="lastmonth">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="submit" value="'.abTranslate('Older than a month').'">
</form>

<form action="?'.$abw.$abp.'=clearhits" method="post" style="display: inline-block;">
<input name="'.$abp.'" type="hidden" value="hits">
<input name="todate" type="hidden" value="lastyear">
<input style="cursor:pointer;" class="btn btn-sm btn-danger" type="submit" name="submit" value="'.abTranslate('Older than a year').'">
</form>
</p>
<p>'.abTranslate('Delete all of these entries. Reduce the size of the database file (VACUUM), may take a long time.').'</p>
<hr />
<p><strong>For crontab</strong> ('.abTranslate('deleting old records by cron').'):</p>';
$content .= '<p><code>0 1 * * * /usr/bin/php -q '.dirname(dirname(__FILE__)).'/code/clear_old_hits.php lastmonth > /dev/null 2>&1</code><br />
<small class="text-muted">'.abTranslate('Check with your hosting provider on how to run a php script in a crontab.').'</small></p>
<p>'.abTranslate('Instead of lastmonth, the following values are possible:').'</p>
<ul>
<li>yesterday - '.abTranslate('Older than a day').'</li>
<li>lastweek - '.abTranslate('Older than a week').'</li>
<li>lastmonth - '.abTranslate('Older than a month').'</li>
<li>lastyear - '.abTranslate('Older than a year').'</li>
<li>all - '.abTranslate('Empty the records').'</li>
</ul>
';
