<?php

$dsn = 'mysql:host=localhost;dbname=eshop;charset=utf8';
$user = 'root';
$pass = '';
$option = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

try {
    $con = new PDO($dsn, $user, $pass, $option);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    echo 'Fail to Connect to the database.';
}
