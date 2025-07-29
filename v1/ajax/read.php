<?php

$request_headers = getallheaders();
if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}
// use you local Ip and Domain Name Here
if( !in_array($request_headers['Host'],["192.168.33.89","be29-102-89-2-17.ngrok.io","192.168.33.99","digimart.org.ng","Mckodev.attendout.com"]) ){
  http_response_code(502);
  die;
}

$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json,true);
// die(print_r($data));

if(!isset($data['data']) || !isset($data['where'])) {
    http_response_code(400);
    die();
}

$columnWhere = $data['where'];
$table = $data['data'];
// die(var_dump($data));
$result = [];
$vall = formatWhere($columnWhere);
// die($vall);
try{

// $what = getVal($parameters);

  // var_dump($parameters);
  $sql = sprintf('SELECT * FROM %s',
      $table
  );

  if(count($columnWhere) > 0){
      $sql .= " WHERE ".$vall;
  }

  if(isset($data['desc'])){
    if(isset($data['order'])){
        $sql .= " ORDER BY ".$data['order']." DESC ";
    }else{
        $sql .= " ORDER BY DESC ";
    }
  }


  if(isset($data['asc'])){
if (isset($data['order'])) {
      $sql .= " ORDER BY ".$data['order']." ASC ";
}else{
      $sql .= " ORDER BY id ASC ";
}
  }
  if(isset($data['limit'])){
    $sql .= "LIMIT ".$data['limit'];
  }

  // die(var_dump($sql));
$stmt =  $conn->prepare($sql);
$newt = $columnWhere;
// die(var_dump($newt));

if(count($columnWhere) > 0){
$stmt->execute($newt);
}else{
  $stmt->execute();
}

// $result['data'] = [];
while($row = $stmt->fetch(PDO::FETCH_BOTH)){
  $result[] = $row;
}
$res["success"] = true;
$res["data"] = $result;

} catch (PDOException $e) {
  // die($e);
    http_response_code(409);
    $result["error"] = true;
    die;
}

$res = json_encode($res);
echo $res;
