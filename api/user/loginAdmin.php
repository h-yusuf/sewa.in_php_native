<?php
require_once "encrypt.php";
require_once "koneksi.php";
$username = isset($_POST['username']) ? $_POST['username'] : null;   
$password = isset($_POST['password']) ? $_POST['password'] : null;   
$username = mysqli_real_escape_string($conn, $username);
$password = mysqli_real_escape_string($conn, $password);
$query = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
$result = $conn->query($query);
header('Content-Type: application/json');    
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();  
    $data = array(
        "message"   => "success",        
        "username"  => $row['username'],            
        "namaLengkap"  => $row['username'],    
        "sessionIdUser"  => $row['id'],    
        "sessionID"  =>  encrypt($row['id'], $key, $iv),
    );
    http_response_code(200);
    echo json_encode($data);
} else {
    $data = array(
        "message" => "error",                
        "keterangan" => "Username atau Password Salah",
    );
    http_response_code(400);
    echo json_encode($data);
}
