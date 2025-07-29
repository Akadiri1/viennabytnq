<?php
 $uro = 'https://'.$_SERVER['HTTP_HOST']."/";
$bd = previewBody($description, 22);
$rf = strip_tags($bd);
$tn = $site_name."(Event) - ".ucwords($event_name);

echo '<title>'.$site_name."(Event) - ".ucwords($event_name).'</title>
<meta name="format-detection" content="telephone=no"/>
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0"/>
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>';
$cut = explode(' ',$event_name);
$metakeys = implode(',',$cut).",";
echo '<meta name="description" content="'.$rf.'" />
<meta name="keywords" content="'.$metakeys.'blog">';
echo '<meta property="og:title" content="'.$site_name."(Event) - ".ucwords($event_name).'" />
<meta property="og:type" content="article" />
<meta property="og:image" content="'.$uro.$image_1.'" />
<meta property="og:image:width" content="450"/>
<meta property="og:image:height" content="298"/>
<meta property="og:description" content="'.$rf.'" />';
echo '<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="'.$site_name."(Event) - ".ucwords($event_name).'">
<meta name="twitter:description" content="'.$rf.'">
<meta name="twitter:image" content="'.$uro.$image_1.'">
<meta name="twitter:image:width" content="280">
<meta name="twitter:image:height" content="150">';
 ?>
