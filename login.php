<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$conexion = new mysqli("localhost", "root", "Cayetano99", "fastfood_db");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    $stmt = $conexion->prepare("SELECT id, clave, rol FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $clave_bd, $rol);
        $stmt->fetch();

        if ($clave === $clave_bd) {
            $_SESSION['usuario'] = $usuario;
            $_SESSION['id'] = $id;
            $_SESSION['rol'] = $rol;

            if ($rol === 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $mensaje = "Contraseña incorrecta.";
        }
    } else {
        $mensaje = "Usuario no encontrado.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - FastFood</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Arial', sans-serif;
    }

    html, body {
      height: 100%;
      overflow: hidden;
    }

    body {
      background: url('img/portada.jpeg') no-repeat center center;
      background-size: cover;
      position: relative;
    }

    .overlay {
      position: absolute;
      top: 0;
      right: 0;
      width: 60%;
      height: 100%;
      background: rgba(246, 152, 62, 0.9);
      clip-path: polygon(40% 0%, 100% 0%, 100% 100%, 0% 100%);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    form {
      width: 80%;
      max-width: 350px;
      display: flex;
      flex-direction: column;
      gap: 25px;
      color: white;
    }

    h2 {
      text-align: center;
      font-size: 28px;
      color: white;
    }

    .error {
      background: rgba(255, 0, 0, 0.3);
      padding: 10px;
      border-radius: 5px;
      text-align: center;
      color: #fff;
      font-size: 14px;
    }

    .input-group {
      position: relative;
    }

    .input-group::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 8px;
      height: 8px;
      background-color: white;
      border-radius: 50%;
    }

    .input-group input {
      width: 100%;
      padding: 10px 10px 10px 25px;
      background: transparent;
      border: none;
      border-bottom: 1px solid white;
      color: white;
      font-size: 16px;
      outline: none;
    }

    .input-group input::placeholder {
      color: rgba(255, 255, 255, 0.8);
    }

    button {
      padding: 12px;
      background-color: #ffd1a4;
      color: #f6983e;
      border: none;
      border-radius: 20px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.3s ease;
    }

    button:hover {
      background-color: #ffc285;
    }

    .text-center {
      text-align: center;
    }

    a {
      color: white;
      text-decoration: none;
      font-size: 14px;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="overlay">
    <form method="POST">
      <h2>Iniciar sesión</h2>
      <?php if (!empty($mensaje)) echo "<p class='error'>$mensaje</p>"; ?>
      <div class="input-group">
        <input type="text" name="usuario" placeholder="Usuario" required>
      </div>
      <div class="input-group">
        <input type="password" name="clave" placeholder="Contraseña" required>
      </div>
      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
