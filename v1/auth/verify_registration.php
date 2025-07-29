<?php

ob_start();
//session_start();
// die($site_name.$site_email.$description);

if(isset($_GET['token'])){


$stmt = $conn->prepare("SELECT * FROM verify WHERE token=:tk");
$data = [
  ":tk"=> $_GET['token']
];
$stmt->execute($data);
if($stmt->rowCount() < 1){
    die("error");
}else{

  $row = $stmt->fetch(PDO::FETCH_BOTH);
  extract($row);
  $hash_id = $email;

  $whereHash_id['email'] = $hash_id;
  $userinfo = selectContent($conn,'users',$whereHash_id);

// die(var_dump($userinfo));
  $stmt = $conn->prepare("UPDATE users SET user_status=:vs WHERE email=:gid");
  $ver = 1;
  // $status = 1;
  $stmt->bindParam(":gid",$hash_id);
  $stmt->bindParam(":vs",$ver);
  // $stmt->bindParam(":us",$status);
  $stmt->execute();

  $email = $userinfo[0]['email'];
  $firstname = $userinfo[0]['firstname'];
  $lastname = $userinfo[0]['lastname'];
// if($userinfo['level'] == 9 ){

  $txt2='
  <html>
  <head>
  <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0;">
  <link href="http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800" rel="stylesheet" type="text/css">
  <title>'.$site_name.'</title>

  <style type="text/css">

  [style*="Open Sans"] {font-family: "Open Sans", Arial, sans-serif !important}

  div, p, a, li, td { -webkit-text-size-adjust:none; }

  *{-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;}

  .ReadMsgBody
  {width: 100%; background-color: #ffffff;}
  .ExternalClass
  {width: 100%; background-color: #ffffff;}
  body{width: 100%; height: 100%; background-color: #ffffff; margin:0; padding:0; -webkit-font-smoothing: antialiased;}
  html{width: 100%; background-color: #ffffff;}


  p {padding: 0!important; margin-top: 0!important; margin-right: 0!important; margin-bottom: 0!important; margin-left: 0!important; }

  .hover:hover {opacity:0.85;filter:alpha(opacity=85);}
  .underline:hover {text-decoration: underline!important;}

  .jump:hover {opacity:0.75;filter:alpha(opacity=75);padding-top: 10px!important;}

  a.rotator img {-webkit-transition: all 1s ease-in-out; -moz-transition: all 1s ease-in-out; -o-transition: all 1s ease-in-out; -ms-transition: all 1s ease-in-out; }

  a.rotator img:hover {-webkit-transform: rotate(360deg); -moz-transform: rotate(360deg); -o-transform: rotate(360deg); -ms-transform: rotate(360deg); }

  @-webkit-keyframes bounceIn { 0% {opacity: 0;-webkit-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);transform: scale(1);}}

  @keyframes bounceIn {0% {opacity: 0;-webkit-transform: scale(.3);-ms-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);-ms-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);-ms-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);-ms-transform: scale(1);transform: scale(1);}}

  #box {-webkit-animation: bounceIn 2s; -moz-animation: bounceIn 2s; -o-animation: bounceIn 2s; animation: bounceIn 2s; }

  @-webkit-keyframes bounceIn {0% {opacity: 0;-webkit-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);transform: scale(1);}}

  @keyframes bounceIn {0% {opacity: 0;-webkit-transform: scale(.3);-ms-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);-ms-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);-ms-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);-ms-transform: scale(1);transform: scale(1);}}

  #box2 { -webkit-animation: bounceIn 1.5s; -moz-animation: bounceIn 1.5s; -o-animation: bounceIn 1.5s; animation: bounceIn 1.5s; }

  @-webkit-keyframes bounceIn { 0% {opacity: 0;-webkit-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);transform: scale(1);}}

  @keyframes bounceIn {0% {opacity: 0;-webkit-transform: scale(.3);-ms-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);-ms-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);-ms-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);-ms-transform: scale(1);transform: scale(1);}}

  #box3 {-webkit-animation: bounceIn 2s; -moz-animation: bounceIn 2s; -o-animation: bounceIn 2s; animation: bounceIn 2s; }

  @-webkit-keyframes bounceIn {0% {opacity: 0;-webkit-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);transform: scale(1);}}

  @keyframes bounceIn {0% {opacity: 0;-webkit-transform: scale(.3);-ms-transform: scale(.3);transform: scale(.3);}50% {opacity: 1;-webkit-transform: scale(1.05);-ms-transform: scale(1.05);transform: scale(1.05);}70% {-webkit-transform: scale(.9);-ms-transform: scale(.9);transform: scale(.9);}100% {opacity: 1;-webkit-transform: scale(1);-ms-transform: scale(1);transform: scale(1);}}

  #box4 { -webkit-animation: bounceIn 1.5s; -moz-animation: bounceIn 1.5s; -o-animation: bounceIn 1.5s; animation: bounceIn 1.5s; }

  #logo img {width: 125px; height: auto;}
  #logo_footer img {width: 100px; height: auto;}
  .image175 img {width: 175px; height: auto;}
  .icon34 img {width: 34px; height: auto;}
  #icon13 img {width: 13px; height: auto;}
  .icon30 img {width: 30px; height: auto;}
  .image191 img {width: 191px; height: auto; border-radius: 4px;}
  .avatar83 img {width: 83px; height: auto;}
  .image194 img {width: 194px; height: auto; border-radius: 4px;}
  .icon37 img {width: 37px; height: auto;}
  .image400 img {width: 400px; height: auto;}
  .image600 img {width: 600px; height: auto; border-radius: 4px;}
  .image185 img {width: 185px; height: auto; border-radius: 4px;}
  .image352 img {width: 352px; height: auto;}
  .icons17 img {width: 17px; height: auto;}

  @media only screen and (max-width: 640px){
  		body{width:auto!important;}
  		table[class=full] {width: 100%!important; clear: both; }
  		table[class=mobile] {width: 100%!important; padding-left: 20px; padding-right: 20px; clear: both; }
  		table[class=fullCenter] {width: 100%!important; text-align: center!important; clear: both; }
  		td[class=fullCenter] {width: 100%!important; text-align: center!important; clear: both; }
  		*[class=erase] {display: none;}
  		*[class=buttonScale] {float: none!important; text-align: center!important; display: inline-block!important; clear: both;}
  		.[class=textLeft] {text-align: left!important; float: left!important;}
  		.h10 {height: 10px!important;}
  		table[class=scale33] {width: 30%!important;}
  		table[class=scale33Right] {width: 30%!important;}
  		table[class=scale33_2] {width: 32%!important;}
  		table[class=scale33Right_2] {width: 32%!important;}
  		table[class=scale50] {width: 46%!important;}
  		table[class=scale50Right] {width: 46%!important;}
  		.image185 img {width: 100%!important; height: auto;}
  		.image194 img {width: 100%!important; height: auto;}
  		.pad {width: 100%!important; padding-left: 15px!important; padding-right: 15px!important;}
  		table[class=w10] {width: 5%!important; }
  		table[class=w5] {width: 2%!important; }
  		.image600 img {width: 100%!important; height: auto!important; border-radius: 4px;}
  		.image400 img {width: 100%!important; height: auto;}
  		.image352 img {width: 100%!important; height: auto;}
  		.h40 {height: 40px!important;}
  		.h30 {height: 30px!important;}
  		td[class=pad20] {width: 100%!important; padding-left: 20px!important; padding-right: 20px!important; clear: both; }
  }

  @media only screen and (max-width: 479px){
  		body{width:auto!important;}
  		table[class=full] {width: 100%!important; clear: both; }
  		table[class=mobile] {width: 100%!important; padding-left: 20px; padding-right: 20px; clear: both; }
  		table[class=fullCenter] {width: 100%!important; text-align: center!important; clear: both; }
  		td[class=fullCenter] {width: 100%!important; text-align: center!important; clear: both; }
  		*[class=erase] {display: none;}
  		*[class=buttonScale] {float: none!important; text-align: center!important; display: inline-block!important; clear: both;}
  		.h10 {height: 10px!important;}
  		table[class=scale33] {width: 100%!important;}
  		table[class=scale33Right] {width: 100%!important;}
  		table[class=scale33_2] {width: 100%!important;}
  		table[class=scale33Right_2] {width: 100%!important;}
  		table[class=scale50] {width: 100%!important;}
  		table[class=scale50Right] {width: 100%!important;}
  		.image185 img {width: 100%!important; height: auto;}
  		.image194 img {width: 100%!important; height: auto;}
  		.pad {width: 100%!important; padding-left: 15px!important; padding-right: 15px!important;}
  		table[class=w10] {width: 100%;}
  		.image600 img {width: 100%!important; height: auto!important; border-radius: 4px;}
  		.image400 img {width: 100%!important; height: auto;}
  		.image352 img {width: 100%!important; height: auto;}
  		.h40 {height: 40px!important;}
  		.h30 {height: 30px!important;}
  		td[class=pad20] {width: 100%!important; padding-left: 20px!important; padding-right: 20px!important; clear: both; }
  }
  </style>

  </head>
  <body style="margin: 0; padding: 0;">


    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="full">
    	<tr>
    		<td width="100%" valign="top" bgcolor="#ffffff" align="center">


    			<!-- Wrapper -->
    			<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="mobile">
    				<tr>
    					<td align="center">

    						<!-- Space -->
    						<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" class="full">
    							<tr>
    								<td width="100%" height="50"></td>
    							</tr>
    						</table><!-- End Space -->

    						<!-- Wrapper -->
    						<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" class="full">
    							<tr>
    								<td width="100%" align="center">

    									<!-- Image 600 -->
    									<table width="600" border="0" cellpadding="0" cellspacing="0" align="left" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;" class="fullCenter">
    										<tr>
    											<td width="100%" class="image600">
    												<a target="_blank" href="boardspeck.com" style="text-decoration: none;"><img src="'.$logo_directory.'" alt="" border="0" width="600" height="auto" class="hover" style="border-radius: 4px;"></a>
    											</td>
    										</tr>
    										<tr>
    											<td width="100%" height="25" class="h10"></td>
    										</tr>
    										<tr>
    											<td valign="middle" width="100%">

    												<table width="190" border="0" cellpadding="0" cellspacing="0" align="right" style="border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;" class="full">
    													<tr>
    														<td height="35" width="100%" style="text-align: right; font-family: Helvetica, Arial, sans-serif, "Open Sans"; font-size: 13px; color: #fa6f6f; line-height: 24px; font-weight: 400;" class="textLeft" id="icon13">

    															<span style="text-decoration: none;"><img src="images/icon_13_1.png" width="13" alt="" border="0" style="vertical-align: middle; padding-bottom: 2px;"></span>
    															&nbsp;
    															<a target="_blank" href="'.$_SERVER['HTTP_HOST'].'" style="text-decoration: none; color: #f67b7c;">'.$site_name.'</a>
    															&nbsp;&nbsp;
    															<span style="text-decoration: none;"><img src="images/icon_13_2.png" width="13" alt="" border="0" style="vertical-align: middle; padding-bottom: 3px;"></span>
    															&nbsp;
    															<span style="text-decoration: none;"><img src="images/icon_13_3.png" width="13" alt="" border="0" style="vertical-align: middle; padding-bottom: 3px;"></span>
    														</td>
    													</tr>
    												</table>

    												<table width="400" border="0" cellpadding="0" cellspacing="0" align="left" style="border-collapse:colapse; mso-table-lspace:0pt; mso-table-rspace:0pt;" class="full">
    													<tr>
    														<td height="35" width="100%" style="text-align: left; font-family: Helvetica, Arial, sans-serif, '."'Open Sans'".'; font-size: 24px; color: #444444; line-height: 32px; font-weight: 700;">
    															Welcome To '.ucwords($site_name).'
    														</td>
    													</tr>
    												</table>

    											</td>
    										</tr>
    										<tr>
    											<td width="100%" height="25"></td>
    										</tr>
    										<tr>
    											<td valign="middle" width="100%" style="text-align: left; font-family: Helvetica, Arial, sans-serif, '."'Open Sans'".'; font-size: 14px; color: #808080; line-height: 22px; font-weight: 400;">
                          <p>Dear <strong>'.$firstname.',</strong></p>
                          <p>We are glad to know that you find our resources useful and valuable.</p>
                          <p>


As a creative entrepreneur who seeks to constantly improve and lead the much needed change in your ecosystem, you will find timely resources on our platforms.</p>
<p>Whether free or paid, our faculties have carefully and diligently curated all the materials we are sharing with you. They will help increase your knowledge and improve your market share when you effectively apply them.</p>
<p>We look forward to hearing your feedback thinkingschng@gmail.com and <a href="https://twitter.com/thinkingschng">thinkingschng</a></p>
<p>Regards</p>


  <p>'.$site_name.'</p>
    											</td>
    										</tr>
    										<tr>
    											<td width="100%" height="30"></td>
    										</tr>
    										<!-- Button Left -->
    										<tr>
    											<td width="auto" align="left">

    												<table border="0" cellpadding="0" cellspacing="0" align="left">
    													<tr>
    														<td width="auto" align="center" height="37" bgcolor="#fa6f6f" style="border-top-left-radius: 20px; border-top-right-radius: 20px; border-bottom-right-radius: 20px; border-bottom-left-radius: 20px; padding-left: 22px; padding-right: 22px; font-weight: bold; font-family: Helvetica, Arial, sans-serif, '."'Open Sans'".'; color: #ffffff; font-weight: 600;">
    															<a target="_blank" href="'.'https://'.$_SERVER['HTTP_HOST'].'/login" style="color: #ffffff; font-size: 14px; text-decoration: none; line-height: 34px; width: 100%;">Login Here</a>
    														</td>
    													</tr>
    												</table>

    											</td>
    										</tr>
    										<!-- Button Left -->
    										<tr>
    											<td width="100%" height="70"></td>
    										</tr>
    									</table>
    								</td>
    							</tr>
    						</table><!-- End Wrapper 2 -->

    					</td>
    				</tr>
    			</table><!-- End Wrapper -->

    		</td>
    	</tr>
    </table>
  </body>
  </html>
  ';

// }


  $to = $email;
  // $subject = $site_name." Verification";
  $subject2 = "Welcome To ".$site_name;
  // $headers = "MIME-Version: 1.0" . "\r\n";
  // $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  // $headers .= "From: ".$site_name." <".$site_email .">". "\r\n" .
  // "CC: banjimayowa@gmail.com";
  //
  //
  // // mail($to,$subject,$txt,$headers);
  //
  // mail($to,$subject2,$txt2,$headers);




  require APP_PATH.'/phpm/PHPMailerAutoload.php';

  // If necessary, modify the path in the require statement below to refer to the
  // location of your Composer autoload.php file.
  // require 'phpm/PHPMailerAutoload.php';


  // Instantiate a new PHPMailer
  $mail = new PHPMailer;

  // Tell PHPMailer to use SMTP
  $mail->isSMTP();

  // Replace sender@example.com with your "From" address.
  // This address must be verified with Amazon SES.
  $mail->setFrom($site_email, $site_name);
  $mail->AddReplyTo($site_email, $site_name);

  // Replace recipient@example.com with a "To" address. If your account
  // is still in the sandbox, this address must be verified.
  // Also note that you can include several addAddress() lines to send
  // email to multiple recipients.
  $mail->addAddress($email, $firstname);

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
  $mail->Subject = $subject2;

  // The HTML-formatted body of the email
  $mail->Body = $txt2;

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
    // die(var_dump("Email not sent. " , $mail->ErrorInfo , PHP_EOL));
  } else {
    // echo "Email sent!" , PHP_EOL;
    // exit();
  }


  // mail($email, "test email", "test email message");

  // require_once "Mail.php"; // PEAR Mail package
  // require_once ('Mail/mime.php'); // PEAR Mail_Mime packge
  //
  //       $from = "info@mckodev.com.ng"; //enter your email address
  //  $to = $email; //enter the email address of the contact your sending to
  //  $subject = "BOARDSPECK VERIFICATION"; // subject of your email
  //
  // $headers = array ('From' => $from,'To' => $to, 'Subject' => $subject);
  //
  // // $text = ''; // text versions of email.
  // $html = "<html><body>Hello, $firstname $lastname <br>You have been verified at the Boardspeck Web Office. You can now login to the Web Office at \"https://news.mckodev.com.ng/adminLogin"; // html versions of email.
  //
  // $crlf = "\n";
  //
  // $mime = new Mail_mime($crlf);
  // // $mime->setTXTBody($text);
  // $mime->setHTMLBody($html);
  //
  // //do not ever try to call these lines in reverse order
  // $body = $mime->get();
  // $headers = $mime->headers($headers);
  //
  //  $host = "localhost"; // all scripts must use localhost
  //  $username = "qservers@mckodev.com.ng"; //  your email address (same as webmail username)
  //  $password = "Digital2017+"; // your password (same as webmail password)
  //
  // $smtp = Mail::factory('smtp', array ('host' => $host, 'auth' => true,
  // 'username' => $username,'password' => $password));
  //
  // $mail = $smtp->send($to, $headers, $body);
  //
  // if (PEAR::isError($mail)) {
  //
  // $error['mail']= "<p>" . $mail->getMessage() . "</p>";
  // }
  // else {
  // 	$success = [];
  //  		$success['mail'] = "A verification link has been sent to your mail";
  // }

  // die(var_dump($userinfo));
  // if($tb == ""){

   // $to = "boardspeck@gmail.com";
   // $subject = "Boardspeck Web Office Content Upload";
   // $txt = "Hello Admin, ($firstn $lastn)has added a content on "."$url"." page at the back office. Kindly check for and approval";
   // $headers = "From: info@boardspeck.com" . "\r\n" .
   // "CC: banjimayowa@gmail.com";
   // mail($to,$subject,$txt,$headers);

// $bdd = adminInfo($conn,"j90819542aBn72i");
// $who = $bdd['image_2'];
// $headings = "Newly Verified User";
// $message = "Hello Admin, ($firstname $lastname) has Registered. Kindly check for verification";
// $page = "viewUsers";
// $webp = time();
// include 'include/adminNotification.php';





  $stmt2 = $conn->prepare("DELETE FROM verify WHERE token=:tk");
  $stmt2->execute($data);
$_SESSION['id'] = $userinfo[0]['hash_id'];
  header("Location:/home");
}

;

}else{
  die("error");
}


 ?>
