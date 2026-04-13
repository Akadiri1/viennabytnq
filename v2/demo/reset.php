<?php

// session_start();
session_destroy();
if (isset($_SERVER['HTTP_REFERAL'])) {
  header("Location:".$_SERVER['HTTP_REFERAL']);
}else{
  header("Location:/");
}
exit();
 ?>
