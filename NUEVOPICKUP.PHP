<?php
// Configuración conexión
$host = "localhost";
$usuario = "root";
$contrasena = "Cayetano99";
$basedatos = "fastfood_db";

$conn = new mysqli($host, $usuario, $contrasena, $basedatos);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener productos para mostrar en el formulario
$productos = [];
$result = $conn->query("SELECT id, nombre, precio FROM productos");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Variables para mensaje y total
$mensaje = "";
$totalCuenta = 0.0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $repartidor = trim($_POST["repartidor"] ?? '');
    $cantidades = $_POST["cantidad"] ?? [];

    if (empty($repartidor)) {
        $mensaje = "Por favor, ingresa el nombre del repartidor.";
    } elseif (empty(array_filter($cantidades, fn($q) => $q > 0))) {
        $mensaje = "Selecciona al menos un producto con cantidad mayor a 0.";
    } else {
        // Iniciar transacción para integridad
        $conn->begin_transaction();

        try {
            // Insertar pedido general
            $stmt = $conn->prepare("INSERT INTO pedidos_pickup (repartidor, fecha_pedido) VALUES (?, NOW())");
            $stmt->bind_param("s", $repartidor);
            $stmt->execute();
            $pedido_id = $stmt->insert_id;
            $stmt->close();

            // Preparar inserción detalles
            $stmt_detalle = $conn->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");

            foreach ($cantidades as $producto_id => $cantidad) {
                $cantidad = (int)$cantidad;
                if ($cantidad > 0) {
                    // Buscar precio actual del producto para el detalle
                    $precio = 0;
                    foreach ($productos as $p) {
                        if ($p['id'] == $producto_id) {
                            $precio = (float)$p['precio'];
                            break;
                        }
                    }
                    $stmt_detalle->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $precio);
                    $stmt_detalle->execute();

                    // Sumar al total
                    $totalCuenta += $precio * $cantidad;
                }
            }
            $stmt_detalle->close();

            $conn->commit();
            $mensaje = "Pedido creado correctamente para el repartidor: " . htmlspecialchars($repartidor);
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al crear el pedido: " . $e->getMessage();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Nuevo Pedido Pick Up</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background:#f9f9f9; }
        h1 { margin-bottom: 20px; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        table { border-collapse: collapse; width: 60%; margin-top: 10px; background:#fff; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background-color: #f2f2f2; }
        input[type="number"] { width: 60px; padding: 5px; }
        button { margin-top: 15px; padding: 10px 20px; font-size: 16px; cursor: pointer; }
        .mensaje { margin-top: 20px; font-weight: bold; color: green; }
        .total-cuenta { margin-top: 15px; font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>

<h1>Crear nuevo pedido Pick Up</h1>

<?php if ($mensaje): ?>
    <p class="mensaje"><?php echo $mensaje; ?></p>
<?php endif; ?>

<form method="POST" action="NUEVOPICKUP.PHP">
    <label for="repartidor">Nombre del repartidor:</label>
    <input type="text" id="repartidor" name="repartidor" required value="<?php echo htmlspecialchars($_POST['repartidor'] ?? ''); ?>">

    <label>Selecciona productos y cantidades:</label>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio unitario</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $prod):
                $id = $prod['id'];
                $cantidad_val = (int)($_POST['cantidad'][$id] ?? 0);
                $subtotal = $cantidad_val * $prod['precio'];
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                    <td>$<?php echo number_format($prod['precio'], 2); ?></td>
                    <td>
                        <input type="number" name="cantidad[<?php echo $id; ?>]" min="0" value="<?php echo $cantidad_val; ?>" oninput="calcularSubtotal(<?php echo $id; ?>)">
                    </td>
                    <td id="subtotal-<?php echo $id; ?>">$<?php echo number_format($subtotal, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <tr><td colspan="4">No hay productos disponibles.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total-cuenta" id="total-cuenta">Total: $<?php echo number_format($totalCuenta, 2); ?></div>

    <button type="submit">Crear Pedido</button>
</form>

<script>
// Script para actualizar subtotales y total en tiempo real
const productos = <?php echo json_encode($productos, JSON_HEX_TAG); ?>;
function calcularSubtotal(id) {
    const inputCantidad = document.querySelector(`input[name="cantidad[${id}]"]`);
    const cantidad = parseInt(inputCantidad.value) || 0;
    const producto = productos.find(p => p.id == id);
    const subtotalElem = document.getElementById(`subtotal-${id}`);
    const subtotal = cantidad * producto.precio;
    subtotalElem.textContent = "$" + subtotal.toFixed(2);

    // Actualizar total
    let total = 0;
    productos.forEach(p => {
        const cant = parseInt(document.querySelector(`input[name="cantidad[${p.id}]"]`).value) || 0;
        total += cant * p.precio;
    });
    document.getElementById('total-cuenta').textContent = "Total: $" + total.toFixed(2);
}
</script>

</body>
</html>
