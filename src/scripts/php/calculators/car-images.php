<? 

// Подмена URL изображения и выдача его со своего сервера
if (isset($_REQUEST['vin']) && isset($_REQUEST['image'])) {
  $vin = $_REQUEST['vin']; 
  $image = $_REQUEST['image'];
  
  header('Content-type: image/png');
  echo file_get_contents("http://185.20.226.75:3000/aos-parser/images/$vin/$image.png");
}

?>