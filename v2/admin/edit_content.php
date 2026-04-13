<?php
ob_start();
//session_start();
$level_check = ['MASTER',3,2,1];
$where['table_name'] = $_GET['data'];
$column_name['column_name'] = "column_name";
$columns = selectTableContent($conn,'information_schema.columns',$column_name,$where);


$id = $_GET['id'];
$data = $_GET['data'];

$where2['id'] = $id;
$content = selectContent($conn,$data,$where2);
// die(var_dump($content));

//used to load new style for new admin console code
$stranger = true;
include APP_PATH."/demo_views/admin/include/link_include.php";
$error= [];
if(array_key_exists('submit', $_POST)){

  if(empty($error)){

    $clean = array_map('trim', $_POST);




    $post = $clean ;
    array_pop($post);
    $where3['id'] = $id;
    updateContent($conn,$_GET['data'],$post,$where3);
    // die;
    header("Location:/".base64url_decode($_GET['location']));
  }
}
?>
<!-- [ Header ] end -->
<div class="container">
  <div class="wrapper">
    <div class="content">
      <div class="content">
        <!-- [ breadcrumb ] start -->

        <!-- [ breadcrumb ] end -->
        <div class="main-body">
          <div class="page-wrapper">
            <!-- page start -->





            <div class="row">
              <div class="col-xl-6 col-md-12">
                <div class="card">
                  <div class="card-header">
                    <h5>Edit <?php echo ucwords(str_replace('_'," ",$_GET['data'])); ?></h5>
                  </div>
                  <div class="card-body">
                    <div class="col-md-12">
                      <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                          <?php
                          // die(var_dump($columns));
                          foreach ($columns as $key => $value){ ?>
                            <?php
                            $remains = explode("_",$value['column_name']);

                          array_shift($remains);

                            $remains = implode("_",$remains);

                            if(explode("_",$value['column_name'])[0] == "input"){ ?>
                              <label for="<?php echo ucwords(str_replace("_"," ",$remains)) ?>"><?php echo ucwords(str_replace("_"," ",$remains)) ?></label>
                              <input name="<?php echo $value['column_name']; ?>" type="text" class="form-control"  placeholder="<?php echo ucwords(str_replace("_"," ",$remains)) ?>" value="<?php echo $content[0][$value['column_name']] ?>" required><br>
                          <?php  } ?>

                          <?php  if(explode("_",$value['column_name'])[0] == "text"){ ?>
                              <label for="<?php echo ucwords(str_replace("_"," ",$remains)) ?>"><?php echo ucwords(str_replace("_"," ",$remains)) ?></label>
                              <!-- <input name="<?php echo $value['column_name']; ?>" type="text" class="form-control"  placeholder="<?php echo ucwords(str_replace("_"," ",$remains)) ?>" value="<?php echo $content[0][$value['column_name']] ?>" required> -->
                              <textarea placeholder="<?php echo ucwords(str_replace("_"," ",$remains)) ?>" name="<?php echo $value['column_name']; ?>" id="classic-editor"><?php echo $content[0][$value['column_name']] ?></textarea>

                              <br>
                          <?php  } ?>

                          <?php  if(explode("_",$value['column_name'])[0] == "select"){ ?>
                            <label for="<?php echo ucwords(str_replace("_"," ",$remains)) ?>"><?php echo ucwords(str_replace("_"," ",$remains)) ?></label>

                            <?php $select = selectContent($conn,'selection_'.$remains,[]); ?>

                          <select class="form-control" name="select_<?php echo $remains ?>"  required>
                            <option value="">-Select <?php echo ucwords(str_replace("_"," ",$remains));  ?>-</option>
                            <?php foreach ($select as $key => $value2){ ?>
                              <option value="<?php echo $value2['id']; ?>"><?php echo $value2['input_name']; ?></option>

                            <?php } ?>

                          </select>

                              <br>
                          <?php  }

                           ?>





                          <?php } ?>
                          <!-- <label for="exampleInputEmail1">Email address</label> -->


                        </div>



                      <input class="btn btn-primary" type="submit" name="submit" value="Edit">

                      </form>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-xl-6 col-md-12">

                <div class="col-md-12 col-sm-12">
                  <div class="card card-border-c-blue">
                    <div class="card-header">
                      <a href="#!" class="text-secondary">Tips</a>

                    </div>
                    <div class="card-body card-task">
                      <div class="row">
                        <div class="col-sm-12">
                          <ul>


                          <?php foreach ($columns as $key => $value){ ?>
                            <?php
                            $remains = explode("_",$value['column_name']);

                          array_shift($remains);

                            $remains = implode("_",$remains);

                            if(explode("_",$value['column_name'])[0] == "input"){ ?>
                              <li>Enter <?php echo ucwords(str_replace("_"," ",$remains)); ?></li>
                            <?php } ?>


                          <?php  if(explode("_",$value['column_name'])[0] == "text"){ ?>
                              <li>Enter <?php echo ucwords(str_replace("_"," ",$remains)); ?> Text</li>
                            <?php } ?>

                          <?php  if(explode("_",$value['column_name'])[0] == "select"){ ?>
                              <li>Select <?php echo ucwords(str_replace("_"," ",$remains)); ?> </li>
                            <?php } ?>

                          <?php  if($value['column_name'] == "image_1"){ ?>
                              <li>Upload Image</li>
                              <p>Landscape pictures are best best used on web pages. Ensure to upload files that are less than 1MB for better web page load performance </p>
                            <?php } ?>



                            <?php } ?>


  </ul>
                          <!-- <p class="task-due"><strong> Due : </strong><strong class="label label-primary">23 hours</strong></p> -->
                        </div>
                      </div>

                    </div>
                  </div>
                </div>



            </div>



            <!-- END OF CONTENT -->

          </div>
        </div>
      </div>
    </div>
  </div>



  <!-- Required Js -->
  <script src="/da/assets/js/vendor-all.min.js"></script>
  <script src="/da/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
  <script src="/da/assets/js/pcoded.min.js"></script>

  <!-- prism Js -->
  <script src="/da/assets/plugins/prism/js/prism.min.js"></script>
  <script src="/da/assets/js/horizontal-menu.js"></script>

  <script src="/da/assets/plugins/ckeditor/js/ckeditor.js"></script>

  <script type="text/javascript">
      $(window).on('load', function() {
          // classic editor
          ClassicEditor.create(document.querySelector('#classic-editor'))
              .catch(error => {
                  console.error(error);
              });
      });
  </script>
  <script type="text/javascript">
  // Collapse menu
  (function() {
    if ($('#layout-sidenav').hasClass('sidenav-horizontal') || window.layoutHelpers.isSmallScreen()) {
      return;
    }
    try {
      window.layoutHelpers.setCollapsed(
        localStorage.getItem('layoutCollapsed') === 'true',
        false
      );
    } catch (e) {}
  })();
  $(function() {
    // Initialize sidenav
    $('#layout-sidenav').each(function() {
      new SideNav(this, {
        orientation: $(this).hasClass('sidenav-horizontal') ? 'horizontal' : 'vertical'
      });
    });

    // Initialize sidenav togglers
    $('body').on('click', '.layout-sidenav-toggle', function(e) {
      e.preventDefault();
      window.layoutHelpers.toggleCollapsed();
      if (!window.layoutHelpers.isSmallScreen()) {
        try {
          localStorage.setItem('layoutCollapsed', String(window.layoutHelpers.isCollapsed()));
        } catch (e) {}
      }
    });
  });
  $(document).ready(function() {
    $("#pcoded").pcodedmenu({
      MenuTrigger: 'hover',
      SubMenuTrigger: 'hover',
      themelayout: 'horizontal',
    });
  });
  </script>
  <script type="text/javascript">
  // layout types
  $('.layout-type > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.layout-type > a').removeClass('active');
    $('.navbar').removeClassPrefix('navbar-image-');
    $(this).addClass('active');
    $('head').append('<link rel="stylesheet" class="layout-css" href="">');
    if (temp == "menu-dark") {
      $('.navbar').removeClassPrefix('menu-');
      $('.navbar').removeClass('navbar-dark');
    }
    if (temp == "menu-light") {
      $('.navbar').removeClassPrefix('menu-');
      $('.navbar').removeClass('navbar-dark');
      $('.navbar').addClass(temp);
    }
    if (temp == "reset") {
      location.reload();
    }
    if (temp == "dark") {
      $('.navbar').removeClassPrefix('menu-');
      $('.navbar').addClass('navbar-dark');
      $('.layout-css').attr("href", "/da/assets/css/layouts/dark.css");
    } else {
      $('.layout-css').attr("href", "");
    }
  });
  // Header Color
  $('.header-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.header-color > a').removeClass('active');
    $(this).addClass('active');
    if (temp == "header-default") {
      $('.header').removeClassPrefix('header-');
    } else {
      $('.header').removeClassPrefix('header-');
      $('.header').addClass(temp);
    }
  });
  // rtl layouts
  $('#theme-rtl').change(function() {
    $('head').append('<link rel="stylesheet" class="rtl-css" href="">');
    if ($(this).is(":checked")) {
      $('.rtl-css').attr("href", "/da/assets/css/layouts/rtl.css");
      $('html').attr("dir", "rtl");
    } else {
      $('.rtl-css').attr("href", "");
      $('html').removeAttr("dir");
    }
  });
  // Menu Color
  $('.navbar-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.navbar-color > a').removeClass('active');
    $('.navbar').addClass('brand-dark');
    $('.navbar').removeClassPrefix('menu-');
    $(this).addClass('active');
    if (temp == "navbar-default") {
      $('.navbar').removeClassPrefix('navbar-');
      $('.navbar').removeClassPrefix('brand-dark');
    } else {
      $('.navbar').removeClassPrefix('navbar-');
      $('.navbar').addClass(temp);
    }
  });
  // Active Color
  $('.active-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.active-color > a').removeClass('active');
    $(this).addClass('active');
    if (temp == "active-default") {
      $('.navbar').removeClassPrefix('active-');
    } else {
      $('.navbar').removeClassPrefix('active-');
      $('.navbar').addClass(temp);
    }
  });

  // Menu Icon Color
  $('#icon-colored').change(function() {
    if ($(this).is(":checked")) {
      $('.navbar').addClass('icon-colored');
    } else {
      $('.navbar').removeClass('icon-colored');
    }
  });

  // title Color
  $('.title-color > a').on('click', function() {
    var temp = $(this).attr('data-value');
    $('.title-color > a').removeClass('active');
    $(this).addClass('active');
    if (temp == "title-default") {
      $('.navbar').removeClassPrefix('title-');
    } else {
      $('.navbar').removeClassPrefix('title-');
      $('.navbar').addClass(temp);
    }
  });
  // Menu Dropdown icon
  function drpicon(temp) {
    if (temp == "style1") {
      $('.navbar').removeClassPrefix('drp-icon-');
    } else {
      $('.navbar').removeClassPrefix('drp-icon-');
      $('.navbar').addClass('drp-icon-' + temp);
    }
  }
  // Menu subitem icon
  function menuitemicon(temp) {
    if (temp == "style1") {
      $('.navbar').removeClassPrefix('menu-item-icon-');
    } else {
      $('.navbar').removeClassPrefix('menu-item-icon-');
      $('.navbar').addClass('menu-item-icon-' + temp);
    }
  }

  $.fn.removeClassPrefix = function(prefix) {
    this.each(function(i, it) {
      var classes = it.className.split(" ").map(function(item) {
        return item.indexOf(prefix) === 0 ? "" : item;
      });
      it.className = classes.join(" ");
    });
    return this;
  };
  </script>

  <!-- <div class="footer-fab">
    <div class="b-bg">
      <i class="fas fa-question"></i>
    </div>
    <div class="fab-hover">
      <ul class="list-unstyled">
        <li><a href="#"  data-text="Send Report" class="btn btn-icon btn-rounded btn-info m-0"><i class="fas fa-info-circle fa-ban"></i></a></li>
      </ul>
    </div>
  </div> -->
  <script src="/assets/js/jquery-min.js" type="text/javascript">
    </script>
  <script src="/assets/js/bootstrap.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/material.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/material-kit.js" type="text/javascript">
    </script>
  <script src="/assets/js/jquery.parallax.js" type="text/javascript">
    </script>
  <script src="/assets/js/owl.carousel.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/wow.js" type="text/javascript">
    </script>
  <script src="/assets/js/main.js" type="text/javascript">
    </script>
  <script src="/assets/js/jquery.counterup.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/waypoints.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/jasny-bootstrap.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/form-validator.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/contact-form-script.js" type="text/javascript">
    </script>
  <script src="/assets/js/jquery.themepunch.revolution.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/jquery.themepunch.tools.min.js" type="text/javascript">
    </script>
  <script src="/assets/js/bootstrap-select.min.js">
    </script>
  <script src="/assets/js/fileinput.js">
    </script>
  <!-- modal-window-effects Js -->
  <script src="/da/assets/plugins/modal-window-effects/js/classie.js"></script>
  <script src="/da/assets/plugins/modal-window-effects/js/modalEffects.js"></script>
  <script src="/da/assets/plugins/ekko-lightbox/js/ekko-lightbox.min.js"></script>
  <script src="/da/assets/plugins/lightbox2-master/js/lightbox.min.js"></script>
  <script src="/da/assets/js/pages/ac-lightbox.js"></script>
  <!--  -->
  <script type="text/javascript" src="/map/getmap.js"></script>
  <script src="/da/assets/js/analytics.js"></script>

</body>



</html>
