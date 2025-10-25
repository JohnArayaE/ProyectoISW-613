<?php
session_start();
include('common/conexion.php');

// Verificar que el usuario es ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'ADMIN') {
    header('Location: Login.php');
    exit();
}

// Obtener todos los usuarios
$sql = "SELECT id, nombre, apellido, cedula, correo, telefono, rol, estado FROM usuarios ORDER BY id DESC";
$result = $conn->query($sql);
$usuarios = [];
if ($result) {
    $usuarios = $result->fetch_all(MYSQLI_ASSOC);
}

// Mostrar mensajes de sesión
$mensaje = $_SESSION['mensaje'] ?? null;
$error = $_SESSION['error'] ?? null;

// Limpiar mensajes de sesión después de mostrarlos
unset($_SESSION['mensaje']);
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - AVENTONES</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_dashboard.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-car logo-icon"></i>
                    <h1 class="logo-text">AVENTONES - Admin</h1>
                </div>
                <div class="admin-info">
                    <span class="admin-name">Bienvenido, <?php echo $_SESSION['user_nombre']; ?></span>
                </div>
                <nav class="nav-menu">
                    <a href="RegisterAdmin.php" class="btn btn-primary">
                        <i class="fas fa-user-plus btn-icon"></i>Crear Administrador
                    </a>
                    <a href="Configurations.php" class="btn btn-outline">
                        <i class="fas fa-cog btn-icon"></i>Configuración
                    </a>
                    <a href="actions/logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt btn-icon"></i>Cerrar Sesión
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number"><?php echo count($usuarios); ?></h3>
                        <p class="stat-label">Total Usuarios</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number">
                            <?php echo count(array_filter($usuarios, function($user) { return $user['estado'] === 'ACTIVO'; })); ?>
                        </h3>
                        <p class="stat-label">Usuarios Activos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number">
                            <?php echo count(array_filter($usuarios, function($user) { return $user['estado'] === 'PENDIENTE'; })); ?>
                        </h3>
                        <p class="stat-label">Pendientes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="stat-number">
                            <?php echo count(array_filter($usuarios, function($user) { return $user['estado'] === 'INACTIVO'; })); ?>
                        </h3>
                        <p class="stat-label">Inactivos</p>
                    </div>
                </div>
            </div>

            <!-- Lista de Usuarios -->
            <div class="users-section">
                <h2 class="section-title">Gestión de Usuarios</h2>
                
                <?php if ($mensaje): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['telefono'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo strtolower($usuario['rol']); ?>">
                                                <?php echo $usuario['rol']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($usuario['estado']); ?>">
                                                <?php echo $usuario['estado']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="actions/changeUserStatus.php" class="status-form">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <?php if ($usuario['estado'] === 'ACTIVO' || $usuario['estado'] === 'PENDIENTE'): ?>
                                                    <input type="hidden" name="nuevo_estado" value="INACTIVO">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('¿Estás seguro de que quieres desactivar este usuario?')">
                                                        <i class="fas fa-ban btn-icon"></i>Desactivar
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="nuevo_estado" value="ACTIVO">
                                                    <button type="submit" class="btn btn-success btn-sm"
                                                            onclick="return confirm('¿Estás seguro de que quieres activar este usuario?')">
                                                        <i class="fas fa-check btn-icon"></i>Activar
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="no-users">
                                        <i class="fas fa-users no-users-icon"></i>
                                        <p>No hay usuarios registrados</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 AVENTONES. Panel de Administración</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>