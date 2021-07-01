<? 

// Подмена URL изображения и выдача его со своего сервера
if (isset($_REQUEST['vin']) && isset($_REQUEST['image'])) {
  $vin = $_REQUEST['vin']; 
  $image = $_REQUEST['image'];
  
  header('Content-type: image/png');
  echo file_get_contents("http://80.78.254.156/aos-parser/images/$vin/$image.png");
}

?>