<?php
session_start();
if(isset($_SESSION['user_id'])) {
    // Redirigir según el rol
    if ($_SESSION['user_rol'] === 'CHOFER') {
        header('Location: vehicles.php');
    } 
    elseif($_SESSION['user_rol'] === 'ADMIN') {
        header('Location: AdminDashboard.php');
    }
    else {
        header('Location: index.php');
    }
    exit();
}
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    $success_message = '<div class="alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
}

if (isset($_GET['error'])) {
    $error_message = '<div class="alert-error">' . htmlspecialchars($_GET['error']) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <?php echo $success_message; ?>
    <?php echo $error_message; ?>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="img/logo.png" alt="logo_principal">
            </div>
            <h1 class="login-title">AVENTONES</h1>
            <form action="actions/LoginStart.php" method="post">
                <label for="correo">EMAIL</label>
                <input type="email" id="correo" name="correo" required>
                
                <label for="contrasena">PASSWORD</label>
                <input type="password" id="contrasena" name="contrasena" required>
                
                <p class="register-link">  
                    Not a user? <a href="Registration.php">Register now</a>
                </p>
                <p class="register-link">
                    Are you a driver? <a href="RegisterDriver.php">Register as driver</a>
                </p>
                <button type="submit">LOGIN</button>    
            </form>
        </div>
    </div>
</body>
</html>