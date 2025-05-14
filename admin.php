<?php
require 'conexion.php'; // Incluye session_start() y $pdo

// (Opcional) Protección básica si se desea en el futuro
// if (!isset($_SESSION['admin_logged_in'])) {
// header('Location: admin_login.php'); // Redirigir a una página de login de admin
// exit();
// }

$mensaje_accion = null; 
if (isset($_SESSION['admin_flash_message'])) {
    $mensaje_accion = $_SESSION['admin_flash_message'];
    unset($_SESSION['admin_flash_message']);
}

$vista_actual = $_GET['vista'] ?? 'ver_productos';

$productos = [];
$historial_compras_agrupado = []; 
$producto_a_editar = null;

try {
    if ($vista_actual === 'ver_productos' || $vista_actual === 'editar_producto') {
        $stmt_productos = $pdo->query("SELECT * FROM producto ORDER BY id_prod DESC");
        $productos = $stmt_productos->fetchAll();
    }

    if ($vista_actual === 'editar_producto' && isset($_GET['id_prod'])) {
        $id_prod_editar = filter_input(INPUT_GET, 'id_prod', FILTER_VALIDATE_INT);
        if ($id_prod_editar) {
            $stmt_edit = $pdo->prepare("SELECT * FROM producto WHERE id_prod = :id_prod");
            $stmt_edit->execute([':id_prod' => $id_prod_editar]);
            $producto_a_editar = $stmt_edit->fetch();
            if (!$producto_a_editar) {
                $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Producto no encontrado para editar. ID: ' . $id_prod_editar];
                header('Location: admin.php?vista=ver_productos');
                exit;
            }
        } else {
            $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'ID de producto para editar no válido.'];
            header('Location: admin.php?vista=ver_productos');
            exit;
        }
    }

    if ($vista_actual === 'ver_historial') {
        $stmt_historial = $pdo->query("
            SELECT 
                h.id_compra_grupo, 
                h.cantidad AS cantidad_comprada, 
                h.precio_unitario AS precio_unitario_compra, 
                h.subtotal_prod,
                p.nombre AS nombre_producto, 
                p.imgpath,
                u.nombre_usuario AS nombre_cliente,
                u.email AS email_cliente,
                h.id_compra -- Añadido para ordenar dentro del grupo si es necesario
            FROM historial h
            JOIN producto p ON h.producto = p.id_prod
            JOIN usuario u ON h.usuario = u.id_usuario
            ORDER BY h.id_compra_grupo DESC, h.id_compra ASC -- Ordenar por grupo y luego por item
        ");
        $historial_items_individuales = $stmt_historial->fetchAll();

        foreach($historial_items_individuales as $item_hist){
            $id_grupo = $item_hist['id_compra_grupo'];
            if (!isset($historial_compras_agrupado[$id_grupo])) {
                 $historial_compras_agrupado[$id_grupo] = [
                    'items' => [],
                    'cliente_nombre' => $item_hist['nombre_cliente'],
                    'cliente_email' => $item_hist['email_cliente'],
                    'total_compra' => 0
                    // Podrías añadir la fecha de la primera entrada del grupo aquí si la tuvieras
                 ];
            }
            $historial_compras_agrupado[$id_grupo]['items'][] = $item_hist;
            $historial_compras_agrupado[$id_grupo]['total_compra'] += $item_hist['subtotal_prod'];
        }
    }

} catch (PDOException $e) {
    $mensaje_accion = ['tipo' => 'danger', 'mensaje' => 'Error de base de datos: ' . $e->getMessage()];
    error_log("Error en admin.php (carga inicial): " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion_post = $_POST['accion_admin'] ?? '';

    if ($accion_post === 'guardar_producto') {
        $id_prod_post = filter_input(INPUT_POST, 'id_prod', FILTER_VALIDATE_INT);
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Café');
        $subcategoria = trim($_POST['subcategoria'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
        $cantidad_alm = filter_input(INPUT_POST, 'cantidad_alm', FILTER_VALIDATE_INT);
        $fabricante = trim($_POST['fabricante'] ?? '');
        $origen = trim($_POST['origen'] ?? '');
        $imgpath_actual = $_POST['imgpath_actual'] ?? '';

        if (empty($nombre) || empty($categoria) || empty($subcategoria) || $precio === false || $precio < 0 || $cantidad_alm === false || $cantidad_alm < 0) {
            $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Nombre, categoría, subcategoría, precio y cantidad son obligatorios y deben ser válidos.'];
        } else {
            $imgpath_final = $imgpath_actual; 

            if (isset($_FILES['imgpath_nueva']) && $_FILES['imgpath_nueva']['error'] == UPLOAD_ERR_OK) {
                // ***** CAMBIO DE RUTA DE SUBIDA *****
                $upload_dir = 'img/'; // Directorio raíz 'img/'
                // No es necesario crear 'img/' si ya existe en la raíz de tu proyecto.
                // Si 'img/' no existe, el siguiente bloque intentará crearla.
                // Es crucial que 'img/' tenga permisos de escritura para el servidor web.
                if (!is_dir($upload_dir)) {
                    // Intentar crear el directorio si no existe
                    if (!mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) { // Comprobar de nuevo si se creó
                        $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Error: No se pudo crear el directorio de subida \'img/\'. Verifica los permisos.'];
                        header('Location: admin.php?vista=' . ($id_prod_post ? 'editar_producto&id_prod='.$id_prod_post : 'agregar_producto'));
                        exit;
                    }
                }
                
                $nombre_archivo_temporal = $_FILES['imgpath_nueva']['tmp_name'];
                $nombre_archivo_original = basename($_FILES['imgpath_nueva']['name']);
                $extension_archivo = strtolower(pathinfo($nombre_archivo_original, PATHINFO_EXTENSION));
                // Para evitar colisiones y asegurar nombres de archivo únicos y válidos:
                $nombre_base_limpio = preg_replace("/[^a-zA-Z0-9_.-]/", "", pathinfo($nombre_archivo_original, PATHINFO_FILENAME));
                $nombre_archivo_unico = $nombre_base_limpio . '_' . time() . '.' . $extension_archivo;
                $ruta_destino = $upload_dir . $nombre_archivo_unico;

                $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($extension_archivo, $permitidas)) {
                    if (move_uploaded_file($nombre_archivo_temporal, $ruta_destino)) {
                        $imgpath_final = $ruta_destino; 
                        if ($id_prod_post && !empty($imgpath_actual) && $imgpath_actual !== $imgpath_final && file_exists($imgpath_actual) && strpos($imgpath_actual, 'placehold.co') === false) {
                           // unlink($imgpath_actual); // Descomentar con MUCHO CUIDADO si quieres borrar la imagen anterior
                        }
                    } else {
                        $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Error al mover el archivo subido a \'img/\'. Verifica permisos.'];
                        header('Location: admin.php?vista=' . ($id_prod_post ? 'editar_producto&id_prod='.$id_prod_post : 'agregar_producto'));
                        exit;
                    }
                } else {
                    $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Tipo de archivo no permitido. Sube JPG, JPEG, PNG, GIF o WEBP.'];
                    header('Location: admin.php?vista=' . ($id_prod_post ? 'editar_producto&id_prod='.$id_prod_post : 'agregar_producto'));
                    exit;
                }
            } elseif (isset($_FILES['imgpath_nueva']) && $_FILES['imgpath_nueva']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['imgpath_nueva']['error'] != UPLOAD_ERR_OK) {
                $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Error al subir la imagen. Código: ' . $_FILES['imgpath_nueva']['error']];
                header('Location: admin.php?vista=' . ($id_prod_post ? 'editar_producto&id_prod='.$id_prod_post : 'agregar_producto'));
                exit;
            }

            try {
                if ($id_prod_post) { 
                    $sql_guardar = "UPDATE producto SET nombre = :nombre, categoria = :categoria, subcategoria = :subcategoria, descripcion = :descripcion, precio = :precio, cantidad_alm = :cantidad_alm, fabricante = :fabricante, origen = :origen, imgpath = :imgpath WHERE id_prod = :id_prod";
                    $stmt_guardar = $pdo->prepare($sql_guardar);
                    $params_guardar = [
                        ':nombre' => $nombre, ':categoria' => $categoria, ':subcategoria' => $subcategoria,
                        ':descripcion' => $descripcion, ':precio' => $precio, ':cantidad_alm' => $cantidad_alm,
                        ':fabricante' => $fabricante, ':origen' => $origen, ':imgpath' => $imgpath_final, ':id_prod' => $id_prod_post
                    ];
                } else { 
                    $sql_guardar = "INSERT INTO producto (nombre, categoria, subcategoria, descripcion, precio, cantidad_alm, fabricante, origen, imgpath) VALUES (:nombre, :categoria, :subcategoria, :descripcion, :precio, :cantidad_alm, :fabricante, :origen, :imgpath)";
                    $stmt_guardar = $pdo->prepare($sql_guardar);
                     $params_guardar = [
                        ':nombre' => $nombre, ':categoria' => $categoria, ':subcategoria' => $subcategoria,
                        ':descripcion' => $descripcion, ':precio' => $precio, ':cantidad_alm' => $cantidad_alm,
                        ':fabricante' => $fabricante, ':origen' => $origen, ':imgpath' => $imgpath_final
                    ];
                }
                $stmt_guardar->execute($params_guardar);
                $_SESSION['admin_flash_message'] = ['tipo' => 'success', 'mensaje' => 'Producto guardado exitosamente.'];
            } catch (PDOException $e) {
                $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Error al guardar el producto: ' . $e->getMessage()];
                 error_log("Error al guardar producto en admin.php: " . $e->getMessage());
            }
        }
        header('Location: admin.php?vista=ver_productos');
        exit;
    }
    elseif ($accion_post === 'eliminar_producto' && isset($_POST['id_prod_eliminar'])) {
        $id_prod_eliminar = filter_var($_POST['id_prod_eliminar'], FILTER_VALIDATE_INT);
        if ($id_prod_eliminar) {
            try {
                $pdo->beginTransaction();

                $stmt_get_img = $pdo->prepare("SELECT imgpath FROM producto WHERE id_prod = :id_prod");
                $stmt_get_img->execute([':id_prod' => $id_prod_eliminar]);
                $img_a_borrar = $stmt_get_img->fetchColumn();

                $stmt_delete = $pdo->prepare("DELETE FROM producto WHERE id_prod = :id_prod");
                $stmt_delete->execute([':id_prod' => $id_prod_eliminar]);

                if ($stmt_delete->rowCount() > 0) {
                    if (!empty($img_a_borrar) && file_exists($img_a_borrar) && strpos($img_a_borrar, 'placehold.co') === false && strpos($img_a_borrar, 'img/') === 0) { // Solo borrar si está en la carpeta img/
                       // unlink($img_a_borrar); // ¡¡¡CUIDADO AL DESCOMENTAR!!!
                    }
                    $_SESSION['admin_flash_message'] = ['tipo' => 'success', 'mensaje' => 'Producto eliminado exitosamente.'];
                    $pdo->commit();
                } else {
                    $_SESSION['admin_flash_message'] = ['tipo' => 'warning', 'mensaje' => 'No se encontró el producto para eliminar o ya fue eliminado.'];
                    $pdo->rollBack();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                // Verificar si el error es por restricción de FK
                if ($e->getCode() == '23000') { // Código SQLSTATE para violación de integridad (puede variar según DB)
                     $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Error: No se puede eliminar el producto porque está referenciado en el historial de compras o carritos. Considere desactivarlo en su lugar.'];
                } else {
                    $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'Error al eliminar el producto. Detalle: ' . $e->getMessage()];
                }
                error_log("Error al eliminar producto en admin.php: " . $e->getMessage());
            }
        } else {
            $_SESSION['admin_flash_message'] = ['tipo' => 'danger', 'mensaje' => 'ID de producto no válido para eliminar.'];
        }
        header('Location: admin.php?vista=ver_productos');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Coffee Cat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <button class="btn btn-dark d-md-none sidebar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <a href="admin.php" class="admin-brand text-decoration-none">
                        <i class="bi bi-cup-hot-fill"></i> Coffee Cat Admin
                    </a>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($vista_actual === 'ver_productos' || $vista_actual === 'editar_producto') ? 'active' : ''; ?>" href="admin.php?vista=ver_productos">
                                <i class="bi bi-list-task"></i>Ver/Modificar Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($vista_actual === 'agregar_producto') ? 'active' : ''; ?>" href="admin.php?vista=agregar_producto">
                                <i class="bi bi-plus-square-dotted"></i>Agregar Producto
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($vista_actual === 'ver_historial') ? 'active' : ''; ?>" href="admin.php?vista=ver_historial">
                                <i class="bi bi-journals"></i>Ver Historial
                            </a>
                        </li>
                        <li class="nav-item mt-auto pt-3 border-top border-secondary">
                            <a class="nav-link" href="index.php" target="_blank">
                                <i class="bi bi-shop"></i>Ir a la Tienda
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <?php
                        switch ($vista_actual) {
                            case 'ver_productos': echo 'Listado de Productos'; break;
                            case 'agregar_producto': echo 'Agregar Nuevo Producto'; break;
                            case 'editar_producto': echo 'Editar Producto'; break;
                            case 'ver_historial': echo 'Historial de Compras'; break;
                            default: echo 'Panel de Administración';
                        }
                        ?>
                    </h1>
                </div>

                <?php if ($mensaje_accion): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($mensaje_accion['tipo']); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje_accion['mensaje']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($vista_actual === 'ver_productos'): ?>
                    <a href="admin.php?vista=agregar_producto" class="btn btn-coffee mb-3"><i class="bi bi-plus-lg"></i> Agregar Nuevo Producto</a>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th><th>Imagen</th><th>Nombre</th><th>Categoría</th><th>Subcat.</th><th>Precio</th><th>Stock</th><th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                    <tr><td colspan="8" class="text-center">No hay productos.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['id_prod']); ?></td>
                                        <td>
                                            <img src="<?php echo !empty($producto['imgpath']) ? htmlspecialchars($producto['imgpath']) : 'https://placehold.co/50x50/E1D4C0/4B3621?text=N/A'; ?>" 
                                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="product-thumb">
                                        </td>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['subcategoria']); ?></td>
                                        <td class="text-end">$<?php echo number_format($producto['precio'], 2); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($producto['cantidad_alm']); ?></td>
                                        <td>
                                            <a href="admin.php?vista=editar_producto&id_prod=<?php echo $producto['id_prod']; ?>" class="btn btn-sm btn-outline-primary mb-1" title="Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <form action="admin.php?vista=ver_productos" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="accion_admin" value="eliminar_producto">
                                                <input type="hidden" name="id_prod_eliminar" value="<?php echo $producto['id_prod']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger mb-1" title="Eliminar">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($vista_actual === 'agregar_producto' || ($vista_actual === 'editar_producto' && $producto_a_editar)): ?>
                    <div class="card">
                        <div class="card-header card-header-custom">
                           <h5> <?php echo $vista_actual === 'editar_producto' ? 'Editando: ' . htmlspecialchars($producto_a_editar['nombre']) : 'Ingresar Datos del Nuevo Producto'; ?></h5>
                        </div>
                        <div class="card-body">
                            <form action="admin.php?vista=ver_productos" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="accion_admin" value="guardar_producto">
                                <?php if ($vista_actual === 'editar_producto'): ?>
                                    <input type="hidden" name="id_prod" value="<?php echo htmlspecialchars($producto_a_editar['id_prod']); ?>">
                                    <input type="hidden" name="imgpath_actual" value="<?php echo htmlspecialchars($producto_a_editar['imgpath']); ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre Producto <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto_a_editar['nombre'] ?? ''); ?>" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="categoria" name="categoria" value="<?php echo htmlspecialchars($producto_a_editar['categoria'] ?? 'Café'); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="subcategoria" class="form-label">Subcategoría <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="subcategoria" name="subcategoria" value="<?php echo htmlspecialchars($producto_a_editar['subcategoria'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($producto_a_editar['descripcion'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="precio" class="form-label">Precio (MXN) <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" value="<?php echo htmlspecialchars($producto_a_editar['precio'] ?? '0.00'); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cantidad_alm" class="form-label">Stock <span class="text-danger">*</span></label>
                                            <input type="number" min="0" class="form-control" id="cantidad_alm" name="cantidad_alm" value="<?php echo htmlspecialchars($producto_a_editar['cantidad_alm'] ?? '0'); ?>" required>
                                        </div>
                                         <div class="mb-3">
                                            <label for="imgpath_nueva" class="form-label">
                                                <?php echo $vista_actual === 'editar_producto' && !empty($producto_a_editar['imgpath']) ? 'Cambiar Imagen' : 'Subir Imagen'; ?>
                                            </label>
                                            <input class="form-control" type="file" id="imgpath_nueva" name="imgpath_nueva" accept="image/jpeg,image/png,image/gif,image/webp">
                                            <?php if ($vista_actual === 'editar_producto' && !empty($producto_a_editar['imgpath'])): ?>
                                                <img src="<?php echo htmlspecialchars($producto_a_editar['imgpath']); ?>" alt="Imagen actual" class="img-preview d-block">
                                            <?php else: ?>
                                                <img id="imagePreview" src="https://placehold.co/120x120/E1D4C0/4B3621?text=Vista+Previa" alt="Vista previa" class="img-preview d-block">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fabricante" class="form-label">Fabricante</label>
                                        <input type="text" class="form-control" id="fabricante" name="fabricante" value="<?php echo htmlspecialchars($producto_a_editar['fabricante'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="origen" class="form-label">Origen</label>
                                        <input type="text" class="form-control" id="origen" name="origen" value="<?php echo htmlspecialchars($producto_a_editar['origen'] ?? ''); ?>">
                                    </div>
                                </div>
                                <hr>
                                <button type="submit" class="btn btn-coffee px-4">
                                    <i class="bi bi-save2-fill"></i> <?php echo $vista_actual === 'editar_producto' ? 'Actualizar Producto' : 'Guardar Producto'; ?>
                                </button>
                                <a href="admin.php?vista=ver_productos" class="btn btn-outline-secondary px-4">Cancelar</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($vista_actual === 'ver_historial'): ?>
                    <?php if (empty($historial_compras_agrupado)): ?>
                        <div class="alert alert-info">No hay historial de compras para mostrar.</div>
                    <?php else: ?>
                        <?php foreach ($historial_compras_agrupado as $id_grupo => $compra_grupo): ?>
                            <div class="card mb-4 historial-grupo">
                                <div class="card-header historial-grupo-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <strong>ID Compra Grupo:</strong> <?php echo htmlspecialchars($id_grupo); ?> <br>
                                            <strong>Cliente:</strong> <?php echo htmlspecialchars($compra_grupo['cliente_nombre']); ?> (<?php echo htmlspecialchars($compra_grupo['cliente_email']); ?>)
                                        </span>
                                        <strong class="h5">Total Compra: $<?php echo number_format($compra_grupo['total_compra'], 2); ?></strong>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-center">Cantidad</th>
                                                <th class="text-end">Precio Unit.</th>
                                                <th class="text-end">Subtotal Ítem</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($compra_grupo['items'] as $item_hist): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item_hist['nombre_producto']); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($item_hist['cantidad_comprada']); ?></td>
                                                <td class="text-end">$<?php echo number_format($item_hist['precio_unitario_compra'], 2); ?></td>
                                                <td class="text-end fw-semibold">$<?php echo number_format($item_hist['subtotal_prod'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
