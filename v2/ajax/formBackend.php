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
if (isset($data['input_form_temp'])) {
  $data['input_form_temp'] = base64_encode($data['input_form_temp']);
}



$result = [];
try {
  // $business = selectContent($conn, "read_businesses", ['id' => $data['business_id']]);
  if (isset($data['action'])) {
    $userId = $_SESSION['id'];
    if (!isset($data['active'])) {
      if (isset($_SESSION['edit'])) {
        unset($_SESSION['edit']);
      }
    }
    $input_form_no = selectContentDesc($conn, "panel_forms", ['user_id' => $userId,'business_id'=>$data['business_id']],'id',1);
    // die(var_dump($countForm));
    if (isset($_SESSION['edit'])) {
      $check = selectContent($conn,'panel_forms',['business_id'=>$data['business_id'],'id'=>$_SESSION['formId']]);
      $countForm = $check[0]['sequence'];
    }else{
      if (count($input_form_no) > 0) {
        $countForm = $input_form_no[0]['sequence'];
      }else{
        $countForm = 0;
      }
      $check = selectContent($conn,'panel_forms',['business_id'=>$data['business_id'],'sequence'=>$countForm],'id',1);

  }
  // die(var_dump($countForm));
    if ($countForm > 0) {
      $seq = $check[0]['sequence'] + 1;
      // die("valid");
    }else{
      // die("invalid");
      $seq = 1;
    }
    // die(var_dump($countForm));
    $hash = "abcdefghijklmnopqrstuvw".rand(0000000000,9999999999);
    $hashh = str_shuffle($hash);
    $hash_id = "FormId".$hashh;
    // die(print_r($data));
    $data = cleanInsert($data);
    $data['hash_id'] = $hash_id;
    $data['sequence'] = $seq;
    $data['user_id'] = $userId;
    // die(print_r($countForm));

    if($data['action'] == 1){
      if (!isset($data['active'])) {
        unset($data['action']);

        // unset($data['active']);
        $stmt = $conn->prepare('insert into panel_forms (`hash_id`,`user_id`,`business_id`,`input_form_name`,`sequence`,`time_created`,`date_created`) values (:hash_id,:user_id,:business_id,:input_form_name,:sequence,NOW(),NOW())');
        if($stmt->execute($data)){
          $result["success"] = true;
          // die(var_dump($result));
          $data['action'] = 1;
          $result['action'] = 2;
        }
      }else{
        // die("I got here");


          $stmt = $conn->prepare("update panel_forms set input_form_name = :fn where business_id = :bi and sequence =:seq");
          $stmt->bindParam(":seq",$countForm);

        // $rew = "fovt";
        $stmt->bindParam(":fn",$data['input_form_name']);
        // $stmt->bindParam(":cl",$data['campaign_link']);
        $stmt->bindParam(":bi",$data['business_id']);
        if($stmt->execute()){
          $result["success"] = true;
          // die("I got here
          $result['action'] = 2;
        }
      }

    }
    if ($data['action'] == 2) {
      // var_dump($_SESSION);

          // die("i got here");
          // die(var_dump($data));
          $stmt = $conn->prepare('UPDATE panel_forms SET input_form_temp=:ft WHERE sequence=:seq AND business_id=:bus_id');
        $arr = [
          "ft"=>$data['input_form_temp'],
          "bus_id"=>$data['business_id'],
          "seq"=>$countForm
        ];
        if($stmt->execute($arr)){
          $result['success'] = true;
          $result['action'] = 2;
        };

    }

    if ($data['action'] == 3) {
      // if ($data['type'] == 1) {
        $stmt = $conn->prepare('UPDATE panel_forms SET input_form_submission_title=:it,text_form_submission_message=:tm,input_form_submission_redirect=:sr WHERE sequence=:seq AND business_id=:bus_id');
      $arr = [
        "it"=>$data['input_form_submission_title'],
        "tm"=>$data['text_form_submission_message'],
        "sr"=>$data['input_form_submission_redirect'],
        "bus_id"=>$data['business_id'],
        "seq"=>$countForm
      ];
    // }elseif ($data['type'] == 2) {
      // die(print_r($data));
      // var_dump("link");
    //   $stmt = $conn->prepare('UPDATE panel_forms SET input_form_submission_redirect=:sr WHERE sequence=:seq AND business_id=:bus_id');
    // $arr = [
    //   "sr"=>$data['input_form_submission_redirect'],
    //   "bus_id"=>$data['business_id'],
    //   "seq"=>$countForm
    // ];
  // }
          // die("i got here");

        if($stmt->execute($arr)){
          $result['success'] = true;
          $result['action'] = 4;
        };

    }
    if($data['action'] == 5){
      if(count($check) > 0){
        // var_dump($countForm);
        $update  = $conn->prepare('UPDATE panel_forms SET input_form_name=:fm, input_form_temp=:ft,status=1 WHERE sequence=:seq AND business_id=:bus_id');
        $arr = [
          ":fm"=>$data['input_form_name'],
          ":ft"=>$data['input_form_temp'],
          "bus_id"=>$data['business_id'],
          "seq"=>$countForm
        ];
        if($update->execute($arr)){
          $result['success'] = true;
          $formName = str_replace(" ","-",$check[0]['input_form_name']);
          $result['hash'] = "cmp=".$formName."&data=".base64_encode($check[0]['business_id'])."&formId=".base64_encode($check[0]['id']);
        }

      }else{
        $result['error'] = true;
      }
    }

  }

  if (isset($data['verify']) && $data['verify'] == 1) {
      $verify = verifyCampaignLink($conn,$data['formId'],$data['hash'],$data['used']);
      if ($verify['success'] == true) {
        $result['success'] = true;
        $result['redirect'] = $verify['redirect'];
      }
}
if (isset($data['reg']) && $data['reg'] == 1) {
  $userId = createHash();

      // if (isset($data['refId'])) {
      //   $up = $conn->
      // }
      $fetch = selectContent($conn,"campaigns",['id'=>base64_decode($data['formId'])]);
      $business = selectContent($conn, "read_businesses", ['id' => $fetch[0]['business_id']]);
      $campName = str_replace(" ","-",$fetch[0]['campaign_name']);
        $email = $data['email'];
        $name = $data['name'];
        $to = strip_tags($email);
        $subject = $fetch[0]['campaign_name']." Referral Campaign";
        $txt = "Hello $name, Thanks for signing up to our Campaign, Follow this link to get your referral link as well";
        if($fetch[0]['invite_reward_name'] !== ""){
          $txt .= " and get rewards for every of your friends you refer <br>";
        }
        $txt .= 'https://'.$_SERVER['HTTP_HOST']."/refer?cmp=".$campName."&data=".base64_encode($fetch[0]['business_id'])."&formId=".base64_encode($fetch[0]['id'])."&hash=".$fetch[0]['hash_id']."&refId=".$userId;


     // die(var_dump($txt));






        // $headers = "From: ".$site_email . "\r\n" .
        // "CC: banjimayowa@gmail.com";
        //
        // mail($to,$subject,$txt,$headers);


        require APP_PATH.'/phpm/PHPMailerAutoload.php';

        // If necessary, modify the path in the require statement below to refer to the
        // location of your Composer autoload.php file.
        // require 'phpm/PHPMailerAutoload.php';


        // Instantiate a new PHPMailer
        $mail = new PHPMailer;

        // Tell PHPMailer to use SMTP
        $mail->IsSMTP(true);
        $mail->SMTPDebug  = 2;

        // Replace sender@example.com with your "From" address.
        // This address must be verified with Amazon SES.
        $mail->setFrom($business[0]['input_business_email'], $business[0]['input_business_name']);
        $mail->AddReplyTo($business[0]['input_business_email'], $business[0]['input_business_name']);

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
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Tells PHPMailer to send HTML-formatted email
        $mail->isHTML(true);

        // The alternative email body; this is only displayed when a recipient
        // opens the email in a non-HTML email client. The \r\n represents a
        // line break.
        $mail->AltBody = "Do not send a reply to this mail";

        if(!$mail->send()) {
          die(print_r($mail->ErrorInfo));
          $result['emailErr'] = true;
          // die(var_dump("Email not sent. " , $mail->ErrorInfo , PHP_EOL));

        } else {
          // echo "Email sent!" , PHP_EOL;

          $insert = $conn->prepare("insert into campaign_users values(null,:formId,:hash,:ri,:nm,:em,:ru,now(),now())");
          $ru = 0;
          $param = [
            'formId'=>base64_decode($data['formId']),
            'hash'=>$userId,
            'ri'=>$data['ref_id'],
            'nm'=>$data['name'],
            'em'=>$data['email'],
            'ru'=>$ru
          ];
          if($insert->execute($param)){
            $verify = verifyCampaignLink($conn,$data['formId'],$data['hash'],$data['ref_id']);
            $result['hash'] = "cmp=".$campName."&data=".base64_encode($fetch[0]['business_id'])."&formId=".base64_encode($fetch[0]['id'])."&refId=".$userId;
            $result['success'] = true;
            $result['redirect'] = $verify['redirect'];
          // exit();
        }
      }




    // if (isset($data['ref_id']) && $data['ref_id'] !== 0) {
    //   // code...
    //   $user = selectContent($conn,"campaign_users",['campaign_id'=>base64_decode($data['formId']),'hash_id'=>$data['ref_id']]);
    //   die(var_dump($user));
    //   $upUser = $user[0]['referred_users'] + 1;
    //   updateContent($conn,"campaign_users",['referred_users'=>$upUser],['id'=>$user[0]['id']]);
    // }
}
if (isset($data['edit']) && $data['edit'] == 1) {
$check = selectContent($conn,'campaigns',['id'=>base64_decode($data['formId'])]);
if (count($check) > 0) {
  $result['data'] = $check[0];
  // $ssl = explode("://",$check[0]['campaign_link']);
  // die(print_r($ssl));
  // code...
}
$_SESSION['edit'] = true;
$_SESSION['formId'] = $check[0]['id'];
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
// var_dump($result);
$res = json_encode($result);
echo $res;
