<?php
// Last update: 2022.01.08
if(!isset($ab_version)) die('access denied');

if ($ab_config['re_check'] == 1) {
echo '<script src="https://www.google.com/recaptcha/api.js?render='.$ab_config['recaptcha_key'].'"></script>';
}

$ab_output = array();
$ab_parse_url = parse_url($ab_config['uri']); // текущий урл
if ($ab_config['utm_referrer'] == 1 AND $ab_config['referer'] != '') {
if (isset($ab_parse_url['query'])) {
parse_str($ab_parse_url['query'], $ab_output);
}

$ab_output['utm_referrer'] = isset($_GET['utm_referrer']) ? trim(strip_tags($_GET['utm_referrer'])) : $ab_config['referer'];
$ab_new_url = $ab_parse_url['path'].'?'.http_build_query($ab_output);
} else {
$ab_new_url = $ab_config['uri'];
}

?>
<style>
.btn-color {border: 2px solid transparent; cursor: pointer; font-size: 18px; line-height: 15px; padding: 15px 15px; text-decoration: none; text-shadow: none; border-radius: 3px; box-shadow: none; transition: 0.25s; display: block; margin: 0 auto;}
.btn-color:hover {line-height: 16px; padding: 16px 16px; border: 1px solid #ccc;}
</style>
<script>
if (window.location.hostname !== window.atob("<?php echo base64_encode($ab_config['host']); ?>") && window.location.hostname !== window.atob("<?php echo base64_encode(strstr($ab_config['host'], ':', true)); ?>")) {
window.location = window.atob("<?php echo base64_encode('http://'.$ab_config['host'].$ab_config['uri']); ?>");
throw "stop";
}

function b64_to_utf8(str) {
str = str.replace(/\s/g, '');    
return decodeURIComponent(escape(window.atob(str)));
}

setTimeout(Button, <?php echo $ab_config['timer']+4; ?>000);

var country = '<?php echo $ab_config['country']; ?>';
var action = '<?php echo preg_replace("/[^0-9a-z]/","", $ab_config['host']); ?>';
var h1 = '<?php echo md5($ab_config['email'].$ab_config['pass'].$ab_config['host'].$ab_config['useragent'].$ab_config['accept_lang'].$ab_config['ip_short'].$ab_config['re_check'].$ab_config['ho_check']); ?>';
var h2 = '<?php echo md5('Antibot:'.$ab_config['email']); ?>';
var ipfull = '<?php echo $ab_config['ip']; ?>';
var ip = '<?php echo $ab_config['ip_short']; ?>';
var via = '<?php echo $ab_config['http_via']; ?>';
var v = '<?php echo $ab_version; ?>';
var re = '<?php echo $ab_config['re_check']; ?>';
var rk = '<?php echo $ab_config['recaptcha_key']; ?>';
var ho = '<?php echo $ab_config['ho_check']; ?>';
var cid = '<?php echo $ab_config['cid']; ?>';
var ptr = '<?php echo $ab_config['ptr']; ?>';
var width = screen.width;
var height = screen.height;
var cwidth = document.documentElement.clientWidth;
var cheight = document.documentElement.clientHeight;
var colordepth = screen.colorDepth;
var pixeldepth = screen.pixelDepth;
var phpreferrer = '<?php echo preg_replace("/[^0-9a-z-.:]/","", parse_url($ab_config['refhost'], PHP_URL_HOST)); ?>';
var referrer = document.referrer;
if (referrer != '') {var referrer = document.referrer.split('/')[2].split(':')[0];}

<?php if ($ab_config['re_check'] == 1) { ?>
grecaptcha.ready(function() {
//document.getElementById("btn").innerHTML = '✓✕✕'; // receiving token
grecaptcha.execute('<?php echo $ab_config['recaptcha_key']; ?>', {action: action}).then(function(token) {
//document.getElementById("btn").innerHTML = '✓✓✕'; // token received
var data = 'country='+country+'&action='+action+'&token='+token+'&h1='+h1+'&h2='+h2+'&ipfull='+ipfull+'&ip='+ip+'&via='+via+'&v='+v+'&re='+re+'&rk='+rk+'&ho='+ho+'&cid='+cid+'&ptr='+ptr+'&w='+width+'&h='+height+'&cw='+cwidth+'&ch='+cheight+'&co='+colordepth+'&pi='+pixeldepth+'&ref='+referrer;
CloudTest(window.atob('<?php echo base64_encode($ab_config['check_url']); ?>'), 5000, data, 0);
});
});      
<?php } else { ?>
function nore() {
//document.getElementById("btn").innerHTML = '✓✓✕';
var token = '0';
var data = 'country='+country+'&action='+action+'&token='+token+'&h1='+h1+'&h2='+h2+'&ipfull='+ipfull+'&ip='+ip+'&via='+via+'&v='+v+'&re='+re+'&rk='+rk+'&ho='+ho+'&cid='+cid+'&ptr='+ptr+'&w='+width+'&h='+height+'&cw='+cwidth+'&ch='+cheight+'&co='+colordepth+'&pi='+pixeldepth+'&ref='+referrer;
CloudTest(window.atob('<?php echo base64_encode($ab_config['check_url']); ?>'), 5000, data, 0);
}
setTimeout(nore, <?php echo $ab_config['timer']; ?>000);
<?php } ?>

function Button() {
<?php if ($ab_config['input_button'] != 1) { 
if ($ab_config['many_buttons'] == 1) {
$div = '';
$js = array();
foreach ($words as $word) {
$id = 'w'.md5($ab_config['time'].$word.rand(1,99999));
if (rand(1,2) == 1) {
shuffle($words2);
$id2 = 'w'.md5($id).rand(1,99999);
$div .= '<span class=\"'.$id2.'\"></span>';
$js[] = 'document.getElementsByClassName("'.$id2.'")[0].style.display = "none";';
$js[] = 'document.getElementsByClassName("'.$id2.'")[0].innerHTML = b64_to_utf8("'.base64_encode(urlencode($words2[0])).'");';
}
$div .= '<span class=\"'.$id.'\"></span>';
$js[] = 'document.getElementsByClassName("'.$id.'")[0].innerHTML = b64_to_utf8("'.base64_encode(urlencode($word)).'");';
}
shuffle($js);
// 
shuffle($ab_config['colors']);
$buttons = '<p>';
foreach ($ab_config['colors'] as $ab_config['color']) {
$buttons .= '<button style=\"background:'.$ab_config['color'].'; display: inline;\" class=\"btn btn-color\" type=\"submit\" name=\"color\" value=\"'.$ab_config['color'].'\" title=\"'.abTranslate($ab_config['color']).'\"></button> ';
}
$buttons .= '</p>';

// если возможно, то делаем картинкой:
if(function_exists('imagecreatetruecolor')) {
$im = imagecreatetruecolor(rand(1,30), rand(1,30));

$color_code['RED'] = imagecolorallocate($im, rand(220,255), rand(0,30), rand(0,30)); // красный
$color_code['BLACK'] = imagecolorallocate($im, rand(0,15), rand(0,25), rand(0,25)); // черный
$color_code['YELLOW'] = imagecolorallocate($im, rand(245,255), rand(220,255), rand(0,25)); // желтый
$color_code['GRAY'] = imagecolorallocate($im, rand(120,130), rand(125,135), rand(125,135)); // серый
$color_code['BLUE'] = imagecolorallocate($im, rand(0,30), rand(0,30), rand(155,255)); // синий
$color_code['GREEN'] = imagecolorallocate($im, rand(0,30), rand(125,250), rand(0,30)); // зеленый
$color_code['PURPLE'] = imagecolorallocate($im, rand(120,130), rand(0,30), rand(120,130)); // фиолетовый

imagefill($im, 0, 0, $color_code[$color]);
ob_start(); 
imagepng($im);
imagedestroy($im);
$image_data = ob_get_contents(); 
ob_end_clean(); 
$js = array();
$div = '<img width=\"40\" height=\"20\" style=\"vertical-align: middle;\" src=\"data:image/png;base64,'.base64_encode($image_data).'\" />';
}

?>
document.getElementById("btn").innerHTML = "<p style=\"font-size: 1.2em;\"><?php echo abTranslate('If you are human, click on the button with the color most similar to this one:'); ?> <strong><?php echo $div; ?></strong></p><?php echo '<form action=\"'.$ab_new_url.'\" method=\"post\"><input name=\"time\" type=\"hidden\" value=\"'.$ab_config['time'].'\"><input name=\"submit\" type=\"hidden\" value=\"submit\"><input name=\"antibot\" type=\"hidden\" value=\"'.md5($ab_config['salt'].$ab_config['time'].$ab_config['ip'].$ab_config['useragent']).'\"><input name=\"colors\" type=\"hidden\" value=\"'.md5($ab_config['salt'].$ab_config['time'].$ab_config['ip'].$ab_config['useragent'].$color).'\"><input name=\"cid\" type=\"hidden\" value=\"'.$ab_config['cid'].'\">'.$buttons.'</form>'; ?>";
<?php
echo "\n".implode("\n", $js)."\n";
} else {
?>
document.getElementById("btn").innerHTML = b64_to_utf8("<?php echo base64_encode('<p style="font-size: 1.2em;">'.abTranslate('Are you not a robot? Click on the button to continue:').'</p><form action="'.$ab_new_url.'" method="post"><input name="time" type="hidden" value="'.$ab_config['time'].'"><input name="antibot" type="hidden" value="'.md5($ab_config['salt'].$ab_config['time'].$ab_config['ip'].$ab_config['useragent']).'"><input name="cid" type="hidden" value="'.$ab_config['cid'].'"><input style="cursor: pointer;" class="btn btn-success" type="submit" name="submit" value="'.abTranslate('I am human. Continue.').'"></form>'); ?>");	
<?php }} ?>
}

function CloudTest(s, t, d, b){
var cloud = new XMLHttpRequest();
cloud.open("POST", s, true)
cloud.setRequestHeader('Content-type', 'application/x-www-form-urlencoded;');
cloud.timeout = t; // time in milliseconds

cloud.onload = function () {
if(cloud.status == 200) {
//document.getElementById("btn").innerHTML = '✓✓✓';
  console.log('good: '+cloud.status);
var obj = JSON.parse(this.responseText);
if (typeof(obj.error) == "string") {
document.getElementById("error").innerHTML = obj.error;
<?php if ($ab_config['check_url'] == 'https://cloud.antibot.cloud/antibot7.php') { ?>
if (obj.error == "") {
var img = document.createElement('img');
img.style.display = "none";
img.src = "https://mc.yandex.ru/pixel/6677347880582999110?rnd=%aw_random%";
img.referrerPolicy = "no-referrer";
document.body.appendChild(img);
}
<?php } ?>
}
if (typeof(obj.cookie) == "string") {
document.getElementById("btn").innerHTML = "<?php echo abTranslate('Loading page, please wait...'); ?>";
var d = new Date();
d.setTime(d.getTime() + (10 * 24 * 60 * 60 * 1000));
var expires = "expires="+ d.toUTCString();
document.cookie = "<?php echo 'antibot_'.md5($ab_config['salt'].$ab_config['host'].$ab_config['ip_short']); ?>="+obj.cookie+"; " + expires + "; path=/;";
document.cookie = "lastcid="+obj.cid+"; " + expires + "; path=/;";
<?php
if ($ab_config['utm_referrer'] == 1) {
echo 'document.location = "'.$ab_new_url.'";';
} else {
echo 'location.reload(true);';
}
?>

} else {
Button();
console.log('bad bot');
}
} else {
//document.getElementById("btn").innerHTML = '✓✓✕';
  console.log('other error');
  if (b == 1) {Button();} else {CloudTest(window.atob('<?php echo base64_encode($ab_config['check_url2']); ?>'), 5000, d, 1);}
}
};
cloud.onerror = function(){
//document.getElementById("btn").innerHTML = '✓✓✕';
	console.log("error: "+cloud.status);
	if (b == 1) {Button();} else {CloudTest(window.atob('<?php echo base64_encode($ab_config['check_url2']); ?>'), 5000, d, 1);}
}
cloud.ontimeout = function () {
  // timeout
//document.getElementById("btn").innerHTML = '✓✓✕';
  console.log('timeout');
  if (b == 1) {Button();} else {CloudTest(window.atob('<?php echo base64_encode($ab_config['check_url2']); ?>'), 5000, d, 1);}
};
cloud.send(d);
}
</script>
