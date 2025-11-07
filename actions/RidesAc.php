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
    header('Location: ../rides.php?error=invalid_method');
    exit();
}

// Router de acciones
switch ($action) {
    case 'save':
        createRide($conn, $user_id);
        break;
    case 'update':
        updateRide($conn, $user_id);
        break;
    case 'delete':
        deleteRide($conn, $user_id);
        break;
    default:
        header('Location: ../rides.php?error=invalid_action');
        exit();
}

/**
 * CREAR RIDE - MÚLTIPLES RIDES POR DÍAS SELECCIONADOS
 */
function createRide($conn, $user_id) {
    // Validar campos requeridos
    $required_fields = ['nombre_ride', 'lugar_salida', 'lugar_llegada', 'hora', 'costo', 'espacios_totales', 'id_vehiculo'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header('Location: ../CreateRide.php?error=missing_fields');
            exit();
        }
    }

    // Validar que se hayan seleccionado días
    if (!isset($_POST['dias_semana']) || empty($_POST['dias_semana'])) {
        header('Location: ../CreateRide.php?error=no_days_selected');
        exit();
    }

    // Sanitizar datos
    $nombre_ride = mysqli_real_escape_string($conn, trim($_POST['nombre_ride']));
    $lugar_salida = mysqli_real_escape_string($conn, trim($_POST['lugar_salida']));
    $lugar_llegada = mysqli_real_escape_string($conn, trim($_POST['lugar_llegada']));
    $hora = mysqli_real_escape_string($conn, trim($_POST['hora']));
    $costo = floatval($_POST['costo']);
    $espacios_totales = intval($_POST['espacios_totales']);
    $id_vehiculo = intval($_POST['id_vehiculo']);
    $dias_seleccionados = $_POST['dias_semana'];

    // Validaciones específicas
    if ($costo < 0 || $costo > 1000) {
        header('Location: ../CreateRide.php?error=invalid_cost');
        exit();
    }

    if ($espacios_totales < 1) {
        header('Location: ../CreateRide.php?error=invalid_seats');
        exit();
    }

    // Validar que el vehículo pertenezca al usuario
    if (!vehicleBelongsToUser($conn, $id_vehiculo, $user_id)) {
        header('Location: ../CreateRide.php?error=invalid_vehicle');
        exit();
    }

    // Validar que no exceda la capacidad del vehículo
    $vehicle_capacity = getVehicleCapacity($conn, $id_vehiculo);
    if ($espacios_totales > $vehicle_capacity - 1) {
        header('Location: ../CreateRide.php?error=exceeds_capacity');
        exit();
    }

    // Crear rides para cada día seleccionado
    $success_count = 0;
    $error_count = 0;

    foreach ($dias_seleccionados as $dia_semana) {
        $dia_semana = mysqli_real_escape_string($conn, trim($dia_semana));
        
        // Verificar si ya existe un ride similar para el mismo día y hora
        if (!similarRideExists($conn, $id_vehiculo, $dia_semana, $hora)) {
            $sql = "INSERT INTO rides (id_chofer, id_vehiculo, nombre_ride, lugar_salida, lugar_llegada, hora, dia_semana, costo, espacios_totales, espacios_disponibles, estado) 
                    VALUES ('$user_id', '$id_vehiculo', '$nombre_ride', '$lugar_salida', '$lugar_llegada', '$hora', '$dia_semana', '$costo', '$espacios_totales', '$espacios_totales', 'ACTIVO')";

            if (mysqli_query($conn, $sql)) {
                $success_count++;
            } else {
                error_log("Error en consulta SQL para día $dia_semana: " . mysqli_error($conn));
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }

    // Manejar resultado
    if ($success_count > 0) {
        if ($error_count > 0) {
            header('Location: ../rides.php?success=rides_partially_created&created=' . $success_count . '&failed=' . $error_count);
        } else {
            header('Location: ../rides.php?success=rides_created&count=' . $success_count);
        }
    } else {
        header('Location: ../CreateRide.php?error=ride_creation_failed');
    }
    
    exit();
}

/**
 * ACTUALIZAR RIDE (para edición individual)
 */
function updateRide($conn, $user_id) {
    // Verificar que se haya proporcionado un ID
    if (!isset($_POST['ride_id']) || empty($_POST['ride_id'])) {
        header('Location: ../rides.php?error=invalid_id');
        exit();
    }

    $ride_id = $_POST['ride_id'];

    // Validar que el ride pertenezca al usuario
    if (!rideBelongsToUser($conn, $ride_id, $user_id)) {
        header('Location: ../rides.php?error=ride_not_found');
        exit();
    }

    // Validar campos requeridos
    $required_fields = ['nombre_ride', 'lugar_salida', 'lugar_llegada', 'hora', 'costo', 'espacios_totales', 'id_vehiculo', 'dia_semana'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../EditRide.php?id=$ride_id&error=missing_fields");
            exit();
        }
    }

    // Sanitizar datos
    $nombre_ride = mysqli_real_escape_string($conn, trim($_POST['nombre_ride']));
    $lugar_salida = mysqli_real_escape_string($conn, trim($_POST['lugar_salida']));
    $lugar_llegada = mysqli_real_escape_string($conn, trim($_POST['lugar_llegada']));
    $hora = mysqli_real_escape_string($conn, trim($_POST['hora']));
    $costo = floatval($_POST['costo']);
    $espacios_totales = intval($_POST['espacios_totales']);
    $id_vehiculo = intval($_POST['id_vehiculo']);
    $dia_semana = mysqli_real_escape_string($conn, trim($_POST['dia_semana']));

    // Validaciones específicas
    if ($costo < 0 || $costo > 1000) {
        header("Location: ../EditRide.php?id=$ride_id&error=invalid_cost");
        exit();
    }

    if ($espacios_totales < 1) {
        header("Location: ../EditRide.php?id=$ride_id&error=invalid_seats");
        exit();
    }

    // Validar que el vehículo pertenezca al usuario
    if (!vehicleBelongsToUser($conn, $id_vehiculo, $user_id)) {
        header("Location: ../EditRide.php?id=$ride_id&error=invalid_vehicle");
        exit();
    }

    // Validar que no exceda la capacidad del vehículo
    $vehicle_capacity = getVehicleCapacity($conn, $id_vehiculo);
    if ($espacios_totales > $vehicle_capacity - 1) {
        header("Location: ../EditRide.php?id=$ride_id&error=exceeds_capacity");
        exit();
    }

    // Verificar si ya existe un ride similar (excluyendo el actual)
    if (similarRideExistsForUpdate($conn, $id_vehiculo, $dia_semana, $hora, $ride_id)) {
        header("Location: ../EditRide.php?id=$ride_id&error=ride_exists");
        exit();
    }

    // Obtener espacios_disponibles actuales
    $current_ride = getRide($conn, $ride_id);
    $espacios_disponibles = $current_ride['espacios_disponibles'];

    // Ajustar espacios disponibles si cambian los totales
    if ($espacios_totales != $current_ride['espacios_totales']) {
        $diferencia = $espacios_totales - $current_ride['espacios_totales'];
        $espacios_disponibles += $diferencia;
        
        if ($espacios_disponibles < 0) $espacios_disponibles = 0;
        if ($espacios_disponibles > $espacios_totales) $espacios_disponibles = $espacios_totales;
    }

    // Actualizar en base de datos
    $sql = "UPDATE rides SET 
            nombre_ride = '$nombre_ride', 
            lugar_salida = '$lugar_salida', 
            lugar_llegada = '$lugar_llegada', 
            hora = '$hora', 
            dia_semana = '$dia_semana', 
            costo = '$costo', 
            espacios_totales = '$espacios_totales', 
            espacios_disponibles = '$espacios_disponibles', 
            id_vehiculo = '$id_vehiculo' 
            WHERE id = '$ride_id'";

    if (mysqli_query($conn, $sql)) {
        header('Location: ../rides.php?success=ride_updated');
    } else {
        error_log("Error en actualización SQL: " . mysqli_error($conn));
        header("Location: ../EditRide.php?id=$ride_id&error=database_error");
    }
    
    exit();
}

/**
 * ELIMINAR RIDE
 */
function deleteRide($conn, $user_id) {
    // Verificar que se haya proporcionado un ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header('Location: ../rides.php?error=invalid_id');
        exit();
    }

    $ride_id = $_GET['id'];

    // Validar que el ride pertenezca al usuario
    if (!rideBelongsToUser($conn, $ride_id, $user_id)) {
        header('Location: ../rides.php?error=ride_not_found');
        exit();
    }

    $sql = "UPDATE rides SET estado = 'CANCELADO' WHERE id = '$ride_id'";
    
    if (mysqli_query($conn, $sql)) {
        header('Location: ../rides.php?success=ride_deleted');
    } else {
        error_log("Error en eliminación SQL: " . mysqli_error($conn));
        header('Location: ../rides.php?error=delete_failed');
    }
    
    exit();
}

/**
 * FUNCIONES AUXILIARES
 */
function vehicleBelongsToUser($conn, $vehicle_id, $user_id) {
    $sql = "SELECT id FROM vehiculos WHERE id = '$vehicle_id' AND id_chofer = '$user_id'";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0);
}

function rideBelongsToUser($conn, $ride_id, $user_id) {
    $sql = "SELECT r.id FROM rides r 
            JOIN vehiculos v ON r.id_vehiculo = v.id 
            WHERE r.id = '$ride_id' AND v.id_chofer = '$user_id'";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0);
}

function getVehicleCapacity($conn, $vehicle_id) {
    $sql = "SELECT capacidad FROM vehiculos WHERE id = '$vehicle_id'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['capacidad'];
    }
    return 0;
}

function getRide($conn, $ride_id) {
    $sql = "SELECT * FROM rides WHERE id = '$ride_id'";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row;
    }
    return null;
}

function similarRideExists($conn, $vehicle_id, $dia_semana, $hora) {
    $sql = "SELECT id FROM rides WHERE id_vehiculo = '$vehicle_id' AND dia_semana = '$dia_semana' AND hora = '$hora' AND estado = 'ACTIVO'";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0);
}

function similarRideExistsForUpdate($conn, $vehicle_id, $dia_semana, $hora, $exclude_ride_id) {
    $sql = "SELECT id FROM rides WHERE id_vehiculo = '$vehicle_id' AND dia_semana = '$dia_semana' AND hora = '$hora' AND estado = 'ACTIVO' AND id != '$exclude_ride_id'";
    $result = mysqli_query($conn, $sql);
    return ($result && mysqli_num_rows($result) > 0);
}

// Cerrar conexión
mysqli_close($conn);
?>