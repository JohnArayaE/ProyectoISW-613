<?php
session_start();
include('../common/conexion.php');

header('Content-Type: application/json');

if (!isset($_GET['ride_id']) || empty($_GET['ride_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de ride no proporcionado']);
    exit();
}

$ride_id = intval($_GET['ride_id']);

try {
    $sql = "SELECT r.*, v.marca, v.modelo, v.anio, v.capacidad 
            FROM rides r 
            JOIN vehiculos v ON r.id_vehiculo = v.id 
            WHERE r.id = ? AND r.estado = 'ACTIVO'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ride_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ride = $result->fetch_assoc();
    $stmt->close();

    if ($ride) {
        // Convertir tipos numéricos
        $ride['costo'] = floatval($ride['costo']);
        $ride['espacios_disponibles'] = intval($ride['espacios_disponibles']);
        $ride['capacidad'] = intval($ride['capacidad']);
        
        echo json_encode(['success' => true, 'ride' => $ride]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ride no encontrado']);
    }
} catch (Exception $e) {
    error_log("Error en ObtenerRide: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

$conn->close();
?>