<?php
/* API Service
 *
 * BPJS API Caller - RS API Webservice
 * 
 * Copyright, 2020. dr. Diko Aprilio
 */

// Display Error
error_reporting(E_ALL);
ini_set('display_errors', 'On');


// Timezone
date_default_timezone_set("Asia/Jakarta");


// Load Library
require_once '../vendor/autoload.php';
require_once '../database/config.php';
require_once '../function/function.php';


// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, x-username, x-password");


// Load JWT
use \Firebase\JWT\JWT;
use \Exdeus\JWT\EX;


// Define variable
$headers = apache_request_headers();
$data = array(
    'username' => (isset($headers['X-Username']) ? $headers['X-Username'] : $headers['x-username']),
    'token' => (isset($headers['X-Token']) ? $headers['X-Token'] : $headers['x-token']),
	'remoteaddr' => (isset($headers['Origin']) ? $headers['Origin'] : $headers['origin'])
);


// Menerima semua request melalui POST/GET
$body = file_get_contents('php://input');
if (!empty($body)) {
    $res = json_decode(urldecode($body), true);
}
$headers = apache_request_headers();


// Rearrange semua array sesuai variable yang kita inginkan
$res = array(
    'nomorkartu' => $res['nomorkartu'],
    'nik' => $res['nik'],
	'kodepoli' => $res['kodepoli'],
	'tanggalperiksa' => $res['tanggalperiksa']
);

// {
//     "nomorkartu": "00012345678",
//     "nik": "3212345678987654",
//     "kodepoli": "001",
//     "tanggalperiksa": "2020-01-28"
// }

// Return array to Object data
$data   = (object)$data;
$res    = (object)$res;


// jika tidak ingin/perlu memvalidasi apakah permintaan bukan dari server bpjs
// silahkan comment code di bawah ini
// !*START!
if ($data->remoteaddr != $HOST_BPJS) {
    EX::response(NULL, "Autentikasi gagal: invalid remote address", 202);
}
// !*END*!

// Pastikan bahwa username dan password tidak kosong/null
elseif (isset($data->username) && isset($data->token)) {
    
    try {
        // $JWT    = JWT::encode($PAYLOAD, $KEY);
        $JWT    = JWT::decode($data->token, $KEY, array('HS256'));

        $time = time();
        if ($JWT->exp < $time) {
            EX::response(NULL, "Token Expired", 202);
            
        } else {
            // Metode jika tidak menggunakan class

            // jika username dan password ada, lanjutkan koneksi ke DB	
            $db = new PDO("mysql:host=".$MYSQL_HOST.";port=".$MYSQL_PORT.";dbname=".$MYSQL_DB, $MYSQL_USER, $MYSQL_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Setelah koneksi berhasil lanjutkan query untuk generate token
        
            // Jika ingin lebih secure tambahkan timestamp; time()
            try {
                // kalau beda tabel silahkan pakai INNER/LEFT/RIGHT/OUTER JOIN sesuai style masing masing
                $stmt = $db->query("SELECT A.nomorantrean, A.keterangan, B.tanggalantrean, B.sisaantrean, B.antreanpanggil, C.namapoli, C.kodepolihuruf
                                    FROM tbl_kunjungan AS A
                                    INNER JOIN tbl_antrean AS B ON B.tanggalantrean = A.tanggalperiksa
                                    INNER JOIN tbl_poli AS C ON A.kodepoli = C.kodepoli
                                    WHERE A.nik = '$res->nik'
                                    AND B.tanggalantrean = '$res->tanggalperiksa'
                                    AND A.kodepoli = '$res->kodepoli' ");

                $total_results = $stmt->rowCount();
        
                if ($total_results > 0) {
                    $row = $stmt->fetchObject();
                    
                    $angkaantrean = preg_replace('/\D/', '', $row->nomorantrean);

                    // Jika nomor antrian pasien sudah terlewati,
                    // variabel keterangan diganti menjadi "Nomor antrian anda sudah terlewat"
                    if ($row->antreanpanggil > (int)$angkaantrean) {
                        $row->keterangan = "Nomor antrian anda sudah terlewat, harap mengambil antrean kembali.";
                    }

                    // Jika angka antrean panggil kurang dari 10
                    // ditambahkan angka 0 di depannya --- Cara simpel
                    if ($row->antreanpanggil < 10) {
                        $row->antreanpanggil = "0".$row->antreanpanggil;
                    }
        
                    $response = [
                        "nomorantrean" => $row->nomorantrean,
                        "angkaantrean" => (int)$angkaantrean,
                        "namapoli" => $row->namapoli,
                        "sisaantrean" => $row->sisaantrean,
                        "antreanpanggil" => $row->kodepolihuruf.$row->antreanpanggil,
                        "keterangan" => $row->keterangan
                    ];
                    
                    EX::response($response, "OK", 200);
            
                    // Clear db
                    $stmt->closeCursor();
                    $db = null;
                    
                } else {
                    EX::response(NULL, "Autentikasi gagal: username atau token salah", 202);
                }	
            
            } catch(PDOException $e) {
                    EX::response(NULL, $e->getMessage(), 400);
            }
        }
    } catch (\Exception $e) {
		// Jika terdapat error maka akan ditampilkan dalam format JSON
	    EX::response(NULL, $e->getMessage(), 202);
    }

} else {
	EX::response(NULL, "X-Username atau X-Token tidak ada", 202);
}

?>