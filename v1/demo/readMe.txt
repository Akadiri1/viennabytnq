
copy the demo folder into the v1 folder

===============================
copy /da folder to /www folder

Create mkp-images folder 

you can use the command { mkdir /vagrant/www/mkp-images }

===============================================================
Then add this to the <head> of default header.php

<?php
  if($websiteStyle[0]['status'] === "demo"){
 ?>
<link rel="stylesheet" href="/da/assets/css/style.css">

<link rel="stylesheet" href="https://mckodev.com.ng/mkp_assets/plugin/style.css?ver=<?php echo time();?>">


<?php  }?>


Add font awesome cdn if the template does not have font awesome

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" integrity="sha512-1sCRPdkRXhBV2PBLUdRb4tMg1w2YPf37qatUFeS7zlBy7jJI8Lf4VHwWfZZfpXtYSLy85pkm9GaYVYMfw5BC1A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

=======================================================
create style.php in your includes folder

copy the template default style.CSS with style tag

  <style media="screen">
    default template style
  </style>

and replace the default color with #<?php echo $style_color;?>

Then add this to the <head> in the header.php also

<?php if (isset($style_color)){ ?>
 <?php include APP_PATH."/views/includes/style.php";?>

<?php } ?>

=========================================================

Then add this to the footer.php

<?php
  if($websiteStyle[0]['status'] === "demo"){
 ?>
<?php
//include APP_PATH.'/demo/includes/demo.php';
 ?>
 <!--
 <script src="/da/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
 <script src="/da/assets/js/pcoded.min.js"></script>
 <script src="/da/demo.min.js"></script>-->
 
 <script src="https://mckodev.com.ng/mkp_assets/plugin/script.js?ver=<?php echo time();?>"></script>

 <?php
}
  ?>

================================================================
Replace the index.php file with the one in this demo folder



Then in the index add your server ip or domain to $allowedHeaders array.


import the read_website_info.sql and the website_status.sql files into your database
