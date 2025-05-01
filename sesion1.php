<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['contrasena'])) {
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_email'] = $user['email'];
        
        // Recordar sesión por 30 días
        if (!empty($_POST['remember'])) {
            $cookie_value = json_encode([
                'user_id' => $user['id_usuario'],
                'token' => bin2hex(random_bytes(16))
            ]);
            
            setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/");
        }
        
        header("Location: cuenta.php");
        exit();
    }

    if ($user && password_verify($password, $user['contrasena'])) {
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_email'] = $user['email'];
        header("Location: cuenta.php");
        exit();
    } else {
        $error = "Datos incorrectos. Por favor, verifica tu correo electrónico y contraseña.";
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
    <link rel="stylesheet" href="login.css">

</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cup-hot-fill"></i> Coffee Cat
            </a>
            
            <div class="d-flex align-items-center order-lg-3 ms-auto">
                <!-- Iconos siempre visibles -->
                <a href="#" class="nav-link nav-icon">
                    <i class="bi bi-person"></i>
                    <span class="d-none d-lg-inline">Cuenta</span>
                </a>
                <a href="#" class="nav-link nav-icon">
                    <i class="bi bi-cart3"></i>
                    <span class="badge rounded-pill">3</span>
                    <span class="d-none d-lg-inline">Carrito</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <div class="collapse navbar-collapse order-lg-2" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house-door me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shop-window me-1"></i>Productos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-cup-straw me-2"></i>Café en Grano
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-mortarboard-fill me-2"></i>Café Molido
                            </a></li>
                            <li><a class="dropdown-item" href="#">
                                <i class="bi bi-funnel-fill me-2"></i>Accesorios
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-info-circle me-1"></i>Nosotros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-chat-dots me-1"></i>Contacto
                        </a>
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

                        <!-- Formulario -->
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label coffee-label">
                                    <i class="bi bi-envelope me-2"></i>Correo electrónico
                                </label>
                                <input type="email" 
                                       name="email" 
                                       class="form-control coffee-input" 
                                       placeholder="ejemplo@dominio.com"
                                       required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label coffee-label">
                                    <i class="bi bi-lock me-2"></i>Contraseña
                                </label>
                                <input type="password" 
                                       name="password" 
                                       class="form-control coffee-input" 
                                       placeholder="••••••••"
                                       required>
                            </div>

                            <div class="mb-4 d-flex justify-content-between">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="remember" 
                                           id="remember">
                                    <label class="form-check-label coffee-label" for="remember">
                                        Recordar sesión
                                    </label>
                                </div>
                                <a href="#" class="text-decoration-none coffee-link">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>

                            <button type="submit" 
                                    class="btn btn-coffee w-100 py-2 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                            </button>

                            <div class="text-center">
                                <p class="text-muted mb-0">¿No tienes cuenta?
                                    <a href="registro.php" class="coffee-link text-decoration-none">
                                        Regístrate aquí
                                    </a>
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
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script src="index.js"></script>
</body>
</html>