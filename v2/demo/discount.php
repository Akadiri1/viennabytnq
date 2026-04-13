<?php

// session_start();
$ref_Id = base64url_decode($_GET['ref']);

$discount = selectContent($mck_conn, "panel_discount", ['input_referral_id' => $ref_Id]);

// echo $discount[0]['input_referral_id'];
// var_dump($discount);

if(count($discount) > 0){

  $_SESSION['discount'] = $discount[0]['input_referral_id'];
  // echo "Your ref id is ".$_SESSION['discount'];
  header("Location:/");
  exit();
}else{

  header("Location:/");
  exit();
}

// var_dump($discount)
 ?>
