<?php
session_start();
include('../common/conexion.php');

// Verificar que el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login.php');
    exit();
}

// Inicializar variables para mensajes
$mensaje = '';
$error = '';

// Obtener ID del usuario de la sesión
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del formulario
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $cedula = trim($_POST['cedula']);
        $correo = trim($_POST['correo']);
        $telefono = trim($_POST['telefono']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];

        // Validaciones básicas
        if (empty($nombre) || empty($apellido) || empty($cedula) || empty($correo)) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }

        // Verificar si el correo ya existe en otro usuario
        $check_email_sql = "SELECT id FROM usuarios WHERE correo = ? AND id != ?";
        $check_stmt = $conn->prepare($check_email_sql);
        $check_stmt->bind_param("si", $correo, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("El correo electrónico ya está en uso por otro usuario");
        }
        $check_stmt->close();

        // Verificar si la cédula ya existe en otro usuario
        $check_cedula_sql = "SELECT id FROM usuarios WHERE cedula = ? AND id != ?";
        $check_stmt = $conn->prepare($check_cedula_sql);
        $check_stmt->bind_param("si", $cedula, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("La cédula ya está en uso por otro usuario");
        }
        $check_stmt->close();

        // Procesar la foto de perfil si se subió una nueva
        $foto_ruta = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto = $_FILES['foto'];
            
            // Validar tipo de archivo
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($foto['tmp_name']);
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Solo se permiten archivos JPG, PNG o GIF");
            }
            
            // Validar tamaño (5MB máximo)
            if ($foto['size'] > 5 * 1024 * 1024) {
                throw new Exception("La imagen no puede ser mayor a 5MB");
            }
            
            // Crear directorio si no existe
            $upload_dir = "../uploads/" . $user_id . "/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generar nombre único para el archivo
            $file_extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $file_extension;
            $foto_ruta = "uploads/" . $user_id . "/" . $filename;
            $full_path = "../" . $foto_ruta;
            
            // Mover archivo
            if (!move_uploaded_file($foto['tmp_name'], $full_path)) {
                throw new Exception("Error al subir la imagen");
            }
            
            // Si hay una foto anterior, eliminarla
            $old_photo_sql = "SELECT foto_ruta FROM usuarios WHERE id = ?";
            $old_stmt = $conn->prepare($old_photo_sql);
            $old_stmt->bind_param("i", $user_id);
            $old_stmt->execute();
            $old_result = $old_stmt->get_result();
            
            if ($old_result->num_rows > 0) {
                $old_user = $old_result->fetch_assoc();
                if (!empty($old_user['foto_ruta']) && file_exists("../" . $old_user['foto_ruta'])) {
                    unlink("../" . $old_user['foto_ruta']);
                }
            }
            $old_stmt->close();
        }

        // Construir la consulta SQL para actualizar
        if ($foto_ruta) {
            // Actualizar con nueva foto
            $update_sql = "UPDATE usuarios SET nombre = ?, apellido = ?, cedula = ?, correo = ?, telefono = ?, fecha_nacimiento = ?, foto_ruta = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssssssi", $nombre, $apellido, $cedula, $correo, $telefono, $fecha_nacimiento, $foto_ruta, $user_id);
        } else {
            // Actualizar sin cambiar la foto
            $update_sql = "UPDATE usuarios SET nombre = ?, apellido = ?, cedula = ?, correo = ?, telefono = ?, fecha_nacimiento = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssssi", $nombre, $apellido, $cedula, $correo, $telefono, $fecha_nacimiento, $user_id);
        }

        // Ejecutar la actualización
        if ($stmt->execute()) {
            // Actualizar datos en la sesión
            $_SESSION['user_nombre'] = $nombre;
            $_SESSION['user_apellido'] = $apellido;
            $_SESSION['user_correo'] = $correo;
            
            if ($foto_ruta) {
                $_SESSION['user_foto'] = $foto_ruta;
            }
            
            $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente";
        } else {
            throw new Exception("Error al actualizar el perfil en la base de datos");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        $_SESSION['error_update'] = $error;
    }
    
    // Redirigir de vuelta a Configurations.php
    header('Location: ../Configurations.php');
    exit();
    
} else {
    // Si no es POST, redirigir al login
    header('Location: ../Login.php');
    exit();
}

$conn->close();
?>