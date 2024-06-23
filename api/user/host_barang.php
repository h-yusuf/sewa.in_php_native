<?php
//ini_set('display_errors', 0);
require_once "koneksi.php";
require_once "encrypt.php";
$sessionID = isset($_GET['sessionID']) ? $_GET['sessionID'] : null;
$decrypt = decrypt($sessionID, $key, $iv);
$sessionIdUser = isset($_GET['sessionIdUser']) ? $_GET['sessionIdUser'] : "_";

$query = "SELECT * FROM user WHERE id='$sessionIdUser'";
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
            (barang.namaBarang LIKE '%$search%' OR 
            user.kota LIKE '%$search%') and
            barang.idUser = '" . $sessionIdUser . "'
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

    case 'getDetail':
        $idBarang = isset($_GET['idBarang']) ? $_GET['idBarang'] : null;
        $query = "SELECT 
            user.id as idUser, 
            user.namaLengkap as namaLengkap,
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
            barang.id = '" . $idBarang . "' 
            and
            barang.idUser = '" . $sessionIdUser . "'
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

    case 'insert':
        header('Content-Type: application/json');
        $idPembayaranCekAfter = '';
        $uploadDir = "uploads/$sessionIdUser/";
        $allowedTypes = ["jpg", "jpeg", "png"];
        $fileName = $_FILES["fotoBarang"]["name"];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($fileType, $allowedTypes)) {
            $tempFile = $_FILES["fotoBarang"]["tmp_name"];
            $targetFile = $uploadDir . uniqid() . "." . $fileType;
            if (move_uploaded_file($tempFile, $targetFile)) {

                $namaBarang = isset($_POST['namaBarang']) ? $_POST['namaBarang'] : null;
                $tarifHarian = isset($_POST['tarifHarian']) ? $_POST['tarifHarian'] : null;

                $sql = "INSERT INTO barang (idUser, namaBarang, tarifHarian, fotoBarang) 
                VALUES ('$sessionIdUser', '$namaBarang', '$tarifHarian','$targetFile')";
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
        } else {
            $data = array(
                "message" => "error",
                "error" => "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed."
            );
            http_response_code(400);
            echo json_encode($data);
        }
        $conn->close();
        break;


    case 'edit':
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

    case 'delete':
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

    case 'bookingReject':
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $query = "
        SELECT barang.status as barangStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian, 
        barang.namaBarang as namaBarang ,
        barang.id as idBarang
        from barang 
        inner join user on barang.idUser = user.id
        inner join transaksi on transaksi.idBarang = barang.id
        where transaksi.id = '" . $idTransaksi . "'";
        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status = $row['barangStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaBarang = $row['namaBarang'];
            $idBarang = $row['idBarang'];
            if ($status != "booking") {
                header('Content-Type: application/json');
                $data = array(
                    "message" => "error",
                    "keterangan" => "booking tidak bisa ditolak"
                );
                http_response_code(400);
                echo json_encode($data);
                exit();
            } else {

                $sqlUpdateBarang = "update barang set 
                status = 'ready'
                where id='" . $idBarang . "'
                ";
                $conn->query($sqlUpdateBarang);

                $sqlUpdate = "update transaksi set 
                status = 'bookingReject'
                where id='" . $idTransaksi . "'
                ";

                header('Content-Type: application/json');
                try {
                    if ($conn->query($sqlUpdate) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success",
                            "keterangan" => "anda telah menolak transaksi dari Host"
                        );
                        http_response_code(200);
                        echo json_encode($data);
                        exit();
                    } else {
                        throw new Exception($conn->error);
                    }
                } catch (Exception $e) {
                    $data = array(
                        "message" => "error",
                        "error" => $e->getMessage()
                    );
                    http_response_code(400);
                }
                http_response_code(200);
                echo json_encode($data);

            }
        }
        $conn->close();
        break;

    case 'bookingConfirm':
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $query = "
        SELECT barang.status as barangStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian, 
        barang.namaBarang as namaBarang,
        barang.id as idBarang

        from Barang 
        inner join user on barang.idUser = user.id
        inner join transaksi on transaksi.idBarang = barang.id
        where transaksi.id = '" . $idTransaksi . "'";
        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status = $row['barangStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaBarang = $row['namaBarang'];
            $idBarang = $row['idBarang'];
            if ($status != "booking") {
                header('Content-Type: application/json');
                $data = array(
                    "message" => "error",
                    "keterangan" => "booking tidak bisa diterima"
                );
                http_response_code(400);
                echo json_encode($data);
                exit();
            } else {

                $sqlUpdate = "update transaksi set 
                status = 'bookingConfirm'
                where id='" . $idTransaksi . "'";

                header('Content-Type: application/json');
                try {
                    if ($conn->query($sqlUpdate) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success",
                            "keterangan" => "anda telah Menerima Transaksi dari guest"
                        );
                        http_response_code(200);
                        echo json_encode($data);
                        exit();
                    } else {
                        throw new Exception($conn->error);
                    }
                } catch (Exception $e) {
                    $data = array(
                        "message" => "error",
                        "error" => $e->getMessage()
                    );
                    http_response_code(400);
                }
                http_response_code(200);
                echo json_encode($data);

            }
        }
        $conn->close();
        break;



    case 'serahkanBarangKeGuest':
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $query = "
        SELECT barang.status as barangStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian, 
        barang.namaBarang as namaBarang,
        barang.id as idBarang 
        from barang         
        inner join user on barang.idUser = user.id
        inner join transaksi on transaksi.idBarang = barang.id
        where transaksi.id = '" . $idTransaksi . "'";

        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status = $row['barangStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaBarang = $row['namaBarang'];
            $idBarang = $row['idBarang'];
            if ($status == "booking") {

                $sqlUpdateBarang = "update Barang set 
                status = 'onGuest'
                where id='" . $idBarang . "'
                ";
                $conn->query($sqlUpdateBarang);


                $sqlUpdate = "update transaksi set 
                status = 'onGuest'
                where id='" . $idTransaksi . "'
                ";
                header('Content-Type: application/json');
                try {
                    if ($conn->query($sqlUpdate) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success",
                            "keterangan" => "anda telah menyerahkan Barang ke guest"
                        );
                        http_response_code(200);
                        echo json_encode($data);
                        exit();
                    } else {
                        throw new Exception($conn->error);
                    }
                } catch (Exception $e) {
                    $data = array(
                        "message" => "error",
                        "error" => $e->getMessage()
                    );
                    http_response_code(400);
                }
                http_response_code(200);
                echo json_encode($data);

            }
        }
        $conn->close();
        break;

    case 'menerimaBarangDariGuest':
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $query = "
        SELECT transaksi.status as transaksiStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian, 
        barang.namaBarang as namaBarang,
        barang.id as idBarang  
        from barang 
        inner join user on barang.idUser = user.id
        inner join transaksi on transaksi.idBarang = barang.id
        where transaksi.id = '" . $idTransaksi . "'";

        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status = $row['transaksiStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaBarang = $row['namaBarang'];
            $idBarang = $row['idBarang'];
            if ($status === "dikembalikan") {

                $sqlUpdateBarang = "update Barang set 
                status = 'ready'
                where id='" . $idBarang . "'
                ";
                $conn->query($sqlUpdateBarang);

                $sqlUpdate = "update transaksi set 
                status = 'selesai'
                where id='" . $idTransaksi . "'
                ";
                header('Content-Type: application/json');
                try {
                    if ($conn->query($sqlUpdate) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success",
                            "keterangan" => "anda telah menerima Barang dari guest dan dengan ini transaksi selesai"
                        );
                        http_response_code(200);
                        echo json_encode($data);
                        exit();
                    } else {
                        throw new Exception($conn->error);
                    }
                } catch (Exception $e) {
                    $data = array(
                        "message" => "error",
                        "error" => $e->getMessage()
                    );
                    http_response_code(400);
                }
                http_response_code(200);
                echo json_encode($data);
            } else {
                $data = array(
                    "message" => "error",
                    "error" => "Barang belum dikembalikan"
                );
                http_response_code(400);
                echo json_encode($data);
            }
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
        where transaksi.idHost = '" . $sessionIdUser . "' order by time desc";
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




}
?>