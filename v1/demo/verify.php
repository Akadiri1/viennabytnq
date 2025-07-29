<?php
if(!isset($_GET['id'])){
  die("Error!");
}
$content = selectContent($conn,"panel_payment",['hash_id'=>$_GET['id']]);


echo "<h1>Verification Lite</h1>";
echo "<h2>Id: ".$content[0]['hash_id']."</h2>";
echo "<h2>Amount: ".$content[0]['input_amount']."</h2>";
echo "<h2>Date: ".$content[0]['date_created']."</h2>";


if ($content[0]['input_discount'] !==NULL) {
  echo "<h2>Referal: ".$content[0]['discount']."</h2>";
}
if ($content[0]['input_color'] !==NULL) {
  echo "<h2>Color: ".$content[0]['input_color']."</h2>";
}


 ?>
