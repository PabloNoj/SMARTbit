<?php
session_start();

// Verifica que el usuario sea admin
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Conexión a la base de datos
require 'conexion.php'; // define $conn

$mensaje = "";

// Agregar mesero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_mesero'])) {
    $nombre   = trim($_POST['nombre']);
    $usuario  = trim($_POST['usuario']);
    $password = $_POST['password'];

    if ($nombre === "" || $usuario === "" || $password === "") {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $mensaje = "El nombre de usuario ya está registrado.";
        } else {
            // Insertar nuevo mesero con contraseña hasheada
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $rol = "mesero";

            $stmt_insert = $conn->prepare(
                "INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)"
            );
            $stmt_insert->bind_param("ssss", $nombre, $usuario, $password_hash, $rol);
            if ($stmt_insert->execute()) {
                $mensaje = "Mesero agregado correctamente.";
            } else {
                $mensaje = "Error al agregar mesero: " . $conn->error;
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
}

// Obtener meseros
$meseros       = $conn->query("SELECT id, nombre, usuario FROM usuarios WHERE rol = 'mesero'");
$total_meseros = $meseros->num_rows;

// Obtener ventas (pedidos cerrados)
$ventas = $conn->query("
    SELECT p.id, p.fecha, u.nombre AS mesero, p.tipo_pedido
    FROM pedidos p
    JOIN usuarios u ON p.id_usuario = u.id
    WHERE p.estado = 'cerrado'
    ORDER BY p.fecha DESC
");

// Total de ventas
$totales       = $conn->query("SELECT COUNT(*) AS total FROM pedidos WHERE estado = 'cerrado'");
$total_ventas  = $totales->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Admin</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:Segoe UI, Tahoma, Verdana, sans-serif; }
    body {
      display:flex; min-height:100vh;
      background: linear-gradient(rgba(246,152,62,0.9), rgba(246,152,62,0.9)),
                  url('img/portada.jpeg') center/cover no-repeat;
      color:#2c3e50;
    }
    .sidebar {
      width:220px; background:#2c3e50; color:#fff; padding:20px;
      display:flex; flex-direction:column; gap:10px;
    }
    .sidebar h2 { text-align:center; margin-bottom:20px; }
    .sidebar a {
      color:#fff; text-decoration:none; padding:10px;
      background:#34495e; border-radius:6px; text-align:center;
      transition:background .3s;
    }
    .sidebar a:hover { background:#1abc9c; }
    .main { flex:1; padding:30px; overflow-y:auto; }
    .main h2 { margin-bottom:20px; }
    .tarjeta {
      background:#fff; padding:20px; border-radius:10px;
      margin-bottom:20px; box-shadow:0 2px 5px rgba(0,0,0,0.1);
      font-weight:bold;
    }
    table {
      width:100%; background:#fff; border-collapse:collapse;
      margin-bottom:20px;
    }
    th, td {
      padding:10px; text-align:left; border-bottom:1px solid #ddd;
    }
    th { background:#34495e; color:white; }
    tr:hover { background:#f2f2f2; }
    form {
      background:#fff; padding:20px; border-radius:10px;
      margin-bottom:30px; box-shadow:0 2px 5px rgba(0,0,0,0.1);
      max-width:400px;
    }
    form input[type="text"],
    form input[type="password"] {
      width:100%; padding:10px; margin-bottom:15px;
      border:1px solid #ccc; border-radius:6px;
    }
    form button {
      padding:10px 20px; background:#1abc9c; color:#fff;
      border:none; border-radius:6px; cursor:pointer;
      font-weight:bold; transition:background .3s;
    }
    form button:hover { background:#16a085; }
    .mensaje {
      margin-bottom:15px; padding:10px;
      background:#f8d7da; color:#842029; border-radius:6px;
    }
    .mensaje.exito {
      background:#d1e7dd; color:#0f5132;
    }
    .ventas-section { margin-top:40px; }
    .ventas-section h3 { margin-bottom:15px; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Admin</h2>
    <a href="dashboard_admin.php">Meseros</a>
    <a href="logout.php">Cerrar sesión</a>
  </div>

  <div class="main">
    <h2>Panel de Administración</h2>

    <!-- KPIs -->
    <div class="tarjeta">Total de meseros registrados: <?= $total_meseros ?></div>
    <div class="tarjeta">Total de ventas realizadas: <?= $total_ventas ?></div>

    <!-- Mensaje de operación -->
    <?php if ($mensaje): ?>
      <div class="mensaje <?= strpos($mensaje, 'correctamente') !== false ? 'exito' : '' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <!-- Formulario para agregar mesero -->
    <h3>Agregar nuevo mesero</h3>
    <form method="POST">
      <input type="hidden" name="agregar_mesero" value="1" />
      <label for="nombre">Nombre completo:</label>
      <input type="text" name="nombre" id="nombre" required />

      <label for="usuario">Usuario:</label>
      <input type="text" name="usuario" id="usuario" required />

      <label for="password">Contraseña:</label>
      <input type="password" name="password" id="password" required />

      <button type="submit">Agregar Mesero</button>
    </form>

    <!-- Tabla de meseros -->
    <h3>Meseros registrados</h3>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Usuario</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($meseros->num_rows === 0): ?>
          <tr><td colspan="3" style="text-align:center;">No hay meseros registrados.</td></tr>
        <?php else: ?>
          <?php while ($mesero = $meseros->fetch_assoc()): ?>
          <tr>
            <td><?= $mesero['id'] ?></td>
            <td><?= htmlspecialchars($mesero['nombre']) ?></td>
            <td><?= htmlspecialchars($mesero['usuario']) ?></td>
          </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Apartado Ventas -->
    <div class="ventas-section">
      <h3>Ventas (Cuentas Finalizadas)</h3>
      <table>
        <thead>
          <tr>
            <th>ID Venta</th>
            <th>Fecha</th>
            <th>Mesero</th>
            <th>Tipo de pedido</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($ventas->num_rows === 0): ?>
            <tr><td colspan="4" style="text-align:center;">No hay ventas registradas.</td></tr>
          <?php else: ?>
            <?php while ($fila = $ventas->fetch_assoc()): ?>
            <tr>
              <td><?= $fila['id'] ?></td>
              <td><?= $fila['fecha'] ?></td>
              <td><?= htmlspecialchars($fila['mesero']) ?></td>
              <td><?= htmlspecialchars($fila['tipo_pedido']) ?></td>
            </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</body>
</html>
