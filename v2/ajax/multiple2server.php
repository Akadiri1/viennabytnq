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


// $json = file_get_contents('php://input');
//
// // Converts it into a PHP object
// $data = json_decode($json,true);
// die(var_dump($_POST));
// die();
$result['name'] = [];
$result['status'] = [];
$result['failed'] = [];

foreach ($_POST as $key => $value) {

  $encoded_file = $value;
  $decoded_file = base64_decode($encoded_file);
  /* Now you can copy the uploaded file to your server. */
  // echo $decoded_file;
  $destination = 'uploads/'.time().rand(10000,99999).'filename.jpg';
  file_put_contents($destination, $decoded_file);
  $result['name'][] = 'https://'.$_SERVER['HTTP_HOST']."/".$destination;
  if(file_exists($destination)){
  $result['status'][] = "Uploaded";
  }else{
  $result['failed'][] = "Upload Failed";
  }
}

$result = json_encode($result);
echo $result;
