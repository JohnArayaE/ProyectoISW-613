<?php
session_start();
include('common/conexion.php');

// Verificar si el usuario está logueado y es chofer
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
    header('Location: Login.php');
    exit();
}

// Verificar que se proporcionó un ID de ride
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: rides.php?error=invalid_id');
    exit();
}

$ride_id = intval($_GET['id']);
$id_chofer = $_SESSION['user_id'];

// Obtener datos del ride a editar
$ride_data = null;
try {
    if ($conn) {
        $sql = "SELECT r.*, v.capacidad 
                FROM rides r 
                JOIN vehiculos v ON r.id_vehiculo = v.id 
                WHERE r.id = ? AND v.id_chofer = ? AND r.estado = 'ACTIVO'";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ii", $ride_id, $id_chofer);
            $stmt->execute();
            $result = $stmt->get_result();
            $ride_data = $result->fetch_assoc();
            $stmt->close();
        }
        
        if (!$ride_data) {
            header('Location: rides.php?error=ride_not_found');
            exit();
        }
    }
} catch (Exception $e) {
    error_log("Error al cargar ride: " . $e->getMessage());
    header('Location: rides.php?error=database_error');
    exit();
}

// Obtener vehículos del usuario para el select
$vehiculos = [];
try {
    if ($conn) {
        $sql = "SELECT id, marca, modelo, placa, capacidad FROM vehiculos WHERE id_chofer = ? AND estado = 'activo'";
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
  <title>Aventones — Edit Ride</title>
  <link rel="stylesheet" href="css/CreateRide.css" />
  <script src="js/CreateRide.js" defer></script>
</head>
<body class="create-ride-page">
  <!-- ===== Header ===== -->
  <header class="ride-topbar">
    <!-- Izquierda: logo + título -->
    <div class="ride-brand">
      <img class="ride-logo" src="img/logo.png" alt="Aventones logo">
      <h1>Edit Ride</h1>
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
          <li><a href="Configurations.php">Configuration</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- ===== Contenido Principal ===== -->
  <main class="create-ride-main">
    <div class="form-hero simple">
      <div class="hero-content">
        <h1>Edit Your Ride</h1>
        <p>Update your ride details and schedule</p>
      </div>
    </div>

    <div class="form-container">
      <!-- Mensajes del cliente (JavaScript) -->
      <div id="clientMessage"></div>

      <form id="rideForm" method="post" action="actions/RidesAc.php" class="ride-form">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="ride_id" value="<?php echo $ride_id; ?>">
        
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
                <input type="text" id="nombre_ride" name="nombre_ride" 
                       value="<?php echo htmlspecialchars($ride_data['nombre_ride']); ?>" 
                       placeholder="Morning Commute" required maxlength="100">
                <span class="field-help">Give your ride a descriptive name</span>
              </div>

              <div class="field">
                <label for="id_vehiculo" class="field-label">
                  <span class="label-text">Vehicle *</span>
                </label>
                <select id="id_vehiculo" name="id_vehiculo" required>
                  <option value="">Select your vehicle</option>
                  <?php foreach ($vehiculos as $vehiculo): ?>
                    <option value="<?php echo $vehiculo['id']; ?>" 
                            data-capacity="<?php echo $vehiculo['capacidad']; ?>"
                            <?php echo ($vehiculo['id'] == $ride_data['id_vehiculo']) ? 'selected' : ''; ?>>
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
                <input type="text" id="lugar_salida" name="lugar_salida" 
                       value="<?php echo htmlspecialchars($ride_data['lugar_salida']); ?>" 
                       placeholder="Central Station" required maxlength="255">
                <span class="field-help">Where the ride starts</span>
              </div>

              <div class="field">
                <label for="lugar_llegada" class="field-label">
                  <span class="label-text">Arrival Location *</span>
                </label>
                <input type="text" id="lugar_llegada" name="lugar_llegada" 
                       value="<?php echo htmlspecialchars($ride_data['lugar_llegada']); ?>" 
                       placeholder="Downtown Office" required maxlength="255">
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
                <input type="time" id="hora" name="hora" 
                       value="<?php echo $ride_data['hora']; ?>" 
                       required>
                <span class="field-help">Time when the ride departs</span>
              </div>

              <div class="field days-full-width">
                <label class="field-label">
                  <span class="label-text">Day of Week *</span>
                </label>
                <div class="days-selector">
                  <div class="days-grid">
                    <?php
                    $dias_semana = ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'];
                    $dias_nombres = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    foreach ($dias_semana as $index => $dia): 
                    ?>
                    <label class="day-checkbox">
                      <input type="radio" name="dia_semana" value="<?php echo $dia; ?>" class="day-input"
                             <?php echo ($dia == $ride_data['dia_semana']) ? 'checked' : ''; ?> required>
                      <span class="day-box">
                        <span class="day-name"><?php echo $dias_nombres[$index]; ?></span>
                      </span>
                    </label>
                    <?php endforeach; ?>
                  </div>
                </div>
                <span class="field-help">Select the day for your ride</span>
              </div>
            </div>

            <div class="field-group-inline">
              <div class="field">
                <label for="espacios_totales" class="field-label">
                  <span class="label-text">Available Seats *</span>
                </label>
                <input type="number" id="espacios_totales" name="espacios_totales" 
                       value="<?php echo $ride_data['espacios_totales']; ?>" 
                       min="1" max="8" placeholder="4" required>
                <span class="field-help">Number of available passenger seats</span>
              </div>

              <div class="field">
                <label for="costo" class="field-label">
                  <span class="label-text">Cost per Seat ($) *</span>
                </label>
                <input type="number" id="costo" name="costo" step="0.01" min="0" 
                       value="<?php echo number_format($ride_data['costo'], 2); ?>" 
                       placeholder="5.00" required>
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
            <span>✏️</span>
            Update Ride
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
        <a href="rides.php">Rides</a>
        <a href="bookings.php">Bookings</a>
      </div>
      <p>&copy; 2024 Aventones.com - Connecting drivers and passengers</p>
    </div>
  </footer>

  <script>
  // Script específico para EditRide
  document.addEventListener('DOMContentLoaded', function() {
      // Manejar mensajes de error/éxito específicos para edición
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('error')) {
          const errorMessages = {
              'missing_fields': 'Please fill in all required fields',
              'invalid_vehicle': 'Invalid vehicle selected',
              'database_error': 'Error updating ride. Please try again.',
              'ride_exists': 'A similar ride already exists',
              'invalid_time': 'Invalid time selected',
              'invalid_seats': 'Invalid number of seats',
              'exceeds_capacity': 'Number of seats exceeds vehicle capacity',
              'invalid_cost': 'Invalid cost amount'
          };
          
          const message = errorMessages[urlParams.get('error')];
          if (message) {
              const clientMessage = document.getElementById('clientMessage');
              clientMessage.innerHTML = `
                  <div class="alert error">
                      ${message}
                  </div>
              `;
          }
      }

      // Inicializar capacidad del vehículo
      const vehicleSelect = document.getElementById('id_vehiculo');
      const seatsInput = document.getElementById('espacios_totales');

      if (vehicleSelect && seatsInput) {
          vehicleSelect.addEventListener('change', function() {
              const selectedOption = this.options[this.selectedIndex];
              if (selectedOption && selectedOption.value) {
                  const capacity = parseInt(selectedOption.getAttribute('data-capacity'));
                  seatsInput.max = capacity - 1;
                  seatsInput.placeholder = `Max ${capacity - 1}`;
                  
                  if (parseInt(seatsInput.value) > capacity - 1) {
                      seatsInput.value = capacity - 1;
                  }
              }
          });
      }
  });
  </script>
</body>
</html>