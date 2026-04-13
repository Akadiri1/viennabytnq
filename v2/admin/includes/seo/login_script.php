<script>
window.fbAsyncInit = function() {
    // FB JavaScript SDK configuration and setup
    FB.init({
      appId      : <?php echo $fbid ?>, // FB App ID
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
function fbdLogin() {
  var url = "/signup-backend";
  var method = "POST";
  var param = "hash_id="+"banji";
     param += "&firstname="+"banji";
     param += "&lastname="+"banji";
     param += "&email="+"banji";
     param += "&sso="+"facebook";
     param += "&lastname="+"banji";
     param += "&location="+"<?php
       if(isset($_GET['rd'])){
         echo $_GET['rd'];
       }else{
         if($_SERVER['REQUEST_URI'] == "/login" || $_SERVER['REQUEST_URI'] == "/register"){
         echo "";

       }else{
         echo base64url_encode($_SERVER['REQUEST_URI']);
       }

       }

    ?>";
     login_ajax(method,url,param)



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
            document.getElementById('status').innerHTML = 'Unable to login to <?php echo $site_name ?>';
         }
         }
       }

       xhr.open(method,url,true);
       xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
       xhr.send(param);
     }
}
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
             if($_SERVER['REQUEST_URI'] == "/login" || $_SERVER['REQUEST_URI'] == "/register"){
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
       document.getElementById('status').innerHTML = 'Unable to login to <?php echo $site_name ?>';
    }
    }
  }

  xhr.open(method,url,true);
  xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
  xhr.send(param);
}
</script>
