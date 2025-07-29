<?php
// die("NOE");
$request_headers = getallheaders();
if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}

// if( !in_array($request_headers['Host'],["192.168.33.55","attendout.com"]) ){
//   http_response_code(502);
//   die;
// }
// die(var_dump($_POST));

foreach ($_POST as $key => $value) {
  // code...
  $Ndata = explode("/",$value);

$myLastElement = end($Ndata);

if(file_exists("uploads/".$myLastElement)){
  // echo $myLastElement;
  // die;
    unlink("uploads/".$myLastElement);
    $result['status'][] = "Deleted";
}else{
  $result['failed'][] = "Some Files Failed File Doesnt Exist";
// echo "Doesnt Exist";
// die;
}
}
// $json = file_get_contents('php://input');
//
// // Converts it into a PHP object
// $data = json_decode($json,true);
// die(var_dump($_POST));





$result = json_encode($result);
echo $result;
