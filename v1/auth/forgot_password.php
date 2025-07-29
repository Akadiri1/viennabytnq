<?php

ob_start();
//session_start();
// include("include/link_include.php");

$error= [];

if(array_key_exists('submit', $_POST)){



  if(empty($_POST['email'])){
    $error['email']="Enter a email";
  }

  if(empty($error)){

    if(doesEmailExist($conn,$_POST['email'])){
      $clean = array_map('trim', $_POST);
      $res = getPhone($conn,$_POST['email']);
// die($phone);
      $part_phone = substr($res[0], 0, -3);
    $_SESSION['half_num'] = $part_phone;
    $_SESSION['identity'] = $res[1];
    $_SESSION['name'] = $res[2];
    $_SESSION['email'] = $_POST['email'];


header("Location:/confirmRecovery");

      // $ret = forgotPassword($co$nn, $clean);






       // $to = strip_tags($_POST['email']);
       // $subject = "Boardspeck Web Office Password Recovery";
       // $txt = "Hello User, You made a request for password changes, Follow this link to change our Password https://boardspeck.com/confirm?token=".$ret[0];
       // $headers = "From: info@boardspeck.com" . "\r\n" .
       // "CC: banjimayowa@gmail.com";
       //
       // mail($to,$subject,$txt,$headers);
       // $suc = 'A mail has been sent to your email address, Kindly visit the mail for further instructions';
       // $message = preg_replace('/\s+/', '_', $suc);
       // header("Location:adminLogin?success=$message");


    }else {
      $suc = 'Email does not exist on our system';
      $message = $suc;
      header("Location:login?vr=$message");
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
  <title><?php echo $site_name ?> - Password Recovery</title>
    <meta name="theme-color" content="<?php echo $themecolor ?>">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- <meta name="mediacpmpl-site-verification" content="88b2124c10bb346cdf2e629951be2e58"> -->
  <!-- <meta name="viewport" content="width=device-width"> -->
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="icon" sizes="192x192" href="/<?php echo $logo_directory ?>">

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
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



              <form role="form" class="login-form" method="POST" action="">

              <div class="form-group">

              <div class="input-icon">
              <i class="icon fa fa-envelope"></i>
              <input type="text" id="sender-email" class="form-control" name="email" placeholder="Email Address" required>
              </div>
              </div>

              <input class="btn btn-danger btn-sm" type="submit"  name="submit">
              <!-- <button class="btn btn-common log-btn"></button> -->
              <br>
              <hr>
              <button class="btn btn-common btn-xs" type="button" name="button"><a href="/login"> Back</a></button>

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
