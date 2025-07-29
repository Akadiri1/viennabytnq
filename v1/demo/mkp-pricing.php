<?php



$request_headers = getallheaders();

if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}

if( !in_array($request_headers['Host'],$allowedHeaders) ){
  http_response_code(502);
  die;
}



$json = file_get_contents('php://input');

// Converts it into a PHP object
$data = json_decode($json,true);


$response = [];


$price = 60000;

	if (isset($_SESSION['discount'])) {
	$price = $price - 5000;
	}

    $initial = $price/100;
    $initial = $initial*30;
    $rest = $price - $initial;

    $permonth = $rest/7;


	$messageFull = "https://api.whatsapp.com/send?phone=+23408168785591&text=Hello I will like to place Order for ".$site_name." Full Payment:".$price;
	$messageFlexible = "https://api.whatsapp.com/send?phone=+23408168785591&text=Hello I will like to place Order for ".$site_name." Flexible Payment. Initial:".$initial.". Will make payment of ".$permonth." per month. for 7 Months. ";

	if (isset($_POST['payfull'])) {

	$new = [];
	$new['hash_id'] = time()."_".rand(100000,999999);
	$new['time_created'] = date("h:i:s");
	$new['date_created'] = date("Y-m-d");
	$new['visibility'] = "hide";
	$new['input_color'] = $style_color;
	if (isset($_SESSION['discount'])) {
	  $new['input_discount'] = $_SESSION['discount'];
	}
	$new['input_amount'] = $price;

	insertSafe($conn,'panel_payment',$new);
	$messageFull = $messageFull." http://".$_SERVER['HTTP_HOST']."/verify?id=".$new['hash_id'];
	// header("Location:".$messageFull);

	$response['rdr'] = $messageFull;



	}


	if (isset($_POST['payflexible'])) {
	  $new = [];
	  $new['hash_id'] = time()."_".rand(100000,999999);
	  $new['time_created'] = date("h:i:s");
	  $new['date_created'] = date("Y-m-d");
	  $new['visibility'] = "hide";
	  $new['input_color'] = $style_color;
	  if (isset($_SESSION['discount'])) {
	    $new['input_discount'] = $_SESSION['discount'];
	  }
	  $new['input_amount'] = $initial;

	  insertSafe($conn,'panel_payment',$new);
	  $messageFlexible = $messageFlexible." http://".$_SERVER['HTTP_HOST']."/verify?id=".$new['hash_id'];
	  // header("Location:".$messageFlexible);
	  $response['rdr'] = $messageFlexible;


	}

$response['full_price'] = number_format($price);
$response['flexible_price'] = number_format($initial);


$res = json_encode($response);
echo $res;


       ?>