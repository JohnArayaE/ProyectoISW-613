<?php
// pending.php - Script corregido con nombre de tabla 'reservas'

// Incluir conexión y PHPMailer
$currentDir = dirname(__FILE__);
$parentDir = dirname($currentDir);
include($parentDir . '/common/conexion.php');
require $parentDir . '/PHPMailer/src/PHPMailer.php';
require $parentDir . '/PHPMailer/src/SMTP.php';
require $parentDir . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Configurar minutos desde argumento
$minutos = 30;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $minutos = (int)$argv[1];
}

echo "Buscando reservas pendientes con más de $minutos minutos...\n";

// CONSULTA CORREGIDA: 'reservas' en lugar de 'reserves'
$sql = "
    SELECT 
        res.id as reserve_id,
        res.fecha_creacion,
        res.cantidad_espacios,
        ride.nombre_ride,
        ride.lugar_salida,
        ride.lugar_llegada,
        ride.hora,
        ride.dia_semana,
        usr.correo as email_chofer,
        usr.nombre as nombre_chofer,
        TIMESTAMPDIFF(MINUTE, res.fecha_creacion, NOW()) as minutos_pendiente
    FROM reservas res  -- ✅ CORREGIDO: 'reservas' no 'reserves'
    INNER JOIN rides ride ON res.id_ride = ride.id
    INNER JOIN usuarios usr ON ride.id_chofer = usr.id
    WHERE res.estado = 'PENDIENTE'
    AND TIMESTAMPDIFF(MINUTE, res.fecha_creacion, NOW()) >= $minutos
    ORDER BY res.fecha_creacion ASC
";

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

$total = mysqli_num_rows($result);
echo "Encontradas $total reservas pendientes.\n";

$enviados = 0;
while ($reserva = mysqli_fetch_assoc($result)) {
    if (enviarNotificacion($reserva, $minutos)) {
        $enviados++;
        echo "Notificación enviada a: {$reserva['email_chofer']}\n";
    } else {
        echo "Error enviando notificación a: {$reserva['email_chofer']}\n";
    }
}

echo "Proceso completado. Se enviaron $enviados notificaciones.\n";

mysqli_close($conn);

function enviarNotificacion($reserva, $minutos) {
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
        $mail->addAddress($reserva['email_chofer'], $reserva['nombre_chofer']);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Recordatorio: Solicitud de reserva pendiente';

        $hora_formateada = date("g:i A", strtotime($reserva['hora']));
        $url_panel = 'http://localhost:8080/ProyectoISW/ProyectoISW-613/bookings.php';

        $mail->Body = "
        <html>
        <body>
            <h2>¡Hola {$reserva['nombre_chofer']}!</h2>
            <p>Tienes una solicitud de reserva pendiente por más de $minutos minutos.</p>
            <h3>Detalles de la reserva:</h3>
            <ul>
                <li><strong>Viaje:</strong> {$reserva['nombre_ride']}</li>
                <li><strong>Ruta:</strong> {$reserva['lugar_salida']} → {$reserva['lugar_llegada']}</li>
                <li><strong>Horario:</strong> {$reserva['dia_semana']} a las {$hora_formateada}</li>
                <li><strong>Espacios solicitados:</strong> {$reserva['cantidad_espacios']}</li>
                <li><strong>Solicitado hace:</strong> {$reserva['minutos_pendiente']} minutos</li>
            </ul>
            <p>Por favor, revisa tu panel de control para aceptar o rechazar esta solicitud.</p>
            <p><a href='$url_panel'>Gestionar reservas</a></p>
        </body>
        </html>
        ";

        $mail->AltBody = "Hola {$reserva['nombre_chofer']},\n\nTienes una solicitud de reserva pendiente por $minutos minutos.\n\nDetalles:\n- Viaje: {$reserva['nombre_ride']}\n- Ruta: {$reserva['lugar_salida']} → {$reserva['lugar_llegada']}\n- Horario: {$reserva['dia_semana']} a las {$hora_formateada}\n- Espacios: {$reserva['cantidad_espacios']}\n\nGestiona tu reserva en: $url_panel";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}
?>