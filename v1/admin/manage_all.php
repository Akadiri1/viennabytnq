<?php
ob_start();
//session_start();
// $level_check = ['MASTER',3,2,1];
// SELECT table_name FROM information_schema.tables;
// SELECT table_name FROM information_schema.tables WHERE ;
$where['table_name'] = strtolower("panel_".$uri[2]);
$column_name['column_name'] = "column_name";
$columns = selectTableContent($conn,'information_schema.columns',$column_name,$where);
// include 'includes/header.php';


//used to load new style for new admin console code
$stranger = true;
include APP_PATH."/demo_views/admin/include/link_include.php";

// $arr['name'] = "Book";



$where = [];
$content = selectContent($conn,'panel_'.$uri[2],$where);
// die(var_dump($content));

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



            <div class="row">
              <!-- Zero config table start -->
              <div class="col-sm-12">
                <div class="card">
                  <div class="card-header">
                    <h5><?php echo ucwords(str_replace("_"," ",($uri[2]))) ?></h5>
                  </div>

                  <div class="card-body">
                    <div class="dt-responsive table-responsive">
                      <table id="simpletable" class="table table-striped table-bordered nowrap">
                        <thead>
                          <tr>
                            <?php foreach ($columns as $key => $value): ?>
                              <?php
                              $remains = explode("_",$value['column_name']);

                              array_shift($remains);

                              $remains = implode("_",$remains);

                              if(explode("_",$value['column_name'])[0] == "input"){ ?>
                                <th><?php echo ucwords(str_replace("_"," ",$remains)) ?></th>

                              <?php  } ?>

                            <?php endforeach; ?>

                            <?php foreach ($columns as $key => $value): ?>
                              <?php
                              $remains = explode("_",$value['column_name']);

                              array_shift($remains);

                              $remains = implode("_",$remains);
                              ?>
                              <?php


                              if($value['column_name'] == "visibility"){ ?>
                                <th>Visibility</th>

                              <?php  } ?>
                              <?php


                              if($value['column_name'] == "image_1"){ ?>
                                <th>Image</th>

                              <?php  } ?>


                            <?php endforeach; ?>
                            <th>Edit</th>
                            <th>Delete</th>

                            <?php foreach ($columns as $key => $value): ?>
                              <?php
                              $remains = explode("_",$value['column_name']);

                              array_shift($remains);

                              $remains = implode("_",$remains);
                              ?>



                              <?php


                              if(explode("_",$value['column_name'])[0] == "select"){ ?>
                                <th><?php echo ucwords(str_replace("_"," ",$remains)) ?></th>

                              <?php  } ?>
                              <?php


                              if(explode("_",$value['column_name'])[0] == "text"){ ?>
                                <th><?php echo ucwords(str_replace("_"," ",$remains)) ?></th>

                              <?php  } ?>






                            <?php endforeach; ?>



                            <th>Date Posted</th>
                            <th>Time Posted</th>

                          </tr>
                        </thead>






                        <tbody>

                          <?php foreach ($content as $key => $record){ ?>
                            <tr>

                            <?php foreach ($columns as $key => $column){ ?>
                              <?php
                              $remains = explode("_",$column['column_name']);

                              array_shift($remains);


                              ?>
                              <?php if(explode("_",$column['column_name'])[0] == "input"){

                                $to_fetch  = $column['column_name'];
                                ?>

                                <td><?php echo $record[$to_fetch];  ?></td>
                              <?php } ?>


                            <?php } ?>
                            <?php foreach ($columns as $key => $column){ ?>
                              <?php
                              $remains = explode("_",$column['column_name']);

                              array_shift($remains);


                              ?>
                              <?php if($column['column_name'] == "visibility"){


                                ?>

                                <td><?php echo $record['visibility'];  ?>  <br>
                                  <a href="/updateContent.php?id=<?php echo $record['id'] ?>&visibility=show&data=panel_<?php echo $uri[2] ?>"><button type="button" class="btn btn-success btn-sm"><i class="feather feather icon-eye"></i>Show</button></a>
                                  <a href="/updateContent.php?id=<?php echo $record['id'] ?>&visibility=hide&data=panel_<?php echo $uri[2] ?>"><button type="button" class="btn btn-warning btn-sm"><i class="feather feather icon-eye-off"></i>Hide</button></a></td>



                              <?php } ?>

                              <?php if($column['column_name'] == "image_1"){

                                ?>

                                <td><a href="/change-image?id=<?php echo $record['id'] ?>&data=panel_<?php echo $uri[2] ?>&location=<?php echo base64url_encode($uri[1]."/".$uri[2]) ?>"><img src="/<?php echo $record['thumbnail'] ?>" alt="" width="100" height="70"></a></td>

                              <?php } ?>


                            <?php } ?>


                            <td> <a href="/admin-edit-content?id=<?php echo $record['id'] ?>&data=panel_<?php echo $uri[2] ?>&location=<?php echo base64url_encode($uri[1]."/".$uri[2]) ?>"><button type="button" class="btn btn-primary"><i class="feather feather icon-edit"></i>Edit</button></a></td>
                            <td><a href="/deleteContent.php?id=<?php echo $record['id'] ?>&data=panel_<?php echo $uri[2] ?>"><button type="button" class="btn btn-danger"><i class="feather feather icon-trash-2"></i>Delete</button></a></td>


                            <?php foreach ($columns as $key => $column){ ?>
                              <?php
                              $remains = explode("_",$column['column_name']);

                              array_shift($remains);


                              ?>
                              <?php if(explode("_",$column['column_name'])[0] == "text"){

                                $to_fetch  = $column['column_name'];
                                ?>

                                <td><?php echo $record[$to_fetch];  ?></td>
                              <?php } ?>
                              <?php if(explode("_",$column['column_name'])[0] == "select"){

                                $to_fetch  = $column['column_name'];
                                ?>

                                <td><?php echo $record[$to_fetch];  ?></td>
                              <?php } ?>


                            <?php } ?>



                                <td><?php echo $record['date_created'];  ?></td>
                                <td><?php echo $record['time_created'];  ?></td>



</tr>
                          <?php } ?>



                        </tbody>









                        <tfoot>
                          <tr>
                            <?php foreach ($columns as $key => $value): ?>
                              <?php
                              $remains = explode("_",$value['column_name']);

                              array_shift($remains);

                              $remains = implode("_",$remains);

                              if(explode("_",$value['column_name'])[0] == "input"){ ?>
                                <th><?php echo ucwords(str_replace("_"," ",$remains)) ?></th>

                              <?php  } ?>

                            <?php endforeach; ?>

                            <?php foreach ($columns as $key => $value): ?>
                              <?php
                              $remains = explode("_",$value['column_name']);

                              array_shift($remains);

                              $remains = implode("_",$remains);
                              ?>
                              <?php


                              if($value['column_name'] == "visibility"){ ?>
                                <th>Visibility</th>

                              <?php  } ?>
                              <?php


                              if($value['column_name'] == "image_1"){ ?>
                                <th>Image</th>

                              <?php  } ?>


                            <?php endforeach; ?>
                            <th>Edit</th>
                            <th>Delete</th>

                            <?php foreach ($columns as $key => $value): ?>
                              <?php
                              $remains = explode("_",$value['column_name']);

                              array_shift($remains);

                              $remains = implode("_",$remains);
                              ?>



                              <?php


                              if(explode("_",$value['column_name'])[0] == "select"){ ?>
                                <th><?php echo ucwords(str_replace("_"," ",$remains)) ?></th>

                              <?php  } ?>
                              <?php


                              if(explode("_",$value['column_name'])[0] == "text"){ ?>
                                <th><?php echo ucwords(str_replace("_"," ",$remains)) ?></th>

                              <?php  } ?>






                            <?php endforeach; ?>



                            <th>Date Posted</th>
                            <th>Time Posted</th>

                          </tr>
                        </tfoot>
                      </table>
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
<!-- Tables -->
<script src="/da/assets/plugins/data-tables/js/datatables.min.js"></script>
<script src="/da/assets/js/pages/data-basic-custom.js"></script>
<!--  -->
<script type="text/javascript" src="/map/getmap.js"></script>
<script src="/da/assets/js/analytics.js"></script>

</body>



</html>
