<?php
session_start();
include "conexion.php";
if (!isset($_SESSION['id']) || $_SESSION['rol'] != 'mesero') {
    header("Location: login.php");
    exit();
}

// Obtener mesas y productos
$mesas = range(1, 10); // Ejemplo mesas 1 a 10

$result = $conn->query("SELECT * FROM productos");
$productos = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tomar Pedido - Mesero</title>
<style>
    body { font-family: Arial, sans-serif; }
    #menu, #pedido { float: left; width: 45%; margin: 10px; }
    #pedido ul { list-style: none; padding: 0; }
    #pedido ul li { margin-bottom: 5px; }
</style>
<script>
let pedido = [];
function agregarProducto(id, nombre, precio) {
    let existente = pedido.find(p => p.id === id);
    if (existente) {
        existente.cantidad++;
    } else {
        pedido.push({id, nombre, precio, cantidad: 1});
    }
    mostrarPedido();
}

function eliminarProducto(id) {
    pedido = pedido.filter(p => p.id !== id);
    mostrarPedido();
}

function mostrarPedido() {
    let lista = document.getElementById("listaPedido");
    lista.innerHTML = "";
    let total = 0;
    pedido.forEach(item => {
        let subtotal = item.precio * item.cantidad;
        total += subtotal;
        let li = document.createElement("li");
        li.textContent = `${item.nombre} - Cantidad: ${item.cantidad} - $${subtotal.toFixed(2)}`;
        let btn = document.createElement("button");
        btn.textContent = "Eliminar";
        btn.onclick = () => eliminarProducto(item.id);
        li.appendChild(btn);
        lista.appendChild(li);
    });
    document.getElementById("total").textContent = "Total: $" + total.toFixed(2);
    document.getElementById("pedidoJSON").value = JSON.stringify(pedido);
}

function enviarPedido() {
    if (pedido.length === 0) {
        alert("El pedido está vacío");
        return;
    }
    let mesa = document.getElementById("mesa").value;
    let personas = document.getElementById("personas").value;
    if (!mesa || !personas) {
        alert("Selecciona mesa y número de personas");
        return;
    }

    // Enviar por AJAX
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "guardar_pedido.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
        if (this.responseText == "ok") {
            alert("Pedido guardado correctamente");
            pedido = [];
            mostrarPedido();
            document.getElementById("mesa").value = "";
            document.getElementById("personas").value = "";
        } else {
            alert("Error al guardar pedido");
        }
    };
    xhr.send("mesa=" + mesa + "&personas=" + personas + "&pedido=" + encodeURIComponent(document.getElementById("pedidoJSON").value));
}
</script>
</head>
<body>

<h2>Tomar Pedido</h2>

<label>Mesa:
    <select id="mesa" required>
        <option value="">Selecciona mesa</option>
        <?php foreach($mesas as $m): ?>
            <option value="<?= $m ?>"><?= $m ?></option>
        <?php endforeach; ?>
    </select>
</label>
<br><br>
<label>Número de personas: <input type="number" id="personas" min="1" max="20" required></label>

<div id="menu">
    <h3>Menú</h3>
    <?php foreach ($productos as $p): ?>
        <div>
            <b><?= htmlspecialchars($p['nombre']) ?></b> - $<?= number_format($p['precio'], 2) ?>
            <button type="button" onclick="agregarProducto(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>', <?= $p['precio'] ?>)">Agregar</button>
        </div>
    <?php endforeach; ?>
</div>

<div id="pedido">
    <h3>Pedido</h3>
    <ul id="listaPedido"></ul>
    <p id="total">Total: $0.00</p>
    <input type="hidden" id="pedidoJSON" name="pedido">
    <button onclick="enviarPedido()">Guardar Pedido</button>
</div>

</body>
</html>
