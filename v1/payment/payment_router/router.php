<?php
$uri = explode("/",$_SERVER['REQUEST_URI']);

$id= NULL;
if (isset($_GET['id'])) {
  $id = $_GET['id'];
}
$flwref= NULL;
if (isset($_GET['flwref'])) {
  $flwref = $_GET['flwref'];
}
$txref= NULL;
if (isset($_GET['txref'])) {
  $txref = $_GET['txref'];
}
$event= NULL;
if (isset($_GET['event'])) {
  $event = $_GET['event'];
}
$cancelled= NULL;
if (isset($_GET['cancelled'])) {
  $cancelled = $_GET['cancelled'];
}



switch ($uri[1]){
  case "invoice?id=$id":
  include APP_PATH."/v1/payment/invoice.php";
  break;
  case "confirm_payment?flwref=$flwref&txref=$txref":
  include APP_PATH."/v1/payment/confirmations.php";
  break;

  case "confirm_payment?flwref=$flwref&txref=$txref&event=$event&":
  include APP_PATH."/v1/payment/confirmations.php";
  break;
  case "confirm_payment?flwref=$flwref&txref=$txref":
  include APP_PATH."/v1/payment/confirmations.php";
  break;
  case "confirm_payment?cancelled=$cancelled&txref=$txref&event=$event&":
  include APP_PATH."/v1/payment/confirmations.php";
  break;
  case "confirm_payment?cancelled=$cancelled&txref=$txref":
  include APP_PATH."/v1/payment/confirmations.php";
  break;

  case "rave_webhook":
  include APP_PATH."/v1/payment/rave_webhook.php";
  break;



}
