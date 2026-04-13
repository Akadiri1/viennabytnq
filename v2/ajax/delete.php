<?php
header("Content-Security-Policy: default-src 'none';");
$request_headers = getallheaders();
if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}



$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json,true);
// die(var_dump($data));

if(!isset($data['data']) || !isset($data['where'])) {
    http_response_code(400);
    die();
}

$columnWhere = $data['where'];
$table = $data['data'];
// $parameters = $data['values'];
// die(var_dump($data));
$result = [];

try{

  // $what = getVal($parameters);
  $vall = formatWhere($columnWhere);

    // var_dump($parameters);
    $sql = sprintf('DELETE FROM %s',
        $table
    );
        $sql .= " WHERE ".$vall;


    //die(var_dump($sql));
  $stmt =  $conn->prepare($sql);
  $newt = $columnWhere;
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
