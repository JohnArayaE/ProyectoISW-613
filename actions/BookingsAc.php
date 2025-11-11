<?php
session_start();

// INCLUIR CONEXIÓN CON RUTA ABSOLUTA
$ruta_base = dirname(__DIR__); // Esto sube un nivel desde la carpeta actions
include($ruta_base . '/common/conexion.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo "ERROR: No autorizado";
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "ERROR: Método no permitido";
    exit();
}

// Verificar que los datos POST existen
if (!isset($_POST['booking_id']) || !isset($_POST['action'])) {
    echo "ERROR: Datos incompletos";
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_rol'];
$booking_id = intval($_POST['booking_id']);
$action = $_POST['action'];

// Validar datos
if ($booking_id <= 0) {
    echo "ERROR: ID de reserva inválido";
    exit();
}

// Lista de acciones válidas
$acciones_validas = ['accept', 'reject', 'cancel'];
if (!in_array($action, $acciones_validas)) {
    echo "ERROR: Acción no válida";
    exit();
}

// Crear conexión
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "ERROR: Error de conexión a la base de datos: " . $conn->connect_error;
    exit();
}

try {
    // Obtener información de la reserva
    $sql = "SELECT rv.*, rd.id_chofer, rd.espacios_disponibles 
            FROM reservas rv 
            JOIN rides rd ON rv.id_ride = rd.id 
            WHERE rv.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        echo "ERROR: Reserva no encontrada";
        exit();
    }

    // Verificar permisos según el rol
    if ($user_role === 'CHOFER') {
        // Chofer solo puede modificar reservas de sus propios rides
        if ($booking['id_chofer'] != $user_id) {
            echo "ERROR: No tienes permisos para esta acción";
            exit();
        }
    } else {
        // Pasajero solo puede modificar sus propias reservas
        if ($booking['id_pasajero'] != $user_id) {
            echo "ERROR: No tienes permisos para esta acción";
            exit();
        }
    }

    // Verificar que la reserva se puede modificar
    $estados_no_modificables = ['CANCELADA', 'RECHAZADA', 'COMPLETADA'];
    if (in_array($booking['estado'], $estados_no_modificables)) {
        echo "ERROR: Esta reserva no se puede modificar";
        exit();
    }

    // Determinar el nuevo estado según la acción
    $nuevo_estado = '';
    switch ($action) {
        case 'accept':
            if ($user_role !== 'CHOFER') {
                echo "ERROR: Solo los conductores pueden aceptar reservas";
                exit();
            }
            $nuevo_estado = 'ACEPTADA';
            break;

        case 'reject':
            if ($user_role !== 'CHOFER') {
                echo "ERROR: Solo los conductores pueden rechazar reservas";
                exit();
            }
            $nuevo_estado = 'RECHAZADA';
            // Al rechazar, debemos liberar los espacios
            liberarEspacios($conn, $booking);
            break;

        case 'cancel':
            $nuevo_estado = 'CANCELADA';
            // Al cancelar, debemos liberar los espacios
            liberarEspacios($conn, $booking);
            break;
    }

    // Actualizar el estado de la reserva
    $stmt = $conn->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la actualización: " . $conn->error);
    }
    
    $stmt->bind_param("si", $nuevo_estado, $booking_id);
    
    if ($stmt->execute()) {
        echo "SUCCESS: Reserva " . ($action === 'accept' ? 'aceptada' : ($action === 'reject' ? 'rechazada' : 'cancelada')) . " exitosamente";
    } else {
        throw new Exception("Error al actualizar la reserva: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

$conn->close();

// Función para liberar espacios cuando se rechaza o cancela una reserva
function liberarEspacios($conn, $booking) {
    $nuevos_espacios = $booking['espacios_disponibles'] + $booking['cantidad_espacios'];
    
    $stmt = $conn->prepare("UPDATE rides SET espacios_disponibles = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $nuevos_espacios, $booking['id_ride']);
        $stmt->execute();
        $stmt->close();
    }
}
?>
