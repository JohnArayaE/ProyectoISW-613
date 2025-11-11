<?php
session_start();
include('common/conexion.php');

// Verificar que el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

// Obtener datos actuales del usuario
$user_id = $_SESSION['user_id'];
$sql = "SELECT nombre, apellido, cedula, correo, telefono, fecha_nacimiento, foto_ruta FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

// Foto del usuario con valor por defecto
$foto_usuario = !empty($usuario['foto_ruta']) ? $usuario['foto_ruta'] : 'img/logo.png';

// Determinar la página Home según el rol
$home_page = 'index.php';
if ($_SESSION['user_rol'] === 'ADMIN') {
    $home_page = 'AdminDashboard.php';
} elseif ($_SESSION['user_rol'] === 'CHOFER') {
    $home_page = 'vehicles.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurations - AVENTONES</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/configurations.css">
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
                    <a href="<?php echo $home_page; ?>" class="nav-link">Home</a>
                </nav>

                <!-- Menú de usuario -->
                <div class="user-menu">
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
                            <li><a href="<?php echo $home_page; ?>" class="dropdown-link">Home</a></li>
                            <li><a href="actions/logout.php" class="dropdown-link logout-btn">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="main-content">
        <div class="container">
            <div class="config-container">
                <div class="config-card">
                    <!-- Encabezado -->
                    <div class="config-header">
                        <h1 class="config-title">CONFIGURATIONS</h1>
                        <p class="config-subtitle">Update your personal information</p>
                    </div>

                    <!-- === AQUÍ VAN LOS MENSAJES === -->
                    <?php if (isset($_SESSION['mensaje_exito'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['mensaje_exito'];
                            unset($_SESSION['mensaje_exito']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_update'])): ?>
                        <div class="alert alert-error">
                            <?php 
                            echo $_SESSION['error_update'];
                            unset($_SESSION['error_update']);
                            ?>
                        </div>
                    <?php endif; ?>
                    

                    <!-- Formulario Principal -->
                    <form action="actions/updateProfile.php" method="post" enctype="multipart/form-data" autocomplete="off" class="form">
                        <div class="form-grid">

                            <!-- Fila 1 -->
                            <div class="field">
                                <label for="nombre">First Name</label>
                                <input type="text" id="nombre" name="nombre" 
                                       value="<?php echo htmlspecialchars($usuario['nombre']); ?>" 
                                       required class="form-input">
                            </div>

                            <div class="field">
                                <label for="apellido">Last Name</label>
                                <input type="text" id="apellido" name="apellido" 
                                       value="<?php echo htmlspecialchars($usuario['apellido']); ?>" 
                                       required class="form-input">
                            </div>

                            <!-- Fila 2 -->
                            <div class="field">
                                <label for="fecha_nacimiento">Birth</label>
                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" 
                                       value="<?php echo htmlspecialchars($usuario['fecha_nacimiento'] ?? ''); ?>" 
                                       required class="form-input">
                            </div>

                            <div class="field">
                                <label for="cedula">ID Number</label>
                                <input type="text" id="cedula" name="cedula" 
                                       value="<?php echo htmlspecialchars($usuario['cedula']); ?>" 
                                       required class="form-input">
                            </div>

                            <!-- Email (a lo ancho) -->
                            <div class="field span-2">
                                <label for="correo">Email</label>
                                <input type="email" id="correo" name="correo" 
                                       value="<?php echo htmlspecialchars($usuario['correo']); ?>" 
                                       required class="form-input">
                            </div>

                            <!-- Teléfono (a lo ancho) -->
                            <div class="field span-2">
                                <label for="telefono">Phone Number</label>
                                <input type="tel" id="telefono" name="telefono" 
                                       value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>" 
                                       required class="form-input">
                            </div>

                            <!-- Foto de perfil -->
                            <div class="photo-section span-2">
                                <label for="foto">Profile Picture</label>
                                <div class="photo-container">
                                    <div class="photo-preview">
                                        <img src="<?php echo $foto_usuario; ?>" alt="Current Profile" 
                                             id="photoPreview" onerror="this.src='img/logo.png'">
                                    </div>
                                    <input type="file" id="foto" name="foto" accept="image/*" class="file-input">
                                    <label for="foto" class="btn-file">
                                        <i class="fas fa-upload"></i>
                                        Choose Image
                                    </label>
                                </div>
                                <p class="photo-hint">JPG, PNG or GIF. Max 5MB.</p>
                            </div>
                            <!-- Botones de acción -->
                            <div class="actions span-2">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    Update Profile
                                </button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 AVENTONES. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/configurations.js"></script>
</body>
</html>
<?php $conn->close(); ?>