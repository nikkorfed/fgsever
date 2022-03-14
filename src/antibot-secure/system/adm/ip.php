<?php
// Last update date: 2021.05.17
if(!defined('ANTIBOT')) die('access denied');

$get_ip = isset($_GET['ip']) ? trim(preg_replace("/[^0-9a-zA-Z\.\:]/","", $_GET['ip'])) : '';

if ($get_ip != '') {
$content .= '<p id="result">Loading...</p>
<script>
var xmlhttp = new XMLHttpRequest();
xmlhttp.onreadystatechange = function() {
  if (this.readyState == 4 && this.status == 200) {

var data = JSON.parse(this.responseText);
var k = Object.keys(data);
var result = "";
k.forEach(function(entry) {
    console.log(entry);

result += "<span class=\"text-muted\">"+entry+":</span> "+data[entry]+"<br />";
});
document.getElementById("result").innerHTML = result;
  }
};
xmlhttp.open("POST", "https://cloud.antibot.cloud/whois.php?ip='.$get_ip.'&lang='.$lang_code.'&h2='.md5('Antibot:'.$ab_config['email']).'&h1='.md5($ab_config['email'].$ab_config['pass'].$get_ip).'", true);
xmlhttp.send();
</script>';
} else {
$content .= '<div class="alert alert-danger" role="alert">'.abTranslate('IP address is not set.').'</div>';
}
$title = abTranslate('Whois and IP info.');
