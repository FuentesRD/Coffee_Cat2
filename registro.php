<?php
require 'conexion.php'; // Contiene session_start() y $pdo

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_usuario = trim($_POST['nombre_usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $fecha_nac = $_POST['fecha_nac']; // Puede estar vacío si no es obligatorio
    $num_tarjeta = trim($_POST['num_tarjeta']); // Puede estar vacío
    $codigo_post = trim($_POST['codigo_post']); // Puede estar vacío

    // Validaciones básicas
    if (empty($nombre_usuario) || empty($email) || empty($password) || empty($password_confirm)) {
        $error_msg = "Los campos nombre, email y contraseña son obligatorios.";
    } elseif ($password !== $password_confirm) {
        $error_msg = "Las contraseñas no coinciden.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "El formato del correo electrónico no es válido.";
    } elseif (strlen($password) < 8) {
        $error_msg = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        try {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error_msg = "Este correo electrónico ya está registrado. Intenta iniciar sesión.";
            } else {
                // Insertar nuevo usuario
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO usuario (nombre_usuario, email, contrasena, fecha_nac, num_tarjeta, codigo_post) 
                        VALUES (:nombre_usuario, :email, :contrasena, :fecha_nac, :num_tarjeta, :codigo_post)";
                $stmt = $pdo->prepare($sql);
                
                $stmt->bindParam(':nombre_usuario', $nombre_usuario);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':contrasena', $password_hash);
                
                // Para campos opcionales, pasar NULL si están vacíos
                $fecha_nac_param = !empty($fecha_nac) ? $fecha_nac : null;
                $num_tarjeta_param = !empty($num_tarjeta) ? $num_tarjeta : null;
                $codigo_post_param = !empty($codigo_post) ? $codigo_post : null;

                $stmt->bindParam(':fecha_nac', $fecha_nac_param);
                $stmt->bindParam(':num_tarjeta', $num_tarjeta_param);
                $stmt->bindParam(':codigo_post', $codigo_post_param);
                
                if ($stmt->execute()) {
                    // Redirigir a login con mensaje de éxito
                    $_SESSION['success_message'] = "¡Registro exitoso! Ahora puedes iniciar sesión.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error_msg = "Error al registrar el usuario. Inténtalo de nuevo.";
                }
            }
        } catch(PDOException $e) {
            error_log("Error en registro.php: " . $e->getMessage());
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
    <title>Registro - Coffee Cat</title>
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
                            <li><a class="dropdown-item" href="accesorio.php"><i class="bi bi-funnel-fill me-2"></i>Accesorios</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión</a>
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
                            <div class="text-center mb-4">
                                <i class="bi bi-cup-hot-fill display-4 text-secondary"></i>
                                <h2 class="mt-3 mb-0" style="font-family: 'Playfair Display', serif;">Coffee Cat</h2>
                                <p class="text-muted">Crea tu cuenta</p>
                            </div>

                            <?php if (!empty($error_msg)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_msg; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="registro.php" novalidate>
                                <div class="mb-3">
                                    <label for="nombre_usuario" class="form-label coffee-label"><i class="bi bi-person-badge me-2"></i>Nombre de Usuario</label>
                                    <input type="text" name="nombre_usuario" id="nombre_usuario" class="form-control coffee-input" value="<?php echo isset($_POST['nombre_usuario']) ? htmlspecialchars($_POST['nombre_usuario']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label coffee-label"><i class="bi bi-envelope me-2"></i>Correo Electrónico</label>
                                    <input type="email" name="email" id="email" class="form-control coffee-input" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label coffee-label"><i class="bi bi-lock me-2"></i>Contraseña</label>
                                    <input type="password" name="password" id="password" class="form-control coffee-input" required>
                                    <small class="form-text text-muted">Mínimo 8 caracteres.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirm" class="form-label coffee-label"><i class="bi bi-lock-fill me-2"></i>Confirmar Contraseña</label>
                                    <input type="password" name="password_confirm" id="password_confirm" class="form-control coffee-input" required>
                                </div>
                                
                                <hr class="my-4">
                                <p class="text-muted small">Información adicional (opcional):</p>

                                <div class="mb-3">
                                    <label for="fecha_nac" class="form-label coffee-label"><i class="bi bi-calendar-event me-2"></i>Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nac" id="fecha_nac" class="form-control coffee-input" value="<?php echo isset($_POST['fecha_nac']) ? htmlspecialchars($_POST['fecha_nac']) : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="num_tarjeta" class="form-label coffee-label"><i class="bi bi-credit-card me-2"></i>Número de Tarjeta</label>
                                    <input type="text" name="num_tarjeta" id="num_tarjeta" class="form-control coffee-input" pattern="[0-9]{13,16}" title="Número de tarjeta de 13 a 16 dígitos" value="<?php echo isset($_POST['num_tarjeta']) ? htmlspecialchars($_POST['num_tarjeta']) : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="codigo_post" class="form-label coffee-label"><i class="bi bi-mailbox2 me-2"></i>Código Postal</label>
                                    <input type="text" name="codigo_post" id="codigo_post" class="form-control coffee-input" pattern="[0-9]{5}" title="Código postal de 5 dígitos" value="<?php echo isset($_POST['codigo_post']) ? htmlspecialchars($_POST['codigo_post']) : ''; ?>">
                                </div>

                                <button type="submit" class="btn btn-coffee w-100 py-2 mb-3 mt-3">
                                    <i class="bi bi-person-plus-fill me-2"></i>Crear Cuenta
                                </button>

                                <div class="text-center">
                                    <p class="text-muted mb-0">¿Ya tienes una cuenta?
                                        <a href="login.php" class="coffee-link text-decoration-none">Inicia Sesión</a>
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