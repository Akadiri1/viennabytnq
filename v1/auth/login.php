<?php
ob_start();


$login_page = true;

$error = [];
if(array_key_exists('submit', $_POST)){

if(empty($_POST['email'])){
  $error['email']="Enter a email";
}

if(empty($_POST['pword'])){
  $error['pword']="Enter a password";
}

if(empty($error)){
  $clean = array_map('trim', $_POST);
  $loc = "";
  if(isset($_GET['rd'])){
    $loc = base64url_decode($_GET['rd']);
  }else{
    $loc = "timesheet";
  }




$where['email'] =  $clean['email'];
$record = selectContent($conn,"users",$where);
// $record2 = selectContent($conn,"admin",["email"=>$clean['email']]);
// die(var_dump($record));
if(count($record) > 0 && password_verify($clean['pword'],$record[0]['hash'])){
  if( $record[0]['user_status'] !== "1" ){
    $suc = 'Dear '.ucwords($record[0]['firstname']).', You Have Not been verified, kindly visit your email for verification link';
    $message = $suc;
    if(isset($_GET['rd'])){
              header("Location:login?wn=$message&rd=".base64url_decode($_GET['rd']));
              exit();
    }else{
          header("Location:login?wn=$message");
          exit();
    }

    die;
  }



// // session_start();
//   if($record[0]['is_admin'] == 1  || $record[0]['level'] == "MASTER"){
//     $_SESSION['user_admin'] = $record[0]['hash_id'];
//     // $_SESSION['admin_name'] = $record[0]['firstname']." ".$record[0]['lastname'];
//   }

  $_SESSION['id'] = $record[0]['hash_id'];
  // setLogin($dbconn,$hash_id);
  // die;
    header("Location:$loc");
    exit();

}elseif(count($record2) > 0 && password_verify($clean['pword'],$record2[0]['hash'])){
  if( $record2[0]['user_status'] !== "1" ){
    $suc = 'Dear '.ucwords($record2[0]['firstname']).', You Have Not been verified, kindly visit your email for verification link';
    $message = $suc;
    if(isset($_GET['rd'])){
              header("Location:login?wn=$message&rd=".base64url_decode($_GET['rd']));
              exit();
    }else{
          header("Location:login?wn=$message");
          exit();
    }

    die;
  }



// // session_start();
//   if($record[0]['is_admin'] == 1  || $record[0]['level'] == "MASTER"){
//     $_SESSION['user_admin'] = $record[0]['hash_id'];
//     // $_SESSION['admin_name'] = $record[0]['firstname']." ".$record[0]['lastname'];
//   }

  $_SESSION['id'] = $record2[0]['hash_id'];
  // setLogin($dbconn,$hash_id);
  // die;
    header("Location: /timesheet");
    exit();

  }else{

  $suc = 'Invalid Email or Password';
  $message = $suc;
  if(isset($_GET['rd'])){
            header("Location:login?err=$message&rd=".$_GET['rd']);
            exit();
  }else{
  header("Location:login?err=$message");
  exit();
}
}


  // usersLogin($conn,$conn1,$sid,$clean,$loc,$st);
}
}


 ?>

<!DOCTYPE html>
<html lang="" dir="ltr">
  <head>
    <meta name="theme-color" content="<?php //echo $themecolor ?>">
    <meta charset="utf-8">
    <title><?php echo $site_name ?> Login</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

   	<!-- <meta name="mediacpmpl-site-verification" content="88b2124c10bb346cdf2e629951be2e58"> -->
   	<!-- <meta name="viewport" content="width=device-width"> -->
   		<meta name="mobile-web-app-capable" content="yes">
      <meta name="google-signin-scope" content="profile email">
         <meta name="google-signin-client_id" content="<?php echo getenv("GOOGLE_LOGIN"); ?>">
         <script src="https://apis.google.com/js/platform.js" async defer></script>
   		<link rel="icon" sizes="192x192" href="/ico.png">

   	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
   	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <script src="https://boardspeck.com/ui/js/jquery.min.js"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="/da/assets/css/style.css">
   	<link rel="stylesheet" href="/da/assets/fonts/material/css/materialdesignicons.min.css">
       <link rel="stylesheet" href="/da/assets/fonts/fontawesome/css/fontawesome-all.min.css">
           <link rel="stylesheet" href="/da/assets/fonts/themify/themify.css" >
             <link rel="stylesheet" href="/da/assets/plugins/animation/css/animate.min.css" >
               <link rel="stylesheet" href="/da/assets/plugins/prism/css/prism.min.css" >
               <link rel="stylesheet" href="/da/assets/plugins/modal-window-effects/css/md-modal.css">
             <!-- Light Box -->
             <link rel="stylesheet" href="/da/assets/plugins/ekko-lightbox/css/ekko-lightbox.min.css">
             <link rel="stylesheet" href="/da/assets/plugins/lightbox2-master/css/lightbox.min.css">
              <script src="/da/assets/js/pages/dashboard-help.js"></script>
              <link rel="shortcut icon" href="/vipresa_favicon.png" type="image/x-icon">
              <link rel="icon" href="/vipresa_favicon.png" type="image/x-icon">
   <!-- <link rel="stylesheet" type="text/css" href="https://boardspeck.com/card/core.cleanui.css"> -->


   <link href='//fonts.googleapis.com/css?family=Raleway:400,100,100italic,200,200italic,300,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic' rel='stylesheet' type='text/css'>
   <link href='//fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic' rel='stylesheet' type='text/css'>
   <script src="https://boardspeck.com/ui/bootstrap/js/bootstrap.min.js"></script>
   <!-- <link rel="stylesheet" type="text/css" href="ui/font/flaticon.css"> -->
   <link rel="manifest" href="/manifest.json" />

    <link rel="stylesheet" type="text/css" href="card/vendors/font-icomoon/style.css">
    <style media="screen">
    .fa-facebook {
   background: #3B5998;
   color: white;
   }
   h1 { font-family: Georgia, Times, "Times New Roman", serif; font-size: 24px; font-style: normal; font-variant: normal; font-weight: 700; line-height: 26.4px; } h3 { font-family: Georgia, Times, "Times New Roman", serif; font-size: 14px; font-style: normal; font-variant: normal; font-weight: 700; line-height: 15.4px; } p,small,a,label { font-family: Georgia, Times, "Times New Roman", serif; font-size: 16px; font-style: normal; font-variant: normal; font-weight: 400; line-height: 20px; } blockquote { font-family: Georgia, Times, "Times New Roman", serif; font-size: 21px; font-style: normal; font-variant: normal; font-weight: 400; line-height: 30px; } pre { font-family: Georgia, Times, "Times New Roman", serif; font-size: 13px; font-style: normal; font-variant: normal; font-weight: 400; line-height: 18.5714px; }

    </style>
  </head>

  <body >
    <script>
    window.fbAsyncInit = function() {
        // FB JavaScript SDK configuration and setup
        FB.init({
          appId      : '<?php echo getenv('FBID') ?>', // FB App ID
          cookie     : true,  // enable cookies to allow the server to access the session
          xfbml      : true,  // parse social plugins on this page
          version    : 'v2.8' // use graph api version 2.8
        });

        // Check whether the user already logged in
        FB.getLoginStatus(function(response) {
            if (response.status === 'connected') {
                //display user data
                // getFbUserData();
            }
        });
    };

    // Load the JavaScript SDK asynchronously
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    // Facebook login with JavaScript SDK

    function fbLogin() {

        FB.login(function (response) {
            if (response.authResponse) {
                // Get and display the user profile data
                getFbUserData();
            } else {
                document.getElementById('status').innerHTML = 'User cancelled login or did not fully authorize.';
            }
        }, {scope: 'email'});
    }

    // Fetch the user profile data from facebook
    function getFbUserData(){
        FB.api('/me', {locale: 'en_US', fields: 'id,first_name,last_name,email,link,gender,locale,picture'},
        function (response) {

          console.log(response);
          var url = "/signup-backend";
          var method = "POST";
          var param = "hash_id="+response.id;
             param += "&firstname="+response.first_name;
             param += "&lastname="+response.last_name;
             param += "&email="+response.email;
             param += "&sso="+"facebook";
             param += "&location="+"<?php
               if(isset($_GET['rd'])){
                 echo $_GET['rd'];
               }else{
                 if($_SERVER['REQUEST_URI'] == "/login" || $_SERVER['REQUEST_URI'] == "/signup"){
                 echo "";

                  }else{
                 echo base64url_encode($_SERVER['REQUEST_URI']);
                  }

               }

            ?>";
             login_ajax(method,url,param)






            // document.getElementById('fbLink').setAttribute("onclick","fbLogout()");
            // document.getElementById('fbLink').innerHTML = 'Logout from Facebook';
            // document.getElementById('status').innerHTML = 'Thanks for logging in, ' + response.first_name + '!';
            // document.getElementById('userData').innerHTML = '<p><b>FB ID:</b> '+response.id+'</p><p><b>Name:</b> '+response.first_name+' '+response.last_name+'</p><p><b>Email:</b> '+response.email+'</p><p><b>Gender:</b> '+response.gender+'</p><p><b>Locale:</b> '+response.locale+'</p><p><b>Picture:</b> <img src="'+response.picture.data.url+'"/></p><p><b>FB Profile:</b> <a target="_blank" href="'+response.link+'">click to view profile</a></p>';
        });
    }

    // Logout from facebook
    function fbLogout() {
        FB.logout(function() {
            document.getElementById('fbLink').setAttribute("onclick","fbLogin()");
            document.getElementById('fbLink').innerHTML = '<img src="fblogin.png"/>';
            document.getElementById('userData').innerHTML = '';
            document.getElementById('status').innerHTML = 'You have successfully logout from Facebook.';
        });
    }
    function login_ajax(method,url,param){
      var xhr = new XMLHttpRequest();
      xhr.onreadystatechange = function(){
        if(xhr.readyState == 4 && xhr.status == 200){
          console.log(xhr.responseText);
        var res = JSON.parse(xhr.responseText);
        console.log(res);
        if(res.status){
          if(res.status == "success"){
            window.location = res.location;
          }else{
             document.getElementById('status').innerHTML = 'You have already signed up click <a href="/login">[HERE] to signin</a>';
          }
        }else{
           document.getElementById('status').innerHTML = 'Unable to login to Attendout';
        }
        }
      }

      xhr.open(method,url,true);
      xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
      xhr.send(param);
    }
    </script>

    <div class="auth-wrapper">
   	<div class="auth-content container">
   		<div class="card">
   			<div class="row align-items-center">
   				<div class="col-md-6">
   					<div class="card-body">
   						<!-- <img src="../assets/images/logo-dark.png" alt="" class="img-fluid mb-4"> -->
   						<h4 class="mb-3 f-w-400"><img width="100px" height="100px" src="<?php echo $logo_directory ?>" alt="" class="img-fluid mb-4"><?php #echo $site_name ?></h4>
   						<h6 class="mb-3 f-w-400">Login into your account</h6>

               <!-- <button class="btn btn-facebook mb-2 mr-2" onclick="fbLogin()" id="fbLink"><i class="fab fa-facebook-f"></i>facebook</button> -->
               <!-- <button class="btn btn-googleplus mb-2 mr-2">    </button> -->
               <div  class="g-signin2 mb-2 mr-2" data-onsuccess="onSignIn" data-theme="dark"></div>
               <!-- Display user profile data -->
               <div id="userData"></div>
               <div id="status"></div>
               <!-- <button class="btn btn-twitter mb-2 mr-2"><i class="fab fa-twitter"></i>Twitter</button> -->
             <!-- <div class="saprator"><span>OR</span></div> -->
             <?php if (isset($_GET['success'])): ?>
               <div class="alert alert-success alert-dismissible show" role="alert">
       											<strong>Successful!</strong> <?php echo $_GET['success']; ?>
       											<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
       										</div>
             <?php endif; ?>
             <?php if (isset($_GET['wn'])): ?>
               <div class="alert alert-warning alert-dismissible show" role="alert">
       											<strong>Notice!</strong> <?php echo $_GET['wn']; ?>
       											<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
       										</div>
             <?php endif; ?>
             <?php if (isset($_GET['err'])): ?>
               <div class="alert alert-danger alert-dismissible show" role="alert">
       											<strong>Notice!</strong> <?php echo str_replace("_", " ", $_GET['err']); ?>
       											<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
       										</div>
             <?php endif; ?>
             <?php if (isset($_GET['ne'])): ?>
               <div class="alert alert-danger alert-dismissible show" role="alert">
       											<strong>Notice!</strong> <?php echo $_GET['ne']; ?>
       											<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
       										</div>
             <?php endif; ?>
   <form class="" action="" method="post">


   						<div class="input-group mb-2">
   							<div class="input-group-prepend">
   								<span class="input-group-text"><i class="feather icon-mail"></i></span>
   							</div>
   							<input name="email" type="email" class="form-control" placeholder="Email address" required>
   						</div>
   						<div class="input-group mb-3">
   							<div class="input-group-prepend">
   								<span class="input-group-text"><i class="feather icon-lock"></i></span>
   							</div>
   							<input name="pword" type="password" class="form-control" placeholder="Password" required>
   						</div>
               <!-- <div class="form-group text-left mt-2">
                 <div class="checkbox checkbox-primary d-inline">
                   <input type="checkbox" name="checkbox-fill-1" id="checkbox-fill-a1" checked="">
                   <label for="checkbox-fill-a1" class="cr"> Save credentials</label>
                 </div>
               </div> -->
               <!-- <button >Login</button> -->
               <input class="btn btn-primary mb-4" type="submit" name="submit" value="Login">

               </form>
               <p class="mb-2 text-muted">Forgot password? <a href="/forgotPassword" class="f-w-400">Reset</a></p>
               <!-- <p class="mb-0 text-muted">Don’t have an account? <a href="/signup" class="f-w-400">Signup</a></p> -->




   					</div>
   				</div>
   				<div class="col-md-6 d-none d-md-block">
   					<img src="<?php echo $logo_directory ?>" style="max-width:300px" alt="" class="img-fluid">
   				</div>
   			</div>
   		</div>
      <div class="mx-auto"  >
        <span>Powered and Secured by <a style="font-size:10px" href="https://mckodev.com.ng">Mckodev Tech Lab</a> </span>
      </div>
   	</div>
   </div>
<script type="text/javascript" src="jquery-qrcode-master/qrcode.min.js"></script>

<script>
$(document).ready(function(){
var element = $("#html-content-holder"); // global variable
var getCanvas; // global variable

           html2canvas(element, {
           onrendered: function (canvas) {
                  // $("#previewImage").append(canvas);

                  getCanvas = canvas;
                  document.getElementById('btn-Convert-Html2Image').innerHTML = 'Click to Download';
                  document.getElementById('btn-Convert-Html2Image').setAttribute("class","btn btn-success btn sm");
                  // console.log("me");
               }
           });





  $("#btn-Convert-Html2Image").on('click', function () {
    console.log('here');
    getCanvas.webkitImageSmoothingEnabled = false;
    getCanvas.mozImageSmoothingEnabled = false;
    getCanvas.imageSmoothingEnabled = false;
    var imgageData = getCanvas.toDataURL("image/jpg",1.0);

    // Now browser starts downloading it instead of just showing it
    var newData = imgageData.replace(/^data:image\/png/, "data:application/octet-stream");
    $("#btn-Convert-Html2Image").attr("download", "attendoutevent_invitation.jpg").attr("href", newData);
    // document.getElementById('btn-Convert-Html2Image').innerHTML = '';
    // document.getElementById('btn-Convert-Html2Image').setAttribute("class","");

    // window.location= "event?id=<?php //echo $hash_id ?>"
  });
});



</script>
<script>
    function onSignIn(googleUser) {
      // Useful data for your client-side scripts:
      var profile = googleUser.getBasicProfile();
      console.log("ID: " + profile.getId()); // Don't send this directly to your server!
      console.log('Full Name: ' + profile.getName());
      console.log('Given Name: ' + profile.getGivenName());
      console.log('Family Name: ' + profile.getFamilyName());
      console.log("Image URL: " + profile.getImageUrl());
      console.log("Email: " + profile.getEmail());

      // The ID token you need to pass to your backend:
      var id_token = googleUser.getAuthResponse().id_token;
      var url = "/signup-backend";
      var method = "POST";
      var param = "hash_id="+profile.getId();
         param += "&firstname="+profile.getName();
         param += "&lastname="+profile.getGivenName();
         param += "&email="+profile.getEmail();
         param += "&sso="+"google";
         param += "&location="+"<?php
           if(isset($_GET['rd'])){
             echo $_GET['rd'];
           }else{
             if($_SERVER['REQUEST_URI'] == "/login" || $_SERVER['REQUEST_URI'] == "/signup"){
             echo "";

           }else{
             echo base64url_encode($_SERVER['REQUEST_URI']);
           }

           }

        ?>";
         googleUser.disconnect()
         login_ajax(method,url,param)
      console.log("ID Token: " + id_token);
    }





  </script>


  </body>
</html>
