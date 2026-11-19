<?php
header('Content-Type: application/json');
require_once 'db.php';

$stmt = $pdo->query("SELECT * FROM cinemas ORDER BY id");
echo json_encode($stmt->fetchAll());
?>
