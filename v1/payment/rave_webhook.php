<?php


// header("Content-Security-Policy: default-src 'none';");
// header("Access-Control-Allow-Origin: http://192.168.33.23/test.php");
include('include/Flutterwave-Rave-PHP-SDK/lib/rave.php');
include('include/Flutterwave-Rave-PHP-SDK/lib/raveEventHandlerInterface.php');
use Unirest\Request\Body;
$request_headers = getallheaders();
if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}
//
// if( !in_array($request_headers['Host'],["192.168.33.55","attendout.com"]) ){
//   http_response_code(502);
//   die;
// }


$json = file_get_contents('php://input');
$json = json_decode($json,true);




$signature = (isset($_SERVER['HTTP_VERIF_HASH']) ? $_SERVER['HTTP_VERIF_HASH'] : '');

/* It is a good idea to log all events received. Add code *
 * here to log the signature and body to db or file       */

if (!$signature) {
    // echo "No Signature";
    // only a post with rave signature header gets our attention
    exit();
}

// Store the same signature on your server as an env variable and check against what was sent in the headers
$local_signature = getenv('SECRET_HASH');

// confirm the event's signature
if( $signature !== $local_signature ){
  // silently forget this ever happened
   //"Signature Mismatch";
  exit();
}


// var_dump($json);



if($json['event.type'] == "BANK_TRANSFER_TRANSACTION"){



  if($json['status'] == "successful"){

    $determine = explode("_",$json['txRef']);
    if(count($determine) > 1){



  $invoice = selectContent($conn,'sub_invoice',['invoice_id'=>$json['txRef']]);
  // var_dump($invoice);
  if(count($invoice) < 1){
// exit();
  }else{

    if(isset($_GET['cancelled'])){
      $data["status"] = "Payment Success and Cancelled";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }
  extract($invoice[0]);
  $currency = "NGN";

  $data = array('txref' => $json['txRef'],
    'SECKEY' => getenv("RAVE_SECRETE")//secret key from pay button generated on rave dashboard
  );



    // make request to endpoint using unirest.
    $headers = array('Content-Type' => 'application/json');
    $body = Unirest\Request\Body::json($data);
    $url = getenv("PAY_URL")."/flwv3-pug/getpaidx/api/v2/verify"; //please //please make sure to change this to production url when you go live

  // Make `POST` request and handle response with unirest
    $response = Unirest\Request::post($url, $headers, $body);
  // var_dump($response);
  // die;
  // echo $amount_due;
  // echo $response->body->data->amount;
  // echo "<hr>";
  // echo $amount_due;
    //check the status is success
    if ($response->body->data->status === "successful" && $response->body->data->chargecode === "00") {
        //confirm that the amount is the amount you wanted to charge
        if ($response->body->data->amount === intval($amount_due)) {


updateContent($conn,'sub_invoice',["status"=>"paid"],["invoice_id"=>$_GET['txref']]);


include "process_script.php";
exit();






  // $array_keys = array_keys($array[$uri[1]]);













    //header("Location:/paidtraineeqr/".$data['qr']);
        }else{
          $data["status"] = "Training - Success, Verified But Payment Unmatched";
          $data["invoice_id"] = $json['txRef'];
          $data['time_created'] = date("h:i:s");
          $data['date_created'] = date("Y-m-d");
          insertSafe($conn,'complications',$data);
          //header("Location:/paymentFailed");
          die;
        }
    }else{
      $data["status"] = "Training - Success But Unverified";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }




  }






    }else{
      exit();
    }






  }
}
if($json['event.type'] == "USSD_TRANSACTION"){



  if($json['status'] == "successful"){

    $determine = explode("_",$json['txRef']);
    if(count($determine) > 1){



  $invoice = selectContent($conn,'sub_invoice',['invoice_id'=>$json['txRef']]);
  // var_dump($invoice);
  if(count($invoice) < 1){
// exit();
  }else{

    if(isset($_GET['cancelled'])){
      $data["status"] = "Payment Success and Cancelled";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }
  extract($invoice[0]);
  $currency = "NGN";

  $data = array('txref' => $json['txRef'],
    'SECKEY' => getenv("RAVE_SECRETE")//secret key from pay button generated on rave dashboard
  );



    // make request to endpoint using unirest.
    $headers = array('Content-Type' => 'application/json');
    $body = Unirest\Request\Body::json($data);
    $url = getenv("PAY_URL")."/flwv3-pug/getpaidx/api/v2/verify"; //please //please make sure to change this to production url when you go live

  // Make `POST` request and handle response with unirest
    $response = Unirest\Request::post($url, $headers, $body);
  // var_dump($response);
  // die;
  // echo $amount_due;
  // echo $response->body->data->amount;
  // echo "<hr>";
  // echo $amount_due;
    //check the status is success
    if ($response->body->data->status === "successful" && $response->body->data->chargecode === "00") {
        //confirm that the amount is the amount you wanted to charge
        if ($response->body->data->amount === intval($amount_due)) {


updateContent($conn,'sub_invoice',["status"=>"paid"],["invoice_id"=>$_GET['txref']]);


// mail("bob@domain.com","Hello World","Email sent using PHP via msmtp");

include "process_script.php";
exit();






  // $array_keys = array_keys($array[$uri[1]]);













    //header("Location:/paidtraineeqr/".$data['qr']);
        }else{
          $data["status"] = "Training - Success, Verified But Payment Unmatched";
          $data["invoice_id"] = $json['txRef'];
          $data['time_created'] = date("h:i:s");
          $data['date_created'] = date("Y-m-d");
          insertSafe($conn,'complications',$data);
          //header("Location:/paymentFailed");
          die;
        }
    }else{
      $data["status"] = "Training - Success But Unverified";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }




  }






    }else{
      exit();
    }






  }
}
if($json['event.type'] == "ACCOUNT_TRANSACTION"){



  if($json['status'] == "successful"){

    $determine = explode("_",$json['txRef']);
    if(count($determine) > 1){



  $invoice = selectContent($conn,'sub_invoice',['invoice_id'=>$json['txRef']]);
  // var_dump($invoice);
  if(count($invoice) < 1){
// exit();
  }else{

    if(isset($_GET['cancelled'])){
      $data["status"] = "Payment Success and Cancelled";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }
  extract($invoice[0]);
  $currency = "NGN";

  $data = array('txref' => $json['txRef'],
    'SECKEY' => getenv("RAVE_SECRETE")//secret key from pay button generated on rave dashboard
  );



    // make request to endpoint using unirest.
    $headers = array('Content-Type' => 'application/json');
    $body = Unirest\Request\Body::json($data);
    $url = getenv("PAY_URL")."/flwv3-pug/getpaidx/api/v2/verify"; //please //please make sure to change this to production url when you go live

  // Make `POST` request and handle response with unirest
    $response = Unirest\Request::post($url, $headers, $body);
  // var_dump($response);
  // die;
  // echo $amount_due;
  // echo $response->body->data->amount;
  // echo "<hr>";
  // echo $amount_due;
    //check the status is success
    if ($response->body->data->status === "successful" && $response->body->data->chargecode === "00") {
        //confirm that the amount is the amount you wanted to charge
        if ($response->body->data->amount === intval($amount_due)) {


updateContent($conn,'sub_invoice',["status"=>"paid"],["invoice_id"=>$_GET['txref']]);


include "process_script.php";
exit();






  // $array_keys = array_keys($array[$uri[1]]);













    //header("Location:/paidtraineeqr/".$data['qr']);
        }else{
          $data["status"] = "Training - Success, Verified But Payment Unmatched";
          $data["invoice_id"] = $json['txRef'];
          $data['time_created'] = date("h:i:s");
          $data['date_created'] = date("Y-m-d");
          insertSafe($conn,'complications',$data);
          //header("Location:/paymentFailed");
          die;
        }
    }else{
      $data["status"] = "Training - Success But Unverified";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }




  }






    }else{
      exit();
    }






  }
}
if($json['event.type'] == "MVISA-QR_TRANSACTION"){



  if($json['status'] == "successful"){

    $determine = explode("_",$json['txRef']);
    if(count($determine) > 1){



  $invoice = selectContent($conn,'sub_invoice',['invoice_id'=>$json['txRef']]);
  // var_dump($invoice);
  if(count($invoice) < 1){
// exit();
  }else{

    if(isset($_GET['cancelled'])){
      $data["status"] = "Payment Success and Cancelled";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }
  extract($invoice[0]);
  $currency = "NGN";

  $data = array('txref' => $json['txRef'],
    'SECKEY' => getenv("RAVE_SECRETE")//secret key from pay button generated on rave dashboard
  );



    // make request to endpoint using unirest.
    $headers = array('Content-Type' => 'application/json');
    $body = Unirest\Request\Body::json($data);
    $url = getenv("PAY_URL")."/flwv3-pug/getpaidx/api/v2/verify"; //please //please make sure to change this to production url when you go live

  // Make `POST` request and handle response with unirest
    $response = Unirest\Request::post($url, $headers, $body);
  // var_dump($response);
  // die;
  // echo $amount_due;
  // echo $response->body->data->amount;
  // echo "<hr>";
  // echo $amount_due;
    //check the status is success
    if ($response->body->data->status === "successful" && $response->body->data->chargecode === "00") {
        //confirm that the amount is the amount you wanted to charge
        if ($response->body->data->amount === intval($amount_due)) {


updateContent($conn,'sub_invoice',["status"=>"paid"],["invoice_id"=>$_GET['txref']]);


include "process_script.php";
exit();






  // $array_keys = array_keys($array[$uri[1]]);













    //header("Location:/paidtraineeqr/".$data['qr']);
        }else{
          $data["status"] = "Training - Success, Verified But Payment Unmatched";
          $data["invoice_id"] = $json['txRef'];
          $data['time_created'] = date("h:i:s");
          $data['date_created'] = date("Y-m-d");
          insertSafe($conn,'complications',$data);
          //header("Location:/paymentFailed");
          die;
        }
    }else{
      $data["status"] = "Training - Success But Unverified";
      $data["invoice_id"] = $json['txRef'];
      $data['time_created'] = date("h:i:s");
      $data['date_created'] = date("Y-m-d");
      insertSafe($conn,'complications',$data);
      //header("Location:/paymentFailed");
      die;
    }




  }






    }else{
      exit();
    }






  }
}


die();
// $json["PBFPubKey"] = 'FLWPUBK-82b3dd14c27d064bc65cdb4a8ebe871b-X';
// $json["PBFPubKey"] = 'FLWPUBK-152b9bb9bdc800a667ac03146ce0e604-X';
// die(var_dump($json));
// $json = ["recipientaccount"=>3095270743,"destbankcode"=>"011","PBFPubKey"=>"FLWPUBK-82b3dd14c27d064bc65cdb4a8ebe871b-X"];
// die(var_dump($json));
// // Converts it into a PHP object
// $data = json_decode($json,true);

// $headers = array('Content-Type' => 'application/json');
// $body = Unirest\Request\Body::json($json);



// $url = "https://api.ravepay.co/flwv3-pug/getpaidx/api/resolve_account";
// $url = "https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/resolve_account";
// $response = Unirest\Request::post($url, $headers, $body);
// var_dump($response->body->data->data);
// die();
// if ($response->body->data->status === "success" && $response->body->data->data->responsecode === "00"){
//   $result['data'] = $response->body->data->data;
//   $result['success'] = true;
// }else{
//   $result['data'] = "";
//   $result['error'] = true;
// }

// if(!isset($data['data']) || !isset($data['values'])){
//     http_response_code(400);
//     die();
// }


$res = json_encode($result);
echo $res;
