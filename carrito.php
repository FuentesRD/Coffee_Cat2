<?php
require 'conexion.php'; // Asegura session_start() y $pdo

// Proteger la página
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'carrito.php';
    header("Location: login.php?mensaje=Por favor, inicia sesión para ver tu carrito.");
    exit();
}

$user_id = $_SESSION['user_id']; // Este es el id_usuario de la tabla 'usuario'
$carrito_items = [];
$subtotal_carrito = 0;
$total_cantidad_productos = 0; // Para calcular el envío
$costo_envio = 0;
$total_final_carrito = 0;
$mensaje_flash = null;

if (isset($_SESSION['flash_message'])) {
    $mensaje_flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

try {
    $stmt_carrito = $pdo->prepare("
        SELECT p.id_prod, c.cantidad, p.nombre, p.precio, p.imgpath, p.cantidad_alm
        FROM carrito c
        JOIN producto p ON c.producto = p.id_prod
        WHERE c.usuario = :id_usuario_sesion
    ");
    $stmt_carrito->bindParam(':id_usuario_sesion', $user_id, PDO::PARAM_INT);
    $stmt_carrito->execute();
    $carrito_items = $stmt_carrito->fetchAll();

    if ($carrito_items) {
        foreach ($carrito_items as $item) {
            $subtotal_carrito += $item['precio'] * $item['cantidad'];
            $total_cantidad_productos += $item['cantidad'];
        }

        // Calcular costo de envío
        if ($total_cantidad_productos == 1) {
            $costo_envio = 89.00;
        } elseif ($total_cantidad_productos == 2) {
            $costo_envio = 67.00;
        } elseif ($total_cantidad_productos >= 3) {
            $costo_envio = 49.00;
        }
        // Si $total_cantidad_productos es 0 (carrito vacío después de eliminar todo), $costo_envio permanecerá 0.

        $total_final_carrito = $subtotal_carrito + $costo_envio;
    }

} catch (PDOException $e) {
    error_log("Error al obtener carrito en carrito.php: " . $e->getMessage());
    $mensaje_flash = ['mensaje' => 'No se pudo cargar tu carrito. Inténtalo más tarde.', 'tipo' => 'danger'];
    $carrito_items = []; // Asegurar que el carrito esté vacío si hay error
    // Resetear totales si hay error cargando el carrito
    $subtotal_carrito = 0;
    $total_cantidad_productos = 0;
    $costo_envio = 0;
    $total_final_carrito = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Coffee Cat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="carrito.css"> 
    <style>
        .product-image-sm { max-width: 80px; max-height: 80px; object-fit: cover; border-radius: 0.25rem;}
        .input-cantidad { width: 80px; text-align: center; }
        .table th, .table td { vertical-align: middle; }
        .cart-summary .list-group-item { border-left: 0; border-right: 0; }
        .cart-summary .list-group-item:first-child { border-top: 0; }
        .cart-summary .list-group-item:last-child { border-bottom: 0; }
        .btn-coffee {
            background-color: #4B3621; /* --color-primario */
            border-color: #4B3621;
            color: #FFF8E1; /* --color-claro */
        }
        .btn-coffee:hover {
            background-color: #3a2a1a;
            border-color: #3a2a1a;
            color: #FFF8E1;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="bi bi-cup-hot-fill"></i> Coffee Cat</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door me-1"></i>Inicio</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><i class="bi bi-shop-window me-1"></i>Productos</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="grano.php">Café en Grano</a></li>
                            <li><a class="dropdown-item" href="molido.php">Café Molido</a></li>
                            <li><a class="dropdown-item" href="accesorios.php">Accesorios</a></li>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="cuenta.php"><i class="bi bi-person-circle me-1"></i>Mi Cuenta</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión</a></li>
                        <li class="nav-item"><a class="nav-link" href="registro.php"><i class="bi bi-person-plus-fill me-1"></i>Registrarse</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="carrito.php">
                             <i class="bi bi-cart3 me-1"></i>Carrito 
                             <span id="cart-badge-count" class="badge rounded-pill bg-danger"><?php echo $_SESSION['cart_item_count'] ?? 0; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-5 pt-5 mb-5">
        <h1 class="mb-4 display-5"><i class="bi bi-cart-check-fill text-primary"></i> Tu Carrito de Compras</h1>

        <?php if ($mensaje_flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($mensaje_flash['tipo']); ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje_flash['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($carrito_items)): ?>
            <div class="alert alert-info text-center py-4">
                <p class="lead mb-3">Tu carrito está actualmente vacío.</p>
                <a href="index.php" class="btn btn-lg btn-coffee"><i class="bi bi-arrow-left-circle me-2"></i>Continuar Comprando</a>
            </div>
        <?php else: ?>
            <div class="table-responsive shadow-sm rounded mb-4">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 10%;">Producto</th>
                            <th scope="col">Nombre</th>
                            <th scope="col" class="text-center">Precio Unit.</th>
                            <th scope="col" class="text-center" style="width: 25%;">Cantidad</th>
                            <th scope="col" class="text-end">Subtotal</th>
                            <th scope="col" class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($carrito_items as $item): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo !empty($item['imgpath']) ? htmlspecialchars($item['imgpath']) : 'https://placehold.co/80x80/E1D4C0/4B3621?text=Caf%C3%A9'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['nombre']); ?>" class="img-fluid product-image-sm">
                                </td>
                                <td>
                                    <a href="producto_detalle.php?id=<?php echo $item['id_prod']; ?>" class="text-decoration-none fw-bold text-dark">
                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                    </a>
                                    <br>
                                    <small class="text-muted">Stock: <?php echo $item['cantidad_alm']; ?></small>
                                </td>
                                <td class="text-center">$<?php echo number_format($item['precio'], 2); ?></td>
                                <td class="text-center">
                                    <form action="logica_carrito.php" method="POST" class="d-inline-flex align-items-center justify-content-center">
                                        <input type="hidden" name="action" value="actualizar_cantidad_carrito">
                                        <input type="hidden" name="id_prod" value="<?php echo $item['id_prod']; ?>">
                                        <input type="hidden" name="pagina_retorno" value="carrito.php">
                                        <input type="number" name="cantidad" class="form-control form-control-sm input-cantidad" 
                                               value="<?php echo $item['cantidad']; ?>" min="0" max="<?php echo $item['cantidad_alm']; ?>" 
                                               aria-label="Cantidad para <?php echo htmlspecialchars($item['nombre']); ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm ms-2" title="Actualizar cantidad">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end fw-bold">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                                <td class="text-center">
                                    <a href="logica_carrito.php?action=eliminar_item_carrito&id_prod=<?php echo $item['id_prod']; ?>&pagina_retorno=carrito.php" 
                                       class="btn btn-outline-danger btn-sm" title="Eliminar producto">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="row justify-content-end mt-4">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="card shadow-sm cart-summary">
                        <div class="card-body p-4">
                            <h4 class="card-title mb-3 text-primary">Resumen del Pedido</h4>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Subtotal
                                    <span>$<?php echo number_format($subtotal_carrito, 2); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    Envío
                                    <span>$<?php echo number_format($costo_envio, 2); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0 fw-bold h4 text-success">
                                    Total
                                    <span>$<?php echo number_format($total_final_carrito, 2); ?></span>
                                </li>
                            </ul>
                            <form action="logica_carrito.php" method="POST" class="mt-4">
                                <input type="hidden" name="action" value="procesar_compra_carrito">
                                <input type="hidden" name="pagina_retorno" value="carrito.php">
                                <button type="submit" class="btn btn-coffee w-100 py-2 btn-lg">
                                    <i class="bi bi-credit-card-fill me-2"></i>Confirmar Compra
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Coffee Cat. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
