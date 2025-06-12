<?php
session_start();
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require 'conexion.php'; // define $conn

// Sólo meseros
if (!isset($_SESSION['rol']) || $_SESSION['rol']!=='mesero') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mesa  = isset($input['mesa']) ? intval($input['mesa']) : 0;
if (!$mesa || !isset($_SESSION['pedidos'][$mesa])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Mesa inválida o sin pedido']);
    exit;
}

$pedido = $_SESSION['pedidos'][$mesa];
$total  = 0;
foreach ($pedido['pedidosComensales'] as $comensal) {
    foreach ($comensal as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
}

try {
    $conn->begin_transaction();

    // Inserta total (fecha = CURRENT_TIMESTAMP)
    $stmt = $conn->prepare("INSERT INTO ventas (total) VALUES (?)");
    $stmt->bind_param("d", $total);
    $stmt->execute();
    $venta_id = $stmt->insert_id;
    $stmt->close();

    // Inserta cada detalle (FK producto_id validado al cargar dinámicamente)
    $stmt2 = $conn->prepare(
      "INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio)
       VALUES (?, ?, ?, ?)"
    );
    foreach ($pedido['pedidosComensales'] as $comensal) {
        foreach ($comensal as $item) {
            $stmt2->bind_param(
              "iiid",
              $venta_id,
              $item['id'],
              $item['cantidad'],
              $item['precio']
            );
            $stmt2->execute();
        }
    }
    $stmt2->close();

    // Libera la mesa
    unset($_SESSION['pedidos'][$mesa]);

    $conn->commit();
    echo json_encode(['success'=>true,'message'=>'Venta registrada correctamente']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error al registrar la venta: '.$e->getMessage()]);
}
