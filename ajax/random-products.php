<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$stmt = $pdo->query("
    SELECT product_id, name, description, image1, image2, price
    FROM products
    ORDER BY RAND()
    LIMIT 2
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
