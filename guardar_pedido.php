<?php
session_start();
header('Content-Type: application/json');

// Sólo meseros
if (!isset($_SESSION['rol']) || $_SESSION['rol']!=='mesero') {
    http_response_code(403);
    echo json_encode(['status'=>'error','message'=>'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (
    !isset($data['mesa'], $data['comensales'], $data['pedidosComensales']) ||
    !is_int($data['mesa']) ||
    !is_int($data['comensales']) ||
    !is_array($data['pedidosComensales'])
) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Datos incompletos o inválidos']);
    exit;
}

$mesa = $data['mesa'];
// Guardar la estructura exacta que luego leerá finalizar_cuenta.php
$_SESSION['pedidos'][$mesa] = [
    'comensales'        => $data['comensales'],
    'pedidosComensales' => $data['pedidosComensales']
];

echo json_encode(['status'=>'ok','mesa'=>$mesa]);
