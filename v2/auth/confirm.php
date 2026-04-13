<?php
ob_start();
//session_start();
if(isset($_GET['token'])){


$stmt = $conn->prepare("SELECT * FROM verify WHERE token=:tk");
$data = [
  ":tk"=> $_GET['token']
];
$stmt->execute($data);
if($stmt->rowCount() < 1){
    die("error");
}else{
  $row = $stmt->fetch(PDO::FETCH_BOTH);
  extract($row);
  $_SESSION['user_to_edit'] = $email;


  $stmt2 = $conn->prepare("DELETE FROM verify WHERE token=:tk");
  $stmt2->execute($data);
    header("Location:/changePassword");
}
















}else{
  die("error");
}


 ?>
