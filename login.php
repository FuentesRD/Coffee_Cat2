<?php
require 'conexion.php'; // Contiene session_start() y $pdo

$error_msg = '';
$success_msg = ''; // Para mensajes de registro exitoso

// Verificar si hay un mensaje de éxito desde el registro
if (isset($_SESSION['success_message'])) {
    $success_msg = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Limpiar el mensaje para que no se muestre de nuevo
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Limpiar y validar email
    $email_input = trim($_POST['email']); // Añadido trim()
    $email = filter_var($email_input, FILTER_VALIDATE_EMAIL);
    
    $password = trim($_POST['password']); // Añadido trim()
    $remember_me = isset($_POST['remember']);

    if (!$email) {
        $error_msg = "Por favor, introduce un correo electrónico válido.";
    } elseif (empty($password)) {
        $error_msg = "Por favor, introduce tu contraseña.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = :email");
            // Es común almacenar y buscar emails en minúsculas para evitar problemas de sensibilidad de mayúsculas/minúsculas.
            // Si hiciste esto en el registro, también hazlo aquí:
            // $email_to_search = strtolower($email);
            // $stmt->bindParam(':email', $email_to_search);
            $stmt->bindParam(':email', $email); // Usando el email validado
            $stmt->execute();
            $user = $stmt->fetch();

            // Para depuración (descomentar SOLO en desarrollo si el problema persiste):
            if (!$user) {
                error_log("Login FAILED: Email no encontrado en BD - " . $email);
            } else {
                error_log("Login INFO: Email encontrado - " . $email . " | Hash en BD: " . $user['contrasena']);
                if (password_verify($password, $user['contrasena'])) {
                    error_log("Login SUCCESS: password_verify tuvo éxito para " . $email);
                } else {
                    error_log("Login FAILED: password_verify falló para " . $email . ". Contraseña ingresada (longitud): " . strlen($password));
                }
            }

            if ($user && password_verify($password, $user['contrasena'])) {
                // Inicio de sesión exitoso
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_nombre'] = $user['nombre_usuario'];
                $_SESSION['user_email'] = $user['email'];
                
                if ($remember_me) {
                    $token_recordarme = bin2hex(random_bytes(32));
                    $cookie_value = json_encode([
                        'user_id' => $user['id_usuario'],
                        'token' => $token_recordarme 
                    ]);
                    
                    // En un sistema real, $token_recordarme (o su hash) se almacenaría en la tabla 'usuario'
                    // y se verificaría al leer la cookie.
                    // Ejemplo:
                    // $hashed_token = password_hash($token_recordarme, PASSWORD_DEFAULT);
                    // $stmt_update_token = $pdo->prepare("UPDATE usuario SET remember_token = :token WHERE id_usuario = :id");
                    // $stmt_update_token->execute([':token' => $hashed_token, ':id' => $user['id_usuario']]);

                    setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true); // 30 días, HttpOnly, Secure si HTTPS
                } else {
                    if (isset($_COOKIE['remember_me'])) {
                        setcookie('remember_me', '', time() - 3600, "/");
                    }
                }
                
                header("Location: cuenta.php");
                exit();
            } else {
                $error_msg = "Correo electrónico o contraseña incorrectos. Por favor, verifica tus datos.";
            }
        } catch (PDOException $e) {
            error_log("Error en login.php: " . $e->getMessage());
            $error_msg = "Ocurrió un error en el servidor. Por favor, inténtalo más tarde.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Coffee Cat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="registro.css">

</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cup-hot-fill"></i> Coffee Cat
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                     <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop-window me-1"></i>Productos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="grano.php"><i class="bi bi-cup-straw me-2"></i>Café en Grano</a></li>
                            <li><a class="dropdown-item" href="molido.php"><i class="bi bi-cup"></i>Café Molido</a></li>
                            <li><a class="dropdown-item" href="accesorios.php"><i class="bi bi-funnel-fill me-2"></i>Accesorios</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registro.php"><i class="bi bi-person-plus-fill me-1"></i>Registrarse</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="auth-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                    <div class="card auth-card shadow-lg">
                        <div class="card-body p-4 p-sm-5">
                            <!-- Logo -->
                            <div class="text-center mb-5">
                                <i class="bi bi-cup-hot-fill display-4 text-secondary"></i>
                                <h2 class="mt-3 mb-0" style="font-family: 'Playfair Display', serif;">Coffee Cat</h2>
                                <p class="text-muted">Inicio de Sesión</p>
                            </div>

                            <?php if (!empty($error_msg)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error_msg); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($success_msg)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success_msg); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Formulario -->
                            <form method="POST" action="login.php">
                                    <div class="mb-3">
                                        <label for="email" class="form-label coffee-label"><i class="bi bi-envelope me-2"></i>Correo Electrónico</label>
                                        <input type="email" name="email" id="email" class="form-control coffee-input" placeholder="ejemplo@dominio.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                    </div>

                                <div class="mb-3">
                                        <label for="password" class="form-label coffee-label"><i class="bi bi-lock me-2"></i>Contraseña</label>
                                        <input type="password" name="password" id="password" class="form-control coffee-input" placeholder="••••••••" required>
                                    </div>

                                <div class="mb-4 d-flex justify-content-between">
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                                        <label class="form-check-label coffee-label-check" for="remember">Recordarme</label>
                                    </div>

                                    <a href="#" class="text-decoration-none coffee-link">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                </div>

                                <button type="submit" class="btn btn-coffee w-100 py-2 mb-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                                </button>

                                <div class="text-center">
                                    <p class="text-muted mb-0">¿No tienes cuenta?
                                        <a href="registro.php" class="coffee-link text-decoration-none">Regístrate aquí</a>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-geo-alt me-2"></i>Ubicación</h5>
                    <p class="mb-0">Av. Café 123, Ciudad de México</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-clock me-2"></i>Horario</h5>
                    <p>Lun-Vie: 9 AM - 7 PM</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="bi bi-telephone me-2"></i>Contacto</h5>
                    <p class="mb-0">contacto@cafedelmundo.com</p>
                    <p>Tel: +52 55 1234 5678</p>
                </div>
            </div>
            <div class="text-center pt-3 border-top">
                <p class="mb-0">
                    Síguenos:
                    <a href="#" class="text-white mx-2"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white mx-2"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white mx-2"><i class="bi bi-whatsapp"></i></a>
                </p>
            </div>
        </div>
    </footer>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>