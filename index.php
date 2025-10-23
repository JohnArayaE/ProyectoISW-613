<?php
session_start();
include('common/conexion.php');

// Obtener parámetros de búsqueda
$origen = $_GET['origen'] ?? '';
$destino = $_GET['destino'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_asc';

// Construir consulta base
$sql = "SELECT r.*, v.marca, v.modelo, v.anio 
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
    case 'fecha_desc':
        $sql .= " ORDER BY r.fecha DESC, r.hora DESC";
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
    default: // fecha_asc
        $sql .= " ORDER BY r.fecha ASC, r.hora ASC";
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
    <title>AVENTONES - Encuentra tu Ride</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-car logo-icon"></i>
                    <h1 class="logo-text">AVENTONES</h1>
                </div>
                
                <!-- Navegación central -->
                <nav class="nav-menu">
                    <a href="index.php" class="nav-link active">Home</a>
                    <a href="#" class="nav-link">Bookings</a>
                </nav>

                <!-- Menú de usuario -->
                <div class="user-menu">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="profile-menu">
                            <?php
                            // Verificar si el archivo existe, si no usar logo
                            if (!file_exists($foto_usuario)) {
                                $foto_usuario = 'img/logo.png';
                            }
                            ?>
                            <img src="<?php echo $foto_usuario; ?>" alt="User Icon" class="avatar" id="avatarBtn" 
                                 onerror="this.src='img/logo.png'" />
                            <ul class="dropdown" id="profileDropdown">
                                <li><a href="index.php" class="dropdown-link">Home</a></li>
                                <li><a href="#" class="dropdown-link">Bookings</a></li>
                                <li><a href="#" class="dropdown-link">Configurations</a></li>
                                <li><a href="actions/logout.php" class="dropdown-link logout-btn">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="Login.php" class="btn btn-primary">Iniciar Sesión</a>
                            <a href="Registration.php" class="btn btn-outline">Registrarse</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h2 class="hero-title">Encuentra tu ride perfecto</h2>
                <p class="hero-subtitle">Viaja seguro, económico y con estilo</p>
                
                <!-- Formulario de Búsqueda -->
                <form method="GET" action="" class="search-form">
                    <div class="search-grid">
                        <div class="form-group">
                            <label for="origen" class="form-label">Origen</label>
                            <input type="text" id="origen" name="origen" value="<?php echo htmlspecialchars($origen); ?>" 
                                   class="form-input" placeholder="¿De dónde partes?">
                        </div>
                        <div class="form-group">
                            <label for="destino" class="form-label">Destino</label>
                            <input type="text" id="destino" name="destino" value="<?php echo htmlspecialchars($destino); ?>" 
                                   class="form-input" placeholder="¿A dónde vas?">
                        </div>
                        <div class="form-group form-group-button">
                            <button type="submit" class="btn btn-primary btn-search">
                                <i class="fas fa-search btn-icon"></i>Buscar Rides
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <!-- Filtros de Ordenamiento -->
            <div class="filters-section">
                <h3 class="section-title">
                    <?php echo empty($origen) && empty($destino) ? 'Rides Disponibles' : 'Resultados de Búsqueda'; ?>
                    <span class="rides-count">(<?php echo count($rides); ?>)</span>
                </h3>
                
                <div class="sort-container">
                    <span class="sort-label">Ordenar por:</span>
                    <select id="ordenSelect" class="sort-select">
                        <option value="fecha_asc" <?php echo $orden === 'fecha_asc' ? 'selected' : ''; ?>>Fecha (Más próximo)</option>
                        <option value="fecha_desc" <?php echo $orden === 'fecha_desc' ? 'selected' : ''; ?>>Fecha (Más lejano)</option>
                        <option value="origen_asc" <?php echo $orden === 'origen_asc' ? 'selected' : ''; ?>>Origen (A-Z)</option>
                        <option value="origen_desc" <?php echo $orden === 'origen_desc' ? 'selected' : ''; ?>>Origen (Z-A)</option>
                        <option value="destino_asc" <?php echo $orden === 'destino_asc' ? 'selected' : ''; ?>>Destino (A-Z)</option>
                        <option value="destino_desc" <?php echo $orden === 'destino_desc' ? 'selected' : ''; ?>>Destino (Z-A)</option>
                    </select>
                </div>
            </div>

            <!-- Lista de Rides -->
            <div class="rides-grid">
                <?php if (count($rides) > 0): ?>
                    <?php foreach ($rides as $ride): ?>
                        <div class="ride-card">
                            <div class="ride-content">
                                <!-- Header del Ride -->
                                <div class="ride-header">
                                    <div class="ride-info-left">
                                        <span class="ride-date">
                                            <?php 
                                            $fecha = new DateTime($ride['fecha']);
                                            echo $fecha->format('d M') . ', ' . substr($ride['hora'], 0, 5);
                                            ?>
                                        </span>
                                        <h4 class="ride-price">₡<?php echo number_format($ride['costo'], 2); ?></h4>
                                    </div>
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_rol'] === 'PASAJERO'): ?>
                                        <button onclick="reservarRide(<?php echo $ride['id']; ?>)" 
                                                class="btn btn-success btn-reserve">
                                            Reservar
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Ruta -->
                                <div class="ride-route">
                                    <h5 class="ride-route-title">
                                        <?php echo htmlspecialchars($ride['lugar_salida']); ?> → 
                                        <?php echo htmlspecialchars($ride['lugar_llegada']); ?>
                                    </h5>
                                </div>

                                <!-- Información del Vehículo -->
                                <div class="ride-details">
                                    <div class="vehicle-info">
                                        <i class="fas fa-car detail-icon"></i>
                                        <?php echo htmlspecialchars($ride['marca'] . ' ' . $ride['modelo']); ?> · 
                                        <?php echo $ride['anio']; ?>
                                    </div>
                                    <div class="seats-info">
                                        <i class="fas fa-users detail-icon"></i>
                                        <?php echo $ride['espacios_disponibles']; ?> asientos disponibles
                                    </div>
                                </div>

                                <!-- Día de la semana -->
                                <div class="ride-day">
                                    <span class="day-badge">
                                        <i class="fas fa-calendar day-icon"></i><?php echo $ride['dia_semana']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-rides">
                        <i class="fas fa-car-side no-rides-icon"></i>
                        <h4 class="no-rides-title">No se encontraron rides</h4>
                        <p class="no-rides-text">Intenta con otros criterios de búsqueda</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 AVENTONES. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Script para el menú desplegable del avatar
        document.addEventListener('DOMContentLoaded', function() {
            const avatarBtn = document.getElementById('avatarBtn');
            const profileDropdown = document.getElementById('profileDropdown');

            if (avatarBtn && profileDropdown) {
                avatarBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function() {
                    profileDropdown.classList.remove('show');
                });

                profileDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            // Ordenamiento
            const ordenSelect = document.getElementById('ordenSelect');
            if (ordenSelect) {
                ordenSelect.addEventListener('change', function() {
                    const url = new URL(window.location);
                    url.searchParams.set('orden', this.value);
                    window.location.href = url.toString();
                });
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>