<?php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['mesa'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Mesa no especificada']);
    exit;
}

$mesa = intval($_GET['mesa']);
$pedido = $_SESSION['pedidos'][$mesa] ?? null;

echo json_encode(['pedido' => $pedido]);
?>
