<?php
$uri = explode("/",$_SERVER['REQUEST_URI']);



switch ($uri[1]) {

  case "add":
  include APP_PATH."/ajax/add.php";
exit();  break;
  case "read":
  include APP_PATH."/ajax/read.php";
exit();  break;
  case "put":
  include APP_PATH."/ajax/put.php";
exit();  break;
  case "delete":
  include APP_PATH."/ajax/delete.php";
exit();  break;
  case "upload2server":
  include APP_PATH."/ajax/upload2server.php";
exit();  break;
  case "delete2server":
  include APP_PATH."/ajax/delete2server.php";
exit();  break;
  case "change2server":
  include APP_PATH."/ajax/change2server.php";
exit();  break;
  case "multiple2server":
  include APP_PATH."/ajax/multiple2server.php";
exit();  break;

  case "campaignBackend":
  include APP_PATH."/ajax/campaignBackend.php";
exit();  break;

  case "formBackend":
  include APP_PATH."/ajax/formBackend.php";
exit();  break;

  case "invoiceBackend":
  include APP_PATH."/ajax/invoiceBackend.php";
exit();  break;
}
