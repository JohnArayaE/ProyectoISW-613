<?php
session_start();
include('common/conexion.php');

// Verificar si el usuario está logueado y es chofer
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
    header('Location: Login.php');
    exit();
}

// Consultar rides del usuario actual - SOLO RIDES ACTIVOS
$id_chofer = $_SESSION['user_id'];
$rides = [];

try {
    if (!$conn) {
        throw new Exception("No hay conexión a la base de datos");
    }
    
    // CONSULTA CORREGIDA - sin campo fecha
    $sql = "SELECT r.*, v.marca, v.modelo, v.placa 
            FROM rides r 
            JOIN vehiculos v ON r.id_vehiculo = v.id 
            WHERE r.id_chofer = ? AND r.estado = 'ACTIVO'
            ORDER BY 
                CASE r.dia_semana 
                    WHEN 'LUNES' THEN 1
                    WHEN 'MARTES' THEN 2
                    WHEN 'MIERCOLES' THEN 3
                    WHEN 'JUEVES' THEN 4
                    WHEN 'VIERNES' THEN 5
                    WHEN 'SABADO' THEN 6
                    WHEN 'DOMINGO' THEN 7
                END, 
                r.hora ASC";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_chofer);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $rides[] = $row;
        }
        
        $stmt->close();
    } else {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Error al cargar rides: " . $e->getMessage());
}

// Foto desde sesión con default
$foto_usuario = !empty($_SESSION['user_foto']) ? $_SESSION['user_foto'] : 'img/logo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Aventones — My Rides</title>
  <link rel="stylesheet" href="css/rides.css" />
  <script src="js/rides.js" defer></script>
</head>
<body class="rides-page">
  <!-- ===== Header ===== -->
  <header class="rides-topbar">
    <div class="rides-brand">
      <img class="rides-logo" src="img/logo.png" alt="Aventones logo">
      <h1>My Rides</h1>
    </div>

    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="vehicles.php">Home</a></li>
        <li><a href="rides.php" class="active" data-role-only="driver">Rides</a></li>
        <li><a href="bookings.php">Bookings</a></li>
      </ul>
    </nav>

    <div class="right-box">
      <a href="CreateRide.php" class="btn neon">New Ride</a>
      
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
            <li><a href="configuration.php">Configuration</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- ===== Mensajes del Sistema ===== -->
  <?php 
  // Manejar mensajes de éxito/error desde URL
  if (isset($_GET['success'])) {
      $success_messages = [
          'ride_created' => 'Ride created successfully!',
          'rides_created' => 'Rides created successfully!',
          'rides_partially_created' => 'Some rides were created successfully!',
          'ride_updated' => 'Ride updated successfully!',
          'ride_deleted' => 'Ride deleted successfully!'
      ];
      $message = $success_messages[$_GET['success']] ?? 'Operation completed successfully!';
      $message_type = 'success';
  } elseif (isset($_GET['error'])) {
      $error_messages = [
          'missing_fields' => 'Please fill in all required fields',
          'invalid_vehicle' => 'Invalid vehicle selected',
          'database_error' => 'Error saving ride. Please try again.',
          'ride_exists' => 'A similar ride already exists',
          'invalid_time' => 'Invalid time selected',
          'no_days_selected' => 'Please select at least one day',
          'invalid_seats' => 'Invalid number of seats',
          'exceeds_capacity' => 'Number of seats exceeds vehicle capacity',
          'past_date' => 'Date cannot be in the past',
          'ride_creation_failed' => 'Failed to create ride. Please try again.',
          'invalid_id' => 'Invalid ride ID',
          'ride_not_found' => 'Ride not found',
          'delete_failed' => 'Failed to delete ride'
      ];
      $message = $error_messages[$_GET['error']] ?? 'An error occurred!';
      $message_type = 'error';
  }
  ?>

  <?php if (isset($message)): ?>
    <div class="system-message <?php echo $message_type; ?>">
      <?php echo $message; ?>
      <?php if (isset($_GET['created']) && isset($_GET['failed'])): ?>
        <br><small>Created: <?php echo $_GET['created']; ?>, Failed: <?php echo $_GET['failed']; ?></small>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ===== Contenido ===== -->
  <main class="rides-content">
    <?php if (empty($rides)): ?>
      <article class="rides-empty" id="emptyState">
        <div class="rides-illu" aria-hidden="true"></div>
        <h3>No rides yet</h3>
        <p>Create your first ride to start offering transportation services.</p>
        <a href="CreateRide.php" class="btn neon ghost">Create ride</a>
      </article>
    <?php else: ?>
      <section class="rides-grid" id="ridesGrid">
        <?php foreach ($rides as $ride): ?>
          <article class="ride-card" data-id="<?php echo $ride['id']; ?>">
            <span class="ride-accent"></span>

            <div class="ride-header">
              <h3 class="ride-name"><?php echo htmlspecialchars($ride['nombre_ride']); ?></h3>
              <span class="ride-cost">$<?php echo number_format($ride['costo'], 2); ?></span>
            </div>

            <div class="ride-route">
              <div class="route-item">
                <span class="route-dot start"></span>
                <span class="route-text"><?php echo htmlspecialchars($ride['lugar_salida']); ?></span>
              </div>
              <div class="route-item">
                <span class="route-dot end"></span>
                <span class="route-text"><?php echo htmlspecialchars($ride['lugar_llegada']); ?></span>
              </div>
            </div>

            <div class="ride-details">
              <div class="detail-item">
                <span class="detail-label">Day</span>
                <span class="detail-value"><?php echo htmlspecialchars($ride['dia_semana']); ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Time</span>
                <span class="detail-value"><?php echo date('g:i A', strtotime($ride['hora'])); ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Seats</span>
                <span class="detail-value"><?php echo $ride['espacios_disponibles']; ?> / <?php echo $ride['espacios_totales']; ?> available</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Vehicle</span>
                <span class="detail-value"><?php echo htmlspecialchars($ride['marca'] . ' ' . $ride['modelo']); ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Status</span>
                <span class="detail-value <?php echo strtolower($ride['estado']); ?>"><?php echo $ride['estado']; ?></span>
              </div>
            </div>

            <footer class="ride-actions">
              <a href="EditRide.php?id=<?php echo $ride['id']; ?>" class="btn outline">Edit</a>
              <button class="btn danger" data-delete="<?php echo $ride['id']; ?>">Delete</button>
            </footer>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>

  <!-- ===== Footer ===== -->
  <footer class="rides-footer">
    <div class="footer-links">
      <a href="vehicles.php">Home</a> |
      <a href="bookings.php">Bookings</a> |
      <a href="rides.php" data-role-only="driver">Rides</a>
    </div>
    <p>&copy; Aventones.com</p>
  </footer>
</body>
</html>