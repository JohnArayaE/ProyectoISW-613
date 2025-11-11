<?php
session_start();
include('common/conexion.php');

// Verificar si el usuario está logueado y es chofer
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
    header('Location: Login.php');
    exit();
}

// Verificar que se proporcionó un ID válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: vehicles.php?error=invalid_id');
    exit();
}

$vehicle_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Obtener datos del vehículo
$vehiculo = null;
try {
    $sql = "SELECT * FROM vehiculos WHERE id = ? AND id_chofer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $vehicle_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: vehicles.php?error=vehicle_not_found');
        exit();
    }
    
    $vehiculo = $result->fetch_assoc();
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error al cargar vehículo: " . $e->getMessage());
    header('Location: vehicles.php?error=database_error');
    exit();
}

// Manejar mensajes de éxito/error
$message = '';
$message_type = '';

if (isset($_GET['error'])) {
    $error_messages = [
        'missing_fields' => 'Please fill in all required fields.',
        'invalid_file_type' => 'Please select a valid image file (PNG, JPG, JPEG).',
        'file_too_large' => 'File size must be less than 5MB.',
        'upload_failed' => 'Error uploading photo. Please try again.',
        'database_error' => 'Database error. Please try again.',
        'invalid_year' => 'Please enter a valid manufacturing year (1980 - ' . (date('Y') + 1) . ').',
        'invalid_seats' => 'Seating capacity must be between 1 and 9.'
    ];
    
    if (isset($error_messages[$_GET['error']])) {
        $message = $error_messages[$_GET['error']];
        $message_type = 'error';
    }
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
  <title>Aventones — Edit Vehicle</title>
  <link rel="stylesheet" href="css/CreateVehicle.css" />
  <script src="js/vehicles.js" defer></script>
  <script src="js/CreateVehicle.js" defer></script>
</head>
<body class="create-vehicle-page">
  <!-- ===== Header ===== -->
  <header class="veh-topbar">
    <div class="veh-brand">
      <img class="veh-logo" src="img/logo.png" alt="Aventones logo">
      <h1>Edit Vehicle</h1>
    </div>

    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="vehicles.php" class="active">My Vehicles</a></li>
        <li><a href="rides.php">Rides</a></li>
        <li><a href="bookings.php">Bookings</a></li>
      </ul>
    </nav>

    <div class="right-box">
      <a href="vehicles.php" class="btn outline">Back to Vehicles</a>
      
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
  <main class="create-vehicle-main">
    <div class="form-hero simple">
      <div class="hero-content">
        <h1>Edit Your Vehicle</h1>
        <p>Update your vehicle details to keep your information current</p>
      </div>
    </div>

    <div class="form-container">
      <!-- Mensajes del servidor -->
      <?php if (!empty($message)): ?>
        <div id="serverMessage" class="alert <?php echo $message_type; ?>">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <!-- Mensajes del cliente (JavaScript) -->
      <div id="clientMessage"></div>

      <form id="vehicleForm" method="post" action="actions/VehiclesAc.php" enctype="multipart/form-data" class="vehicle-form">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="vehicle_id" value="<?php echo $vehiculo['id']; ?>">
        
        <!-- Sección 1: Información Básica -->
        <div class="form-section card">
          <div class="section-header">
            <div class="section-number">1</div>
            <h3>Basic Information</h3>
          </div>
          
          <div class="form-grid">
            <div class="field-group">
              <div class="field">
                <label for="plate" class="field-label">
                  <span class="label-text">License Plate *</span>
                </label>
                <input type="text" id="plate" name="plate" 
                       value="<?php echo htmlspecialchars($vehiculo['placa']); ?>" 
                       placeholder="ABC-123" required maxlength="20">
                <span class="field-help">Enter your vehicle's license plate number</span>
              </div>

              <div class="field">
                <label for="color" class="field-label">
                  <span class="label-text">Color *</span>
                </label>
                <input type="text" id="color" name="color" 
                       value="<?php echo htmlspecialchars($vehiculo['color']); ?>" 
                       placeholder="White" required maxlength="50">
                <span class="field-help">Vehicle exterior color</span>
              </div>
            </div>

            <div class="field-group">
              <div class="field">
                <label for="brand" class="field-label">
                  <span class="label-text">Brand *</span>
                </label>
                <input type="text" id="brand" name="brand" 
                       value="<?php echo htmlspecialchars($vehiculo['marca']); ?>" 
                       placeholder="Toyota" required maxlength="50">
                <span class="field-help">Vehicle manufacturer</span>
              </div>

              <div class="field">
                <label for="model" class="field-label">
                  <span class="label-text">Model *</span>
                </label>
                <input type="text" id="model" name="model" 
                       value="<?php echo htmlspecialchars($vehiculo['modelo']); ?>" 
                       placeholder="Corolla" required maxlength="50">
                <span class="field-help">Vehicle model name</span>
              </div>
            </div>

            <div class="field-group">
              <div class="field">
                <label for="year" class="field-label">
                  <span class="label-text">Manufacturing Year *</span>
                </label>
                <input type="number" id="year" name="year" 
                       value="<?php echo htmlspecialchars($vehiculo['anio']); ?>" 
                       min="1980" max="<?php echo date('Y') + 1; ?>" step="1" placeholder="2020" required>
                <span class="field-help">Year the vehicle was manufactured</span>
              </div>

              <div class="field">
                <label for="seats" class="field-label">
                  <span class="label-text">Seating Capacity *</span>
                </label>
                <input type="number" id="seats" name="seats" 
                       value="<?php echo htmlspecialchars($vehiculo['capacidad']); ?>" 
                       min="1" max="9" placeholder="4" required>
                <span class="field-help">Total seats including driver</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sección 2: Foto del Vehículo -->
        <div class="form-section card">
          <div class="section-header">
            <div class="section-number">2</div>
            <h3>Vehicle Photo</h3>
          </div>

          <div class="photo-section">
            <div class="photo-upload-card">
              <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📷</div>
                <div class="upload-content">
                  <h4>Update Vehicle Photo</h4>
                  <p>Click to browse or drag & drop</p>
                  <span class="file-types">PNG, JPG, JPEG up to 5MB</span>
                </div>
                <input type="file" id="photo" name="photo" accept="image/*" class="file-input">
              </div>
              
              <div class="preview-container" id="previewContainer" <?php echo !empty($vehiculo['foto_vehiculo']) ? 'style="display: block;"' : ''; ?>>
                <div class="preview-card">
                  <?php if (!empty($vehiculo['foto_vehiculo'])): ?>
                    <img id="previewImage" src="<?php echo $vehiculo['foto_vehiculo']; ?>" alt="Vehicle preview">
                  <?php else: ?>
                    <img id="previewImage" alt="Vehicle preview">
                  <?php endif; ?>
                  <div class="preview-overlay">
                    <button type="button" class="btn remove-btn" id="removePhoto">
                      <span>✕</span>
                    </button>
                  </div>
                </div>
                <p class="preview-text">Current photo</p>
              </div>
            </div>
            <?php if (!empty($vehiculo['foto_vehiculo'])): ?>
              <p class="photo-note">Leave empty to keep current photo</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Acciones del Formulario -->
        <div class="form-actions">
          <a href="vehicles.php" class="btn outline large">
            <span>←</span>
            Cancel
          </a>
          <button type="submit" class="btn neon large" id="submitBtn">
            <span>✏️</span>
            Update Vehicle
          </button>
        </div>
      </form>
    </div>
  </main>

  <!-- ===== Footer ===== -->
  <footer class="veh-footer">
    <div class="footer-content">
      <div class="footer-links">
        <a href="index.php">Home</a>
        <a href="vehicles.php">My Vehicles</a>
        <a href="rides.php">Rides</a>
        <a href="bookings.php">Bookings</a>
      </div>
      <p>&copy; 2024 Aventones.com - Connecting drivers and passengers</p>
    </div>
  </footer>
</body>
</html>