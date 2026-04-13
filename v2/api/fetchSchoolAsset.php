<?php
// header("Access-Control-Allow-Origin: http://localhost/rest-api-authentication-example/");
// header("Content-Type: application/json; charset=UTF-8");
// header("Access-Control-Allow-Methods: POST");
// header("Access-Control-Max-Age: 3600");
// header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 // http_response_code(501);
// echo $_POST['sector'];
// echo "No";
$request_headers = getallheaders();
// if(isset($request_headers['X-USERNAME']) && $request_headers['X-USERNAME'] == 'apiuser') {
//     // do stuff
//
// var_dump($_POST);

if($_SERVER['REQUEST_METHOD'] !=="POST"){
   http_response_code(400);
   die;
}



try {
  // echo "book";
  // var_dump($request_headers);
  // var_dump($_SERVER);
$table = "schools";

  $stmt = $conn->prepare("SELECT * FROM $table WHERE lga_vector_code =:lvc AND category=:lv");
  $stmt->bindParam(":lv",$_POST['level']);
  $stmt->bindParam(":lvc",$_POST['mapcode']);
  $stmt->execute();
  $result = [];
  $record = [];
  $classrooms = 0;
  $students = 0;
  $maleStudents = 0;
  $femaleStudents = 0;
  $teachers = 0;
  $maleTeachers = 0;
  $femaleTeachers = 0;
  $laboratory = 0;
  while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $record[] = $row;
    $classrooms += $row['classroom_no'];
    $students += $row['students_no'];
    $femaleStudents += $row['female_students_no'];
    $maleStudents += $row['male_students_no'];
    $teachers += $row['teachers_no'];
    $maleTeachers += $row['male_teachers_no'];
    $femaleTeachers += $row['female_teachers_no'];
    $laboratory += $row['laboratory_no'];



}
$result['record'] = $record;
$result['success'] = true;
$result['classrooms'] = $classrooms;
$result['students'] = $students;
$result['male_students'] = $maleStudents;
$result['female_students'] = $femaleStudents;
$result['teachers'] = $teachers;
$result['male_teachers'] = $maleTeachers;
$result['female_teachers'] = $femaleTeachers;
$result['laboratory'] = $laboratory;

} catch (PDOException $e) {

  $result['failed'] = $e;
  $result['failed'] = "Something Went Wrong";
}


echo json_encode($result);
