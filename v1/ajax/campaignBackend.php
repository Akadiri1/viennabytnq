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
  // $business = selectContent($conn, "read_businesses", ['id' => $data['business_id']]);
  if (isset($data['action'])) {
    $userId = $_SESSION['id'];
    if (!isset($data['active'])) {
      if (isset($_SESSION['edit'])) {
        unset($_SESSION['edit']);
      }
    }
    $campaign_no = selectContent($conn, "campaigns", ['user_id' => $userId,'business_id'=>$data['business_id']]);
    // die(var_dump($countCampaign));
    if (isset($_SESSION['edit'])) {
      $check = selectContent($conn,'campaigns',['business_id'=>$data['business_id'],'id'=>$_SESSION['campId']]);
      $countCampaign = $check[0]['sequence'];
    }else{
      $countCampaign = count($campaign_no);
    $check = selectContent($conn,'campaigns',['business_id'=>$data['business_id'],'sequence'=>$countCampaign]);
  }
    if ($countCampaign > 0) {
      $seq = $countCampaign + 1;
      // die("valid");
    }else{
      // die("invalid");
      $seq = 1;
    }
    // die(var_dump($countCampaign));
    $hash = "abcdefghijklmnopqrstuvw".rand(0000000000,9999999999);
    $hashh = str_shuffle($hash);
    $hash_id = "CmpId".$hashh;
    // die(print_r($data));
    $data = cleanInsert($data);
    $data['hash_id'] = $hash_id;
    $data['sequence'] = $seq;
    $data['user_id'] = $userId;
    // die(print_r($countCampaign));

    if($data['action'] == 1){
      if (!isset($data['active'])) {
        unset($data['action']);

        // unset($data['active']);
        $stmt = $conn->prepare('insert into campaigns (`hash_id`,`user_id`,`business_id`,`campaign_name`,`campaign_link`,`sequence`,`time_created`,`date_created`) values (:hash_id,:user_id,:business_id,:campaign_name,:campaign_link,:sequence,NOW(),NOW())');
        if($stmt->execute($data)){
          $result["success"] = true;
          // die(var_dump($result));
          $data['action'] = 1;
          $result['action'] = 2;
        }
      }else{
        // die("I got here");


          $stmt = $conn->prepare("update campaigns set campaign_name = :cn, campaign_link = :cl where business_id = :bi and sequence =:seq");
          $stmt->bindParam(":seq",$countCampaign);

        // $rew = "fovt";
        $stmt->bindParam(":cn",$data['campaign_name']);
        $stmt->bindParam(":cl",$data['campaign_link']);
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

          $stmt = $conn->prepare('UPDATE campaigns SET invite_reward_name=:iR,referral_reward_name=:rR,referral_limit=:limitt WHERE sequence=:seq AND business_id=:bus_id');
        $arr = [
          "iR"=>$data['inviteReward'],
          "rR"=>$data['referralReward'],
          "limitt"=>$data['referLimit'],
          "bus_id"=>$data['business_id'],
          "seq"=>$countCampaign
        ];
        if($stmt->execute($arr)){
          $result['success'] = true;
          $result['action'] = 3;
        };

    }

    if ($data['action'] == 3) {
      if ($data['type'] == 2) {
        $stmt = $conn->prepare('UPDATE campaigns SET email_welcome_from=:ewf,email_welcome_subject=:ews,email_welcome_reply=:ewr,email_welcome_content=:ewc,email_welcome_test=:ewt,email_success_from=:esf,email_success_subject=:ess,email_success_reply=:esr,email_success_content=:esc,email_success_test=:est WHERE sequence=:seq AND business_id=:bus_id');
      $arr = [
        "ewf"=>$data['welcomeFrom'],
        "ews"=>$data['welcomeSubject'],
        "ewr"=>$data['welcomeReply'],
        "ewc"=>$data['welcomeContent'],
        "ewt"=>$data['welcomeTest'],
        "esf"=>$data['successFrom'],
        "ess"=>$data['successSubject'],
        "esr"=>$data['successReply'],
        "esc"=>$data['successContent'],
        "est"=>$data['successTest'],
        "bus_id"=>$data['business_id'],
        "seq"=>$countCampaign
      ];
    }elseif ($data['type'] == 1) {
      // die(print_r($data));
      $stmt = $conn->prepare('UPDATE campaigns SET email_welcome_from=:ewf,email_welcome_subject=:ews,email_welcome_reply=:ewr,email_welcome_content=:ewc,email_welcome_test=:ewt WHERE sequence=:seq AND business_id=:bus_id');
    $arr = [
      "ewf"=>$data['welcomeFrom'],
      "ews"=>$data['welcomeSubject'],
      "ewr"=>$data['welcomeReply'],
      "ewc"=>$data['welcomeContent'],
      "ewt"=>$data['welcomeTest'],
      "bus_id"=>$data['business_id'],
      "seq"=>$countCampaign
    ];
  }
          // die("i got here");

        if($stmt->execute($arr)){
          $result['success'] = true;
          $result['action'] = 4;
        };

    }
    if($data['action'] == 5){
      if(count($check) > 0){
        // var_dump($countCampaign);
        $update  = $conn->prepare('UPDATE campaigns SET status=1 WHERE sequence=:seq AND business_id=:bus_id');
        $arr = [
          "bus_id"=>$data['business_id'],
          "seq"=>$countCampaign
        ];
        if($update->execute($arr)){
          $result['success'] = true;
          $campName = str_replace(" ","-",$check[0]['campaign_name']);
          $result['hash'] = "cmp=".$campName."&data=".base64_encode($check[0]['business_id'])."&campId=".base64_encode($check[0]['id']);
        }

      }else{
        $result['error'] = true;
      }
    }

  }

  if (isset($data['verify']) && $data['verify'] == 1) {
      $verify = verifyCampaignLink($conn,$data['campId'],$data['hash'],$data['used']);
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
      $fetch = selectContent($conn,"campaigns",['id'=>base64_decode($data['campId'])]);
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
        $txt .= 'https://'.$_SERVER['HTTP_HOST']."/refer?cmp=".$campName."&data=".base64_encode($fetch[0]['business_id'])."&campId=".base64_encode($fetch[0]['id'])."&hash=".$fetch[0]['hash_id']."&refId=".$userId;


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
        // $mail->SMTPDebug  = 2;

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
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

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

          $insert = $conn->prepare("insert into campaign_users values(null,:campId,:hash,:ri,:nm,:em,:ru,now(),now())");
          $ru = 0;
          $param = [
            'campId'=>base64_decode($data['campId']),
            'hash'=>$userId,
            'ri'=>$data['ref_id'],
            'nm'=>$data['name'],
            'em'=>$data['email'],
            'ru'=>$ru
          ];
          if($insert->execute($param)){
            $verify = verifyCampaignLink($conn,$data['campId'],$data['hash'],$data['ref_id']);
            $result['hash'] = "cmp=".$campName."&data=".base64_encode($fetch[0]['business_id'])."&campId=".base64_encode($fetch[0]['id'])."&refId=".$userId;
            $result['success'] = true;
            $result['redirect'] = $verify['redirect'];
          // exit();
        }
      }




    // if (isset($data['ref_id']) && $data['ref_id'] !== 0) {
    //   // code...
    //   $user = selectContent($conn,"campaign_users",['campaign_id'=>base64_decode($data['campId']),'hash_id'=>$data['ref_id']]);
    //   die(var_dump($user));
    //   $upUser = $user[0]['referred_users'] + 1;
    //   updateContent($conn,"campaign_users",['referred_users'=>$upUser],['id'=>$user[0]['id']]);
    // }
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
