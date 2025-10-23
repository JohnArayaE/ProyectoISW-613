<?php
session_start();
include('../common/conexion.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    // Buscar usuario por correo
    $sql = "SELECT * FROM usuarios WHERE correo = '$correo'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $usuario = mysqli_fetch_assoc($result);
        
        // Verificar contraseña
        if (password_verify($contrasena, $usuario['contrasena_hash'])) {
            
            // Verificar si la cuenta está activa
            if ($usuario['estado'] === 'ACTIVO') {
                // Iniciar sesión
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_nombre'] = $usuario['nombre'];
                $_SESSION['user_apellido'] = $usuario['apellido'];
                $_SESSION['user_correo'] = $usuario['correo'];
                $_SESSION['user_rol'] = $usuario['rol'];
                $_SESSION['user_foto'] = $usuario['foto_ruta'];  
                
                // DEBUG TEMPORAL
                error_log("LOGIN - Usuario: " . $usuario['correo'] . " | Rol: " . $usuario['rol']);
                
                // CORREGIR: Agregar condición para ADMIN
                if ($usuario['rol'] === 'CHOFER') {
                    header('Location: ../vehicles.php');
                } 
                elseif ($usuario['rol'] === 'ADMIN') {
                    header('Location: ../AdminDashboard.php');
                }
                else {
                    header('Location: ../index.php');
                }
                exit();
                
            } else {
                header('Location: ../Login.php?error=Account not activated. Please check your email.');
                exit();
            }
            
        } else {
            header('Location: ../Login.php?error=Incorrect password');
            exit();
        }
    } else {
        header('Location: ../Login.php?error=User not found');
        exit();
    }
    
    mysqli_close($conn);
} else {
    header('Location: ../Login.php');
    exit();
}
?>