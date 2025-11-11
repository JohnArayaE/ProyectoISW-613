<?php
include('../common/conexion.php');

// Incluir PHPMailer
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $cedula = $_POST['cedula'];
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];
    $telefono = $_POST['telefono'];
    $rol = $_POST['rol'];

    // Verificar contraseñas
    $contrasena_repetir = $_POST['contrasena_repetir'];
    if ($contrasena !== $contrasena_repetir) {
        header('Location: ../Registration.php?error=password_mismatch');
        exit();
    }

    // Hash de contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_BCRYPT);

       // Manejo de foto
    $foto_ruta = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $foto_nombre = uniqid() . '_' . basename($_FILES['photo']['name']);
        $foto_ruta = 'uploads/' . $foto_nombre;  
        
        if (!file_exists('../uploads')) {
            mkdir('../uploads', 0777, true);
        }
        
    
        move_uploaded_file($_FILES['photo']['tmp_name'], '../' . $foto_ruta);
    }

    // Insertar usuario
    $sql = "INSERT INTO usuarios (nombre, apellido, fecha_nacimiento, cedula, correo, telefono, foto_ruta, contrasena_hash, rol, estado) 
            VALUES ('$nombre', '$apellido', '$fecha_nacimiento', '$cedula', '$correo', '$telefono', '$foto_ruta', '$contrasena_hash', '$rol', 'PENDIENTE')";

    if (mysqli_query($conn, $sql)) {
        $user_id = mysqli_insert_id($conn);
        
        // Generar token
        $token = bin2hex(random_bytes(32));
        $expira_en = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Insertar token
        $sql_token = "INSERT INTO tokens_activacion (id_usuario, token, expira_en) VALUES ('$user_id', '$token', '$expira_en')";
        
        if (mysqli_query($conn, $sql_token)) {
            // Enviar correo
            $email_result = enviarCorreoVerificacion($correo, $nombre, $token);
            
            if ($email_result) {
                header('Location: ../Login.php?success=Te hemos enviado un correo de verificación');
            } else {
                // Si falla el correo, mostrar el enlace en pantalla
                mostrarEnlaceActivacion($nombre, $token);
                exit();
            }
        } else {
            header('Location: ../Registration.php?error=Error al crear token');
        }
    } else {
        header('Location: ../Registration.php?error=Error en el registro');
    }

    mysqli_close($conn);
}

function enviarCorreoVerificacion($correo_destino, $nombre, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'correosproyecto33@gmail.com';
        $mail->Password = 'swvk maxw csrj irqb'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        // Configuración SSL
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Remitente y destinatario
        $mail->setFrom('correosproyecto33@gmail.com', 'Aventones');
        $mail->addAddress($correo_destino, $nombre);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu cuenta - Aventones';
        
        $url_activacion = "http://localhost:8080/ProyectoISW/ProyectoISW-613/actions/activateAccount.php?token=" . $token;
        
        $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #333;'>¡Hola $nombre! 👋</h2>
                <p>Gracias por registrarte en <strong>Aventones</strong>. Estamos emocionados de tenerte con nosotros.</p>
                
                <p>Para activar tu cuenta y comenzar a usar nuestros servicios, haz clic en el siguiente botón:</p>
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$url_activacion' style='background: #4CAF50; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block;'>
                        ✅ Activar Mi Cuenta
                    </a>
                </p>
            </div>
        </body>
        </html>
        ";
        $mail->AltBody = "Hola $nombre,\n\nGracias por registrarte en Aventones. Para activar tu cuenta, visita este enlace:\n\n$url_activacion\n\nEste enlace expirará en 24 horas.\n\nSi no te registraste, puedes ignorar este mensaje.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}
?>