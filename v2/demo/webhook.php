<?php
// session_start();
// die("got here");

if(isset($_GET['color'])){
$_SESSION['color'] = $_GET['color'];
}

if(isset($_GET['location'])){

$location = base64url_decode($_GET['location']);
header("Location:".$location );
var_dump($location);

}else{

header("Location:/");
}

?>
