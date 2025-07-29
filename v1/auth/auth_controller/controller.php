<?php
function doesPhoneNumberExist($dbconn, $input){
  $result = false;
  $stmt = $dbconn->prepare("SELECT * FROM users WHERE phone_number = :tp");
  $stmt->bindParam(":tp", $input);
  $stmt->execute();
  $count = $stmt->rowCount();
  if($count>0){
    $result = true;
  }
  return $result;
}

function doChangePassword($dbconn, $input,$hash_id){
  try{
    $hash = password_hash($input['pword'], PASSWORD_BCRYPT);
    #insert data
    $stmt = $dbconn->prepare("UPDATE users SET hash=:h WHERE hash_id=:hid");

    $data = [
      ':h' => $hash,
      ':hid' => $hash_id
    ];
    $stmt->execute($data);

    // $stmt2 = $dbconn->prepare("UPDATE user SET hash=:h WHERE hash_id=:hid");
    // #bind params...
    //
    // $stmt2->execute($data);

    $stmt3 = $dbconn->prepare("DELETE FROM verify WHERE email=:hid");
    #bind params...
    $data2 = [
      ':hid' => $hash_id
    ];
    $stmt3->execute($data2);

  }
  catch(PDOException $e){
    die("Something Went Wrong");
  }

  unset($_SESSION['user_to_edit']);
  session_destroy();

  $suc = 'Password Changed Successfully, You can now login';
  $message = $suc;
  header("Location:/login?success=$message");
}

function usersLogin($dbconn,$dbconn2,$sid, $input,$loc,$st){
  $stmt = $dbconn->prepare("SELECT * FROM users WHERE email = :e ");
  $stmt ->bindParam(":e", $input['email']);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_BOTH);
  if($stmt->rowCount() !=1 || !password_verify($input['pword'], $row['hash'])){
    $suc = 'Invalid Email or Password';
    $message = preg_replace('/\s+/', '_', $suc);
    header("Location:login?err=$message");
  }else{
    // die($row['verification']);
    if( $row['verification'] !== "1" ){
      $suc = 'Dear '.ucwords($row['firstname']).', You Have Not been verified, kindly visit your email for verification link';
      $message = preg_replace('/\s+/', '_', $suc);
      header("Location:login?wn=$message");
      die;
    }




      extract($row);
// //session_start();
      if($row['level'] == 3 || $row['level'] == "MASTER"){
        $_SESSION['admin_id'] = $row['hash_id'];
        $_SESSION['admin_name'] = $row['firstname']." ".$row['lastname'];
      }

      $_SESSION['id'] = $row['hash_id'];
      if($row['usname'] == NULL){
          $_SESSION['username'] = "User";
      }else{
            $_SESSION['username'] = $usname;
      }
      // setLogin($dbconn,$hash_id);
      // die;
        header("Location:$loc");
    }
  }

  function forgotPassword($dbconn,$hash_id){

    $result = [];
    $token_s = 1;
    $ran = rand(0000000000,999999999);
    $tim = time();
    $process = $ran."MckodevVerification".$hash_id;
    $token = $tim."_".str_shuffle($process);


    $updatever = $dbconn->prepare("INSERT INTO verify VALUES(NULL,:em,:tk,:tks)");
    $data2 = [
      'em' => $hash_id,
      'tk' => $token,
      'tks' => $token_s
    ];
    $updatever->execute($data2);
    $result[] = $token;
    return $result;
  }


function getPhone($dbconn, $input){ #placeholders are just there
  $result = [];
  $stmt = $dbconn -> prepare("SELECT * FROM users WHERE email = :em");
  $stmt->bindParam(":em",$input);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_BOTH);
  extract($row);

  $result[] = $phone_number;
  $result[] = $hash_id;
  $result[] = $firstname;

  return $result;
}


function doesEmailExist($dbconn, $input){ #placeholders are just there
  $result = false;
  $stmt = $dbconn -> prepare("SELECT * FROM users WHERE email = :em");
  $stmt->bindParam(":em",$input);
  $stmt->execute();
  $count = $stmt->rowCount();
  if($count>0){
    $result = true;
  }
  return $result;
}
function doesAdminEmailExist($dbconn, $input){ #placeholders are just there
  $result = false;
  $stmt = $dbconn -> prepare("SELECT * FROM admin WHERE email = :em");
  $stmt->bindParam(":em",$input);
  $stmt->execute();
  $count = $stmt->rowCount();
  if($count>0){
    $result = true;
  }
  return $result;
}

function doUserRegister($dbconn, $input){
  try{
  $rnd = rand(0000000000,9999999999);
    $split = $input['firstname'];
    $id = $rnd.cleans($split);
  $hash_id = time().str_shuffle($id);
  // $team = 0;
  // $point = 0;
  // $admin = 0;
  $visibility = "show";
  $hash = password_hash($input['pword'], PASSWORD_BCRYPT);
  #insert data
  $stmt = $dbconn->prepare("INSERT INTO users(firstname,lastname,phone_number, visibility, email,hash,hash_id,time_created,date_created) VALUES(:fn,:ln,:pn,:vs,:e,:h,:hid,NOW(),NOW())");
  #bind params...
  $data = [
    ':fn' => $input['firstname'],
  ':ln' => $input['lastname'],
  ':e' => $input['email'],
  ':pn' => $input['phonenumber'],
  ':h' => $hash,
  ':vs' => "show",
  // ':te' => $team,
  // ':as' => $admin,
  ':hid' => $hash_id
];
$stmt->execute($data);


$result = [];
$token_s = 1;
$ran = rand(0000000000,999999999);
$tim = time();
$process = $ran."MckodevGovernmentDashboardVerification".$hash_id;
$token = $tim."_".str_shuffle($process);


$updatever = $dbconn->prepare("INSERT INTO verify VALUES(NULL,:em,:tk,:tks,:en)");
$data2 = [
'em' => $hash_id,
'tk' => $token,
'tks' => $token_s,
'en' => $input['email']
];
$updatever->execute($data2);
$result[] = $token;
return $result;


}catch(PDOException $e){
  die($e->getMessage());

}

}

function doAdminRegister($dbconn, $input){
  try{
  $rnd = rand(0000000000,9999999999);
    $split = $input['firstname'];
    $id = $rnd.cleans($split);
  $hash_id = time().str_shuffle($id);

  $hash = password_hash($input['pword'], PASSWORD_BCRYPT);
  #insert data
  $stmt = $dbconn->prepare("INSERT INTO admin(firstname,lastname,email,hash,hash_id,time_created,date_created) VALUES(:fn, :ln,:e, :h,:hid,NOW(),NOW())");
  #bind params...
  $data = [
    ':fn' => $input['firstname'],
  ':ln' => $input['lastname'],
  ':e' => $input['email'],
  ':h' => $hash,
  ':hid' => $hash_id
];
$stmt->execute($data);



}catch(PDOException $e){
  die($e->getMessage());

}

}
