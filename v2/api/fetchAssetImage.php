<?php
// header("Access-Control-Allow-Origin: http://localhost/rest-api-authentication-example/");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Max-Age: 3600");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 // http_response_code(501);
// echo $_POST['sector'];

$request_headers = getallheaders();
// if(isset($request_headers['X-USERNAME']) && $request_headers['X-USERNAME'] == 'apiuser') {
//     // do stuff
//
// var_dump($_POST);

if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(400);
   die;
}
try {
  // echo "book";
  // var_dump($request_headers);
  // var_dump($_SERVER);
  $table = "asset_images";
  $stmt = $conn->prepare("SELECT * FROM $table WHERE asset_hash_id =:lvc");
  $stmt->bindParam(":lvc",$_POST['asset_hash_id']);
  $stmt->execute();
  $result = [];
  $record = [];

  while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $record[] = $row;
  }

  $result['success'] = true;
  $result['record'] = $record;
} catch (PDOException $e) {
  $result['failed'] = "Something Went Wrong";
}


echo json_encode($result);
