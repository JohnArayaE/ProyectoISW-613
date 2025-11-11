<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registro — Aventones</title>
  <link rel="stylesheet" href="css/registration.css?v=2">
  <script src="js/registration.js" defer></script>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-logo">
        <img src="img/logo.png" alt="logo_principal">
      </div>

      <h1 class="login-title">AVENTONES</h1>

      <!-- Form con estructura para 2 columnas -->
      <form action="actions/insertUser.php" method="post" enctype="multipart/form-data" autocomplete="off" class="form">
        <div class="form-grid">

          <!-- Fila 1 -->
          <div class="field">
            <label for="nombre">First Name</label>
            <input type="text" id="nombre" name="nombre" required>
          </div>

          <div class="field">
            <label for="apellido">Last Name</label>
            <input type="text" id="apellido" name="apellido" required>
          </div>

          <!-- Fila 2 -->
          <div class="field">
            <label for="fecha_nacimiento">Birth</label>
            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required>
          </div>

          <div class="field">
            <label for="cedula">ID Number</label>
            <input type="text" id="cedula" name="cedula" required>
          </div>

          <!-- Email (a lo ancho) -->
          <div class="field span-2">
            <label for="correo">Email</label>
            <input type="email" id="correo" name="correo" required>
          </div>

          <!-- Passwords -->
          <div class="field">
            <label for="contrasena">Password</label>
            <input type="password" id="contrasena" name="contrasena" required>
          </div>

          <div class="field">
            <label for="contrasena_repetir">Repeat Password</label>
            <input type="password" id="contrasena_repetir" name="contrasena_repetir" required>
          </div>

          <!-- Teléfono (a lo ancho) -->
          <div class="field span-2">
            <label for="telefono">Phone Number</label>
            <input type="tel" id="telefono" name="telefono" required>
          </div>

          <!-- Foto (a lo ancho) -->
          <div class="photo-section span-2">
            <label for="photo">Profile Picture</label>
            <input type="file" id="photo" name="photo" accept="image/*" capture="user" />
            <div class="preview">
              <img id="photoPreview" src="" alt="Preview" />
            </div>
          </div>

          <!-- Rol oculto -->
          <input type="hidden" name="rol" value="PASAJERO">

          <!-- Botón -->
          <div class="actions span-2">
            <button type="submit">Sign Up</button>
          </div>

      <div class="links-row span-2">
    <p class="login-link">
        Already a user? <a href="Login.php">Login here</a>
    </p>
    <p class="login-link">
        Register as driver? <a href="RegisterDriver.php">Click here</a>
    </p>
</div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
