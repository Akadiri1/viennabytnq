<?php
//session_start();
if(!isset($_SESSION['hash_id'])){
  header("Location:/login?rd=".base64url_encode($_SERVER['REQUEST_URI']));
  die;
}

$userInfo = selectContent($conn,'users',["hash_id"=>$_SESSION['hash_id']]);
$invoiceInfo = selectContent($conn,'sub_invoice',["hash_id"=>$_GET['id']]);

// die(var_dump($invoiceInfo));
if(count($invoiceInfo) < 1){
  header("Location:/index");
  die;
}
if(count($userInfo) < 1){
  header("Location:/index");
  die;
}

// var_dump($invoiceInfo);
// die;

if(isset($_POST['pay'])){
  // die(var_dump($_POST));
  $curl = curl_init();

  $customer_email = $_POST['email'];
  $amount = $_POST['amount'];
  $currency = "NGN";
  $txref = $_POST['ref']; // ensure you generate unique references per transaction.
  $PBFPubKey = getenv('RAVE_KEY'); // get your public key from the dashboard.
  $redirect_url = 'https://'.$_SERVER['HTTP_HOST']."/confirm_payment";
  $payment_plan = "pass the plan id"; // this is only required for recurring payments.


  curl_setopt_array($curl, array(
    // CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
    CURLOPT_URL => getenv('PAY_URL')."/flwv3-pug/getpaidx/api/v2/hosted/pay",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode([
      'amount'=>$amount,
      'customer_email'=>$customer_email,
      'currency'=>$currency,
      'txref'=>$txref,
      'PBFPubKey'=>$PBFPubKey,
      'redirect_url'=>$redirect_url,
      'payment_plan'=>$payment_plan,
      'custom_logo'=>'https://'.$_SERVER['HTTP_HOST']."/".$logo_directory,
      'custom_title'=> ucwords($invoiceInfo[0]['title']),
      'customer_firstname'=> $_POST['firstname'],
      'customer_lastname'=> $_POST['lastname']
    ]),
    CURLOPT_HTTPHEADER => [
      "content-type: application/json",
      "cache-control: no-cache"
    ],
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  if($err){
    // there was an error contacting the rave API
    die('Curl returned error: ' . $err);
  }

  $transaction = json_decode($response);
  // die(var_dump($transaction));

  if(!$transaction->data && !$transaction->data->link){
    // there was an error from the API
    print_r('API returned error: ' . $transaction->message);
  }

  // uncomment out this line if you want to redirect the user to the payment page
  //print_r($transaction->data->message);


  // redirect to page so User can pay
  // uncomment this line to allow the user redirect to the payment page
  header('Location: ' . $transaction->data->link);
}

?>

<?php
ob_start();
?>
<!DOCTYPE html>
<html lang="" dir="ltr">
<head>
  <meta charset="utf-8">
  <title><?php $site_name ?></title>
  <meta name="theme-color" content="<?php echo $color; ?>">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="google-signin-scope" content="profile email">
    <link rel="icon" href="/<?php $logo_directory ?>" type="image/x-icon">
  <meta name="google-signin-client_id" content="248617123405-2htvnhfujkps3rcmvshl02s6rlmbtck9.apps.googleusercontent.com">
  <script src="https://apis.google.com/js/platform.js" async defer></script>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta name="author" content="<?php echo $site_name ?>">
  <link rel="stylesheet" href="/assets/css/font-awesome.min.css" type="text/css">
  <!-- <link rel="stylesheet" type="text/css" href="/ui/bootstrap/css/bootstrap.min.css"> -->
  <!-- <link rel="stylesheet" type="text/css" href="/ui/btn/css/bootstrap.min.css"> -->
  <link rel="stylesheet" href="/assets/css/main.css" type="text/css">
  <link rel="stylesheet" type="text/css" href="/ui/style/styles.css">
  <link rel="stylesheet" type="text/css" href="/ui/styles/style2.css">
  <script src="/ui/js/jquery.min.js"></script>
  <link rel="stylesheet" type="text/css" href="/card/core.cleanui.css">
  <link rel="stylesheet" type="text/css" href="/card/widgets.cleanui.css">
  <link rel="stylesheet" type="text/css" href="/card/core.css">
  <link rel="stylesheet" type="text/css" href="/card/utilies.css">
  <link rel="stylesheet" type="text/css" href="/card/card.css">
  <link rel="stylesheet" href="/da/assets/fonts/fontawesome/css/fontawesome-all.min.css">
  <!-- animation css -->
  <link rel="stylesheet" href="/da/assets/plugins/animation/css/animate.min.css">
  <!-- vendor css -->
  <link rel="stylesheet" href="/da/assets/css/style.css">




  <!-- page css -->
  <link rel="stylesheet" href="/da/assets/css/pages/pages.css">
  <!-- Main Style css -->
  <!-- <link href="/prem/assets/css/style.css" rel="stylesheet" type="text/css" media="all" /> -->
  <link href='//fonts.googleapis.com/css?family=Raleway:400,100,100italic,200,200italic,300,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic' rel='stylesheet' type='text/css'>
  <link href='//fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic' rel='stylesheet' type='text/css'>
  <!-- <script src="/ui/bootstrap/js/bootstrap.min.js"></script> -->
  <!-- <link rel="stylesheet" type="text/css" href="ui/font/flaticon.css"> -->
  <link rel="manifest" href="/manifest.json" />
  <script src="/jquery.min.js" ></script>

  <link rel="stylesheet" type="text/css" href="card/vendors/font-icomoon/style.css">
  <style media="screen">
  .fa-facebook {
    background: #3B5998;
    color: white;
  }

  h1 { font-family: Georgia, Times, "Times New Roman", serif; font-size: 24px; font-style: normal; font-variant: normal; font-weight: 700; line-height: 26.4px; } h3 { font-family: Georgia, Times, "Times New Roman", serif; font-size: 14px; font-style: normal; font-variant: normal; font-weight: 700; line-height: 15.4px; } p,small,a,label { font-family: Georgia, Times, "Times New Roman", serif; font-size: 16px; font-style: normal; font-variant: normal; font-weight: 400; line-height: 20px; } blockquote { font-family: Georgia, Times, "Times New Roman", serif; font-size: 21px; font-style: normal; font-variant: normal; font-weight: 400; line-height: 30px; } pre { font-family: Georgia, Times, "Times New Roman", serif; font-size: 13px; font-style: normal; font-variant: normal; font-weight: 400; line-height: 18.5714px; }

  </style>
  <?php
  // if (isset($_SESSION['id'])) {
  	// code...include 'file';
  	// include 'include/tawkto.php';
  // } ?>
</head>
<body style="background-color:grey">

  <div class="col-md-12">
    <div id="center" class="card" style="width:100%;min-height:96vh; Background-color:white;">
      <div class="card-body text-center">
        <a  style="background-color:white;width:80px; height: 80px;" class="utils__avatar utils__avatar--border-white utils__avatar--110" href="/events">
          <div class="" style="width:100%; height: 100%;background:url('/<?php echo $logo_directory ?>'); background-size:80%; background-position:center; background-repeat:no-repeat;">
          </div>
          <!-- <img src="/ico.png" > -->
        </a>
        <br>
        <br>
        <h5><?php echo $site_name ?> Invoice</h5>

        <section id="">
          <div class="container">
            <div class="row">




              <div class="col-md-12">
                <div class="pricing-section no-color text-center" id="pricing">
                  <div class="container">
                    <div class="row">
                      <div class="col-md-12 col-sm-12 ">
                        <div class="container" id="printTable">
                          <div>
                            <div class="card">
                              <div class="row invoice-contact">
                                <div class="col-md-8">
                                  <div class="invoice-box row">
                                    <div class="col-sm-12">
                                      <table class="table table-responsive invoice-table table-borderless p-l-20">
                                        <tbody>
                                          <tr>
                                            <td><a href="index.html" class="b-brand">
                                              <img width="50" height="50" class="img-fluid" src="/<?php echo $logo_directory ?>">
                                            </a>
                                          </td>
                                        </tr>
                                        <tr>
                                          <td><?php echo $site_name ?> </td>
                                        </tr>
                                        <tr>
                                          <td><a class="text-secondary" href="mailto:<?php echo $site_email ?>" target="_top"><?php echo $site_email ?></a></td>
                                        </tr>

                                      </tbody>
                                    </table>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-4"></div>
                            </div>
                            <div class="card-body">
                              <div class="row invoive-info">
                                <div class="col-md-4 col-xs-12 invoice-client-info">
                                  <h6>Users Information :</h6>
                                  <h6 class="m-0"><?php echo ucwords($invoiceInfo[0]['name']) ?></h6>
                                  <!-- <p class="m-0 m-t-10"></p> -->
                                  <p class="m-0"><?php echo $invoiceInfo[0]['phonenumber'] ?></p>
                                  <p><a class="text-secondary" href="mailto:<?php echo $invoiceInfo[0]['email'] ?>" target="_top"><?php echo $invoiceInfo[0]['email'] ?></a></p>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                  <h6>Order Information :</h6>
                                  <table class="table table-responsive invoice-table invoice-order table-borderless">
                                    <tbody>
                                      <tr>
                                        <th>Date :</th>
                                        <td><?php echo decodeDate($invoiceInfo[0]['date_created']) ?></td>
                                      </tr>
                                      <tr>
                                        <th>Status :</th>
                                        <td>
                                          <?php if ($invoiceInfo[0]['status'] == "unpaid"): ?>
                                            <span class="label label-warning">Pending</span>
                                          <?php endif; ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <th>Id :</th>
                                        <td>
                                          #<?php echo $invoiceInfo[0]['hash_id'] ?>
                                        </td>
                                      </tr>
                                      <tr>
                                        <th>Service :</th>
                                        <td>
                                          <?php echo ucwords($invoiceInfo[0]['title']) ?>
                                        </td>
                                      </tr>


                                    </tbody>
                                  </table>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                  <h6 class="m-b-20">Invoice Number <span>#<?php echo $invoiceInfo[0]['invoice_id'] ?></span></h6>
                                  <h6 class="text-uppercase text-primary">Total Due :
                                    <span>&#x20A6;<?php echo $invoiceInfo[0]['amount_due'] ?></span>
                                  </h6>
                                </div>
                              </div>

                              <div class="row">
                                <div class="col-sm-12">
                                  <table class="table table-responsive invoice-table invoice-total">
                                    <tbody>
                                      <tr>
                                        <th>Sub Total :</th>
                                        <td>&#x20A6;<?php echo $invoiceInfo[0]['amount_due'] ?></td>
                                      </tr>
                                      <!-- <tr>
                                      <th>Taxes (10%) :</th>
                                      <td>$57.00</td>
                                    </tr>
                                    <tr>
                                    <th>Discount (5%) :</th>
                                    <td>$45.00</td>
                                  </tr> -->
                                  <tr class="text-info">
                                    <td>
                                      <hr />
                                      <h5 class="text-primary m-r-10">Total :</h5>
                                    </td>
                                    <td>
                                      <hr />
                                      <h5 class="text-primary">&#x20A6;<?php echo $invoiceInfo[0]['amount_due'] ?></h5>
                                    </td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-sm-12">
                              <h6>Terms and Condition :</h6>
                              <p>All Fee are nonrefundable
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="row text-center">
                        <div class="col-sm-6 invoice-btn-group text-center">

                          <form class="" action="" method="post">
                            <input type="hidden" name="amount" value="<?php echo $invoiceInfo[0]['amount_due'] ?>" /> <!-- Replace the value with your transaction amount -->
                            <input type="hidden" name="payment_options" value="" /> <!-- Can be card, account, ussd, qr, mpesa, mobilemoneyghana  (optional) -->
                            <input type="hidden" name="description" value="Payment" /> <!-- Replace the value with your transaction description -->
                            <input type="hidden" name="logo" value="<?php echo 'https://'.$_SERVER['HTTP_HOST']."/".$logo_directory; ?>" /> <!-- Replace the value with your logo url (optional) -->
                            <input type="hidden" name="title" value="<?php echo ucwords($invoiceInfo[0]['title']); ?>" /> <!-- Replace the value with your transaction title (optional) -->
                            <input type="hidden" name="country" value="NG" /> <!-- Replace the value with your transaction country -->
                            <input type="hidden" name="currency" value="NGN" /> <!-- Replace the value with your transaction currency -->
                            <input type="hidden" name="email" value="<?php echo $invoiceInfo[0]['email'] ?>" /> <!-- Replace the value with your customer email -->
                            <input type="hidden" name="firstname" value="<?php echo $invoiceInfo[0]['name'] ?>" /> <!-- Replace the value with your customer firstname (optional) -->
                            <input type="hidden" name="lastname"value="rave" /> <!-- Replace the value with your customer lastname (optional) -->
                            <input type="hidden" name="phonenumber" value="<?php echo $invoiceInfo[0]['phonenumber'] ?>" /> <!-- Replace the value with your customer phonenumber (optional if email is passes) -->
                            <input type="hidden" name="pay_button_text" value="Subscribe" /> <!-- Replace the value with the payment button text you prefer (optional) -->
                            <input type="hidden" name="ref" value="<?php echo $invoiceInfo[0]['invoice_id'] ?>" /> <!-- Replace the value with your transaction reference. It must be unique per transaction. You can delete this line if you want one to be generated for you. -->
                            <!--<input type="hidden" name="env" value="staging">  live or staging -->
                             <!-- Put your secret key here -->
                            <!-- <input type="hidden" name="successurl" value="https://attendout.com/confirm_payment">  -->
                            <!-- Put your success url here -->
                            <!-- <input type="hidden" name="failureurl" value="https://attendout.com/failed"> -->
                            <input class="btn btn-success" type="submit" name="pay" value="Pay &#x20A6;<?php echo $invoiceInfo[0]['amount_due'] ?>">
                          </form>


                        </div>
                        <!-- <div class="col-sm-6 invoice-btn-group text-center">
                          <button type="button" class="btn btn-primary btn-print-invoice m-b-10">Print</button>


                          <button type="button" class="btn btn-secondary m-b-10 ">Cancel</button>
                        </div> -->
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>





      </div>
    </div>
  </div>
</section>
</div>
</div>
<script src="/da/assets/js/vendor-all.min.js"></script>
<script src="/da/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="/da/assets/js/pcoded.min.js"></script>
<!-- <script src="/da/assets/js/menu-setting.min.js"></script> -->


<script>
// print button
function printData() {
  var divToPrint = document.getElementById("printTable");
  newWin = window.open("");
  newWin.document.write(divToPrint.outerHTML);
  newWin.print();
  newWin.close();
}

$('.btn-print-invoice').on('click', function() {
  printData();
})
</script>



</body>
</html>
