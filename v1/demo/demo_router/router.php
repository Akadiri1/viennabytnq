<?php
$uri = explode("/",$_SERVER['REQUEST_URI']);


if (count($uri) > 2) {


  if (!empty($_GET)) {
  $query_string = explode("?",$uri[2])[1];
}else{
  $query_string = "";
}

  switch ($uri[1]."/".$uri[2]) {
    case 'admin/manage-all':
    include APP_PATH."/admin/manage_all.php";
    break;

  }



}else{
  if (!empty($_GET)) {
  $query_string = explode("?",$uri[1])[1];
}else{
  $query_string = "";
}
  // $query_string = explode("?",$uri[1])[1];
  switch ($uri[1]) {


    case 'reset':
    include APP_PATH."/demo/reset.php";
    break;

    case 'mkp-image-upload':
    include APP_PATH."/demo/imageUpload.php";
    break;

    case 'mkp-pricing':
    include APP_PATH."/demo/mkp-pricing.php";
    break;

    case 'maintenance':
    include APP_PATH."/demo/maintenance.php";
    break;

    case 'discount?'.$query_string:
    include APP_PATH."/demo/discount.php";
    break;

    case 'verify?'.$query_string:
    include APP_PATH."/demo/verify.php";
    break;

    case 'webhook?'.$query_string:
    include APP_PATH."/demo/webhook.php";
    break;

    // default:
    // include APP_PATH."/demo/404.php";
    // break;

  }

}










 ?>
