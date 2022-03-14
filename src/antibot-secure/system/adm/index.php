<?php
// Last update date: 2022.01.08
if(!defined('ANTIBOT')) die('access denied');

$title = abTranslate('Home');

$size = number_format(filesize(__DIR__.'/../data/sqlite.db'));

$content .= '<span id="warning"></span>
<div id="new_version_msg" class="alert alert-success" role="alert" style="display:none">'.abTranslate('A new version of the antibot is now available. In order to upgrade, visit page:').' <a href="?'.$abw.$abp.'=update">'.abTranslate('Update').'</a></div>';
if(function_exists('sys_getloadavg')) {
$content .= '<p>Loade average: '.@implode(' ', sys_getloadavg()).'</p>';
}
$content .= '<p>'.abTranslate('Database file size').' data/sqlite.db: '.$size.' bytes.</p>';
if ($ab_config['memcached_counter'] == 1) {
$getStats = $ab_memcached->getStats();
if (is_array($getStats)) {
foreach($getStats as $getStat) {
//print_r($getStat);
$content .= '<p>Memcached: '.abTranslate('used').' '.number_format($getStat['bytes']).' '.abTranslate('of').' '.number_format($getStat['limit_maxbytes']).' '.abTranslate('bytes').' ('.round($getStat['bytes'] * 100 / $getStat['limit_maxbytes'], 2).'%). Uptime: '.$getStat['uptime'].' sec.</p>';
}
}
}

if ($ab_config['check_url'] != 'https://cloud.antibot.cloud/antibot7.php') {
$content .= '
<table class="table table-bordered table-hover table-sm">
  <thead>
    <tr class="table-info">
      <th colspan="3">'.abTranslate('Do you want the antibot to protect your website even better?').' - '.abTranslate('The difference between cloud check and your local').':</th>
    </tr>
    <tr>
      <th scope="col">'.abTranslate('Description').'</th>
      <th scope="col">LOCAL ('.abTranslate('Your').')</th>
      <th scope="col">CLOUD</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>'.abTranslate('Bots Filtering').'</td>
      <td>'.abTranslate('Local (software)').'</td>
      <td>'.abTranslate('Cloud (service)').'</td>
    </tr>
    <tr>
      <td>'.abTranslate('Efficiency of protection').'</td>
      <td>'.abTranslate('about 70%').'</td>
      <td>'.abTranslate('up to 99%').'</td>
    </tr>
    <tr>
      <td>'.abTranslate('Protection against browser bots that support JS, http2 and other technologies').'</td>
      <td>'.abTranslate('Minimum').'</td>
      <td>'.abTranslate('Maximum').'</td>
    </tr>
    <tr>
      <td>'.abTranslate('Blacklist check IP, PTR, Fingerprint, Whois').'</td>
      <td>'.abTranslate('NO').'</td>
      <td>'.abTranslate('YES').'</td>
    </tr>
    <tr>
      <td>'.abTranslate('Check via reCAPTCHA v.3').'</td>
      <td>'.abTranslate('NO').'</td>
      <td>'.abTranslate('YES').'</td>
    </tr>
    <tr>
      <td>'.abTranslate('Blocking hosting (server) IP').'</td>
      <td>'.abTranslate('NO').'</td>
      <td>'.abTranslate('YES').'</td>
    </tr>
    <tr>
      <td>'.abTranslate('Support').'</td>
      <td>'.abTranslate('Only in the Telegram group').'</td>
      <td>'.abTranslate('By Email, private messages in Telegram, in the Telegram group').'</td>
    </tr>
  </tbody>
</table>
<p>'.abTranslate('Cloud service prices:').'<br />
<strong>ONE:</strong> '.abTranslate('1 domain and any subdomains of it. 40 USD per year.').'<br />
<strong>UNLIMITED:</strong> '.abTranslate('Without restrictions of domains, subdomains and without bindings to domains. 120 USD per year.').'<br />
'.abTranslate('50% discount when renewing the service until the end of the current paid subscription.').'<br />
'.abTranslate('+10 days free trial period to test the cloud version after registering on the site:').' <a href="https://antibot.cloud/#login" target="_blank">antibot.cloud</a>.</p>
';
}

// новости:
$content .= '<span id="other_ab_msg"></span>';

$content .= '<script>var current_version = '.$ab_version.';</script>
<script src="https://antibot.cloud/version.php?data='.md5('Antibot:'.$ab_config['email']).'|'.md5($ab_config['check_url']).'|'.$ab_version.'|'.$ab_config['cms'].'" async></script>

<script>
var xmlhttp = new XMLHttpRequest();
xmlhttp.onreadystatechange = function() {
if (this.readyState == 2 && this.status == 200) {
document.getElementById("warning").innerHTML = "<div class=\"alert alert-danger\"><a href=\"data/sqlite.db\" target=\"_blank\">data/sqlite.db</a> - '.abTranslate('The database file has a server response code of 200. It may be available for download. This is unsafe because the database may contain confidential data. Protect the database file from web access.').'</div>";
}
};
xmlhttp.timeout = 1000;
xmlhttp.open("GET", "data/sqlite.db", true);
xmlhttp.send();
</script>
';
