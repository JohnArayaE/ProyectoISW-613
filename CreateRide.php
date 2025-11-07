<?php
session_start();
include('common/conexion.php');

// Verificar si el usuario está logueado y es chofer
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
    header('Location: Login.php');
    exit();
}

// Obtener vehículos del usuario para el select
$id_chofer = $_SESSION['user_id'];
$vehiculos = [];

try {
    if ($conn) {
        $sql = "SELECT id, marca, modelo, placa, capacidad FROM vehiculos WHERE id_chofer = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $id_chofer);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $vehiculos[] = $row;
            }
            
            $stmt->close();
        }
    }
} catch (Exception $e) {
    error_log("Error al cargar vehículos: " . $e->getMessage());
}

// Foto desde sesión
$foto_usuario = isset($_SESSION['user_foto']) ? $_SESSION['user_foto'] : 'img/logo.png';
if (!file_exists($foto_usuario)) {
    $foto_usuario = 'img/logo.png';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Aventones — Create Ride</title>
  <link rel="stylesheet" href="css/CreateRide.css" />
  <script src="js/CreateRide.js" defer></script>
</head>
<body class="create-ride-page">
  <!-- ===== Header ===== -->
  <header class="ride-topbar">
    <!-- Izquierda: logo + título -->
    <div class="ride-brand">
      <img class="ride-logo" src="img/logo.png" alt="Aventones logo">
      <h1>Create New Ride</h1>
    </div>

    <!-- Centro: navegación -->
    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="vehicles.php">Home</a></li>
        <li><a href="rides.php" class="active">Rides</a></li>
        <li><a href="bookings.php">Bookings</a></li>
      </ul>
    </nav>

    <!-- Derecha: botón + avatar -->
    <div class="right-box">
      <a href="rides.php" class="btn outline">Back to Rides</a>
      
      <div class="profile-menu">
        <img src="<?php echo $foto_usuario; ?>" alt="User Icon" class="avatar" id="avatarBtn" 
             onerror="this.src='img/logo.png'" />
        <ul class="dropdown" id="profileDropdown">
          <li><a href="actions/logout.php" class="logout-btn">Logout</a></li>
          <li><a href="configuration.php">Configuration</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- ===== Contenido Principal ===== -->
  <main class="create-ride-main">
    <div class="form-hero simple">
      <div class="hero-content">
        <h1>Create Your Ride</h1>
        <p>Set up your ride details to start offering transportation services</p>
      </div>
    </div>

    <div class="form-container">
      <!-- Mensajes del cliente (JavaScript) -->
      <div id="clientMessage"></div>

      <form id="rideForm" method="post" action="actions/RidesAc.php" class="ride-form">
        <input type="hidden" name="action" value="save">
        
        <!-- Sección 1: Información Básica -->
        <div class="form-section card">
          <div class="section-header">
            <div class="section-number">1</div>
            <h3>Basic Information</h3>
          </div>
          
          <div class="form-grid">
            <div class="field-group">
              <div class="field">
                <label for="nombre_ride" class="field-label">
                  <span class="label-text">Ride Name *</span>
                </label>
                <input type="text" id="nombre_ride" name="nombre_ride" placeholder="Morning Commute" required maxlength="100">
                <span class="field-help">Give your ride a descriptive name</span>
              </div>

              <div class="field">
                <label for="id_vehiculo" class="field-label">
                  <span class="label-text">Vehicle *</span>
                </label>
                <select id="id_vehiculo" name="id_vehiculo" required>
                  <option value="">Select your vehicle</option>
                  <?php foreach ($vehiculos as $vehiculo): ?>
                    <option value="<?php echo $vehiculo['id']; ?>" data-capacity="<?php echo $vehiculo['capacidad']; ?>">
                      <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo'] . ' - ' . $vehiculo['placa'] . ' (' . $vehiculo['capacidad'] . ' seats)'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <span class="field-help">Choose the vehicle for this ride</span>
              </div>
            </div>

            <div class="field-group">
              <div class="field">
                <label for="lugar_salida" class="field-label">
                  <span class="label-text">Departure Location *</span>
                </label>
                <input type="text" id="lugar_salida" name="lugar_salida" placeholder="Central Station" required maxlength="255">
                <span class="field-help">Where the ride starts</span>
              </div>

              <div class="field">
                <label for="lugar_llegada" class="field-label">
                  <span class="label-text">Arrival Location *</span>
                </label>
                <input type="text" id="lugar_llegada" name="lugar_llegada" placeholder="Downtown Office" required maxlength="255">
                <span class="field-help">Where the ride ends</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sección 2: Horario y Precio -->
        <div class="form-section card">
          <div class="section-header">
            <div class="section-number">2</div>
            <h3>Schedule & Pricing</h3>
          </div>

          <div class="form-grid">
            <div class="field-group">
              <div class="field">
                <label for="hora" class="field-label">
                  <span class="label-text">Departure Time *</span>
                </label>
                <input type="time" id="hora" name="hora" required>
                <span class="field-help">Time when the ride departs</span>
              </div>

              <div class="field">
                <label class="field-label">
                  <span class="label-text">Days of Week *</span>
                </label>
                <div class="days-selector">
                  <div class="days-grid">
                    <label class="day-checkbox">
                      <input type="checkbox" name="dias_semana[]" value="LUNES" class="day-input">
                      <span class="day-box">
                        <span class="day-name">Lun</span>
                      </span>
                    </label>
                    <label class="day-checkbox">
                      <input type="checkbox" name="dias_semana[]" value="MARTES" class="day-input">
                      <span class="day-box">
                        <span class="day-name">Mar</span>
                      </span>
                    </label>
                    <label class="day-checkbox">
                      <input type="checkbox" name="dias_semana[]" value="MIERCOLES" class="day-input">
                      <span class="day-box">
                        <span class="day-name">Mier</span>
                      </span>
                    </label>
                    <label class="day-checkbox">
                      <input type="checkbox" name="dias_semana[]" value="JUEVES" class="day-input">
                      <span class="day-box">
                        <span class="day-name">Jue</span>
                      </span>
                    </label>
                    <label class="day-checkbox">
                      <input type="checkbox" name="dias_semana[]" value="VIERNES" class="day-input">
                      <span class="day-box">
                        <span class="day-name">Vier</span>
                      </span>
                    </label>
                    <label class="day-checkbox">
                      <input type="checkbox" name="dias_semana[]" value="SABADO" class="day-input">
                      <span class="day-box">
                        <span class="day-name">Sab</span>
                      </span>
                    </label>
                    <label class="day-checkbox">
                      <input type="checkbox" name="dias_semana[]" value="DOMINGO" class="day-input">
                      <span class="day-box">
                        <span class="day-name">Dom</span>
                      </span>
                    </label>
                  </div>
                </div>
                <span class="field-help">Select one or more days for your ride</span>
              </div>
            </div>

            <div class="field-group-inline">
              <div class="field">
                <label for="espacios_totales" class="field-label">
                  <span class="label-text">Available Seats *</span>
                </label>
                <input type="number" id="espacios_totales" name="espacios_totales" min="1" max="8" placeholder="4" required>
                <span class="field-help">Number of available passenger seats</span>
              </div>

              <div class="field">
                <label for="costo" class="field-label">
                  <span class="label-text">Cost per Seat ($) *</span>
                </label>
                <input type="number" id="costo" name="costo" step="0.01" min="0" placeholder="5.00" required>
                <span class="field-help">Price per passenger seat</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones del Formulario -->
        <div class="form-actions">
          <a href="rides.php" class="btn outline large">
            <span>←</span>
            Cancel
          </a>
          <button type="submit" class="btn neon large" id="submitBtn">
            <span>🚗</span>
            Create Ride
          </button>
        </div>
      </form>
    </div>
  </main>

  <!-- Footer -->
  <footer class="ride-footer">
    <div class="footer-content">
      <div class="footer-links">
        <a href="vehicles.php">Home</a>
        <a href="rides.php">My Rides</a>
        <a href="bookings.php">Bookings</a>
      </div>
      <p>&copy; 2024 Aventones.com - Connecting drivers and passengers</p>
    </div>
  </footer>
</body>
</html>