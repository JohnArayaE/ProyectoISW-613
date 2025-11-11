<?php
session_start();
include('../common/conexion.php');

// Verificar que el usuario es ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'ADMIN') {
    header('Location: ../Login.php');
    exit();
}

// Verificar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método no permitido";
    header('Location: ../AdminDashboard.php');
    exit();
}

// Verificar que los campos requeridos están presentes
if (!isset($_POST['usuario_id']) || !isset($_POST['nuevo_estado'])) {
    $_SESSION['error'] = "Datos incompletos";
    header('Location: ../AdminDashboard.php');
    exit();
}

$usuario_id = intval($_POST['usuario_id']);
$nuevo_estado = $_POST['nuevo_estado'];

// Validar que el estado sea válido
$estados_permitidos = ['ACTIVO', 'INACTIVO', 'PENDIENTE'];
if (!in_array($nuevo_estado, $estados_permitidos)) {
    $_SESSION['error'] = "Estado no válido";
    header('Location: ../AdminDashboard.php');
    exit();
}

try {
    // Actualizar el estado del usuario
    $update_sql = "UPDATE usuarios SET estado = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $nuevo_estado, $usuario_id);
    
    if ($stmt->execute()) {
        // Determinar el mensaje según la acción
        if ($nuevo_estado === 'ACTIVO') {
            $_SESSION['mensaje'] = "Usuario activado correctamente";
        } elseif ($nuevo_estado === 'INACTIVO') {
            $_SESSION['mensaje'] = "Usuario desactivado correctamente";
        } else {
            $_SESSION['mensaje'] = "Estado actualizado correctamente";
        }
    } else {
        $_SESSION['error'] = "Error al actualizar el estado del usuario";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error en la base de datos: " . $e->getMessage();
}

$conn->close();

// Redirigir de vuelta al dashboard
header('Location: ../AdminDashboard.php');
exit();
?>