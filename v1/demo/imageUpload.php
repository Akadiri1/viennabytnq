<?php

// echo "here";
// print_r($_FILES);
// print_r($_SERVER);
// die;

$request_headers = getallheaders();

if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(502);
   die;
}

// if( !in_array($request_headers['Host'],$allowedHeaders) ){
//   http_response_code(502);
//   die;
// }

$response = [];
// print_r($_FILES);

try {

	if (!function_exists('uploadFiles')) {


	function uploadFiles($input, $name, $upDIR){
	            $result = [];

	            $rnd = rand(0000000, 9999999);
	            $strip_name = str_replace(" ", "_", $input[$name]['name']);
	            $strip_name = str_replace(")", "_", $input[$name]['name']);
	            $strip_name = str_replace("(", "_", $input[$name]['name']);

	            $filename=$rnd.$strip_name;
	            $destination = $upDIR.$filename;

	            if(!move_uploaded_file($input[$name]['tmp_name'], $destination)){
	              $result[] = false;
	            }else{
	              $result[] = true;
	              $result[] = $destination;
	            }
	            return $result;
	          }
	}

$ver['a'] = uploadFiles($_FILES,'image',"mkp-images/");

include APP_PATH.'/demo/includes/colors.inc.php';
    $ex=new GetMostCommonColors();
    // var_dump($ex);
    $num_results=20;
    $reduce_brightness=1;
    $reduce_gradients=1;
    $delta=24;
    // var_dump(APP_PATH."/www/".$ver['a'][1]);
    $_SESSION['image_select'] = $ver['a'][1];
    $colors=$ex->Get_Color($_SESSION['image_select'], $num_results, $reduce_brightness, $reduce_gradients, $delta);
	// $_SESSION['color_select'] = $colors;

	$response['success']['image']  = $ver['a'][1];
	$response['success']['colors'] = $colors;
// header("Location:".$_SERVER['REQUEST_URI']);
// exit();

} catch (Exception $e) {

}




$res = json_encode($response);
echo $res;
die;

 ?>
