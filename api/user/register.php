<?php
require_once "encrypt.php";
require_once "koneksi.php";

header('Content-Type: application/json');

$username = isset($_POST['username']) ? $_POST['username'] : null;   
$password = isset($_POST['password']) ? $_POST['password'] : null;
$nik = isset($_POST['nik']) ? $_POST['nik'] : null;   
$namaLengkap = isset($_POST['namaLengkap']) ? $_POST['namaLengkap'] : null;   
$alamat = isset($_POST['alamat']) ? $_POST['alamat'] : null;   
$noTelf = isset($_POST['noTelf']) ? $_POST['noTelf'] : null;   
$kota = isset($_POST['kota']) ? $_POST['kota'] : null;   

$username = mysqli_real_escape_string($conn, $username);
$password = mysqli_real_escape_string($conn, $password);
$nik = mysqli_real_escape_string($conn, $nik);

$checkNikSql = "SELECT * FROM user WHERE nik = '$nik'";
$checkNikResult = $conn->query($checkNikSql);

if ($checkNikResult->num_rows > 0) {
    $data = array(
        "message" => "Error",                
        "error" => "NIK sudah terdaftar"
    );
    http_response_code(400);
    echo json_encode($data);
    $conn->close();
    exit();
}

$sql = "INSERT INTO user (username, password, nik, namaLengkap, alamat, noTelf, kota) 
        VALUES ('$username', '$password', '$nik', '$namaLengkap', '$alamat', '$noTelf', '$kota')";  

try {
    if ($conn->query($sql) === TRUE) {
        $userId = $conn->insert_id;

        $uploadDir = "uploads/$userId/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ["jpg", "jpeg", "png"]; 
        function uploadFile($file, $uploadDir, $allowedTypes) {
            $fileName = $file["name"];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($fileType, $allowedTypes)) {
                $tempFile = $file["tmp_name"];
                $targetFile = $uploadDir . uniqid() . "." . $fileType;                
                if (move_uploaded_file($tempFile, $targetFile)) {
                    return $targetFile;
                } else {
                    throw new Exception("Gagal upload foto");
                }
            } else {
                throw new Exception("Hanya dapat upload file dengan format .jpg, .jpeg, .png");
            }
        }

        $fotoProfilPath = uploadFile($_FILES["fotoProfil"], $uploadDir, $allowedTypes);
        $fotoKTPPath = uploadFile($_FILES["fotoKTP"], $uploadDir, $allowedTypes);
        $fotoSKTPPath = uploadFile($_FILES["fotoSKTP"], $uploadDir, $allowedTypes);

        $sqlUpdate = "UPDATE user SET 
                        fotoProfil='$fotoProfilPath', 
                        fotoKTP='$fotoKTPPath', 
                        fotoSKTP='$fotoSKTPPath' 
                      WHERE id='$userId'";

        if ($conn->query($sqlUpdate) === TRUE) {
            $data = array(
                "message" => "Registrasi sukses",         
                "username" => $username,       
            );
            http_response_code(200);
            echo json_encode($data);
        } else {
            throw new Exception($conn->error);
        }
    } else {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    $data = array(
        "message" => "Error",                
        "error" => $e->getMessage()
    );
    http_response_code(400);
    echo json_encode($data);
}

$conn->close();
?>