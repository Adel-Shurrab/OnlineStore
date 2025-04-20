<?php
session_start();
if (!isset($_SESSION['email']) && !isset($_SESSION['group_id']) && $_SESSION['group_id'] == 0) {
    header("Location: ../login.php");
    exit;
}
include 'connect.php';

header('Content-Type: application/json');

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    
    $stmt = $con->prepare("SELECT item_id, item_name FROM items WHERE item_name LIKE ? LIMIT 10");
    $stmt->execute(['%' . $query . '%']);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($items);
}
