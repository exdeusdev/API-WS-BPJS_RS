<?php
/* API Service
 *
 * BPJS API Caller - RS API Webservice
 * 
 * Copyright, 2020. dr. Diko Aprilio
 */

// Secret Key
$KEY                        = 'EXDEUS';

// Generate expire token
$expiretime                 = strtotime('+15 minutes');

// Host BPJS
$HOST_BPJS                  = 'https://bpjs-kesehatan.go.id/';

// Database
$MYSQL_HOST					= 'localhost';
$MYSQL_PORT					= '3306';
$MYSQL_USER					= 'root';
$MYSQL_PASS					= 'xzxzxzxz';
$MYSQL_DB					= 'ws-rs_bpjs';
	