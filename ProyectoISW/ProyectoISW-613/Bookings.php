<?php
session_start();
include('common/conexion.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_rol'];

// Obtener bookings según el rol
if ($user_role === 'CHOFER') {
    // Bookings donde el usuario es conductor
    $sql = "
        SELECT rv.*, rd.lugar_salida as origen, rd.lugar_llegada as destino, rd.dia_semana as fecha_viaje, rd.hora as hora_salida, 
               u.nombre as pasajero_nombre, u.apellido as pasajero_apellido,
               v.marca as vehiculo_marca, v.modelo as vehiculo_modelo, v.placa as vehiculo_placa,
               rd.costo as costo_ride
        FROM reservas rv
        JOIN rides rd ON rv.id_ride = rd.id
        JOIN usuarios u ON rv.id_pasajero = u.id
        JOIN vehiculos v ON rd.id_vehiculo = v.id
        WHERE rd.id_chofer = ?
        ORDER BY 
            CASE rd.dia_semana 
                WHEN 'LUNES' THEN 1
                WHEN 'MARTES' THEN 2
                WHEN 'MIERCOLES' THEN 3
                WHEN 'JUEVES' THEN 4
                WHEN 'VIERNES' THEN 5
                WHEN 'SABADO' THEN 6
                WHEN 'DOMINGO' THEN 7
            END DESC, 
            rd.hora DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Bookings donde el usuario es pasajero
    $sql = "
        SELECT rv.*, rd.lugar_salida as origen, rd.lugar_llegada as destino, rd.dia_semana as fecha_viaje, rd.hora as hora_salida,
               u.nombre as conductor_nombre, u.apellido as conductor_apellido,
               v.marca as vehiculo_marca, v.modelo as vehiculo_modelo, v.placa as vehiculo_placa,
               rd.costo as costo_ride
        FROM reservas rv
        JOIN rides rd ON rv.id_ride = rd.id
        JOIN usuarios u ON rd.id_chofer = u.id
        JOIN vehiculos v ON rd.id_vehiculo = v.id
        WHERE rv.id_pasajero = ?
        ORDER BY 
            CASE rd.dia_semana 
                WHEN 'LUNES' THEN 1
                WHEN 'MARTES' THEN 2
                WHEN 'MIERCOLES' THEN 3
                WHEN 'JUEVES' THEN 4
                WHEN 'VIERNES' THEN 5
                WHEN 'SABADO' THEN 6
                WHEN 'DOMINGO' THEN 7
            END DESC, 
            rd.hora DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Foto desde sesión con default
$foto_usuario = !empty($_SESSION['user_foto']) ? $_SESSION['user_foto'] : 'img/logo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Aventones — Bookings</title>
  <link rel="stylesheet" href="css/bookings.css" />
  <script src="js/bookings.js" defer></script>
</head>
<body class="bookings-page">
  <!-- ===== Header ===== -->
  <header class="bookings-topbar">
    <div class="bookings-brand">
      <img class="bookings-logo" src="img/logo.png" alt="Aventones logo">
      <h1>Bookings</h1>
    </div>

    <nav class="main-nav">
      <ul class="nav-links">
        <?php if ($user_role === 'CHOFER'): ?>
          <li><a href="vehicles.php">Home</a></li>
          <li><a href="rides.php">Rides</a></li>
          <li><a href="bookings.php" class="active">Bookings</a></li>
        <?php else: ?>
          <li><a href="index.php">Home</a></li>
          <li><a href="bookings.php" class="active">Bookings</a></li>
        <?php endif; ?>
      </ul>
    </nav>

    <div class="right-box">
      <?php if ($user_role === 'PASAJERO'): ?>
        <a href="index.php" class="btn neon">Find Rides</a>
      <?php else: ?>
        <a href="CreateRide.php" class="btn neon">New Ride</a>
      <?php endif; ?>
      
      <div class="profile-menu">
        <?php
        if (!file_exists($foto_usuario)) {
            $foto_usuario = 'img/logo.png';
        }
        ?>
        
        <img src="<?php echo $foto_usuario; ?>" alt="User Icon" class="avatar" id="avatarBtn" 
             onerror="this.src='img/logo.png'" />
        <ul class="dropdown" id="profileDropdown">
            <li><a href="Configurations.php">Configuration</a></li>
            <li><a href="actions/logout.php" class="logout-btn">Logout</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- ===== Contenido ===== -->
  <main class="bookings-content">
    <?php if (empty($bookings)): ?>
      <article class="bookings-empty" id="emptyState">
        <div class="bookings-illu" aria-hidden="true"></div>
        <h3>No bookings yet</h3>
        <p>
          <?php if ($user_role === 'CHOFER'): ?>
            When passengers book your rides, they will appear here.
          <?php else: ?>
            When you book rides, they will appear here.
          <?php endif; ?>
        </p>
        <?php if ($user_role === 'PASAJERO'): ?>
          <a href="index.php" class="btn neon ghost">Find Rides</a>
        <?php else: ?>
          <a href="CreateRide.php" class="btn neon ghost">Create Ride</a>
        <?php endif; ?>
      </article>
    <?php else: ?>
      <section class="bookings-grid" id="bookingsGrid">
        <?php foreach ($bookings as $booking): ?>
          <article class="booking-card" data-id="<?php echo $booking['id']; ?>">
            <span class="booking-accent"></span>

            <div class="booking-header">
              <div class="booking-route">
                <h3><?php echo htmlspecialchars($booking['origen']); ?> → <?php echo htmlspecialchars($booking['destino']); ?></h3>
                <div class="booking-time">
                  <i class="fas fa-clock"></i>
                  <?php echo substr($booking['hora_salida'], 0, 5); ?> • 
                  <?php echo htmlspecialchars($booking['fecha_viaje']); ?>
                </div>
              </div>
              <div class="booking-price">
                ₡<?php echo number_format($booking['costo_ride'] * $booking['cantidad_espacios'], 2); ?>
              </div>
            </div>

            <div class="booking-status status-<?php echo strtolower($booking['estado']); ?>">
              <?php echo $booking['estado']; ?>
            </div>

            <ul class="booking-details">
              <?php if ($user_role === 'CHOFER'): ?>
                <li>
                  <strong>Passenger:</strong>
                  <span><?php echo htmlspecialchars($booking['pasajero_nombre'] . ' ' . $booking['pasajero_apellido']); ?></span>
                </li>
              <?php else: ?>
                <li>
                  <strong>Driver:</strong>
                  <span><?php echo htmlspecialchars($booking['conductor_nombre'] . ' ' . $booking['conductor_apellido']); ?></span>
                </li>
              <?php endif; ?>
              
              <li>
                <strong>Vehicle:</strong>
                <span><?php echo htmlspecialchars($booking['vehiculo_marca'] . ' ' . $booking['vehiculo_modelo'] . ' (' . $booking['vehiculo_placa'] . ')'); ?></span>
              </li>
              <li>
                <strong>Spaces:</strong>
                <span><?php echo $booking['cantidad_espacios']; ?></span>
              </li>
            </ul>

            <footer class="booking-actions">
              <?php if ($user_role === 'CHOFER' && $booking['estado'] === 'PENDIENTE'): ?>
                <button class="btn success" data-accept="<?php echo $booking['id']; ?>">
                  <i class="fas fa-check"></i> Accept
                </button>
                <button class="btn danger" data-reject="<?php echo $booking['id']; ?>">
                  <i class="fas fa-times"></i> Reject
                </button>
              <?php endif; ?>

              <?php if ($booking['estado'] !== 'CANCELADA' && $booking['estado'] !== 'RECHAZADA' && $booking['estado'] !== 'COMPLETADA'): ?>
                <button class="btn outline" data-cancel="<?php echo $booking['id']; ?>">
                  <i class="fas fa-ban"></i> Cancel
                </button>
              <?php endif; ?>
            </footer>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>

  <!-- ===== Footer ===== -->
  <footer class="bookings-footer">
    <div class="footer-links">
      <?php if ($user_role === 'CHOFER'): ?>
        <a href="vehicles.php">Home</a> |
        <a href="rides.php">Rides</a> |
        <a href="bookings.php">Bookings</a>
      <?php else: ?>
        <a href="index.php">Home</a> |
        <a href="bookings.php">Bookings</a>
      <?php endif; ?>
    </div>
    <p>&copy; Aventones.com</p>
  </footer>

  <!-- ===== Modal de Confirmación ===== -->
  <dialog id="confirmModal">
    <div class="modal-content">
      <h3 id="modalTitle">Confirm Action</h3>
      <p id="modalMessage">Are you sure you want to perform this action?</p>
      <div class="modal-actions">
        <button class="btn outline" id="cancelAction">Cancel</button>
        <button class="btn neon" id="confirmAction">Confirm</button>
      </div>
    </div>
  </dialog>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
<?php 
?>