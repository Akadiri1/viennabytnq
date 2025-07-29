<?php
function getDateDifference($date){
  $now = time(); // or your date as well
$your_date = strtotime($date);
$datediff = $now - $your_date;

echo abs(round($datediff / 86400));
}
function selectContentDesc2($dbconn,$table,$columnWhere,$order,$limit){
  $vall = formatWhere($columnWhere);
  try{

    // $what = getVal($parameters);

    // var_dump($parameters);
    $sql = sprintf('SELECT * FROM %s',
    $table
  );

  if(count($columnWhere) > 0){
    $sql .= " WHERE ".$vall;
  }
  $sql.= " ORDER BY ".$order." DESC";
  if ($limit !== 0) {
    $sql .= " LIMIT ".$limit;
  }


  //die(var_dump($sql));
  $stmt =  $dbconn->prepare($sql);
  $newt = $columnWhere;
  // die(var_dump($newt));
  if(count($columnWhere) > 0){
    $stmt->execute($newt);
  }else{
    $stmt->execute();
  }

  $result = [];
  while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $result[] = $row;
  }

  return $result;
} catch (PDOException $e) {
  die($e);
  die("Error Occured");
}
}
function createTitleHash($title,$ii){
  $hash = createURL($title);
  // $hash = preg_replace("/(?![.=$'€%])\p{P}/u", "", $hashh);
  $dd = base64url_encode($ii);
  $id = $hash."-".$dd;
  return $id;
}
function createURL($string){
  $hss = preg_replace("#[[:punct:]]#", "", $string);
  $hssh = str_replace(" ","-",$hss);
  return $hssh;
}
function decodeURL($string){
  $bck = explode("-",$string);
  $bck_i = end($bck);
  $bck_id = base64url_decode($bck_i);
  return $bck_id;
}
 ?>
