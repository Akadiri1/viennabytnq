<?php
$uri = explode("/",$_SERVER['REQUEST_URI']);

$token = NULL;
if(isset($_GET['token'])){
  $token = $_GET['token'];
}

if (!empty($_GET)) {
$query_string = explode("?",$uri[1])[1];
}else{
$query_string = "";
}

switch ($uri[1]) {
  case "verify?token=$token":
  include APP_PATH."/auth/verify_registration.php";
  break;

  case "forgotPassword":
  include APP_PATH."/auth/forgot_password.php";
  break;

  case "forgotPassword2":
  include APP_PATH."/auth/forgot_password2.php";
  break;

  case "confirmRecovery":
  include APP_PATH."/auth/confirm_recovery.php";
  break;
  case "confirmRecovery":
  include APP_PATH."/auth/confirm_recovery.php";
  break;

  case "login":
  include APP_PATH."/auth/login.php";
  break;
  case "signup":
  include APP_PATH."/auth/signup.php";
  break;
  case "secure":
  include APP_PATH."/auth/secure.php";
  break;
  case "secure?".$query_string:
  include APP_PATH."/auth/secure.php";
  break;
  case "login?".$query_string:
  include APP_PATH."/auth/login.php";
  break;
  case "signup?".$query_string:
  include APP_PATH."/auth/signup.php";
  break;

  case "confirm?token=$token":
  include APP_PATH."/auth/confirm.php";
  break;

  case "changePassword":
  include APP_PATH."/auth/change_password.php";
  break;

}
