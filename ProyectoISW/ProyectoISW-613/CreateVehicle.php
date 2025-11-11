<?php
session_start();
include('common/conexion.php');

// Verificar si el usuario está logueado y es chofer
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
    header('Location: Login.php');
    exit();
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
  <title>Aventones — Add Vehicle</title>
  <link rel="stylesheet" href="css/CreateVehicle.css" />
  <script src="js/CreateVehicle.js" defer></script>
</head>
<body class="create-vehicle-page">
  <!-- ===== Header ===== -->
  <header class="veh-topbar">
    <!-- Izquierda: logo + título -->
    <div class="veh-brand">
      <img class="veh-logo" src="img/logo.png" alt="Aventones logo">
      <h1>Add New Vehicle</h1>
    </div>

    <!-- Centro: navegación -->
    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="vehicles.php" class="active">My Vehicles</a></li>
        <li><a href="rides.php">Rides</a></li>
        <li><a href="bookings.php">Bookings</a></li>
      </ul>
    </nav>

    <!-- Derecha: botón + avatar -->
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
        <h1>Register Your Vehicle</h1>
        <p>Add your vehicle details to start offering rides and earning with Aventones</p>
      </div>
    </div>

    <div class="form-container">
      <!-- Mensajes del cliente (JavaScript) -->
      <div id="clientMessage"></div>

      <form id="vehicleForm" method="post" action="actions/VehiclesAc.php" enctype="multipart/form-data" class="vehicle-form">
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
                <label for="plate" class="field-label">
                  <span class="label-text">License Plate *</span>
                </label>
                <input type="text" id="plate" name="plate" placeholder="ABC-123" required maxlength="20">
                <span class="field-help">Enter your vehicle's license plate number</span>
              </div>

              <div class="field">
                <label for="color" class="field-label">
                  <span class="label-text">Color *</span>
                </label>
                <input type="text" id="color" name="color" placeholder="White" required maxlength="50">
                <span class="field-help">Vehicle exterior color</span>
              </div>
            </div>

            <div class="field-group">
              <div class="field">
                <label for="brand" class="field-label">
                  <span class="label-text">Brand *</span>
                </label>
                <input type="text" id="brand" name="brand" placeholder="Toyota" required maxlength="50">
                <span class="field-help">Vehicle manufacturer</span>
              </div>

              <div class="field">
                <label for="model" class="field-label">
                  <span class="label-text">Model *</span>
                </label>
                <input type="text" id="model" name="model" placeholder="Corolla" required maxlength="50">
                <span class="field-help">Vehicle model name</span>
              </div>
            </div>

            <div class="field-group">
              <div class="field">
                <label for="year" class="field-label">
                  <span class="label-text">Manufacturing Year *</span>
                </label>
                <input type="number" id="year" name="year" min="1980" max="<?php echo date('Y') + 1; ?>" step="1" placeholder="2020" required>
                <span class="field-help">Year the vehicle was manufactured</span>
              </div>

              <div class="field">
                <label for="seats" class="field-label">
                  <span class="label-text">Seating Capacity *</span>
                </label>
                <input type="number" id="seats" name="seats" min="1" max="9" placeholder="4" required>
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
                  <h4>Upload Vehicle Photo</h4>
                  <p>Click to browse or drag & drop</p>
                  <span class="file-types">PNG, JPG, JPEG up to 5MB</span>
                </div>
                <input type="file" id="photo" name="photo" accept="image/*" class="file-input">
              </div>
              
              <div class="preview-container" id="previewContainer">
                <div class="preview-card">
                  <img id="previewImage" alt="Vehicle preview">
                  <div class="preview-overlay">
                    <button type="button" class="btn remove-btn" id="removePhoto">
                      <span>✕</span>
                    </button>
                  </div>
                </div>
                <p class="preview-text">Photo preview</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones del Formulario -->
        <div class="form-actions">
          <a href="vehicles.php" class="btn outline large">
            <span>←</span>
            Cancel
          </a>
          <button type="submit" class="btn neon large" id="submitBtn">
            <span>🚗</span>
            Create Vehicle
          </button>
        </div>
      </form>
    </div>
  </main>

  <!--  Footer -->
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