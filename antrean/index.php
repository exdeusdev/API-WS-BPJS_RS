<?php
// Display Error
error_reporting(E_ALL);
ini_set('display_errors', 'On');


// Timezone
date_default_timezone_set("Asia/Jakarta");


// Load Library
require_once '../vendor/autoload.php';
require_once '../database/config.php';


// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, x-username, x-password");


// Load JWT
use \Firebase\JWT\JWT;


// Define variable
$KEY = $SECRET_KEY;
$headers = apache_request_headers();
$data = array(
    'username' => (isset($headers['X-Username']) ? $headers['X-Username'] : $headers['x-username']),
    'token' => (isset($headers['X-Token']) ? $headers['X-Token'] : $headers['x-token']),
	'remoteaddr' => (isset($headers['Origin']) ? $headers['Origin'] : $headers['origin'])
);


$body = file_get_contents('php://input');
if (!empty($body)) {
    $res = json_decode(urldecode($body), true);
}
$headers = apache_request_headers();


$res = array(
    'nomorkartu' => $res['nomorkartu'],
    'nik' => $res['nik'],
    'nomorrm' => $res['nomorrm'],
    'notelp' => $res['notelp'],
	'tanggalperiksa' => $res['tanggalperiksa'],
	'kodepoli' => $res['kodepoli'],
	'nomorreferensi' => $res['nomorreferensi'],
	'jenisreferensi' => $res['jenisreferensi'],
	'jenisrequest' => $res['jenisrequest'],
	'polieksekutif' => $res['polieksekutif']
);

// Return array to Object data
$data   = (object)$data;
$res    = (object)$res;


// jika tidak ingin/perlu memvalidasi apakah permintaan bukan dari server bpjs
// silahkan comment code di bawah ini
// !*START!
if ($data->remoteaddr != $HOST_BPJS) {
    response (NULL, "Autentikasi gagal: invalid remote address", 202);
}
// !*END*!

// Pastikan bahwa username dan password tidak kosong/null
elseif (isset($data->username) && isset($data->token)) {
    
    // $JWT    = JWT::encode($PAYLOAD, $KEY);
    $JWT    = JWT::decode($data->token, $KEY, array('HS256'));

    $time = time();
    if ($JWT->expire < $time) {
        response(NULL, "Token Expired", 202);
        
    } else {
        // Metode jika tidak menggunakan class

        // jika username dan password ada, lanjutkan koneksi ke DB	
        $db = new PDO("mysql:host=".$MYSQL_HOST.";port=".$MYSQL_PORT.";dbname=".$MYSQL_DB, $MYSQL_USER, $MYSQL_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Setelah koneksi berhasil lanjutkan query untuk generate token
    
        // Jika ingin lebih secure tambahkan timestamp; time()
        try {
            // kalau beda tabel silahkan pakai INNER/LEFT/RIGHT/OUTER JOIN sesuai style masing masing
            $stmt = $db->query("SELECT A.nomorantrean, A.kodebooking, A.jenisantrean, C.namapoli, C.namadokter
                                FROM tbl_kunjungan AS A
                                INNER JOIN tbl_antrean AS B ON B.tanggalantrean = A.tanggalperiksa
                                INNER JOIN tbl_poli AS C ON A.kodepoli = C.kodepoli
                                WHERE A.nik = '$res->nik'
                                AND B.tanggalantrean = '$res->tanggalperiksa'
                                AND A.kodepoli = '$res->kodepoli' ");

            $total_results = $stmt->rowCount();
    
            // estimasi dilayani
            // kalkulasikan sendiri berdasarkan tabel lain jika punya,
            // contoh: log antrian sebelumnya (setiap panggilan punya timestamp, dan bisa dijadikan average interval)
            function estimasidilayani() {
                // Baris kode kalkulasi
                $est = 'CODE';

                return $est;
            }

            if ($total_results > 0) {
                $row = $stmt->fetchObject();
    
                $response = [
                    "nomorantrean" => $row->nomorantrean,
                    "kodebooking" => $row->kodebooking,
                    "jenisantrean" => $row->jenisantrean,
                    "estimasidilayani" => estimasidilayani(),
                    "namapoli" => $row->namapoli,
                    "namadokter" => $row->namadokter
                ];
                
                response($response, "OK", 200);
        
                // Clear db
                $stmt->closeCursor();
                $db = null;
                
            } else {
                response(NULL, "Data tidak ditemukan", 202);
            }	
        
        } catch(PDOException $e) {
                response (NULL, $e->getMessage(), 400);
        }
    }

} else {
	response(NULL, "X-Username atau X-Token tidak ada", 202);
}

function response($response, $message, $code){
	http_response_code($code);
	$response = [
		'response' => $response,
		'metadata' => [
			'message' => $message,
			'code' => $code
		]
	];

	$json_response = json_encode($response ,JSON_PRETTY_PRINT);
	echo $json_response;
}
?>