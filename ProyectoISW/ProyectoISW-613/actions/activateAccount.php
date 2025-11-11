<?php
include('../common/conexion.php');

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verificar token
    $sql = "SELECT ta.*, u.id, u.nombre, u.correo 
            FROM tokens_activacion ta 
            JOIN usuarios u ON ta.id_usuario = u.id 
            WHERE ta.token = '$token' AND ta.usado_en IS NULL AND ta.expira_en > NOW()";
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $user_id = $data['id'];
        
        // Activar usuario
        $sql_update = "UPDATE usuarios SET estado = 'ACTIVO' WHERE id = '$user_id'";
        $sql_token = "UPDATE tokens_activacion SET usado_en = NOW() WHERE token = '$token'";
        
        if (mysqli_query($conn, $sql_update)) {
            mysqli_query($conn, $sql_token);
            header('Location: ../Login.php?success=Cuenta activada exitosamente');
        } else {
            header('Location: ../Login.php?error=Error al activar cuenta');
        }
    } else {
        header('Location: ../Login.php?error=Token inválido o expirado');
    }
    
    mysqli_close($conn);
} else {
    header('Location: ../Login.php?error=No se proporcionó token');
}
?>