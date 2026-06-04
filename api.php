<?php

session_start();

header("Content-Type: application/json");
require_once "config.php";

function validateCsrf(){

    $csrfHeader=$_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if(
        !isset($_SESSION['csrf_token']) ||
        $csrfHeader !== $_SESSION['csrf_token']
    ){

        echo json_encode([
            "success"=>false,
            "message"=>"CSRF Validation Failed"
        ]);

        exit;
    }

}

if($_SERVER['REQUEST_METHOD']=='GET'){

    $sql="SELECT * FROM appointments ORDER BY id DESC";
    $result=$conn->query($sql);

    $appointments=[];

    while($row=$result->fetch_assoc()){
        $appointments[]=$row;
    }

    echo json_encode($appointments);
}

elseif($_SERVER['REQUEST_METHOD']=='POST'){

     validateCsrf();

     

    $data=json_decode(file_get_contents("php://input"),true);

    $patient_name=trim($data['patient_name']);
    $email=trim($data['email']);
    $mobile=trim($data['mobile']);
    $doctor_name=trim($data['doctor_name']);
    $appointment_date=trim($data['appointment_date']);
    $appointment_time=trim($data['appointment_time']);

    if(empty($patient_name)||empty($email)||empty($mobile)||empty($doctor_name)||empty($appointment_date)||empty($appointment_time)){
        echo json_encode(["success"=>false,"message"=>"All fields are required"]);
        exit;
    }

    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        echo json_encode(["success"=>false,"message"=>"Invalid Email Format"]);
        exit;
    }

    if(strlen($mobile)!=10){
        echo json_encode(["success"=>false,"message"=>"Mobile Number Must Be 10 Digits"]);
        exit;
    }

    if($appointment_date < date("Y-m-d")){
        echo json_encode(["success"=>false,"message"=>"Past Date Not Allowed"]);
        exit;
    }

    if(
    $appointment_time < "09:00:00" ||
    $appointment_time > "18:00:00"
){
    echo json_encode([
        "success"=>false,
        "message"=>"Appointment Time Must Be Between 09:00 AM and 06:00 PM"
    ]);
    exit;
}


$limitCheck=$conn->prepare(
"SELECT COUNT(*) as total
 FROM appointments
 WHERE appointment_date=?"
);

$limitCheck->bind_param("s",$appointment_date);
$limitCheck->execute();
$limitResult=$limitCheck->get_result()->fetch_assoc();

if($limitResult['total'] >= 10){

    echo json_encode([
        "success"=>false,
        "message"=>"Daily Appointment Limit Reached"
    ]);

    exit;
}

    $check=$conn->prepare(
    "SELECT id FROM appointments
     WHERE appointment_date=?
     AND appointment_time=?"
);

$check->bind_param(
    "ss",
    $appointment_date,
    $appointment_time
);

$check->execute();

$result=$check->get_result();

if($result->num_rows>0){

    echo json_encode([
        "success"=>false,
        "message"=>"Time Slot Already Booked"
    ]);

    exit;
}

    $stmt=$conn->prepare("INSERT INTO appointments(patient_name,email,mobile,doctor_name,appointment_date,appointment_time) VALUES(?,?,?,?,?,?)");

    $stmt->bind_param("ssssss",$patient_name,$email,$mobile,$doctor_name,$appointment_date,$appointment_time); 

    if($stmt->execute()){
        echo json_encode(["success"=>true,"message"=>"Appointment Added Successfully"]);
    }else{
        echo json_encode(["success"=>false,"message"=>"Database Error"]);
    }
}

elseif($_SERVER['REQUEST_METHOD']=='PUT'){
    
    validateCsrf();

    $data=json_decode(file_get_contents("php://input"),true);

    $id=$data['id'];
    $patient_name=$data['patient_name'];
    $email=$data['email'];
    $mobile=$data['mobile'];
    $doctor_name=$data['doctor_name'];
    $appointment_date=$data['appointment_date'];
    $appointment_time=$data['appointment_time'];

    $stmt=$conn->prepare("UPDATE appointments SET patient_name=?,email=?,mobile=?,doctor_name=?,appointment_date=?,appointment_time=? WHERE id=?");

    $stmt->bind_param("ssssssi",$patient_name,$email,$mobile,$doctor_name,$appointment_date,$appointment_time,$id);

    if($stmt->execute()){
        echo json_encode(["success"=>true,"message"=>"Appointment Updated Successfully"]);
    }else{
        echo json_encode(["success"=>false,"message"=>"Update Failed"]);
    }
}

elseif($_SERVER['REQUEST_METHOD']=='PATCH'){

        validateCsrf();

    $data=json_decode(file_get_contents("php://input"),true);

    $id=$data['id'];
    $status=$data['status'];

    $stmt=$conn->prepare("UPDATE appointments SET status=? WHERE id=?");

    $stmt->bind_param("si",$status,$id);

    if($stmt->execute()){
        echo json_encode(["success"=>true,"message"=>"Status Updated Successfully"]);
    }else{
        echo json_encode(["success"=>false,"message"=>"Status Update Failed"]);
    }
}

elseif($_SERVER['REQUEST_METHOD']=='DELETE'){

    validateCsrf();

    $data=json_decode(file_get_contents("php://input"),true);
    $id=$data['id'];
    $stmt=$conn->prepare("DELETE FROM appointments WHERE id=?");
    $stmt->bind_param("i",$id);

    if($stmt->execute()){
        echo json_encode(["success"=>true,"message"=>"Appointment Deleted Successfully"]);
    }else{
        echo json_encode(["success"=>false,"message"=>"Delete Failed"]);
    }
}

$conn->close();

?>