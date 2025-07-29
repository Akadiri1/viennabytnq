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
      var url = "signup-backend";
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
             if($_SERVER['REQUEST_URI'] == "/login" || $_SERVER['REQUEST_URI'] == "/register"){
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
