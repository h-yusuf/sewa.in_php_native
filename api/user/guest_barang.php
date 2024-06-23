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


    case 'getDetail':
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


    case 'booking':
        $idBarang = isset($_POST['idBarang']) ? $_POST['idBarang'] : null;
        $query = "
        SELECT barang.status as barangStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian, 
        barang.namaBarang as namaBarang
        from barang 
        inner join user on barang.idUser = user.id
        where barang.id = '" . $idBarang . "'";

        $result = $conn->query($query);
        $data = array();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status = $row['barangStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaBarang = $row['namaBarang'];

            if ($status != "ready") {
                header('Content-Type: application/json');
                $data = array(
                    "message" => "error",
                    "keterangan" => "Barang tidak bisa diboking"
                );
                http_response_code(400);
                echo json_encode($data);
                exit();
            } else {

                $sql = "UPDATE barang SET 
                    status = 'booking'                    
                WHERE id = '$idBarang'";
                header('Content-Type: application/json');
                try {
                    if ($conn->query($sql) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $tglPinjam = isset($_POST['tglPinjam']) ? $_POST['tglPinjam'] : null;
                        $tglKembali = isset($_POST['tglKembali']) ? $_POST['tglKembali'] : null;
                        $tgl1 = new DateTime($tglPinjam);
                        $tgl2 = new DateTime($tglKembali);
                        $jarak = $tgl2->diff($tgl1);
                        $jeda = $jarak->d;

                        $tarifTotal = $jeda * $tarifHarian;
                        $sqlInsert = "INSERT INTO transaksi (idBarang, idGuest, idHost, tglPinjam, tglKembali, tarif,status) 
                        VALUES ('$idBarang', '$sessionIdUser', '$idUserHost', '$tglPinjam', '$tglKembali', '$tarifTotal', 'booking')";
                        header('Content-Type: application/json');
                        try {
                            if ($conn->query($sqlInsert) === TRUE) {
                                $last_id = $conn->insert_id;
                                $jmlBarisDiubah = $conn->affected_rows;
                                $data = array(
                                    "message" => "success",
                                    "keterangan" => $namaBarang . " telah sukses anda booking dari tanggal " . $tglPinjam . " sampai " . $tglKembali . " mohon tunggu confirm dari host",
                                    "idTransaksi" => $last_id,
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
        }
        $conn->close();
        break;


    case 'kembalikanBarang':
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $query = "
        SELECT barang.status as barangStatus, barang.idUser as idUserHost, barang.tarifHarian as tarifHarian, 
        barang.namaBarang as namaBarang
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
            if ($status != "onGuest") {
                header('Content-Type: application/json');
                $data = array(
                    "message" => "error",
                    "keterangan" => "Barang tidak bisa dikembalikan karena status masih " . $status
                );
                http_response_code(400);
                echo json_encode($data);
                exit();
            } else {

                $sqlUpdate = "update transaksi set 
                status = 'dikembalikan'
                where id='" . $idTransaksi . "'
                ";
                header('Content-Type: application/json');
                try {
                    if ($conn->query($sqlUpdate) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success",
                            "keterangan" => "mohon tunggu beberapa saat, host akan melakukan pengecekan dan membuat transaksi selesai"
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


    case 'cekTransaksi':
        $query = "
        SELECT 
        transaksi.id as idTransaksi,
        transaksi.status as transaksiStatus,
        barang.idUser as idUserHost,
        barang.tarifHarian as tarifHarian,
        transaksi.tarif as tarifTotal,
        transaksi.tglPinjam as tglPinjam,
        transaksi.tglKembali as tglKembali,
        barang.namaBarang as namaBarang
        from transaksi 
        inner join barang on barang.id = transaksi.idBarang
        inner join user on user.id = transaksi.idHost
        where transaksi.idGuest = '" . $sessionIdUser . "' order by time desc";
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
        SELECT transaksi.status as transaksiStatus,
        barang.idUser as idUserHost,
        barang.tarifHarian as tarifHarian,
        transaksi.tarif as tarifTotal,
        transaksi.tglPinjam as tglPinjam,
        transaksi.tglKembali as tglKembali,
        barang.namaBarang as namaBarang
        from transaksi 
        inner join barang on barang.id = transaksi.idbarang
        inner join user on user.id = transaksi.idHost
        where transaksi.id = '" . $idTransaksi . "' 
        order by time desc"
        ;
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

    case 'cekProfile':
        $query = "
        SELECT *
        from user 
        where user.id = '" . $sessionIdUser . "'";
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

    case 'editProfile':
        $idUser = isset($_POST['idUser']) ? $_POST['idUser'] : null;

        if ($idUser === null) {
            $data = array(
                "message" => "error",
                "error" => "User ID is required"
            );
            http_response_code(400);
            echo json_encode($data);
            exit();
        }

        $fetchQuery = "SELECT * FROM user WHERE id = '" . $idUser . "'";
        $result = $conn->query($fetchQuery);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $currentUsername = $row['username'];
            $currentPassword = $row['password'];
            $currentNamaLengkap = $row['namaLengkap'];
            $currentAlamat = $row['alamat'];
            $currentNoTelf = $row['noTelf'];
            $currentKota = $row['kota'];
            $currentStatus = $row['status'];
        } else {
            $data = array(
                "message" => "error",
                "error" => "User not found"
            );
            http_response_code(404);
            echo json_encode($data);
            exit();
        }

        $username = isset($_POST['username']) && $_POST['username'] !== '' ? $_POST['username'] : $currentUsername;
        $password = isset($_POST['password']) && $_POST['password'] !== '' ? $_POST['password'] : $currentPassword;
        $namaLengkap = isset($_POST['namaLengkap']) && $_POST['namaLengkap'] !== '' ? $_POST['namaLengkap'] : $currentNamaLengkap;
        $alamat = isset($_POST['alamat']) && $_POST['alamat'] !== '' ? $_POST['alamat'] : $currentAlamat;
        $noTelf = isset($_POST['noTelf']) && $_POST['noTelf'] !== '' ? $_POST['noTelf'] : $currentNoTelf;
        $kota = isset($_POST['kota']) && $_POST['kota'] !== '' ? $_POST['kota'] : $currentKota;

        $username = mysqli_real_escape_string($conn, $username);
        $password = mysqli_real_escape_string($conn, $password);
        $namaLengkap = mysqli_real_escape_string($conn, $namaLengkap);
        $alamat = mysqli_real_escape_string($conn, $alamat);
        $noTelf = mysqli_real_escape_string($conn, $noTelf);
        $kota = mysqli_real_escape_string($conn, $kota);

        $sql = "
            UPDATE user SET
            username='" . $username . "',
            password='" . $password . "',
            namaLengkap='" . $namaLengkap . "',
            alamat='" . $alamat . "',
            noTelf='" . $noTelf . "',
            kota='" . $kota . "',
            status='" . $currentStatus . "'
            WHERE id = '" . $idUser . "'
        ";

        header('Content-Type: application/json');
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "edit sukses",
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
}
?>