<?php
session_start();
include('common/conexion.php');

// Verificar si el usuario está logueado y es chofer
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
    header('Location: Login.php');
    exit();
}

// Consultar vehículos del usuario actual
$user_id = $_SESSION['user_id']; // CORRECCIÓN: Cambiar $id_chofer por $user_id
$vehiculos = []; 

try {
    if (!$conn) {
        throw new Exception("No hay conexión a la base de datos");
    }
    
    // CORRECCIÓN: Cambiar la consulta para usar parámetro preparado correctamente
    $sql = "SELECT * FROM vehiculos WHERE id_chofer = ? AND estado = 'activo'"; // CORRECCIÓN: 'activo' en minúscula
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id); // CORRECCIÓN: Usar $user_id en lugar de $id_chofer
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        
        $stmt->close();
    } else {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Error al cargar vehículos: " . $e->getMessage());
}

// Foto desde sesión con default
$foto_usuario = !empty($_SESSION['user_foto']) ? $_SESSION['user_foto'] : 'img/logo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Aventones — My vehicles</title>
  <link rel="stylesheet" href="css/vehicles.css" />
  <script src="js/vehicles.js" defer></script>
</head>
<body class="veh-page">
  <!-- ===== Header ===== -->
  <header class="veh-topbar">
    <div class="veh-brand">
      <img class="veh-logo" src="img/logo.png" alt="Aventones logo">
      <h1>My vehicles</h1>
    </div>

    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="vehicles.php" class="active">Home</a></li>
        <li><a href="rides.php" data-role-only="driver">Rides</a></li>
        <li><a href="bookings.php">Bookings</a></li>
      </ul>
    </nav>

    <div class="right-box">
      <a href="CreateVehicle.php" class="btn neon">New Vehicle</a>
      
      <div class="profile-menu">
        <?php
        $foto_usuario = isset($_SESSION['user_foto']) ? $_SESSION['user_foto'] : 'img/logo.png';
        
        if (!file_exists($foto_usuario)) {
            $foto_usuario = 'img/logo.png';
        }
        ?>
        
        <img src="<?php echo $foto_usuario; ?>" alt="User Icon" class="avatar" id="avatarBtn" 
             onerror="this.src='img/logo.png'" />
        <ul class="dropdown" id="profileDropdown">
            <li><a href="actions/logout.php" class="logout-btn">Logout</a></li>
            <li><a href="Configurations.php">Configuration</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- ===== Mensajes del Sistema ===== -->
  <?php if (!empty($message)): ?>
    <div class="system-message <?php echo $message_type; ?>">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <!-- ===== Contenido ===== -->
  <main class="veh-content">
    <?php if (empty($vehiculos)): ?>
      <article class="veh-empty" id="emptyState">
        <div class="veh-illu" aria-hidden="true"></div>
        <h3>No vehicles yet</h3>
        <p>Add your first vehicle to start offering rides as a driver.</p>
        <a href="CreateVehicle.php" class="btn neon ghost">Add vehicle</a>
      </article>
    <?php else: ?>
      <section class="veh-grid" id="vehGrid">
        <?php foreach ($vehiculos as $vehiculo): ?>
          <article class="veh-card" data-id="<?php echo $vehiculo['id']; ?>">
            <span class="veh-accent"></span>

            <div class="veh-photo">
              <?php if (!empty($vehiculo['foto_vehiculo']) && file_exists($vehiculo['foto_vehiculo'])): ?>
                <img src="<?php echo $vehiculo['foto_vehiculo']; ?>" alt="Vehicle photo">
              <?php else: ?>
                <img src="img/example.jpg" alt="Vehicle photo">
              <?php endif; ?>
            </div>

            <header class="veh-head">
              <span class="veh-plate"><?php echo htmlspecialchars($vehiculo['placa']); ?></span>
              <span class="veh-seats"><?php echo $vehiculo['capacidad']; ?> seats</span>
            </header>

            <ul class="veh-meta">
              <li><strong>Brand/Model:</strong> <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?></li>
              <li><strong>Year:</strong> <?php echo htmlspecialchars($vehiculo['anio']); ?></li>
              <li><strong>Color:</strong> <?php echo htmlspecialchars($vehiculo['color']); ?></li>
            </ul>

            <footer class="veh-actions">
              <a href="EditVehicle.php?id=<?php echo $vehiculo['id']; ?>" class="btn outline">Edit</a>
              <a href="actions/vehiclesAc.php?action=delete&id=<?php echo $vehiculo['id']; ?>" class="btn danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este vehículo?')">Delete</a>
            </footer>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>

  <!-- ===== Footer ===== -->
  <footer class="veh-footer">
    <div class="footer-links">
      <a href="vehicles.php">Home</a> |
      <a href="rides.php" data-role-only="driver">Rides</a> |
      <a href="bookings.php">Bookings</a> |
    </div>
    <p>&copy; Aventones.com</p>
  </footer>
</body>
</html>