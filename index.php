<?php
session_start();
include('common/conexion.php');

// Obtener parámetros de búsqueda
$origen = $_GET['origen'] ?? '';
$destino = $_GET['destino'] ?? '';
$orden = $_GET['orden'] ?? 'dia_asc';

// Construir consulta base
$sql = "SELECT r.*, v.marca, v.modelo, v.anio, v.capacidad 
        FROM rides r 
        JOIN vehiculos v ON r.id_vehiculo = v.id 
        WHERE r.estado = 'ACTIVO' AND r.espacios_disponibles > 0";

// Aplicar filtros de búsqueda
if (!empty($origen)) {
    $origen_esc = $conn->real_escape_string($origen);
    $sql .= " AND r.lugar_salida LIKE '%$origen_esc%'";
}
if (!empty($destino)) {
    $destino_esc = $conn->real_escape_string($destino);
    $sql .= " AND r.lugar_llegada LIKE '%$destino_esc%'";
}

// Aplicar ordenamiento
switch ($orden) {
    case 'dia_desc':
        $sql .= " ORDER BY 
                CASE r.dia_semana 
                    WHEN 'LUNES' THEN 1
                    WHEN 'MARTES' THEN 2
                    WHEN 'MIERCOLES' THEN 3
                    WHEN 'JUEVES' THEN 4
                    WHEN 'VIERNES' THEN 5
                    WHEN 'SABADO' THEN 6
                    WHEN 'DOMINGO' THEN 7
                END DESC, 
                r.hora DESC";
        break;
    case 'origen_asc':
        $sql .= " ORDER BY r.lugar_salida ASC";
        break;
    case 'origen_desc':
        $sql .= " ORDER BY r.lugar_salida DESC";
        break;
    case 'destino_asc':
        $sql .= " ORDER BY r.lugar_llegada ASC";
        break;
    case 'destino_desc':
        $sql .= " ORDER BY r.lugar_llegada DESC";
        break;
    case 'precio_asc':
        $sql .= " ORDER BY r.costo ASC";
        break;
    case 'precio_desc':
        $sql .= " ORDER BY r.costo DESC";
        break;
    default: // dia_asc
        $sql .= " ORDER BY 
                CASE r.dia_semana 
                    WHEN 'LUNES' THEN 1
                    WHEN 'MARTES' THEN 2
                    WHEN 'MIERCOLES' THEN 3
                    WHEN 'JUEVES' THEN 4
                    WHEN 'VIERNES' THEN 5
                    WHEN 'SABADO' THEN 6
                    WHEN 'DOMINGO' THEN 7
                END ASC, 
                r.hora ASC";
}

$result = $conn->query($sql);
$rides = [];
if ($result) {
    $rides = $result->fetch_all(MYSQLI_ASSOC);
}

// Foto desde sesión con default
$foto_usuario = !empty($_SESSION['user_foto']) ? $_SESSION['user_foto'] : 'img/logo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVENTONES - Find Your Ride</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
</head>
<body class="index-page">
    <!-- Header -->
    <header class="index-topbar">
        <div class="index-brand">
            <img class="index-logo" src="img/logo.png" alt="Aventones logo">
            <h1>AVENTONES</h1>
        </div>

        <nav class="main-nav">
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="bookings.php">Bookings</a></li>
            </ul>
        </nav>

        <div class="right-box">
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['user_rol'] === 'CHOFER'): ?>
                    <a href="create_ride.php" class="btn neon">New Ride</a>
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
                        <li><a href="index.php" class="dropdown-link">Home</a></li>
                        <li><a href="bookings.php" class="dropdown-link">Bookings</a></li>
                        <li><a href="Configurations.php" class="dropdown-link">Configuration</a></li>
                        <li><a href="actions/logout.php" class="logout-btn">Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="Login.php" class="btn outline">Login</a>
                    <a href="Registration.php" class="btn neon">Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h2 class="hero-title">Find Your Perfect Ride</h2>
                <p class="hero-subtitle">Travel safe, economical and with style</p>
                
                <!-- Search Form -->
                <form method="GET" action="" class="search-form">
                    <div class="search-grid">
                        <div class="form-group">
                            <label for="origen" class="form-label">From</label>
                            <input type="text" id="origen" name="origen" value="<?php echo htmlspecialchars($origen); ?>" 
                                   class="form-input" placeholder="Where are you leaving from?">
                        </div>
                        <div class="form-group">
                            <label for="destino" class="form-label">To</label>
                            <input type="text" id="destino" name="destino" value="<?php echo htmlspecialchars($destino); ?>" 
                                   class="form-input" placeholder="Where are you going?">
                        </div>
                        <div class="form-group form-group-button">
                            <button type="submit" class="btn neon btn-search">
                                <i class="fas fa-search btn-icon"></i>Search Rides
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="index-content">
        <div class="container">
            <!-- Filters Section -->
            <div class="filters-section">
                <h3 class="section-title">
                    <?php echo empty($origen) && empty($destino) ? 'Available Rides' : 'Search Results'; ?>
                    <span class="rides-count">(<?php echo count($rides); ?>)</span>
                </h3>
                
                <div class="sort-container">
                    <span class="sort-label">Sort by:</span>
                    <select id="ordenSelect" class="sort-select">
                        <option value="dia_asc" <?php echo $orden === 'dia_asc' ? 'selected' : ''; ?>>Day (Monday to Sunday)</option>
                        <option value="dia_desc" <?php echo $orden === 'dia_desc' ? 'selected' : ''; ?>>Day (Sunday to Monday)</option>
                        <option value="origen_asc" <?php echo $orden === 'origen_asc' ? 'selected' : ''; ?>>Origin (A-Z)</option>
                        <option value="origen_desc" <?php echo $orden === 'origen_desc' ? 'selected' : ''; ?>>Origin (Z-A)</option>
                        <option value="destino_asc" <?php echo $orden === 'destino_asc' ? 'selected' : ''; ?>>Destination (A-Z)</option>
                        <option value="destino_desc" <?php echo $orden === 'destino_desc' ? 'selected' : ''; ?>>Destination (Z-A)</option>
                        <option value="precio_asc" <?php echo $orden === 'precio_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                        <option value="precio_desc" <?php echo $orden === 'precio_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                    </select>
                </div>
            </div>

            <!-- Rides Grid -->
            <div class="rides-grid">
                <?php if (count($rides) > 0): ?>
                    <?php foreach ($rides as $ride): ?>
                        <article class="ride-card" data-id="<?php echo $ride['id']; ?>">
                            <span class="ride-accent"></span>

                            <div class="ride-header">
                                <div class="ride-info-left">
                                    <span class="ride-time">
                                        <?php echo substr($ride['hora'], 0, 5); ?>
                                    </span>
                                    <h4 class="ride-price">₡<?php echo number_format($ride['costo'], 2); ?></h4>
                                </div>
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_rol'] === 'PASAJERO'): ?>
                                    <button onclick="reservarRide(<?php echo $ride['id']; ?>)" 
                                            class="btn success btn-reserve">
                                        <i class="fas fa-calendar-plus"></i> Book
                                    </button>
                                <?php endif; ?>
                            </div>

                            <div class="ride-route">
                                <h5 class="ride-route-title">
                                    <?php echo htmlspecialchars($ride['lugar_salida']); ?> → 
                                    <?php echo htmlspecialchars($ride['lugar_llegada']); ?>
                                </h5>
                            </div>

                            <div class="ride-details">
                                <div class="vehicle-info">
                                    <i class="fas fa-car detail-icon"></i>
                                    <?php echo htmlspecialchars($ride['marca'] . ' ' . $ride['modelo']); ?> · 
                                    <?php echo $ride['anio']; ?>
                                </div>
                                <div class="seats-info">
                                    <i class="fas fa-users detail-icon"></i>
                                    <?php echo $ride['espacios_disponibles']; ?> seats available
                                </div>
                            </div>

                            <div class="ride-day">
                                <span class="day-badge">
                                    <i class="fas fa-calendar day-icon"></i><?php echo $ride['dia_semana']; ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-rides">
                        <div class="no-rides-illu" aria-hidden="true"></div>
                        <h4 class="no-rides-title">No rides found</h4>
                        <p class="no-rides-text">Try different search criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Reservation Modal -->
    <dialog id="reservaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Book Ride</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="modalRideInfo"></div>
                <div class="form-group">
                    <label for="cantidadEspacios" class="form-label">Number of spaces:</label>
                    <input type="number" id="cantidadEspacios" name="cantidad_espacios" min="1" value="1" class="form-input">
                    <small id="maxEspaciosInfo" class="form-help"></small>
                </div>
                <div class="price-summary">
                    <p>Cost per space: <span id="costoPorEspacio">₡0.00</span></p>
                    <p class="total-price">Total: <span id="costoTotal">₡0.00</span></p>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn outline" id="cancelarReserva">Cancel</button>
                <button type="button" class="btn neon" id="confirmarReserva">Confirm Booking</button>
            </div>
        </div>
    </dialog>

    <!-- Footer -->
    <footer class="index-footer">
        <div class="footer-links">
            <a href="index.php">Home</a> |
            <a href="bookings.php">Bookings</a>
        </div>
        <p>&copy; 2025 AVENTONES. All rights reserved.</p>
    </footer>

    <script src="js/index.js"></script>
</body>
</html>
<?php 
// No cerrar conexión para evitar conflictos
?>