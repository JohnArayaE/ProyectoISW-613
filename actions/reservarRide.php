<?php
session_start();
include('../common/conexion.php');

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'PASAJERO') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión como pasajero']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos del POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

$ride_id = intval($data['ride_id'] ?? 0);
$cantidad_espacios = intval($data['cantidad_espacios'] ?? 1);
$user_id = $_SESSION['user_id'];

// Validaciones
if ($ride_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de ride inválido']);
    exit();
}

if ($cantidad_espacios < 1) {
    echo json_encode(['success' => false, 'message' => 'Debes reservar al menos 1 asiento']);
    exit();
}

try {
    // Verificar que el ride existe y tiene espacios disponibles
    $sql = "SELECT r.*, v.capacidad 
            FROM rides r 
            JOIN vehiculos v ON r.id_vehiculo = v.id 
            WHERE r.id = ? AND r.estado = 'ACTIVO'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ride_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ride = $result->fetch_assoc();
    $stmt->close();

    if (!$ride) {
        echo json_encode(['success' => false, 'message' => 'El ride no existe o no está disponible']);
        exit();
    }

    // Verificar espacios disponibles
    if ($ride['espacios_disponibles'] < $cantidad_espacios) {
        echo json_encode(['success' => false, 'message' => 'No hay suficientes espacios disponibles']);
        exit();
    }

    // Verificar que no exceda la capacidad del vehículo
    $max_espacios = $ride['capacidad'] - 1; // -1 para el conductor
    if ($cantidad_espacios > $max_espacios) {
        echo json_encode(['success' => false, 'message' => 'No puedes reservar más de ' . $max_espacios . ' espacios']);
        exit();
    }

    // Verificar que el usuario no tenga ya una reserva activa para este ride
    $sql_check = "SELECT id FROM reservas WHERE id_ride = ? AND id_pasajero = ? AND estado IN ('PENDIENTE', 'ACEPTADA')";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $ride_id, $user_id);
    $stmt_check->execute();
    $existing_booking = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($existing_booking) {
        echo json_encode(['success' => false, 'message' => 'Ya tienes una reserva activa para este ride']);
        exit();
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Crear la reserva
        $sql_booking = "INSERT INTO reservas (id_ride, id_pasajero, cantidad_espacios, estado) 
                        VALUES (?, ?, ?, 'PENDIENTE')";
        $stmt_booking = $conn->prepare($sql_booking);
        $stmt_booking->bind_param("iii", $ride_id, $user_id, $cantidad_espacios);
        
        if (!$stmt_booking->execute()) {
            throw new Exception("Error al crear la reserva: " . $stmt_booking->error);
        }
        $stmt_booking->close();

        // Actualizar espacios disponibles en el ride
        $nuevos_espacios = $ride['espacios_disponibles'] - $cantidad_espacios;
        $sql_update = "UPDATE rides SET espacios_disponibles = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $nuevos_espacios, $ride_id);
        
        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar espacios: " . $stmt_update->error);
        }
        $stmt_update->close();

        // Confirmar transacción
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Reserva creada exitosamente. Espera la confirmación del conductor.'
        ]);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en reservarRide: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}

$conn->close();
?>