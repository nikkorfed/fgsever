<?

include 'libraries/PHPMailer.php';
include 'libraries/Exception.php';
include 'libraries/SMTP.php';

if (isset($_POST['form-claim-name']) && isset($_POST['form-claim-phone']) && isset($_POST['form-claim-order-number']) && isset($_POST['form-claim-reason'])) {

  // Собираем данные из формы
  $name = $_POST['form-claim-name'];
  $phone = $_POST['form-claim-phone'];
  $phone_link = str_replace([' ', '(', ')', '-'], '', $phone);
  $order_number = $_POST['form-claim-order-number'];
  $reason = $_POST['form-claim-reason'];

  // Сохраняем загруженные изображения
  if (isset($_FILES['form-claim-images'])) $images = [];
  foreach ($_FILES['form-claim-images']['tmp_name'] as $key => $tempPath) {
    move_uploaded_file($tempPath, $_SERVER['DOCUMENT_ROOT'] . "/images/claims/$order_number-$key.jpg");
    array_push($images, "https://fgsever.ru/images/claims/$order_number-$key.jpg");
  }

  $from = 'info@fgsever.ru';
  $from_name = 'FGSEVER';
  // $to = 'nikkorfed@gmail.com';
  $to = 'riverdale@inbox.ru';
  $blindCopyTo = 'nikkorfed@gmail.com';
  $subject = 'Претензия. ' . $name;

  // Инициализируем библиотеку
  $mail = new PHPMailer\PHPMailer\PHPMailer;
  $mail->CharSet = 'UTF-8';
  $mail->XMailer = ' ';

  // Параметры для отправки
  $mail->isSMTP();
  $mail->Host = 'ssl://smtp.yandex.ru';
  $mail->SMTPAuth = true;
  $mail->Username = 'info@fgsever.ru';
  $mail->Password = 'comandG06';
  $mail->Port = 465;

  $mail->setFrom($from, $from_name);
  $mail->addReplyTo($from, $from_name);
  $mail->addAddress($to);
  $mail->addBCC($blindCopyTo);

  // foreach ($images as $url) $mail->addAttachment($url);

  $mail->isHTML(true);
  $mail->Subject = $subject;

  // Добавление изображений
  if (count($images)) {
    $items = '';
    foreach ($images as $url) $items .= "<img class=\"photo\" src=$url>";
    $imagesBlock = "
      <div class=\"section last\">
        <div class=\"label\">Фотографии:</div>
        $items
      </div>
    ";
  }

  // Содержание письма
  $mail->Body = "
    <html>
      <head>
        <title>$subject</title>
        <style>

          body {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', 'Arial';
            font-size: 15px;
            line-height: 1.5;
            color: #333;
            -webkit-font-smoothing: antialiased;
          }

          .wrapper {
            margin: auto;
            padding: 20px 15px;
            max-width: 600px;
          }

          .main {
            border-radius: 5px;
            background-color: white;
          }

          .main .logo {
            display: block;
            border-bottom: 1px solid #eee;
            height: 60px;
            background: url('http://m72sever.nikkorfed.ru/images/logo.png') 50% 50% no-repeat;
            background-size: 140px;
          }

          .main .content {
            padding: 20px;
          }

          .main .content h1 {
            margin: 0 0 20px;
            font-size: 20px;
            text-align: center;
          }

          .main .content h2 {
            margin: 0 0 10px;
            font-size: 18px;
          }

          .main .content p {
            margin: 0 0 10px;
          }

          .main .content .link {
            text-decoration: none;
            color: #0088cb;
          }

          .main .content .section {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
          }

          .main .content .section.last {
            border: 0;
            padding: 0;
          }

          .main .content .label {
            color: #888;
          }

          .main .content .number {
            white-space: nowrap;
            color: #aaa;
          }

          .main .content .nowrap {
            white-space: nowrap;
          }

          .main .content .photo {
            overflow: hidden;
            margin-bottom: 15px;
            border-radius: 5px;
            width: 100%;
          }

        </style>
      </head>
      <body style=\"background-color: #f8f8f8;\">
        <div class=\"wrapper\">
          <div class=\"main\" style=\"box-shadow: 0 0 15px 0 rgba(69, 69, 69, .05);\">
            <a class=\"logo\"></a>
            <div class=\"content\">
              <h1>Претензия</h1>
              <div class=\"section\">
                <div class=\"label\">Фамилия, имя и отчество:</div>
                <div class=\"text\">$name</div>
              </div>
              <div class=\"section\">
                <div class=\"label\">Телефон:</div>
                <div class=\"text\"><a class=\"link\" href=\"callto:$phone_link\">$phone</a></div>
              </div>
              <div class=\"section\">
                <div class=\"label\">Номер заказ-наряда:</div>
                <div class=\"text\">$order_number</div>
              </div>
              <div class=\"section last\">
                <div class=\"label\">Причина обращения:</div>
                <div class=\"text\">$reason</div>
              </div>
              $imagesBlock
            </div>
          </div>
        </div>
      </body>
    </html>
  ";

  if($mail->send()) echo "Письмо было успешно отправлено.\n";

  // Отправка уведомления в Telegram

  $ch = curl_init();

  $data = [
    'notifyAdmins' => '',
    'token' => '1204592494:AAFQwGuvtIygZ_4gpu2QZsMxDYPrZgkJvug',
    'text' => "*Претензия с сайта FGSEVER.*\n\n*ФИО:* $name\n*Телефон:* $phone\n*Номер заказ-наряда*: $order_number\n*Причина обращения:* $reason",
    'images' => $images,
    'parameters' => [ 'parse_mode' => 'Markdown' ]
  ];

  curl_setopt($ch, CURLOPT_URL, "https://fgsever.ru/scripts/php/bot.php");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

  if (curl_exec($ch)) echo 'Уведомление администраторам в Telegram было успешно отправлено!';
  curl_close($ch);

}

?>
