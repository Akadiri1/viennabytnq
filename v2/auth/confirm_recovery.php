<?php
ob_start();
//session_start();
if(!isset($_SESSION['half_num']) && !isset($_SESSION['identity']) && !isset($_SESSION['name'])){
  $suc = 'Error';
  $message = preg_replace('/\s+/', '_', $suc);
  header("Location:login?err=$message");
  exit();
}
if(isset($_SESSION['identity'])){
  $identity = $_SESSION['identity'];
}
if(isset($_SESSION['email'])){
  $email = $_SESSION['email'];
}
if(isset($_SESSION['half_num'])){
  $half_num = $_SESSION['half_num'];
}
if(isset($_SESSION['name'])){
  $name = $_SESSION['name'];
}
// include("include/link_include.php");


$error= [];
if(array_key_exists('submit', $_POST)){

  if(empty($_POST['number'])){
    $error['number']="Enter number";
  }

  if(empty($error)){
$given_num = $_SESSION['half_num'].$_POST['number'];

if(doesPhoneNumberExist($conn, $given_num)){
  $ret = forgotPassword($conn, $identity);

   $to = strip_tags($email);
   $subject = $site_name." Password Recovery";
   $txt = "Hello $name, You made a request for password changes, Follow this link to change our Password ".'https://'.$_SERVER['HTTP_HOST']."/confirm?token=".$ret[0];


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
   $mail->isSMTP();

   // Replace sender@example.com with your "From" address.
   // This address must be verified with Amazon SES.
   $mail->setFrom($site_email, $site_name);
   $mail->AddReplyTo($site_email, $site_name);

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
     // die(var_dump("Email not sent. " , $mail->ErrorInfo , PHP_EOL));
   } else {
     // echo "Email sent!" , PHP_EOL;
     // exit();
   }







// die(var_dump($mail));








   $suc = 'A mail has been sent to your email address, Kindly visit the mail for further instructions';
   $message = $suc;
   unset($_SESSION['name']);
   unset($_SESSION['identity']);
   unset($_SESSION['half_num']);
   unset($_SESSION['email']);
   session_destroy();
   header("Location:/login?success=$message");
   exit();




}else{
  unset($_SESSION['name']);
  unset($_SESSION['identity']);
  unset($_SESSION['half_num']);
  unset($_SESSION['email']);
  session_destroy();
  $suc = 'Incorrect Answer Supplied';
  $message = $suc;
  header("Location:/login?ne=$message");
  exit();
  }




    }


 //      $from = "info@mckodev.com.ng"; //enter your email address
 // $to = "banjimayowa@gmail.com"; //enter the email address of the contact your sending to
 // $subject = "BOARDSPECK New Admin User Registration"; // subject of your email

  }


 ?>

<!DOCTYPE html>
<html lang="" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="<?php echo $themecolor ?>">
  <!-- <meta name="mediacpmpl-site-verification" content="88b2124c10bb346cdf2e629951be2e58"> -->
  <!-- <meta name="viewport" content="width=device-width"> -->
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="icon" sizes="192x192" href="/<?php echo $logo_directory ?>">

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <title><?php echo $site_name ?></title>
  <meta charset="UTF-8">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body style="background-color:grey">

  <div class="col-md-12">

    <div class="card" style="width:100%;min-height:96vh; Background-color:white;">
      <div class="card-body text-center">

          <div class="mx-auto" style="width:100px; height: 100px;background:url('<?php echo  $logo_directory ?>'); background-size:80%; background-position:center; background-repeat:no-repeat;">
          </div>

        <br>
        <br>
        <h5><?php echo $site_name ?></h5>
        <p>Password Recovery</p>
        <br>
        <section id="">
          <div class="container">
            <div class="row">

              <div class="col-sm-6 col-sm-offset-4 col-md-4 col-md-offset-4 mx-auto">
                <div class="page-login-form">
                  <div class="row"><div class="col-md-12">
              <div class=" posting">
            <div class="card mx-auto" role="alert">



              <h3>
              Verify Your Number (<?php echo $_SESSION['half_num']."***" ?>)
              </h3>
              <form role="form" class="login-form" method="POST" action="">

              <div class="form-group">

              <div class="input-icon ">
              <p>Enter the Last 3 digits of the phone number above. This Number was supplied durning Registration</p>
              <input type="text" class="form-control" name="number"  placeholder="Enter Last 3 Digits" maxlength="3" required>
              </div>
              </div>

              <input class="btn btn-common log-btn" type="submit"  name="submit">
              <!-- <button class="btn btn-common log-btn"></button> -->
              <label for="remember"><a href="/login"> Back</a></label>


              </form>




              </div>
              </div>
              </div>
              </div>
              </div>




                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

</body>
</html>
