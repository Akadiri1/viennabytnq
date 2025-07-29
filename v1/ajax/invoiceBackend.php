<?php
// header("Content-Security-Policy: default-src 'none';");
// header("Access-Control-Allow-Origin: http://192.168.33.23/test.php");
// session_start();
//
// if (!function_exists('getallheaders')) {
//     function getallheaders() {
//     $headers = [];
//     foreach ($_SERVER as $name => $value) {
//         if (substr($name, 0, 5) == 'HTTP_') {
//             $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
//         }
//     }
//     return $headers;
//     }
// }
$request_headers = getallheaders();
if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}

if( !in_array($request_headers['Host'],["192.168.33.89","8f72-102-89-2-17.ngrok.io","192.168.33.99","digimart.org.ng","Mckodev.attendout.com"]) ){
  http_response_code(502);
  die;
}


$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json,true);


$result = [];
try {
  $business = selectContent($conn, "read_businesses", ['id' => $data['business_id']]);
  if (isset($data['action']) && $data['action'] == 8) {
    $result['data'] = unserialize($business[0]['customers']);
    // $result['paypal_email'] = $business[0]['paypal_email'];
    $result['success'] = true;
  }
  if (isset($data['action']) && $data['action'] == 6) {
    $arr = [
      "flutterwave_key"=>$data['flwKey'],
      "flutterwave_secret"=>$data['flwSec'],
      "paypal_email"=>$data['pEmail']
    ];
    $set = serialize($arr);
    updateContent($conn,'read_businesses',["payment_settings"=>$set],["id"=>$data['business_id']]);
    $result['success'] = true;
  }
  if (isset($data['action']) && $data['action'] == 7) {

    $arr = [];
    if ($business[0]['customers'] == NULL || $business[0]['customers'] == "") {
      $arr[1] = [
        "customer_name"=>$data['customerName'],
        "customer_email"=>$data['customerEmail']
      ];
      $customers = $arr;
      print_r($customers);
    }else{

      $custom = unserialize($business[0]['customers']);
      $count = count($custom) + 1;
      $arr[$count] = [
        "customer_name"=>$data['customerName'],
        "customer_email"=>$data['customerEmail']
      ];
      // print_r($custom);
      $customers = array_merge($custom,$arr);
      // die(print_r($customers));
    }
    $cus = serialize($customers);


    $update = $conn->prepare("update read_businesses set customers=:cus where id=:id");
    $update->bindParam(":id",$business[0]['id']);
    $update->bindParam(":cus",$cus);
    $update->execute();
    // die(print_r($customers));
    $result['success'] = true;
  }
  if (isset($data['action']) && $data['action'] == 9) {
    unset($_SESSION['items'][$data['key']]);
    // print_r($_SESSION['items']);
    $result['success'] = true;
  }
  if (isset($data['active'])) {
    $userId = $_SESSION['id'];
    if (!isset($data['active'])) {
      if (isset($_SESSION['edit'])) {
        unset($_SESSION['edit']);
      }
    }
    // $campaign_no = selectContent($conn, "campaigns", ['user_id' => $userId,'business_id'=>$data['business_id']]);
    // die(var_dump($countCampaign));



    // die(var_dump($countCampaign));
    $hash = "abcdghimnorstuvw".rand(00000000,99999999);
    $hashh = str_shuffle($hash);
    $hash_id = "INV".$hashh;
    // die(print_r($data));
    $data = cleanInsert($data);
    $data['hash_id'] = $hash_id;
    $data['user_id'] = $userId;


    if ($data['active'] == 3 && isset($_SESSION['items'])) {

      $userId = createHash();

          // if (isset($data['refId'])) {
          //   $up = $conn->
          // }

          // $campName = str_replace(" ","-",$fetch[0]['campaign_name']);
            $email = $data['billToEmail'];
            $name = $data['billToName'];
            $to = strip_tags($email);
            $subject = "Invoice - INV-".$data['invoice_no']." from ".$data['billFromName'];
            $txt = "<p>Dear $name,<br> Thank you for your business. Your Invoice can be viewed and printed from the link below. You can choose to pay it online.</p>";

            $txt .= 'https://'.$_SERVER['HTTP_HOST']."/view-invoice?inv=".base64_encode($data['invoice_no'])."&data=".base64_encode($data['business_id']);
            $result['hash'] = base64_encode($data['invoice_no'])."&data=".base64_encode($data['business_id']);



            require APP_PATH.'/phpm/PHPMailerAutoload.php';

            // If necessary, modify the path in the require statement below to refer to the
            // location of your Composer autoload.php file.
            // require 'phpm/PHPMailerAutoload.php';


      // die(var_dump($data['discount']));
      $mail = new PHPMailer;

      // Tell PHPMailer to use SMTP
      $mail->IsSMTP(true);
      // $mail->SMTPDebug  = 2;

      // Replace sender@example.com with your "From" address.
      // This address must be verified with Amazon SES.
      $mail->setFrom($data['billFromEmail'], $data['billFromName']);
      $mail->AddReplyTo($data['billFromEmail'], $data['billFromName']);

      // Replace recipient@example.com with a "To" address. If your account
      // is still in the sandbox, this address must be verified.
      // Also note that you can include several addAddress() lines to send
      // email to multiple recipients.
      $mail->addAddress($to, $name);

      // Replace smtp_username with your Amazon SES SMTP user name.
      $mail->Username = $site_email;

      // Replace smtp_password with your Amazon SES SMTP password.
      $mail->Password = getenv("EMAIL_PASSWORD");
      // die(var_dump($mail->Password));

      // Specify a configuration set. If you do not want to use a configuration
      // set, comment or remove the next line.
      // $mail->addCustomHeader('X-SES-CONFIGURATION-SET', 'ConfigSet');

      // If you're using Amazon SES in a region other than US West (Oregon),
      // replace email-smtp.us-west-2.amazonaws.com with the Amazon SES SMTP
      // endpoint in the appropriate region.
      $mail->Host = 'smtp.gmail.com';

      // The subject line of the email
      $mail->Subject = $subject;

      // The HTML-formatted body of the email
      $mail->Body = $txt;

      // Tells PHPMailer to use SMTP authentication
      $mail->SMTPAuth = true;

      // Enable TLS encryption over port 587
      $mail->SMTPSecure = 'tls';
      $mail->Port = 587;

      // Tells PHPMailer to send HTML-formatted email
      $mail->isHTML(true);

      // The alternative email body; this is only displayed when a recipient
      // opens the email in a non-HTML email client. The \r\n represents a
      // line break.
      $mail->AltBody = "Do not send a reply to this mail";

      if(!$mail->send()) {
        // die(print_r($mail));
        // die(var_dump("Email not sent. " , $mail->ErrorInfo , PHP_EOL));
        $stat = 3;
        $items = serialize($_SESSION['items']);
        $stmt = $conn->prepare('insert into panel_invoices values (NULL,:hash_id,:inv_no,:bus_id, :ibfn,:ibfe,:ibtn,:ibte,:ii,:id,:tax,:is,:it,:cur,:if,:ir,:ip,:ipa,:ire,:in,:ida,:idd,:pay_stat,:stat,:p_time,:p_date,NOW(),NOW())');
        $arr = [
        "hash_id"=>$hash_id,
        "inv_no"=>$data['invoice_no'],
        "bus_id"=>$data['business_id'],
        "ibfn"=>$data['billFromName'],
        "ibfe"=>$data['billFromEmail'],
        "ibtn"=>$data['billToName'],
        "ibte"=>$data['billToEmail'],
        "ii"=>$items,
        "id"=>$data['discount'],
        "tax"=>$data['tax'],
        "is"=>$data['subtotal'],
        "it"=>$data['total'],
        "cur"=>$data['currency'],
        "if"=>$data['flutterwave'],
        "ir"=>$data['remita'],
        "ip"=>$data['paypal'],
        "ipa"=>$data['paystack'],
        "ire"=>$data['reoccur'],
        "in"=>$data['notes'],
        "ida"=>$data['invoiceDate'],
        "idd"=>$data['invoiceDueDate'],
        "pay_stat"=>0,
        "p_time"=>NULL,
        "p_date"=>NULL,
        "stat"=>$stat
        ];
        $stmt->execute($arr);
        $result['emailErr'] = true;

      }else{
        $stat = 1;
        $items = serialize($_SESSION['items']);
        $stmt = $conn->prepare('insert into panel_invoices values (NULL,:hash_id,:inv_no,:bus_id, :ibfn,:ibfe,:ibtn,:ibte,:ii,:id,:is,:it,:cur,:if,:ir,:ip,:ipa,:ire,:in,:ida,:idd,:pay_stat,:stat,NOW(),NOW())');
        $arr = [
        "hash_id"=>$hash_id,
        "inv_no"=>$data['invoice_no'],
        "bus_id"=>$data['business_id'],
        "ibfn"=>$data['billFromName'],
        "ibfe"=>$data['billFromEmail'],
        "ibtn"=>$data['billToName'],
        "ibte"=>$data['billToEmail'],
        "ii"=>$items,
        "id"=>$data['discount'],
        "is"=>$data['subtotal'],
        "it"=>$data['total'],
        "cur"=>$data['currency'],
        "if"=>$data['flutterwave'],
        "ir"=>$data['remita'],
        "ip"=>$data['paypal'],
        "ipa"=>$data['paystack'],
        "ire"=>$data['reoccur'],
        "in"=>$data['notes'],
        "ida"=>$data['invoiceDate'],
        "idd"=>$data['invoiceDueDate'],
        "pay_stat"=>0,
        "stat"=>$stat
        ];
        $stmt->execute($arr);

        $result['success'] = true;
    }

    }else{
      $result['emptyItems'] = true;

    }
    if($data['active'] == 0){
      unset($_SESSION['items']);
      unset($_SESSION['customers']);

      $_SESSION['items'][] = [
        "item_name"=>$data['item_name'],
        "item_desc"=>$data['item_desc'],
        "item_price"=>$data['item_price'],
        "item_quantity"=>$data['item_quantity'],
        "item_total"=>$data['item_total']
      ];
      // $param = [];
      // $param['hash_id'] = $hash_id;
      // $param['business_id'] = $data['business_id'];
      // $param['invoice_no'] = $data['invoice_no'];
      //   $stmt = $conn->prepare('insert into panel_invoices (`hash_id`,`business_id`,`invoice_no`,`time_created`,`date_created`) values (:hash_id,:business_id,:invoice_no,NOW(),NOW())');
        // unset($data['active']);

          $result["success"] = true;


    }
    if ($data['active'] == 1) {
      $_SESSION['items'][] = [
        "item_name"=>$data['item_name'],
        "item_desc"=>$data['item_desc'],
        "item_price"=>$data['item_price'],
        "item_quantity"=>$data['item_quantity'],
        "item_total"=>$data['item_total']
      ];

          $result['success'] = true;
          // die(print_r($_SESSION['items']));

    }




  }


if (isset($data['edit']) && $data['edit'] == 1) {
$check = selectContent($conn,'campaigns',['id'=>base64_decode($data['campId'])]);
if (count($check) > 0) {
  $result['data'] = $check[0];
  // $ssl = explode("://",$check[0]['campaign_link']);
  // die(print_r($ssl));
  // code...
}
$_SESSION['edit'] = true;
$_SESSION['campId'] = $check[0]['id'];
$result['success'] = true;
}
if (isset($data['del']) && $data['del'] == 1) {
  $cId = $data['id'];
  die(var_dump($cId));
$delete = $conn->prepare("delete from campaigns where id = $cId");
$delete->execute();
$result['success'] = true;
}
} catch (PDOException $e) {
  // var_dump($data);
  die($e);
    http_response_code(409);
    $result["error"] = true;
    die;
}

$res = json_encode($result);
echo $res;
