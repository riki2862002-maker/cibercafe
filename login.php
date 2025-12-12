<?php
require_once 'config.php';

// Procesar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Procesar login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = '❌ Complete todos los campos';
    } else {
        // Buscar usuario
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Verificar credenciales
        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['email'] = $user['email'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = '❌ Email o contraseña incorrectos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Iniciar Sesión - Cibercafé Pro</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <!-- HEADER -->
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-gamepad"></i>
                </div>
                <h1>Cibercafé Pro</h1>
                <h2>Sistema de Gestión Profesional</h2>
            </div>

            <!-- ALERTA DE ERROR -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO LOGIN -->
            <form method="POST" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        placeholder="admin@cibercafe.com"
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary full-width">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </button>
            </form>

            <!-- INFO DE ACCESO -->
            <div class="login-footer">
                <div class="access-info">
                    <h4>👨‍💼 Credenciales de Prueba</h4>
                    <div class="credentials">
                        <div class="credential">
                            <strong>Admin:</strong> admin@cibercafe.com
                        </div>
                        <div class="credential">
                            <strong>Contraseña:</strong> password
                        </div>
                    </div>
                </div>
                <div class="features">
                    <small>✨ Sistema completo con reportes, impresiones y control total</small>
                </div>
            </div>
        </div>

        <!-- BACKGROUND ANIMADO -->
        <div class="login-bg-animation">
            <div class="bg-circle bg-circle-1"></div>
            <div class="bg-circle bg-circle-2"></div>
            <div class="bg-circle bg-circle-3"></div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const emailInput = document.getElementById('email');
            
            // Focus automático en email
            emailInput.focus();
            
            // Enter en email → password
            emailInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    passwordInput.focus();
                }
            });
            
            // Animación shake en error
            <?php if ($error): ?>
                document.querySelector('.login-card').style.animation = 'shake 0.5s ease-in-out';
            <?php endif; ?>
        });
    </script>
</body>
</html>
