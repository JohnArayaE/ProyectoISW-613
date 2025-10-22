<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header('Location: SearhRides.php');
    exit();
}
$success_message = '';
if (isset($_GET['success'])) {
    $success_message = '<div class="alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
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
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="img/logo.png" alt="logo_principal">
            </div>
            <h1 class="login-title">AVENTONES</h1>
            <form action="actions/login.php" method="post">
                <label for="username">USERNAME</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" required>
                <p class="register-link">  
                    Not a user? <a href="Registration.php">Register now</a>
                </p>
                <button type="submit">LOGIN</button>    
            </form>
        </div>
    </div>
</body>
</html>