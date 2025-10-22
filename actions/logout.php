<?php
session_start();

// Destruir la sesión si existe
session_unset();
session_destroy();

// Redirigir a Login.php sin mensajes
header('Location: ../Login.php');
exit();
?>