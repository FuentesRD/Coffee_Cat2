<?php
require 'conexion.php'; // Contiene session_start() y $pdo

// Proteger la página: si el usuario no está logueado, redirigir a login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_info = null;
$error_msg = '';
$success_msg = '';

// Obtener información del usuario
try {
    $stmt = $pdo->prepare("SELECT id_usuario, nombre_usuario, email, fecha_nac, num_tarjeta, codigo_post FROM usuario WHERE id_usuario = :id_usuario");
    $stmt->bindParam(':id_usuario', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user_info = $stmt->fetch();

    if (!$user_info) {
        // Esto no debería ocurrir si user_id en sesión es válido, pero es una buena verificación
        error_log("Error en cuenta.php: No se encontró usuario con ID: " . $user_id);
        session_unset();
        session_destroy();
        setcookie('remember_me', '', time() - 3600, "/");
        header("Location: login.php?error=session_expired");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error en cuenta.php al obtener datos: " . $e->getMessage());
    $error_msg = "No se pudo cargar la información de tu cuenta. Inténtalo más tarde.";
}

// Lógica para actualizar información (ejemplo básico)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_info'])) {
    $nombre_usuario_upd = trim($_POST['nombre_usuario']);
    $email_upd = trim($_POST['email']); // Considerar si se permite cambiar email y sus implicaciones
    $fecha_nac_upd = $_POST['fecha_nac'];
    $num_tarjeta_upd = trim($_POST['num_tarjeta']);
    $codigo_post_upd = trim($_POST['codigo_post']);

    // Validaciones (similares a las de registro)
    if (empty($nombre_usuario_upd) || empty($email_upd)) {
        $error_msg = "El nombre y el correo electrónico son obligatorios.";
    } elseif (!filter_var($email_upd, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "El formato del correo electrónico no es válido.";
    } else {
        try {
            // Verificar si el nuevo email ya existe (si es diferente al actual y se permite cambiarlo)
            if ($email_upd !== $user_info['email']) {
                $stmt_check_email = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = :email AND id_usuario <> :id_usuario");
                $stmt_check_email->execute([':email' => $email_upd, ':id_usuario' => $user_id]);
                if ($stmt_check_email->rowCount() > 0) {
                    $error_msg = "El nuevo correo electrónico ya está en uso por otra cuenta.";
                }
            }

            if (empty($error_msg)) { // Si no hay errores de validación de email
                $sql_update = "UPDATE usuario SET 
                                nombre_usuario = :nombre_usuario, 
                                email = :email, 
                                fecha_nac = :fecha_nac, 
                                num_tarjeta = :num_tarjeta, 
                                codigo_post = :codigo_post
                               WHERE id_usuario = :id_usuario";
                $stmt_update = $pdo->prepare($sql_update);

                $fecha_nac_param_upd = !empty($fecha_nac_upd) ? $fecha_nac_upd : null;
                $num_tarjeta_param_upd = !empty($num_tarjeta_upd) ? $num_tarjeta_upd : null;
                $codigo_post_param_upd = !empty($codigo_post_upd) ? $codigo_post_upd : null;

                $stmt_update->execute([
                    ':nombre_usuario' => $nombre_usuario_upd,
                    ':email' => $email_upd,
                    ':fecha_nac' => $fecha_nac_param_upd,
                    ':num_tarjeta' => $num_tarjeta_param_upd,
                    ':codigo_post' => $codigo_post_param_upd,
                    ':id_usuario' => $user_id
                ]);

                $success_msg = "¡Tu información ha sido actualizada!";
                // Actualizar la información en la sesión y recargar los datos del usuario
                $_SESSION['user_nombre'] = $nombre_usuario_upd;
                $_SESSION['user_email'] = $email_upd;
                $stmt->execute(); // Re-ejecutar la consulta para obtener los datos actualizados
                $user_info = $stmt->fetch();
            }

        } catch (PDOException $e) {
            error_log("Error en cuenta.php al actualizar datos: " . $e->getMessage());
            $error_msg = "No se pudo actualizar tu información. Inténtalo más tarde.";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - Coffee Cat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="cuenta.css">
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
                            <li><a class="dropdown-item" href="grano.php">Café en Grano</a></li>
                            <li><a class="dropdown-item" href="molido.php">Café Molido</a></li>
                            <li><a class="dropdown-item" href="accesorios.php">Accesorios</a></li>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="cuenta.php"><i class="bi bi-person-circle me-1"></i>Mi Cuenta</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registro.php"><i class="bi bi-person-plus-fill me-1"></i>Registrarse</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-cart3 me-1"></i>Carrito 
                             <span class="badge rounded-pill bg-danger">0</span> </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="page-cuenta pt-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="list-group">
                        <a href="cuenta.php" class="list-group-item list-group-item-action active" aria-current="true">
                            <i class="bi bi-person-fill me-2"></i>Perfil
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h4>Bienvenido, <?php echo htmlspecialchars($user_info['nombre_usuario'] ?? 'Usuario'); ?></h4>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($error_msg)): ?>
                                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                            <?php endif; ?>
                            <?php if (!empty($success_msg)): ?>
                                <div class="alert alert-success"><?php echo $success_msg; ?></div>
                            <?php endif; ?>

                            <?php if ($user_info): ?>
                            <h5>Información de tu Cuenta</h5>
                            <hr>
                            <form method="POST" action="cuenta.php">
                                <input type="hidden" name="actualizar_info" value="1">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                                        <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($user_info['nombre_usuario']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Correo Electrónico</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_nac" class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control" id="fecha_nac" name="fecha_nac" value="<?php echo htmlspecialchars($user_info['fecha_nac'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_post" class="form-label">Código Postal</label>
                                        <input type="text" class="form-control" id="codigo_post" name="codigo_post" value="<?php echo htmlspecialchars($user_info['codigo_post'] ?? ''); ?>" pattern="[0-9]{5}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="num_tarjeta" class="form-label">Número de Tarjeta (simbólico)</label>
                                    <input type="text" class="form-control" id="num_tarjeta" name="num_tarjeta" value="<?php echo htmlspecialchars($user_info['num_tarjeta'] ?? ''); ?>" pattern="[0-9]{13,16}">
                                </div>
                                
                                <button type="submit" class="btn btn-coffee">
                                    <i class="bi bi-save me-2"></i>Actualizar Información
                                </button>
                            </form>
                            
                            <hr class="my-4">
                            <h5>Cambiar Contraseña</h5>
                            <p class="text-muted small">Si deseas cambiar tu contraseña, completa los siguientes campos.</p>
                            <form method="POST" action="cuenta.php">
                                <input type="hidden" name="cambiar_password" value="1">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Contraseña Actual</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_new_password" class="form-label">Confirmar Nueva Contraseña</label>
                                    <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password">
                                </div>
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="bi bi-key-fill me-2"></i>Cambiar Contraseña
                                </button>
                            </form>

                            <?php else: ?>
                                <p>No se pudo cargar la información de tu cuenta.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Coffee Cat. Todos los derechos reservados.</p>
        </div>
    </footer>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>