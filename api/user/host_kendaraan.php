<?php
//ini_set('display_errors', 0);
require_once "koneksi.php";
require_once "encrypt.php";
$sessionID = isset($_GET['sessionID']) ? $_GET['sessionID'] : null;
$decrypt =  decrypt($sessionID, $key, $iv);
$sessionIdUser = isset($_GET['sessionIdUser']) ? $_GET['sessionIdUser'] : "_";

$query = "SELECT * FROM user WHERE id='$sessionIdUser'";
$result = $conn->query($query);
if ($result->num_rows != 1) {
    header('Content-Type: application/json');
    $data = array(
        "message"   => "Session login invalid",
    );
    http_response_code(400);
    echo json_encode($data);
    exit();
}
if ($sessionIdUser != $decrypt){
    header('Content-Type: application/json');
    $data = array(
        "message"   => "Session login invalid",
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
            kendaraan.id as idKendaraan,
            kendaraan.jenis as kendaraanJenis,
            kendaraan.namaKendaraan as namaKendaraan,
            kendaraan.nopol as nopol,
            kendaraan.tarifHarian as tarifHarian,
            kendaraan.fotoKendaraan as fotoKendaraan,
            kendaraan.status as kendaraanStatus            
            from kendaraan
            inner join user on kendaraan.idUser = user.id
            WHERE 
            (kendaraan.jenis LIKE '%$search%' OR 
            kendaraan.namaKendaraan LIKE '%$search%' OR 
            kendaraan.nopol LIKE '%$search%' OR 
            user.kota LIKE '%$search%') and
            kendaraan.idUser = '".$sessionIdUser."'
          ORDER BY namaKendaraan ASC";          
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
        $idKendaraan = isset($_GET['idKendaraan']) ? $_GET['idKendaraan'] : null;
        $query = "SELECT 
            user.id as idUser, 
            user.namaLengkap as namaLengkap,
            user.kota as kota,
            user.noTelf as noTelf,
            kendaraan.id as idKendaraan,
            kendaraan.jenis as kendaraanJenis,
            kendaraan.namaKendaraan as namaKendaraan,
            kendaraan.nopol as nopol,
            kendaraan.tarifHarian as tarifHarian,
            kendaraan.fotoKendaraan as fotoKendaraan,
            kendaraan.status as kendaraanStatus            
            from kendaraan
            inner join user on kendaraan.idUser = user.id
            WHERE 
            kendaraan.id = '".$idKendaraan."' 
            and
            kendaraan.idUser = '".$sessionIdUser."'
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
        $fileName = $_FILES["fotoKendaraan"]["name"];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($fileType, $allowedTypes)) {
            $tempFile = $_FILES["fotoKendaraan"]["tmp_name"];
            $targetFile = $uploadDir . uniqid() . "." . $fileType;                
            if (move_uploaded_file($tempFile, $targetFile)) {                                            
                
                $jenisKendaraan = isset($_POST['jenisKendaraan']) ? $_POST['jenisKendaraan'] : null;
                $namaKendaraan = isset($_POST['namaKendaraan']) ? $_POST['namaKendaraan'] : null;
                $nopol = isset($_POST['nopol']) ? $_POST['nopol'] : null;
                $tarifHarian = isset($_POST['tarifHarian']) ? $_POST['tarifHarian'] : null;
                                
                $sql = "INSERT INTO kendaraan (idUser,jenis, namaKendaraan, nopol, tarifHarian, fotoKendaraan) 
                VALUES ('$sessionIdUser','$jenisKendaraan', '$namaKendaraan', '$nopol', '$tarifHarian','$targetFile')";
                header('Content-Type: application/json');            
                try {
                    if ($conn->query($sql) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success ".$jmlBarisDiubah. " data",         
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
        $jenisKendaraan = isset($_POST['jenisKendaraan']) ? $_POST['jenisKendaraan'] : null;
        $namaKendaraan = isset($_POST['namaKendaraan']) ? $_POST['namaKendaraan'] : null;
        $nopol = isset($_POST['nopol']) ? $_POST['nopol'] : null;
        $tarifHarian = isset($_POST['tarifHarian']) ? $_POST['tarifHarian'] : null;
        $idKendaraan = isset($_POST['idKendaraan']) ? $_POST['idKendaraan'] : null;        
        $uploadDir = "uploads/$sessionIdUser/"; 
        $allowedTypes = ["jpg", "jpeg", "png"]; 
        $fileName = $_FILES["fotoKendaraan"]["name"];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($fileType, $allowedTypes)) {
            $tempFile = $_FILES["fotoKendaraan"]["tmp_name"];
            $targetFile = $uploadDir . uniqid() . "." . $fileType;                
            if (move_uploaded_file($tempFile, $targetFile)) {                                            
                $sql = "
                    update kendaraan set
                    jenis = '".$jenisKendaraan."',
                    namaKendaraan = '".$namaKendaraan."',
                    nopol = '".$nopol."',
                    tarifHarian = '".$tarifHarian."',
                    fotoKendaraan = '".$targetFile."'
                    where id='".$idKendaraan."'
                ";      
                header('Content-Type: application/json');            
                try {
                    if ($conn->query($sql) === TRUE) {
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message" => "success ".$jmlBarisDiubah. " data",         
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
                update kendaraan set
                jenis = '".$jenisKendaraan."',
                namaKendaraan = '".$namaKendaraan."',
                nopol = '".$nopol."',
                tarifHarian = '".$tarifHarian."'      
                where id='".$idKendaraan."'
            ";                     
            header('Content-Type: application/json');            
            try {
                if ($conn->query($sql) === TRUE) {
                    $jmlBarisDiubah = $conn->affected_rows;
                    $data = array(
                        "message" => "success ".$jmlBarisDiubah. " data",         
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
        $idKendaraan = isset($_POST['idKendaraan']) ? $_POST['idKendaraan'] : null;        
        $sql = "DELETE FROM kendaraan WHERE id = '$idKendaraan'";        
        header('Content-Type: application/json');            
        try {
            if ($conn->query($sql) === TRUE) {
                $jmlBarisDiubah = $conn->affected_rows;
                $data = array(
                    "message" => "success ".$jmlBarisDiubah. " data",         
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
        SELECT kendaraan.status as kendaraanStatus, kendaraan.idUser as idUserHost, kendaraan.tarifHarian as tarifHarian, 
        kendaraan.namaKendaraan as namaKendaraan ,
        kendaraan.id as idKendaraan
        from kendaraan 
        inner join user on kendaraan.idUser = user.id
        inner join transaksi on transaksi.idKendaraan = kendaraan.id
        where transaksi.id = '".$idTransaksi."'";                      
        $result = $conn->query($query);        
        $data = array();
        if ($result->num_rows > 0) {            
            $row = $result->fetch_assoc();                
            $status = $row['kendaraanStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaKendaraan = $row['namaKendaraan'];                                 
            $idKendaraan = $row['idKendaraan'];                                 
            if ($status != "booking"){
                header('Content-Type: application/json');            
                $data = array(
                    "message" => "error",                
                    "keterangan" => "booking tidak bisa ditolak"
                );
                http_response_code(400);   
                echo json_encode($data);
                exit();
            }else{

                $sqlUpdateKendaraan = "update kendaraan set 
                status = 'ready'
                where id='".$idKendaraan."'
                ";       
                $conn->query($sqlUpdateKendaraan);                          
                
                $sqlUpdate = "update transaksi set 
                status = 'bookingReject'
                where id='".$idTransaksi."'
                ";                  
                

                header('Content-Type: application/json');                                    
                try {                            
                    if ($conn->query($sqlUpdate) === TRUE) {                            
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message"     => "success",      
                            "keterangan"    => "anda telah menolak transaksi dari Host"                          
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
        SELECT kendaraan.status as kendaraanStatus, kendaraan.idUser as idUserHost, kendaraan.tarifHarian as tarifHarian, 
        kendaraan.namaKendaraan as namaKendaraan,
        kendaraan.id as idKendaraan

        from kendaraan 
        inner join user on kendaraan.idUser = user.id
        inner join transaksi on transaksi.idKendaraan = kendaraan.id
        where transaksi.id = '".$idTransaksi."'";                
        $result = $conn->query($query);        
        $data = array();
        if ($result->num_rows > 0) {            
            $row = $result->fetch_assoc();                
            $status = $row['kendaraanStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaKendaraan = $row['namaKendaraan'];                                 
            $idKendaraan = $row['idKendaraan'];    
            if ($status != "booking"){
                header('Content-Type: application/json');            
                $data = array(
                    "message" => "error",                
                    "keterangan" => "booking tidak bisa diterima"
                );
                http_response_code(400);   
                echo json_encode($data);
                exit();
            }else{
                                                                  
                $sqlUpdate = "update transaksi set 
                status = 'bookingConfirm'
                where id='".$idTransaksi."'";                                  

                header('Content-Type: application/json');                                    
                try {                            
                    if ($conn->query($sqlUpdate) === TRUE) {                            
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message"     => "success",      
                            "keterangan"    => "anda telah Menerima Transaksi dari guest"                          
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



    case 'serahkanKendaraanKeGuest':                                     
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $query = "
        SELECT kendaraan.status as kendaraanStatus, kendaraan.idUser as idUserHost, kendaraan.tarifHarian as tarifHarian, 
        kendaraan.namaKendaraan as namaKendaraan,
        kendaraan.id as idKendaraan 
        from kendaraan         
        inner join user on kendaraan.idUser = user.id
        inner join transaksi on transaksi.idKendaraan = kendaraan.id
        where transaksi.id = '".$idTransaksi."'";        
           
        $result = $conn->query($query);        
        $data = array();
        if ($result->num_rows > 0) {            
            $row = $result->fetch_assoc();                
            $status = $row['kendaraanStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaKendaraan = $row['namaKendaraan'];                                                        
            $idKendaraan = $row['idKendaraan'];                                                        
            if ($status == "booking"){

                $sqlUpdateKendaraan = "update kendaraan set 
                status = 'onGuest'
                where id='".$idKendaraan."'
                ";   
                $conn->query($sqlUpdateKendaraan);


                $sqlUpdate = "update transaksi set 
                status = 'onGuest'
                where id='".$idTransaksi."'
                ";                                  
                header('Content-Type: application/json');                                    
                try {                            
                    if ($conn->query($sqlUpdate) === TRUE) {                            
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message"     => "success",      
                            "keterangan"    => "anda telah menyerahkan kendaraan ke guest"                          
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

    case 'menerimaKendaraanDariGuest':                                     
        $idTransaksi = isset($_POST['idTransaksi']) ? $_POST['idTransaksi'] : null;
        $query = "
        SELECT kendaraan.status as kendaraanStatus, kendaraan.idUser as idUserHost, kendaraan.tarifHarian as tarifHarian, 
        kendaraan.namaKendaraan as namaKendaraan,
        kendaraan.id as idKendaraan  
        from kendaraan 
        inner join user on kendaraan.idUser = user.id
        inner join transaksi on transaksi.idKendaraan = kendaraan.id
        where transaksi.id = '".$idTransaksi."'";     
           
        $result = $conn->query($query);        
        $data = array();
        if ($result->num_rows > 0) {            
            $row = $result->fetch_assoc();                
            $status = $row['kendaraanStatus'];
            $idUserHost = $row['idUserHost'];
            $tarifHarian = $row['tarifHarian'];
            $namaKendaraan = $row['namaKendaraan'];      
            $idKendaraan = $row['idKendaraan'];                                                                                                          
            if ($status == "onGuest"){

                $sqlUpdateKendaraan = "update kendaraan set 
                status = 'ready'
                where id='".$idKendaraan."'
                ";   
                $conn->query($sqlUpdateKendaraan);


                $sqlUpdate = "update transaksi set 
                status = 'selesai'
                where id='".$idTransaksi."'
                ";                                  
                header('Content-Type: application/json');                                    
                try {                            
                    if ($conn->query($sqlUpdate) === TRUE) {                            
                        $jmlBarisDiubah = $conn->affected_rows;
                        $data = array(
                            "message"     => "success",      
                            "keterangan"    => "anda telah menerima kendaraan dari guest dan dengan ini transaksi selesai"                          
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

           

    case 'cekTransaksiDetail':                                     
        $idTransaksi = isset($_GET['idTransaksi']) ? $_GET['idTransaksi'] : null;
        $query = "
        SELECT kendaraan.status as kendaraanStatus, kendaraan.idUser as idUserHost, kendaraan.tarifHarian as tarifHarian,
        transaksi.tarif as tarifTotal,
        transaksi.tglPinjam as tglPinjam,
        transaksi.tglKembali as tglKembali,
        kendaraan.namaKendaraan as namaKendaraan,
        user.namaLengkap as guestNamaLengkap,
        user.kota as guestKota,
        user.alamat as guestAlamat,
        user.noTelf as guestNoTelf,
        transaksi.status as transaksiStatus        
        from transaksi 
        inner join kendaraan on kendaraan.id = transaksi.idKendaraan
        inner join user on user.id = transaksi.idHost
        where transaksi.id = '".$idTransaksi."' 
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

    case 'cekTransaksi':                                             
        $query = "
        SELECT 
        transaksi.id as idTransaksi,
        kendaraan.status as kendaraanStatus, kendaraan.idUser as idUserHost, kendaraan.tarifHarian as tarifHarian,
        transaksi.tarif as tarifTotal,
        transaksi.tglPinjam as tglPinjam,
        transaksi.tglKembali as tglKembali,
        kendaraan.namaKendaraan as namaKendaraan,
        transaksi.status as transaksiStatus     
        from transaksi 
        inner join kendaraan on kendaraan.id = transaksi.idKendaraan
        inner join user on user.id = transaksi.idHost
        where transaksi.idHost = '".$sessionIdUser."' order by time desc";                  
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
