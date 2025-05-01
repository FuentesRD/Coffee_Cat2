<?php
include 'conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validación básica
    if(empty($username) || empty($email) || empty($password)) {
        $error = "Todos los campos son requeridos";
    } else {
        try {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
            $stmt->execute([$email]);
            
            if($stmt->rowCount() > 0) {
                $error = "Este email ya está registrado";
            } else {
                // Insertar nuevo usuario
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuario (nombre_usuario, email, contrasena) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $passwordHash]);
                
                header("Location: sesion1.php?registro=exito");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Error al registrar: " . $e->getMessage();
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
                <div class="col-md-6 col-lg-4">
                    <div class="card auth-card shadow-lg">
                        <div class="card-body">
                            <h2 class="text-center mb-4">
                                <i class="bi bi-cup-hot-fill"></i><br>
                                Registro Completo
                            </h2>
                            
                            <?php if($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label>Nombre completo</label>
                                    <input type="text" name="username" class="form-control" 
                                        maxlength="50" required>
                                </div>

                                <div class="mb-3">
                                    <label>Correo electrónico</label>
                                    <input type="email" name="email" class="form-control" 
                                        maxlength="50" required>
                                </div>

                                <div class="mb-3">
                                    <label>Contraseña</label>
                                    <input type="password" name="password" class="form-control" 
                                        maxlength="50" required>
                                </div>

                                <div class="mb-3">
                                    <label>Fecha de nacimiento</label>
                                    <input type="date" name="fecha_nac" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label>Número de tarjeta</label>
                                    <input type="number" name="num_tarjeta" class="form-control" 
                                        pattern="[0-9]{13,16}" required>
                                    <small class="text-muted">Sin espacios ni guiones</small>
                                </div>

                                <div class="mb-3">
                                    <label>Código Postal</label>
                                    <input type="number" name="codigo_post" class="form-control" 
                                        required>
                                </div>

                                <button type="submit" class="btn btn-coffee w-100 mt-3">
                                    Registrarse
                                </button>
                                
                                <div class="text-center mt-3">
                                    <small>
                                        ¿Ya tienes cuenta? 
                                        <a href="login.php" class="coffee-link">Iniciar Sesión</a>
                                    </small>
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