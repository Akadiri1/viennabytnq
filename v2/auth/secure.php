<?php

ob_start();
// session_start();
// include("include/link_include.php");
$check = selectContent($conn,"users",['hash_id'=>$_SESSION['id']]);
$error= [];

if(array_key_exists('submit', $_POST)){



  if(empty($_POST['phone_number'])){
    $error['phone_number']="Enter a Number";
  }

  if(empty($error)){

if(isset($_POST['submit'])){
  $where3['hash_id'] = $_SESSION['id'];
  $clean = array_map('trim', $_POST);

  $post = $clean ;
  array_pop($post);
  // die(var_dump($post));
  updateContent($conn,'users',$post,$where3);
  unset($_SESSION['name']);

  if(isset($_GET['rd'])){
      header("Location:". base64url_decode($_GET['rd']));
  }else{
      header("Location:/index");
  }

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
  <title><?php echo $site_name ?></title>
      <meta name="theme-color" content="<?php echo $themecolor ?>">
  <!-- <meta name="theme-color" content="#C00000"> -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <!-- <meta name="mediacpmpl-site-verification" content="88b2124c10bb346cdf2e629951be2e58"> -->
  <!-- <meta name="viewport" content="width=device-width"> -->
    <meta name="mobile-web-app-capable" content="yes">

    <link rel="icon" sizes="192x192" href="/<?php echo $logo_directory ?>">

  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
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
        <div class="col-md-6 mx-auto">
          <p><?php echo ucwords($_SESSION['name']); ?>, Secure your account by providing the details below. This would be useful during account recovery</p>

        </div>
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
                <?php if($check[0]['email'] == NULL || $check[0]['email'] == "undefined" ){ ?>
                  <div class="form-group">

                  <div class="input-icon">
                  <i class="icon fas fa-envelope"></i>
                  <input type="text"  class="form-control" name="email" placeholder="Email" required>
                  </div>
                  </div>
              <?php } ?>

              <div class="form-group">

              <div class="input-icon">
              <i class="icon fa fa-phone"></i>
              <input type="text"  class="form-control" name="phone_number" placeholder="Phone Number" required>
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
