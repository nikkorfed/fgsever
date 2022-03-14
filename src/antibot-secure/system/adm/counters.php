<?php
// Last update date: 2021.09.06
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Statistics');

$list = $antibot_db->query("SELECT * FROM counters ORDER BY date DESC LIMIT 30;"); 
if ($ab_config['memcached_counter'] == 1) {
$cron_update_time = (int) $ab_memcached->get($ab_config['memcached_prefix'].'update') + 0;
$ab_memcached->set('memcached_test', 1);
} else {
$cron_update_time = 0;
}

if ($cron_update_time == 0) {
if ($ab_config['memcached_counter'] != 1) {
$content .= '<div class="alert alert-danger" role="alert">'.abTranslate('Memcached statistics is disabled in the antibot config. Set up memcached and enable it if you want to collect statistics on this page.').'</a></div>';
} elseif ($ab_memcached->get('memcached_test') != 1) {
$content .= '<div class="alert alert-danger" role="alert">'.abTranslate('Error: Memcached - no connection. Configure memcached or disable memcached statistics in the antibot config so as not to create unnecessary load on the server.').'</a></div>';
}
}

$content .= '<p>'.abTranslate('Until the next update statistics remains:').' '.(600 - (time() - $cron_update_time)).' '.abTranslate('sec.').'</p>
<table class="table table-bordered table-hover table-sm">
<thead class="thead-light">
<tr>
<th>'.abTranslate('Date').'</th>
<th style="color:red;">'.abTranslate('Failed').'</th>
<th style="color:green;">'.abTranslate('Automatic').'</th>
<th style="color:green;">'.abTranslate('Clicked').'</th>
<th>'.abTranslate('Unique').'</th>
<th>'.abTranslate('Hits').'</th>
<th>'.abTranslate('Good bots').'</th>
<th style="color:red;">'.abTranslate('Blocked').'</th>
<th style="color:red;">'.abTranslate('Fake bots').'</th>
</tr>
</thead>
<tbody>
';
$all_no = 0;
$all_auto = 0;
$all_click = 0;
$all_uusers = 0;
$all_husers = 0;
$all_google = 0;
$all_yandex = 0;
$all_mailru = 0;
$all_bing = 0;
$all_other = 0;
$all_whits = 0;
$all_bbots = 0;
$all_fakes = 0;
while ($echo = $list->fetchArray(SQLITE3_ASSOC)) {
$no = $echo['test'] - $echo['auto'] - $echo['click'];
if ($echo['auto'] == 0) {
$percent = 0;
} else {
$percent = round($echo['click'] * 100 / $echo['auto']);
}
$content .= '<tr>
<td>'.date("Y.m.d", strtotime($echo['date'])).'</td>
<td>'.number_format($no).'</td>
<td>'.number_format($echo['auto']).'</td>
<td>'.number_format($echo['click']).' '.(($percent > 10) ? '<small class="text-danger">'.$percent.'%</small>' : '<small class="text-success">'.$percent.'%</small>').'</td>
<td>'.number_format($echo['uusers']).'</td>
<td>'.number_format($echo['husers']).'</td>
<td>';
if ($ab_config['extended_bot_stat'] == 1) {
$echo['other'] = $echo['whits'] - $echo['google'] - $echo['yandex'] - $echo['mailru'] - $echo['bing'];
$content .= '<small>G: '.number_format($echo['google']).'<br />Y: '.number_format($echo['yandex']).'<br />M: '.number_format($echo['mailru']).'<br />B: '.number_format($echo['bing']).'<br />O: '.number_format($echo['other']).'</small>';
$all_google = $all_google + $echo['google'];
$all_yandex = $all_yandex + $echo['yandex'];
$all_mailru = $all_mailru + $echo['mailru'];
$all_bing = $all_bing + $echo['bing'];
$all_other = $all_other + $echo['other'];
} else {
$content .= number_format($echo['whits']);
$all_whits = $all_whits + $echo['whits'];
}
$content .= '</td>
<td>'.number_format($echo['bbots']).'</td>
<td>'.number_format($echo['fakes']).'</td>
</tr>';
$all_no = $all_no + $no;
$all_auto = $all_auto + $echo['auto'];
$all_click = $all_click + $echo['click'];
$all_uusers = $all_uusers + $echo['uusers'];
$all_husers = $all_husers + $echo['husers'];
$all_bbots = $all_bbots + $echo['bbots'];
$all_fakes = $all_fakes + $echo['fakes'];
}

if ($all_auto == 0) {
$all_percent = 0;
} else {
$all_percent = round($all_click * 100 / $all_auto);
}

$content .= '
<tr>
<td><strong>Total</strong></td>
<td>'.number_format($all_no).'</td>
<td>'.number_format($all_auto).'</td>
<td>'.number_format($all_click).' '.(($all_percent > 10) ? '<small class="text-danger">'.$all_percent.'%</small>' : '<small class="text-success">'.$all_percent.'%</small>').'</td>
<td>'.number_format($all_uusers).'</td>
<td>'.number_format($all_husers).'</td>
<td>';
if ($ab_config['extended_bot_stat'] == 1) {
$content .= '<small>G: '.number_format($all_google).'<br />Y: '.number_format($all_yandex).'<br />M: '.number_format($all_mailru).'<br />B: '.number_format($all_bing).'<br />O: '.number_format($all_other).'</small>';
} else {
$content .= number_format($all_whits);
}
$content .= '</td>
<td>'.number_format($all_bbots).'</td>
<td>'.number_format($all_fakes).'</td>
</tr>
</tbody>
</table>
<p><strong>'.abTranslate('Failed').'</strong> - '.abTranslate('visitors (mostly the bad bots) who didn\'t make to pass AntiBot.').'<br />
<strong>'.abTranslate('Automatic').'</strong> - '.abTranslate('visitors who successfully passed the test.').'<br />
<strong>'.abTranslate('Clicked').'</strong> - '.abTranslate('visitors who did not pass the automatic check, but clicked on the button.').'<br />
<strong class="text-danger">'.abTranslate('Clicked').' %</strong> - '.abTranslate('the number (percentage) of "Clicked" relative to "Automatic". If more than 10%, then this requires attention.').'
<br />
<strong>'.abTranslate('Unique').'</strong> - '.abTranslate('the number of unique visitors who passed the AntiBot check.').'<br />
<strong>'.abTranslate('Hits').'</strong> - '.abTranslate('the number of views by visitors who passed the AntiBot check.').'<br />
<strong>'.abTranslate('Good bots').'</strong> - '.abTranslate('the number of hits by good bots (allowed in the conf.php).').'<br />
<strong>'.abTranslate('Blocked').'</strong> - '.abTranslate('blocked hits according to your rules (by ip, country, language, referrer, ptr).').'<br />
<strong>'.abTranslate('Fake bots').'</strong> - '.abTranslate('hits by fake bots that disguise themselves as good bots.').'
</p>
';
