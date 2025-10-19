<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Aventones — My vehicles</title>
  <link rel="stylesheet" href="css/vehicles.css" />
</head>
<body class="veh-page">

  <!-- ===== Header ===== -->
  <header class="veh-topbar">
    <!-- Izquierda: logo + título -->
    <div class="veh-brand">
      <img class="veh-logo" src="img/logo.png" alt="Aventones logo">
      <h1>My vehicles</h1>
    </div>

    <!-- Centro: navegación -->
    <nav class="main-nav">
      <ul class="nav-links">
        <li><a href="" class="active">Home</a></li>
        <li><a href="" data-role-only="driver">Rides</a></li>
        <li><a href="">Bookings</a></li>
      </ul>
    </nav>

    <!-- Derecha: CTA + avatar -->
    <div class="right-box">
      <button id="btnNew" class="btn neon">+ New Vehicle</button>

      <div class="profile-menu">
        <img src="img/logo.png" alt="User Icon" class="avatar" id="avatarBtn" />
        <ul class="dropdown" id="profileDropdown">
          <li><a href="Registration.php">Register</a></li>
          <li><a href="Login.php">Login</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- ===== Contenido ===== -->
  <main class="veh-content">
    <!-- Empty state -->
    <article class="veh-empty" id="emptyState" hidden>
      <div class="veh-illu" aria-hidden="true"></div>
      <h3>No vehicles yet</h3>
      <p>Add your first vehicle to start offering rides as a driver.</p>
      <button class="btn neon ghost" id="btnFirst">Add vehicle</button>
    </article>

    <!-- Grid de tarjetas -->
    <section class="veh-grid" id="vehGrid">
      <!-- Tarjeta de ejemplo -->
      <article class="veh-card" data-id="1">
        <span class="veh-accent"></span>

        <div class="veh-photo">
          <img src="img/example.jpg" alt="Vehicle photo">
        </div>

        <header class="veh-head">
          <span class="veh-plate">ABC-123</span>
          <span class="veh-seats">4 seats</span>
        </header>

        <ul class="veh-meta">
          <li><strong>Brand/Model:</strong> Toyota Corolla</li>
          <li><strong>Year:</strong> 2020</li>
          <li><strong>Color:</strong> White</li>
        </ul>

        <footer class="veh-actions">
          <button class="btn outline" data-edit="1">Edit</button>
          <button class="btn danger" data-delete="1">Delete</button>
        </footer>
      </article>
      <!-- /tarjeta -->
    </section>
  </main>

  <!-- ===== Modal Crear/Editar ===== -->
  <dialog id="vehModal">
    <form id="vehForm" method="dialog" class="veh-form">
      <h3 id="vehFormTitle">New vehicle</h3>

      <div class="grid2">
        <div class="field">
          <label for="plate">Plate</label>
          <input id="plate" name="plate" placeholder="ABC-123" required>
        </div>
        <div class="field">
          <label for="color">Color</label>
          <input id="color" name="color" placeholder="White" required>
        </div>
      </div>

      <div class="grid3">
        <div class="field">
          <label for="brand">Brand</label>
          <input id="brand" name="brand" placeholder="Toyota" required>
        </div>
        <div class="field">
          <label for="model">Model</label>
          <input id="model" name="model" placeholder="Corolla" required>
        </div>
        <div class="field">
          <label for="year">Year</label>
          <input id="year" name="year" type="number" min="1980" max="2099" step="1" required>
        </div>
      </div>

      <div class="grid2">
        <div class="field">
          <label for="seats">Seats</label>
          <input id="seats" name="seats" type="number" min="1" max="9" required>
        </div>
        <div class="field">
          <label for="photo">Photo</label>
          <input id="photo" name="photo" type="file" accept="image/*" capture="environment">
        </div>
      </div>

      <div class="preview">
        <img id="photoPreview" alt="Preview" hidden>
      </div>

      <menu class="veh-menu">
        <button value="cancel" class="btn outline">Cancel</button>
        <button id="vehSave" value="default" class="btn neon">Save</button>
      </menu>
    </form>
  </dialog>

  <!-- ===== Footer ===== -->
  <footer class="veh-footer">
    <div class="footer-links">
      <a href="">Home</a> |
      <a href="Myrides.html" data-role-only="driver">Rides</a> |
      <a href="login.html">Login</a> |
      <a href="Registration.php">Register</a>
    </div>
    <p>&copy; Aventones.com</p>
  </footer>

  <!--  Esto ira en el js de Vehicles (Recordar) -->
  <script>
    // JavaScript para el menú desplegable del avatar
    document.addEventListener('DOMContentLoaded', function() {
      const avatarBtn = document.getElementById('avatarBtn');
      const profileDropdown = document.getElementById('profileDropdown');
      
      if (avatarBtn && profileDropdown) {
        // Toggle del menú al hacer clic en el avatar
        avatarBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          profileDropdown.classList.toggle('show');
        });
        
        // Cerrar el menú al hacer clic fuera de él
        document.addEventListener('click', function() {
          profileDropdown.classList.remove('show');
        });
        
        // Prevenir que el menú se cierre al hacer clic dentro de él
        profileDropdown.addEventListener('click', function(e) {
          e.stopPropagation();
        });
      }
    });
  </script>
</body>
</html>