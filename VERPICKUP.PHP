<?php
include 'conexion.php';
include 'header.php';

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];

    $precio_query = "SELECT Precio FROM productos WHERE id = $producto_id";
    $precio_result = mysqli_query($conn, $precio_query);
    $precio = mysqli_fetch_assoc($precio_result)['Precio'];

    $insert = "INSERT INTO pickup_detalles (pedido_id, producto_id, cantidad, Precio)
               VALUES ($id, $producto_id, $cantidad, $precio)";
    mysqli_query($conn, $insert);
}

if (isset($_GET['entregar'])) {
    mysqli_query($conn, "UPDATE pickup_pedidos SET estado='entregado' WHERE id=$id");
    header("Location: pickup.php");
    exit;
}

// Mostrar pedido
$pedido = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pickup_pedidos WHERE id = $id"));
?>

<h1>Pedido #<?= $pedido['id'] ?> - <?= $pedido['repartidor'] ?></h1>
<p><strong>Fecha:</strong> <?= $pedido['fecha'] ?> | <strong>Estado:</strong> <?= $pedido['estado'] ?></p>

<?php if ($pedido['estado'] === 'pendiente'): ?>
<form method="POST">
    <label>Producto:</label>
    <select name="producto_id">
        <?php
        $productos = mysqli_query($conn, "SELECT * FROM productos");
        while ($p = mysqli_fetch_assoc($productos)) {
            echo "<option value='{$p['id']}'>{$p['NombreP']} - {$p['Precio']}</option>";
        }
        ?>
    </select>
    <label>Cantidad:</label>
    <input type="number" name="cantidad" value="1" min="1">
    <button type="submit">Agregar producto</button>
</form>

<a href="?id=<?= $id ?>&entregar=1" onclick="return confirm('¿Marcar como entregado?')">✅ Marcar como entregado</a>
<?php endif; ?>

<hr>
<h3>Productos del pedido</h3>
<table border="1">
    <tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Total</th></tr>
    <?php
    $detalles = mysqli_query($conn, "
        SELECT pd.*, pr.NombreP 
        FROM pickup_detalles pd
        JOIN productos pr ON pr.id = pd.producto_id
        WHERE pedido_id = $id
    ");
    $total = 0;
    while ($d = mysqli_fetch_assoc($detalles)) {
        $subtotal = $d['cantidad'] * $d['Precio'];
        $total += $subtotal;
        echo "<tr>
                <td>{$d['NombreP']}</td>
                <td>{$d['cantidad']}</td>
                <td>{$d['Precio']}</td>
                <td>$subtotal</td>
              </tr>";
    }
    ?>
    <tr><td colspan="3"><strong>Total</strong></td><td><strong>$<?= number_format($total, 2) ?></strong></td></tr>
</table>

<?php include 'footer.php'; ?>
