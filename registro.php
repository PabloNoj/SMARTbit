<?php
$conexion = new mysqli("localhost", "root", "Cayetano99", "fastfood_db");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $usuario = $_POST["usuario"];
    $clave = password_hash($_POST["clave"], PASSWORD_DEFAULT);
    $rol = "mesero"; // Fijamos el rol automáticamente

    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre, $usuario, $clave, $rol);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $mensaje = "Error: El usuario ya existe o ocurrió un problema.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro - FastFood</title>
  <style>
    /* Tu estilo original sin cambios */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Arial', sans-serif; }
    html, body { height: 100%; overflow: hidden; }
    body {
      background: url('img/portada.jpeg') no-repeat center center;
      background-size: cover;
      position: relative;
    }
    .overlay {
      position: absolute;
      top: 0; right: 0;
      width: 60%; height: 100%;
      background: rgba(246, 152, 62, 0.9);
      clip-path: polygon(40% 0%, 100% 0%, 100% 100%, 0% 100%);
      display: flex; align-items: center; justify-content: center;
    }
    form {
      width: 80%; max-width: 400px;
      display: flex; flex-direction: column;
      gap: 25px; color: white;
    }
    h2 { text-align: center; font-size: 28px; color: white; }
    .input-group { position: relative; }
    .input-group::before {
      content: ''; position: absolute;
      left: 0; top: 50%; transform: translateY(-50%);
      width: 8px; height: 8px;
      background-color: white; border-radius: 50%;
    }
    .input-group input {
      width: 100%; padding: 10px 10px 10px 25px;
      background: transparent; border: none;
      border-bottom: 1px solid white; color: white;
      font-size: 16px; outline: none;
    }
    .input-group input::placeholder { color: rgba(255, 255, 255, 0.8); }
    button {
      padding: 12px; background-color: #ffd1a4;
      color: #f6983e; border: none; border-radius: 20px;
      font-size: 16px; font-weight: bold; cursor: pointer;
      transition: 0.3s ease;
    }
    button:hover { background-color: #ffc285; }
    .text-center { text-align: center; }
    a {
      color: white; text-decoration: none; font-size: 14px;
    }
    a:hover { text-decoration: underline; }
    .error {
      text-align: center; color: #fff;
      background: rgba(255, 0, 0, 0.3);
      padding: 10px; border-radius: 5px;
    }
  </style>
</head>
<body>
  <div class="overlay">
    <form method="POST" action="registro.php">
      <h2>Registro de Usuario</h2>
      <?php if (isset($mensaje)) echo "<p class='error'>$mensaje</p>"; ?>
      <div class="input-group">
        <input type="text" name="nombre" placeholder="Nombre completo" required />
      </div>
      <div class="input-group">
        <input type="text" name="usuario" placeholder="Nombre de usuario" required />
      </div>
      <div class="input-group">
        <input type="password" name="clave" placeholder="Contraseña" required />
      </div>
      <!-- Eliminado: selección de rol -->
      <button type="submit">Registrarse</button>
      <div class="text-center">
        <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
      </div>
    </form>
  </div>
</body>
</html>
