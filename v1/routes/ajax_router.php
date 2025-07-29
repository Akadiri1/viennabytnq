<?php
$uri = explode("/",$_SERVER['REQUEST_URI']);


$success = NULL;
if(isset($_GET['success'])){
  $success = $_GET['success'];
}

$wn = NULL;
if(isset($_GET['wn'])){
  $wn = $_GET['wn'];
}
$rd = NULL;
if(isset($_GET['rd'])){
  $rd = $_GET['rd'];
}

$id = NULL;
if(isset($_GET['id'])){
  $id = $_GET['id'];
}
$err = NULL;
if(isset($_GET['err'])){
  $err = $_GET['err'];
}

$data = NULL;
if(isset($_GET['data'])){
  $data = $_GET['data'];
}

$location = NULL;
if(isset($_GET['location'])){
  $location = $_GET['location'];
}



switch ($uri[1]) {




  case "admin-login?success=$success":
  include APP_PATH."/admin/admin_login.php";
  break;





  // default:
  //   // code...
  //   break;
}

 ?>
