<?php

if(isset ($_SESSION['id'])){
  unset($_SESSION['id']);
  session_destroy();
  header("Location: /login");
}

if(isset ($_SESSION['hq'])){
  unset($_SESSION['hq']);
  session_destroy();
  header("Location: /login");
}

// if(isset($_SERVER['HTTP_REFERER'])){
//   header("Location: /login");
// }else{
//   header("Location:/index");
// }



 ?>
