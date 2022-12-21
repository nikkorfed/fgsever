<?

include 'libraries/PHPMailer.php';
include 'libraries/Exception.php';
include 'libraries/SMTP.php';

if (isset($_POST['name']) && isset($_POST['phone'])) {

  // Собираем данные из формы
  $name = $_POST['name'];
  $phone = $_POST['phone'];
  $phoneLink = str_replace([' ', '(', ')', '-'], '', $phone);

  $from = 'info@fgsever.ru';
  $fromName = 'FGSEVER';
  $to = 'riverdale@inbox.ru';
  $blindCopyTo = 'nikkorfed@gmail.com';
  $subtitle = $_POST['service'];
  $content = '';

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

  $mail->setFrom($from, $fromName);
  $mail->addReplyTo($from, $fromName);
  $mail->addAddress($to);
  $mail->addBCC($blindCopyTo);
  $mail->isHTML(true);

  // Добавление данных из мобильного приложения
  if (
      isset($_POST['car']) && isset($_POST['service']) &&
      !isset($_POST['codingOptions']) && !isset($_POST['maintenanceParts']) &&
      !isset($_POST['orderParts']) && !isset($_POST['upgradeOptions'])
  ) {

    $car = "";
    foreach ($_POST['car'] as $key => $spec) {
      $last = $key == array_key_last($_POST['car']) ? ' last' : '';
      $car .= "
        <div class=\"section$last\">
          <div class=\"label\">$spec[0]</div>
          <div class=\"text\">$spec[1]</div>
        </div>
      ";
    }

    $content = "
      <div class=\"block\">
        <h2>Автомобиль</h2>
        $car
      </div>
    ";
  }

  // Добавление кодируемых опций
  if (isset($_POST['codingOptions'])) {
    switch ($_POST['car']) {
      case 'g-series':
        $car = 'G серии';
        break;
      case 'f-series':
        $car = 'F серии';
        break;
    }

    $codingOptions = '';
    foreach ($_POST['codingOptions'] as $option) {
      $codingOptions .= "<div class=\"coding-option\">$option</div>";
    }

    $content = "
      <div class=\"block\">
        <h2>Опции для кодирования</h2>
        $codingOptions
      </div>
    ";
  }

  // Добавление расчёта из калькулятора ТО
  if (isset($_POST['maintenanceParts'])) {
    $subtitle = 'Расчёт в калькуляторе ТО';

    $car = "";
    foreach ($_POST['car'] as $key => $spec) {
      $last = $key == array_key_last($_POST['car']) ? ' last' : '';
      $car .= "
        <div class=\"section$last\">
          <div class=\"label\">$spec[0]</div>
          <div class=\"text\">$spec[1]</div>
        </div>
      ";
    }

    $parts = ""; $partsCost = 0; $worksCost = 0; $totalCost = 0;
    foreach ($_POST['maintenanceParts'] as $key => $part) {

      $partName = $part['name'];
      if (isset($part['quantity'])) {
        $partQuantity = $part['quantity'] . ' x ';
        $partName .= ', ' . $part['quantity'] . ' ' . $part['quantityLabel'];
      } else $partQuantity = '';
      if (isset($part['number'])) {
        $partNumber = $part['brand'] . ', ' . $part['number'] . ' (' . ucfirst($part['from']) . ')';
      } else $partNumber = $part['brand'];
      if (isset($part['partPrice'])) {
        $partPrice = ', детали — ' . $partQuantity . number_format($part['partPrice'], 2, ',', ' ') . ' ₽';
        if (isset($part['quantity'])) {
          $partsCost += $part['quantity'] * $part['partPrice'];
          $totalCost += $part['quantity'] * $part['partPrice'];
        } else { 
          $partsCost += $part['partPrice'];
          $totalCost += $part['partPrice'];
        }
      } else $partPrice = '';
      $prices = 'Работа — ' . number_format($part['workPrice'], 2, ',', ' ') . ' ₽' . $partPrice;
      $worksCost += $part['workPrice'];
      $totalCost += $part['workPrice'];

      $parts .= "
        <div class=\"section\">
          <div class=\"text\">$partName</div>
          <div class=\"number\">$partNumber</div>
          <div class=\"label\">$prices</div>
        </div>
      ";
    }

    $parts .= "
      <div class=\"parts-cost\">
        <div class=\"label\">Стоимость деталей</div>
        <div class=\"value\">" . number_format($partsCost, 2, ',', ' ') . " ₽</div>
      </div>
      <div class=\"works-cost\">
        <div class=\"label\">Стоимость работ</div>
        <div class=\"value\">" . number_format($worksCost, 2, ',', ' ') . " ₽</div>
      </div>
      <div class=\"total-cost\">
        <div class=\"label\">Общая стоимость</div>
        <div class=\"value\">" . number_format($totalCost, 2, ',', ' ') . " ₽</div>
      </div>
    ";

    $content = "
      <div class=\"block\">
        <h2>Автомобиль</h2>
        $car
      </div>
      <div class=\"block\">
        <h2>$subtitle</h2>
        $parts
      </div>
    ";
  }

  // Добавление запчастей из поиска запчастей
  if (isset($_POST['orderParts'])) {
    $subtitle = 'Поиск запчастей и их аналогов';

    $parts = ""; $partsCost = 0;
    foreach ($_POST['orderParts'] as $key => $part) {

      $partName = $part['name'];
      if (isset($part['number'])) {
        $partNumber = $part['brand'] . ', ' . $part['number'] . ' (' . ucfirst($part['from']) . ')';
      } else $partNumber = $part['brand'];
      if (isset($part['partPrice'])) {
        $partPrice = 'Стоимость — ' . number_format($part['partPrice'], 2, ',', ' ') . ' ₽';
        $partsCost += $part['partPrice'];
      }

      $parts .= "
        <div class=\"section\">
          <div class=\"text\">$partName</div>
          <div class=\"number\">$partNumber</div>
          <div class=\"label\">$partPrice</div>
        </div>
      ";
    }

    $parts .= "
      <div class=\"parts-cost\">
        <div class=\"label\">Общая стоимость</div>
        <div class=\"value\">" . number_format($partsCost, 2, ',', ' ') . " ₽</div>
      </div>
    ";

    $content = "
      <div class=\"block\">
        <h2>$subtitle</h2>
        $parts
      </div>
    ";
  }

  // Добавление расчёта из калькулятора дооснащения
  if (isset($_POST['upgradeOptions'])) {
    echo '$_POST[\'upgradeOptions\']:' . json_encode($_POST['upgradeOptions'], JSON_PRETTY_PRINT);
    $subtitle = 'Расчёт в калькуляторе дооснащения';

    $car = "";
    foreach ($_POST['car'] as $key => $spec) {
      $last = $key == array_key_last($_POST['car']) ? ' last' : '';
      $car .= "
        <div class=\"section$last\">
          <div class=\"label\">$spec[0]</div>
          <div class=\"text\">$spec[1]</div>
        </div>
      ";
    }

    $upgradeOptions = ""; $totalCost = 0;
    foreach ($_POST['upgradeOptions'] as $option) {
      $optionName = (!empty($option['code']) ? ('<span class="number">' . $option['code'] . '</span> ') : '') . $option['name'];
      $totalCost = $totalCost + $option['price'];
      $optionPrice = $option['shownPrice'];
      $last = $key == array_key_last($_POST['upgradeOptions']) ? ' last' : '';
      $upgradeOptions .= "
        <div class=\"section$last\">
          <div class=\"text\">$optionName</div>
          <div class=\"label\">$optionPrice</div>
        </div>
      ";
    }

    $upgradeOptions .= "
      <div class=\"total-cost\">
        <div class=\"label\">Общая стоимость</div>
        <div class=\"value\">" . number_format($totalCost, 2, ',', ' ') . " ₽</div>
      </div>
    ";

    $content = "
      <div class=\"block\">
        <h2>Автомобиль</h2>
        $car
      </div>
      <div class=\"block\">
        <h2>$subtitle</h2>
        $upgradeOptions
      </div>
    ";
  }

  // Содержание письма
  $subject = 'Заявка. ' . $subtitle;
  $mail->Subject = $subject;
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

          .background {
            height: 100%;
            background-color: #f8f8f8;
          }

          .wrapper {
            margin: auto;
            padding: 20px 15px;
            max-width: 600px;
          }

          .logo {
            display: block;
            border-bottom: 1px solid #eee;
            height: 60px;
            background: url('http://fgsever.ru/images/logo.png') 50% 50% no-repeat;
            background-size: 140px;
          }

          h1 {
            margin: 0 0 20px;
            font-size: 20px;
            text-align: center;
          }

          .subtitle {
            margin: -20px 0 20px;
            font-size: 16px;
            text-align: center;
            color: #888;
          }

          .block {
            overflow-y: auto;
            margin-bottom: 20px;
            border-radius: 5px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 15px 0 rgba(69, 69, 69, .05);
          }

          h2 {
            margin: 0 0 10px;
            font-size: 18px;
          }

          p {
            margin: 0 0 10px;
          }

          .link {
            text-decoration: none;
            color: #1c69d4;
          }

          .section {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
          }

          .section.last {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
          }

          .label {
            color: #888;
          }

          .number {
            white-space: nowrap;
            color: #aaa;
          }

          .nowrap {
            white-space: nowrap;
          }

          .value {
            float: right;
            font-weight: bold;
            color: #333;
          }

          .parts-cost, .works-cost, .total-cost {
            clear: both;
          }

          .parts-cost .label, .works-cost .label, .total-cost .label {
            float: left;
          }

          .total-cost {
            margin-bottom: 15px;
          }

        </style>
      </head>
      <body>
        <div class=\"background\">
          <div class=\"wrapper\">
            <h1>Заявка</h1>
            <div class=\"block\">
              <h2>Клиент</h2>
              <div class=\"section\">
                <div class=\"label\">Фамилия, имя и отчество</div>
                <div class=\"text\">$name</div>
              </div>
              <div class=\"section last\">
                <div class=\"label\">Телефон</div>
                <div class=\"text\"><a class=\"link\" href=\"callto:$phoneLink\">$phone</a></div>
              </div>
            </div>
            <div class=\"block\">
              <h2>Услуга</h2>
              <div class=\"section last\">
                <div class=\"label\">Вид работ</div>
                <div class=\"text\">$subtitle</div>
              </div>
            </div>
            $content
          </div>
        </div>
      </body>
    </html>
  ";

  if($mail->send()) {
    echo "Письмо было успешно отправлено.";
  }

}

?>
