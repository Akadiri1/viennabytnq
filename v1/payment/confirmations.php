<?php
ob_start();
//session_start();
include('include/Flutterwave-Rave-PHP-SDK/lib/rave.php');
include('include/Flutterwave-Rave-PHP-SDK/lib/raveEventHandlerInterface.php');
use Unirest\Request\Body;


$invoice = selectContent($conn,'sub_invoice',['invoice_id'=>$_GET['txref']]);
// var_dump($invoice);


if(count($invoice) < 1){
// exit();
}else{

  if(isset($_GET['cancelled'])){
    $data["status"] = "Success and Cancelled";
    $data["invoice_id"] = $_GET['txref'];
    $data['time_created'] = date("h:i:s");
    $data['date_created'] = date("Y-m-d");
    insertSafe($conn,'complications',$data);
    header("Location:/error.php?message=payment cancelled on page. kindly wait to see if payment was processed");
    die;
  }


extract($invoice[0]);


$currency = "NGN";


// $query = array(
//     "SECKEY" => "FLWSECK-a69a2e475fe63bdf6302fb2dace3b22b-X",
//     "txref" => $_GET['txref']
// );
//
// $data_string = json_encode($query);
//
//
// $ch = curl_init('https://ravesandbox.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify');
// https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify
// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
// curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//
// $response = curl_exec($ch);
//
// $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
// $header = substr($response, 0, $header_size);
// $body = substr($response, $header_size);
//
// curl_close($ch);
//
// $resp = json_decode($response, true);
//
// die(var_dump($response));
//
//   $paymentStatus = $resp['data']['status'];
//   $chargeResponsecode = $resp['data']['chargecode'];
//   $chargeAmount = $resp['data']['amount'];
//   $chargeCurrency = $resp['data']['currency'];
//   $customerMail = $resp['data']['custemail'];
//   $customerName = $resp['data']['custname'];



$data = array('txref' => $_GET['txref'],
  'SECKEY' => getenv("RAVE_SECRETE")//secret key from pay button generated on rave dashboard
);



  // make request to endpoint using unirest.
  $headers = array('Content-Type' => 'application/json');
  $body = Unirest\Request\Body::json($data);
  $url = getenv("PAY_URL")."/flwv3-pug/getpaidx/api/v2/verify"; //please make sure to change this to production url when you go live
// die($url);
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


$_SESSION['success'] = $invoice[0]['hash_id'];
header("Location:".$invoice[0]['redirectUrl']);
      }else{
        $data["status"] = "Success, Verified But Payment Unmatched";
        $data["invoice_id"] = $_GET['txref'];
        $data['time_created'] = date("h:i:s");
        $data['date_created'] = date("Y-m-d");
        insertSafe($conn,'complications',$data);
        header("Location:/error.php?message=payment not processed. ");
        die;
      }
  }else{
    $data["status"] = "Success and But Unverified";
    $data["invoice_id"] = $_GET['txref'];
    $data['time_created'] = date("h:i:s");
    $data['date_created'] = date("Y-m-d");
    insertSafe($conn,'complications',$data);
      header("Location:/error.php?message=Payment not processed");
    die;
  }




}


 ?>
