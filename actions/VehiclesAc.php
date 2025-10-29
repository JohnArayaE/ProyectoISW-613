<?php
session_start();
include('../common/conexion.php');

// Verificar autenticación y rol de chofer
function verifyDriverAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'CHOFER') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    return $_SESSION['user_id'];
}

// Procesar la acción basada en el parámetro 'action'
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'save':
        saveVehicle();
        break;
    case 'delete':
        deleteVehicle();
        break;
    case 'get':
        getVehicle();
        break;
    case 'list':
        listVehicles();
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Guardar vehículo (crear o editar)
function saveVehicle() {
    $user_id = verifyDriverAuth();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $veh_id = $_POST['vehId'] ?? '';
        $placa = mysqli_real_escape_string($GLOBALS['conn'], $_POST['plate']);
        $color = mysqli_real_escape_string($GLOBALS['conn'], $_POST['color']);
        $marca = mysqli_real_escape_string($GLOBALS['conn'], $_POST['brand']);
        $modelo = mysqli_real_escape_string($GLOBALS['conn'], $_POST['model']);
        $anio = mysqli_real_escape_string($GLOBALS['conn'], $_POST['year']);
        $capacidad = mysqli_real_escape_string($GLOBALS['conn'], $_POST['seats']);

        // Manejo de foto
        $foto_vehiculo = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $foto_nombre = uniqid() . '_' . basename($_FILES['photo']['name']);
            $foto_vehiculo = 'uploads/vehicles/' . $foto_nombre;
            
            if (!file_exists('../uploads/vehicles')) {
                mkdir('../uploads/vehicles', 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $foto_vehiculo)) {
                sendResponse(false, 'Error uploading photo');
            }
        }

        if (empty($veh_id)) {
            // Crear nuevo vehículo
            $sql = "INSERT INTO vehiculos (id_chofer, placa, color, marca, modelo, anio, capacidad, foto_vehículo) 
                    VALUES ('$user_id', '$placa', '$color', '$marca', '$modelo', '$anio', '$capacidad', " . 
                    ($foto_vehiculo ? "'$foto_vehiculo'" : "NULL") . ")";
        } else {
            // Editar vehículo existente
            if ($foto_vehiculo) {
                $sql = "UPDATE vehiculos SET placa='$placa', color='$color', marca='$marca', modelo='$modelo', 
                        anio='$anio', capacidad='$capacidad', foto_vehículo='$foto_vehiculo' 
                        WHERE id='$veh_id' AND id_chofer='$user_id'";
            } else {
                $sql = "UPDATE vehiculos SET placa='$placa', color='$color', marca='$marca', modelo='$modelo', 
                        anio='$anio', capacidad='$capacidad' 
                        WHERE id='$veh_id' AND id_chofer='$user_id'";
            }
        }

        if (mysqli_query($GLOBALS['conn'], $sql)) {
            sendResponse(true, empty($veh_id) ? 'Vehicle created successfully' : 'Vehicle updated successfully');
        } else {
            sendResponse(false, 'Database error: ' . mysqli_error($GLOBALS['conn']));
        }
    } else {
        sendResponse(false, 'Invalid request method');
    }
}

// Eliminar vehículo
function deleteVehicle() {
    $user_id = verifyDriverAuth();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $veh_id = $_POST['vehId'] ?? '';
        
        if (empty($veh_id)) {
            sendResponse(false, 'Vehicle ID is required');
        }
        
        $sql = "DELETE FROM vehiculos WHERE id='$veh_id' AND id_chofer='$user_id'";
        
        if (mysqli_query($GLOBALS['conn'], $sql)) {
            sendResponse(true, 'Vehicle deleted successfully');
        } else {
            sendResponse(false, 'Database error: ' . mysqli_error($GLOBALS['conn']));
        }
    } else {
        sendResponse(false, 'Invalid request method');
    }
}

// Obtener un vehículo específico
function getVehicle() {
    $user_id = verifyDriverAuth();
    
    if (isset($_GET['vehId'])) {
        $veh_id = $_GET['vehId'];
        
        $sql = "SELECT * FROM vehiculos WHERE id = '$veh_id' AND id_chofer = '$user_id'";
        $result = mysqli_query($GLOBALS['conn'], $sql);
        
        if (mysqli_num_rows($result) > 0) {
            $vehicle = mysqli_fetch_assoc($result);
            sendResponse(true, 'Vehicle found', $vehicle);
        } else {
            sendResponse(false, 'Vehicle not found');
        }
    } else {
        sendResponse(false, 'Vehicle ID is required');
    }
}

// Listar todos los vehículos del chofer
function listVehicles() {
    $user_id = verifyDriverAuth();
    
    $sql = "SELECT * FROM vehiculos WHERE id_chofer = '$user_id' ORDER BY id DESC";
    $result = mysqli_query($GLOBALS['conn'], $sql);
    $vehicles = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    sendResponse(true, 'Vehicles retrieved', $vehicles);
}

// Función helper para enviar respuestas JSON
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}
?>