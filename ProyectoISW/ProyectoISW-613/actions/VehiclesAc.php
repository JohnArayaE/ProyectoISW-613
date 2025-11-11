<?php
session_start();
include('../common/conexion.php');

// Verificar autenticación y rol de chofer
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
    header('Location: ../Login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Determinar la acción basada en método y parámetros
$action = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    header('Location: ../vehicles.php?error=invalid_method');
    exit();
}

// Router de acciones
switch ($action) {
    case 'save':
        createVehicle($conn, $user_id);
        break;
    case 'update':
        updateVehicle($conn, $user_id);
        break;
    case 'delete':
        deleteVehicle($conn, $user_id);
        break;
    default:
        header('Location: ../vehicles.php?error=invalid_action');
        exit();
}

/**
 * CREAR VEHÍCULO
 */
function createVehicle($conn, $user_id) {
    // Validar campos requeridos
    $required_fields = ['plate', 'color', 'brand', 'model', 'year', 'seats'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header('Location: ../CreateVehicle.php?error=missing_fields');
            exit();
        }
    }

    // Sanitizar datos
    $placa = mysqli_real_escape_string($conn, trim($_POST['plate']));
    $color = mysqli_real_escape_string($conn, trim($_POST['color']));
    $marca = mysqli_real_escape_string($conn, trim($_POST['brand']));
    $modelo = mysqli_real_escape_string($conn, trim($_POST['model']));
    $anio = intval($_POST['year']);
    $capacidad = intval($_POST['seats']);

    // Validaciones específicas
    if ($anio < 1980 || $anio > (date('Y') + 1)) {
        header('Location: ../CreateVehicle.php?error=invalid_year');
        exit();
    }

    if ($capacidad < 1 || $capacidad > 9) {
        header('Location: ../CreateVehicle.php?error=invalid_seats');
        exit();
    }

    // Procesar imagen
    $foto_vehiculo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        
        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            header('Location: ../CreateVehicle.php?error=invalid_file_type');
            exit();
        }
        
        // Validar tamaño (5MB máximo)
        if ($file['size'] > 5 * 1024 * 1024) {
            header('Location: ../CreateVehicle.php?error=file_too_large');
            exit();
        }
        
        // Crear nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_vehicle.' . $extension;
        $upload_path = '../uploads/vehicles/' . $filename;
        
        // Crear directorio si no existe
        $upload_dir = '../uploads/vehicles';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $foto_vehiculo = 'uploads/vehicles/' . $filename;
        } else {
            header('Location: ../CreateVehicle.php?error=upload_failed');
            exit();
        }
    }

    // Insertar en base de datos - SE AGREGA ESTADO 'activo' POR DEFECTO
    if ($foto_vehiculo) {
        $sql = "INSERT INTO vehiculos (id_chofer, placa, color, marca, modelo, anio, capacidad, foto_vehiculo, estado) 
                VALUES ('$user_id', '$placa', '$color', '$marca', '$modelo', '$anio', '$capacidad', '$foto_vehiculo', 'ACTIVO')";
    } else {
        $sql = "INSERT INTO vehiculos (id_chofer, placa, color, marca, modelo, anio, capacidad, estado) 
                VALUES ('$user_id', '$placa', '$color', '$marca', '$modelo', '$anio', '$capacidad', 'ACTIVO')";
    }

    // Ejecutar consulta y manejar errores
    if (mysqli_query($conn, $sql)) {
        header('Location: ../vehicles.php?success=vehicle_created');
    } else {
        error_log("Error en consulta SQL: " . mysqli_error($conn));
        header('Location: ../CreateVehicle.php?error=database_error');
    }
    
    exit();
}

/**
 * ACTUALIZAR VEHÍCULO
 */
function updateVehicle($conn, $user_id) {
    // Verificar que se haya proporcionado un ID
    if (!isset($_POST['vehicle_id']) || empty($_POST['vehicle_id'])) {
        header('Location: ../vehicles.php?error=invalid_id');
        exit();
    }

    $vehicle_id = $_POST['vehicle_id'];

    // Validar que el vehículo pertenezca al usuario (SOLO VEHÍCULOS ACTIVOS)
    if (!vehicleBelongsToUser($conn, $vehicle_id, $user_id)) {
        header('Location: ../vehicles.php?error=vehicle_not_found');
        exit();
    }

    // Validar campos requeridos
    $required_fields = ['plate', 'color', 'brand', 'model', 'year', 'seats'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../EditVehicle.php?id=$vehicle_id&error=missing_fields");
            exit();
        }
    }

    // Sanitizar datos
    $placa = mysqli_real_escape_string($conn, trim($_POST['plate']));
    $color = mysqli_real_escape_string($conn, trim($_POST['color']));
    $marca = mysqli_real_escape_string($conn, trim($_POST['brand']));
    $modelo = mysqli_real_escape_string($conn, trim($_POST['model']));
    $anio = intval($_POST['year']);
    $capacidad = intval($_POST['seats']);

    // Validaciones específicas
    if ($anio < 1980 || $anio > (date('Y') + 1)) {
        header("Location: ../EditVehicle.php?id=$vehicle_id&error=invalid_year");
        exit();
    }

    if ($capacidad < 1 || $capacidad > 9) {
        header("Location: ../EditVehicle.php?id=$vehicle_id&error=invalid_seats");
        exit();
    }

    // Procesar nueva imagen si se proporciona
    $foto_vehiculo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        
        // Validar tipo de archivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            header("Location: ../EditVehicle.php?id=$vehicle_id&error=invalid_file_type");
            exit();
        }
        
        // Validar tamaño (5MB máximo)
        if ($file['size'] > 5 * 1024 * 1024) {
            header("Location: ../EditVehicle.php?id=$vehicle_id&error=file_too_large");
            exit();
        }
        
        // Crear nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_vehicle.' . $extension;
        $upload_path = '../uploads/vehicles/' . $filename;
        
        // Crear directorio si no existe
        $upload_dir = '../uploads/vehicles';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $foto_vehiculo = 'uploads/vehicles/' . $filename;
            
            // Eliminar foto anterior si existe
            $old_photo = getVehiclePhoto($conn, $vehicle_id);
            if ($old_photo && file_exists('../' . $old_photo)) {
                unlink('../' . $old_photo);
            }
        } else {
            header("Location: ../EditVehicle.php?id=$vehicle_id&error=upload_failed");
            exit();
        }
    }

    // Actualizar en base de datos (NO SE MODIFICA EL ESTADO)
    if ($foto_vehiculo) {
        $sql = "UPDATE vehiculos SET placa = '$placa', color = '$color', marca = '$marca', 
                modelo = '$modelo', anio = '$anio', capacidad = '$capacidad', foto_vehiculo = '$foto_vehiculo' 
                WHERE id = '$vehicle_id' AND id_chofer = '$user_id' AND estado = 'ACTIVO'";
    } else {
        $sql = "UPDATE vehiculos SET placa = '$placa', color = '$color', marca = '$marca', 
                modelo = '$modelo', anio = '$anio', capacidad = '$capacidad' 
                WHERE id = '$vehicle_id' AND id_chofer = '$user_id' AND estado = 'ACTIVO'";
    }

    // Ejecutar consulta
    if (mysqli_query($conn, $sql)) {
        header('Location: ../vehicles.php?success=vehicle_updated');
    } else {
        error_log("Error en actualización SQL: " . mysqli_error($conn));
        header("Location: ../EditVehicle.php?id=$vehicle_id&error=database_error");
    }
    
    exit();
}

/**
 * ELIMINAR VEHÍCULO (ELIMINACIÓN LÓGICA - CAMBIAR ESTADO A INACTIVO)
 */
function deleteVehicle($conn, $user_id) {
    // Verificar que se haya proporcionado un ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header('Location: ../vehicles.php?error=invalid_id');
        exit();
    }

    $vehicle_id = $_GET['id'];

    // Verificar que el vehículo pertenezca al usuario (SOLO VEHÍCULOS ACTIVOS)
    if (!vehicleBelongsToUser($conn, $vehicle_id, $user_id)) {
        header('Location: ../vehicles.php?error=vehicle_not_found');
        exit();
    }
    
    // ELIMINACIÓN LÓGICA: Cambiar estado a 'inactivo' en lugar de eliminar
    $sql = "UPDATE vehiculos SET estado = 'INACTIVO' WHERE id = '$vehicle_id' AND id_chofer = '$user_id' AND estado = 'ACTIVO'";
    
    if (mysqli_query($conn, $sql)) {
        header('Location: ../vehicles.php?success=vehicle_deleted');
    } else {
        error_log("Error en actualización SQL: " . mysqli_error($conn));
        header('Location: ../vehicles.php?error=delete_failed');
    }
    
    exit();
}

/**
 * FUNCIONES AUXILIARES
 */

// Verificar si el vehículo pertenece al usuario (SOLO VEHÍCULOS ACTIVOS)
function vehicleBelongsToUser($conn, $vehicle_id, $user_id) {
    $sql = "SELECT id FROM vehiculos WHERE id = '$vehicle_id' AND id_chofer = '$user_id' AND estado = 'ACTIVO'";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0);
}

// Obtener la foto del vehículo (SOLO VEHÍCULOS ACTIVOS)
function getVehiclePhoto($conn, $vehicle_id) {
    $sql = "SELECT foto_vehiculo FROM vehiculos WHERE id = '$vehicle_id' AND estado = 'ACTIVO'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['foto_vehiculo'];
    }
    return null;
}

// Cerrar conexión
mysqli_close($conn);
?>