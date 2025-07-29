<?php
ob_start();
//session_start();
// include("include/link_include.php");
if(!isset($_SESSION['user_to_edit'])){
  header("Location:/index");
}

$error= [];

if(array_key_exists('submit', $_POST)){

  if(empty($_POST['pword'])){
    $error['pword']="Enter a password";
  }
  if(empty($_POST['cpword'])){
    $error['cpword']="Confirm password";
  }

  if($_POST['pword']!=$_POST['cpword']){
    $error['pword'] ="Password mismatch";
  }

  if(empty($error)){
    $clean = array_map('trim', $_POST);
    doChangePassword($conn, $clean,$_SESSION['user_to_edit']);
  }
}

 ?>



<!DOCTYPE html>
<html lang="" dir="ltr">
<head>
  <meta charset="utf-8">
  <title><?php echo $site_name ?></title>
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
        <!-- <p>Password Recovery</p>
        <br> -->
        <section id="">
          <div class="container">
            <div class="row">

              <div class="col-sm-6 col-sm-offset-4 col-md-4 col-md-offset-4 mx-auto">
                <div class="page-login-form">
                  <div class="row"><div class="col-md-12">
              <div class=" posting">
            <div class="card mx-auto" role="alert">

              <h3>
              Enter New Password
              </h3>
              <form role="form" class="login-form" method="POST" action="">

              <div class="form-group">
              <div class="input-icon">
                <?php $display = displayErrors($error, 'pword');
                echo $display ?>
              <i class="icon fa fa-unlock-alt"></i>
              <input type="password" class="form-control" name="pword" placeholder="Password">
              </div>
              </div>
              <div class="form-group">
                <?php $display = displayErrors($error, 'cpword');
                echo $display ?>
              <div class="input-icon">
              <i class="icon fa fa-unlock-alt"></i>
              <input type="password" class="form-control" name="cpword" placeholder="Retype Password">
              </div>
              </div>

              <input type="submit" class="btn btn-common log-btn"  name="submit">

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
