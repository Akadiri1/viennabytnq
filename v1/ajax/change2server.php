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
$encoded_file = $_POST['file'];
$decoded_file = base64_decode($encoded_file);
$encoded_prev_file = $_POST['prev'];
/* Now you can copy the uploaded file to your server. */
// echo $decoded_file;
$destination = 'uploads/'.time().rand(10000,99999).'filename.jpg';
file_put_contents($destination, $decoded_file);
$result['name'] = 'https://'.$_SERVER['HTTP_HOST']."/".$destination;

if(file_exists($destination)){

  $Ndata = explode("/",$encoded_prev_file);

$myLastElement = end($Ndata);

if(file_exists("uploads/".$myLastElement)){
  // echo $myLastElement;
  // die;
    unlink("uploads/".$myLastElement);
}else{
// echo "Doesnt Exist";
// die;
}
$result['status'] = "Uploaded";
}else{
$result['status'] = "Failed";
}

$result = json_encode($result);
echo $result;
