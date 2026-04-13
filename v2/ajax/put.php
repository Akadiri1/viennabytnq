<?php

$request_headers = getallheaders();
if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}

if( !in_array($request_headers['Host'],["192.168.33.89","be29-102-89-2-17.ngrok.io","192.168.33.99","digimart.org.ng","Mckodev.attendout.com"]) ){
  http_response_code(502);
  die;
}


$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json,true);
// die(var_dump($data));

if(!isset($data['data']) || !isset($data['where']) || !isset($data['values'])) {
    http_response_code(400);
    die();
}

$columnWhere = $data['where'];
$table = $data['data'];
$parameters = $data['values'];
// die(var_dump($data));
$result = [];

try{


  // array_pop($parameters);
  $what = formatParam($parameters);
  $vall = formatPutWhere($columnWhere);

    // var_dump($parameters);
    $sql = sprintf('UPDATE %s SET %s',
        $table, $what
    );
    $sql .= " WHERE ".$vall;
    // //die(var_dump($sql));
  $stmt =  $conn->prepare($sql);
  // var_dump($sql);
  $columnWhere = formatWhereParam($columnWhere);
  // var_dump($columnWhere);
  $newt = $parameters + $columnWhere;
  // var_dump($newt);
  // die;
  // die(var_dump($newt));
  $stmt->execute($newt);
$result["success"] = true;

} catch (PDOException $e) {
  die($e);
    http_response_code(409);
    $result["error"] = true;
    die;
}

$res = json_encode($result);
echo $res;
