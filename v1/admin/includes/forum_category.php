<ul class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
  <li><a class="nav-link text-left active" id="v-pills-home-tab" data-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true">Category</a></li>

  <?php
  $where = [];
  $categ = selectContent($conn,'discussion_category',$where) ?>
<?php foreach ($categ as $key => $value): ?>

  <?php
$where2['select_discussion_category'] = $value['id'];
  $count = selectContent($conn,'topic',$where2); ?>
    <li><a class="nav-link text-left" id="v-pills-profile-tab" data-toggle="pill" href="/category/<?php echo $value['hash_id']; ?>" role="tab" aria-controls="v-pills-profile" aria-selected="false"><?php echo $value['input_category_name']; ?><span class="badge badge-light"><?php echo count($count); ?></span></a></li>
<?php endforeach; ?>

  <!-- <li><a class="nav-link text-left" id="v-pills-messages-tab" data-toggle="pill" href="#v-pills-messages" role="tab" aria-controls="v-pills-messages" aria-selected="false">Messages</a></li>
  <li><a class="nav-link text-left" id="v-pills-settings-tab" data-toggle="pill" href="#v-pills-settings" role="tab" aria-controls="v-pills-settings" aria-selected="false">Settings</a></li> -->
</ul>
