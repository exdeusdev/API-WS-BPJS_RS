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
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, x-username, x-password");


// Load JWT & Custom Class
use \Firebase\JWT\JWT;
use \Exdeus\JWT\EX;


// Define variable
$headers = apache_request_headers();
$data = array(
    'username' => (isset($headers['X-Username']) ? $headers['X-Username'] : $headers['x-username']),
    'password' => (isset($headers['X-Password']) ? $headers['X-Password'] : $headers['x-password']),
	'remoteaddr' => (isset($headers['Origin']) ? $headers['Origin'] : $headers['origin'])
);


// Return array to Object data
$data = (object)$data;


// jika tidak ingin/perlu memvalidasi apakah permintaan bukan dari server bpjs
// silahkan comment code di bawah ini
// !*START!
if ($data->remoteaddr != $HOST_BPJS) {
    EX::response(NULL, "Autentikasi gagal: invalid remote address", 202);
}
// !*END*!

// Pastikan bahwa username dan password tidak kosong/null
elseif (isset($data->username) && isset($data->password)) {

    // Metode jika tidak menggunakan class

    // jika username dan password ada, lanjutkan koneksi ke DB	
	$db = new PDO("mysql:host=".$MYSQL_HOST.";port=".$MYSQL_PORT.";dbname=".$MYSQL_DB, $MYSQL_USER, $MYSQL_PASS);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Setelah koneksi berhasil lanjutkan query untuk generate token

    // Jika ingin lebih secure tambahkan timestamp; time()
	try {
		$stmt = $db->query("SELECT username, password FROM tbl_user WHERE username = '$data->username' AND password = '$data->password' ");
		$total_results = $stmt->rowCount();

		if ($total_results > 0) {
            $row = $stmt->fetchObject();

            $PAYLOAD = [
                "username" => $row->username,
                "password" => $row->password,
                "exp" => $expiretime
			];

            $JWT        = JWT::encode($PAYLOAD, $KEY);
            // $DECODED    = JWT::decode($JWT, $KEY, array('HS256'));
			
            EX::response($JWT, "OK", 200);
	
			// Clear db
			$stmt->closeCursor();
			$db = null;
            
		} else {
			EX::response(NULL, "Autentikasi gagal: username atau password salah", 202);
		}	
	
	} catch(PDOException $e) {
		// Jika terdapat error maka akan ditampilkan dalam format JSON
		EX::response(NULL, $e->getMessage(), 400);
	}
	
} else {
	EX::response(NULL, "X-Username atau X-Password tidak ada", 202);
}
?>