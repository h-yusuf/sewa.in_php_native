<?php
//ini_set('display_errors', 0);


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


require_once "koneksi.php";
require_once "encrypt.php";
$sessionID = isset($_GET['sessionID']) ? $_GET['sessionID'] : null;
$decrypt = decrypt($sessionID, $key, $iv);
$sessionIdUser = isset($_GET['sessionIdUser']) ? $_GET['sessionIdUser'] : "_";

$query = "SELECT * FROM admin WHERE id='$sessionIdUser'";
$result = $conn->query($query);
if ($result->num_rows != 1) {
    header('Content-Type: application/json');
    $data = array(
        "message" => "Session login invalid",
    );
    http_response_code(400);
    echo json_encode($data);
    exit();
}
if ($sessionIdUser != $decrypt) {
    header('Content-Type: application/json');
    $data = array(
        "message" => "Session login invalid",
    );
    http_response_code(400);
    echo json_encode($data);
    exit();
}
$action = isset($_GET['action']) ? $_GET['action'] : null;
switch ($action) {
    case 'getAll':
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $query = "SELECT 
            user.id as idUser, 
            user.namaLengkap as namaLengkap,
            user.alamat as alamat,
            user.kota as kota,
            user.noTelf as noTelf,                       
            user.username as username,
            user.password as password,
            user.status as status
            from user            
            WHERE 
            (user.namaLengkap LIKE '%$search%' OR 
            user.kota LIKE '%$search%' OR 
            user.noTelf LIKE '%$search%' OR 
            user.username LIKE '%$search%') 
          ORDER BY user.namaLengkap ASC";
        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $conn->close();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
        break;


    case 'getDetail':
        $idUser = isset($_GET['idUser']) ? $_GET['idUser'] : null;
        $query = "SELECT 
                user.id as idUser, 
                user.namaLengkap as namaLengkap,
                user.alamat as alamat,
                user.kota as kota,
                user.noTelf as noTelf,                       
                user.fotoProfil as fotoProfil,                       
                user.fotoKTP as fotoKTP,                       
                user.nik as nik,                       
                user.fotoSKTP as fotoSKTP,                       
                user.username as username,
                user.password as password
                from user  
                where id='" . $idUser . "'
            ";
        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $conn->close();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
        break;


    case 'add':
        $username = isset($_POST['username']) ? $_POST['username'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        $namaLengkap = isset($_POST['namaLengkap']) ? $_POST['namaLengkap'] : null;
        $alamat = isset($_POST['alamat']) ? $_POST['alamat'] : null;
        $noTelf = isset($_POST['noTelf']) ? $_POST['noTelf'] : null;
        $kota = isset($_POST['kota']) ? $_POST['kota'] : null;
        $username = mysqli_real_escape_string($conn, $username);
        $password = mysqli_real_escape_string($conn, $password);
        $sql = "INSERT INTO user (username, password, namaLengkap, alamat, noTelf,kota) 
                        VALUES ('$username', '$password', '$namaLengkap', '$alamat', '$noTelf', '$kota')";
        header('Content-Type: application/json');
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "registrasi suksess",
                    "username" => $username,
                );
                http_response_code(200);
                echo json_encode($data);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $data = array(
                "message" => "error",
                "error" => $e->getMessage()
            );
            http_response_code(400);
            echo json_encode($data);
        }
        break;


    case 'edit':
        $idUser = isset($_POST['idUser']) ? $_POST['idUser'] : null;
        $username = isset($_POST['username']) ? $_POST['username'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        $namaLengkap = isset($_POST['namaLengkap']) ? $_POST['namaLengkap'] : null;
        $alamat = isset($_POST['alamat']) ? $_POST['alamat'] : null;
        $noTelf = isset($_POST['noTelf']) ? $_POST['noTelf'] : null;
        $kota = isset($_POST['kota']) ? $_POST['kota'] : null;
        $username = mysqli_real_escape_string($conn, $username);
        $password = mysqli_real_escape_string($conn, $password);
        $status = isset($_POST['status']) ? $_POST['status'] : null;

        $sql = "
            update user set
            username='" . $username . "',
            password='" . $password . "',
            namaLengkap='" . $namaLengkap . "',
            alamat='" . $alamat . "',
            noTelf='" . $noTelf . "',
            kota='" . $kota . "',
            status='" . $status . "'
            where id = '" . $idUser . "'
        ";
        header('Content-Type: application/json');
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "edit suksess",
                    "username" => $username,
                );
                http_response_code(200);
                echo json_encode($data);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $data = array(
                "message" => "error",
                "error" => $e->getMessage()
            );
            http_response_code(400);
            echo json_encode($data);
        }
        break;


    case 'delete':
        $idUser = isset($_POST['idUser']) ? $_POST['idUser'] : null;
        $sql = "DELETE FROM user WHERE id = '" . $idUser . "'
        ";
        header('Content-Type: application/json');
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "success " . $jmlBarisDiubah . " data",
                );
                http_response_code(200);
                echo json_encode($data);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $data = array(
                "message" => "error",
                "error" => $e->getMessage()
            );
            http_response_code(400);
            echo json_encode($data);
        }
        break;

    case 'barangGetAll':
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $query = "SELECT 
            user.id as idUser, 
            user.namaLengkap as namaLengkapPemilik,
            user.kota as kota,            
            barang.id as idBarang,
            barang.namaBarang as namaBarang,
            barang.tarifHarian as tarifHarian,
            barang.fotoBarang as fotoBarang,
            barang.status as BarangStatus            
            from barang
            inner join user on barang.idUser = user.id
            WHERE 
            (barang.namaBarang LIKE '%$search%' OR 
            user.kota LIKE '%$search%') and
            barang.status = 'ready'
          ORDER BY namaBarang ASC";
        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $conn->close();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
        break;

    case 'barangGetDetail':
        $idBarang = isset($_GET['idBarang']) ? $_GET['idBarang'] : null;
        $query = "SELECT 
            user.id as idUser, 
            user.namaLengkap as namaLengkapPemilik,
            user.kota as kota,
            user.noTelf as noTelf,
            barang.id as idBarang,
            barang.namaBarang as namaBarang,
            barang.tarifHarian as tarifHarian,
            barang.fotoBarang as fotoBarang,
            barang.status as BarangStatus           
            from barang
            inner join user on barang.idUser = user.id
            WHERE 
        barang.id = '" . $idBarang . "'";
        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $conn->close();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
        break;

    case 'barangEdit':
        header('Content-Type: application/json');
        $namaBarang = isset($_POST['namaBarang']) ? $_POST['namaBarang'] : null;
        $tarifHarian = isset($_POST['tarifHarian']) ? $_POST['tarifHarian'] : null;
        $idBarang = isset($_POST['idBarang']) ? $_POST['idBarang'] : null;
        $uploadDir = "uploads/$sessionIdUser/";
        $allowedTypes = ["jpg", "jpeg", "png"];
        $fileName = $_FILES["fotoBarang"]["name"];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($fileType, $allowedTypes)) {
            $tempFile = $_FILES["fotoBarang"]["tmp_name"];
            $targetFile = $uploadDir . uniqid() . "." . $fileType;
            if (move_uploaded_file($tempFile, $targetFile)) {
                $sql = "
                        update barang set
                        namaBarang = '" . $namaBarang . "',
                        tarifHarian = '" . $tarifHarian . "',
                        fotoBarang = '" . $targetFile . "'
                        where id='" . $idBarang . "'
                    ";
                header('Content-Type: application/json');
                try {
                    if ($conn->query($sql) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success " . $jmlBarisDiubah . " data " . $namaBarang . " Berhasil di ubah",
                        );
                        http_response_code(200);
                        echo json_encode($data);
                    } else {
                        throw new Exception($conn->error);
                    }
                } catch (Exception $e) {
                    $data = array(
                        "message" => "error",
                        "error" => $e->getMessage()
                    );
                    http_response_code(400);
                    echo json_encode($data);
                }
            }
        } else {
            $sql = "
                     update barang set
                        namaBarang = '" . $namaBarang . "',
                        tarifHarian = '" . $tarifHarian . "',
                        fotoBarang = '" . $targetFile . "'
                        where id='" . $idBarang . "'
                ";
            header('Content-Type: application/json');
            try {
                if ($conn->query($sql) === TRUE) {
                    $jmlBarisDiubah = $conn->affected_rows;
                    $data = array(
                        "message" => "success " . $jmlBarisDiubah . " data",
                    );
                    http_response_code(200);
                    echo json_encode($data);
                } else {
                    throw new Exception($conn->error);
                }
            } catch (Exception $e) {
                $data = array(
                    "message" => "error",
                    "error" => $e->getMessage()
                );
                http_response_code(400);
                echo json_encode($data);
            }
        }
        $conn->close();
        break;

    case 'barangDelete':
        $idBarang = isset($_POST['idBarang']) ? $_POST['idBarang'] : null;
        $sql = "DELETE FROM barang WHERE id = '$idBarang'";
        header('Content-Type: application/json');
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "success " . $jmlBarisDiubah . " data",
                );
                http_response_code(200);
                echo json_encode($data);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $data = array(
                "message" => "error",
                "error" => $e->getMessage()
            );
            http_response_code(400);
            echo json_encode($data);
        }
        $conn->close();
        break;


    case 'cekTransaksi':
        $query = "
            SELECT 
            transaksi.id as idTransaksi,
            barang.status as barangStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian,
            transaksi.tarif as tarifTotal,
            transaksi.tglPinjam as tglPinjam,
            transaksi.tglKembali as tglKembali,
            barang.namaBarang as namaBarang,
            transaksi.status as transaksiStatus     
            from transaksi 
            inner join barang on barang.id = transaksi.idBarang
            inner join user on user.id = transaksi.idHost
            order by time desc";
        $result = $conn->query($query);
        $data = array();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $conn->close();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
        break;

    case 'cekTransaksiDetail':
        $idTransaksi = isset($_GET['idTransaksi']) ? $_GET['idTransaksi'] : null;
        $query = "
                SELECT barang.status as barangStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian,
                transaksi.tarif as tarifTotal,
                transaksi.tglPinjam as tglPinjam,
                transaksi.tglKembali as tglKembali,
                barang.namaBarang as namaBarang,
                user.namaLengkap as guestNamaLengkap,
                user.kota as guestKota,
                user.alamat as guestAlamat,
                user.noTelf as guestNoTelf,
                transaksi.status as transaksiStatus        
                from transaksi 
                inner join barang on barang.id = transaksi.idBarang
                inner join user on user.id = transaksi.idHost
                where transaksi.id = '" . $idTransaksi . "' 
                order by time desc";
        $result = $conn->query($query);
        $data = array();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        $conn->close();
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
        break;


    case 'editTransaksiStatus':
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $status = isset($_POST['status']) ? $_POST['status'] : null;
        $sql = "
                UPDATE transaksi SET            
                status='" . $conn->real_escape_string($status) . "'
                WHERE id = '" . $conn->real_escape_string($idTransaksi) . "'
            ";
        header('Content-Type: application/json');
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "Merubah status transaksi sukses. Jumlah baris yang diubah: " . $jmlBarisDiubah,
                    "status" => $status
                );
                http_response_code(200);
                echo json_encode($data);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $data = array(
                "message" => "Gagal merubah status transaksi",
                "error" => $e->getMessage()
            );
            http_response_code(400);
            echo json_encode($data);
        }
        break;

    case 'editBarangStatus':
        $idBarang = isset($_POST['idBarang']) ? $_POST['idBarang'] : null;
        $status = isset($_POST['status']) ? $_POST['status'] : null;
        $sql = "
                UPDATE barang SET            
                status='" . $conn->real_escape_string($status) . "'
                WHERE id = '" . $conn->real_escape_string($idBarang) . "'
            ";
        header('Content-Type: application/json');
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "Merubah status barang sukses. Jumlah baris yang diubah: " . $jmlBarisDiubah,
                    "status" => $status
                );
                http_response_code(200);
                echo json_encode($data);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $data = array(
                "message" => "Gagal merubah status barang",
                "error" => $e->getMessage()
            );
            http_response_code(400);
            echo json_encode($data);
        }
        break;








}
?>