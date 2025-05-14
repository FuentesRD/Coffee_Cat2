<?php
// Start the session
session_start();
require 'conexion.php'; // Asegúrate que este archivo conecta a la DB 'cafe' y crea $pdo

// --- Configuración ---
$producto_por_pagina = 6; // Productos a mostrar por página

// --- Obtener Parámetros (Página Actual y Filtros) ---
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $producto_por_pagina;

$filtro_origen_get = isset($_GET['origen']) && is_array($_GET['origen']) ? $_GET['origen'] : [];
$filtro_fabricante_get = isset($_GET['fabricante']) ? trim($_GET['fabricante']) : '';
$orden_precio = isset($_GET['orden']) ? $_GET['orden'] : '';

// --- Variables para la consulta ---
$productos = [];
$total_productos = 0;
$total_paginas = 0;
$origenes_disponibles = []; // Para filtro dinámico
$debug_sql = ''; // Para depuración
$debug_params = []; // Para depuración

try {
    // --- Obtener Orígenes Disponibles para Filtros ---
    // Filtrando solo por subcategoría 'Molido' y orígenes no nulos/vacíos
    $stmt_origenes = $pdo->query("SELECT DISTINCT origen FROM producto WHERE subcategoria = 'Molido' AND origen IS NOT NULL AND origen <> '' ORDER BY origen ASC");
    $origenes_disponibles = $stmt_origenes->fetchAll(PDO::FETCH_COLUMN);

    // --- Construir Consulta SQL Base y Filtros ---
    $sql_base = "FROM producto WHERE subcategoria = 'Molido'"; // Base de la consulta, solo subcategoría 'Molido'
    $where_clauses = [];
    $params_to_bind = []; // Array asociativo para todos los parámetros nombrados

    // Filtro por origen (usando placeholders nombrados)
    if (!empty($filtro_origen_get)) {
        // Validar que los orígenes seleccionados existan en los disponibles (seguridad extra)
        $filtro_origen_seguro = array_intersect($filtro_origen_get, $origenes_disponibles);

        if (!empty($filtro_origen_seguro)) {
            $origen_placeholders_sql = [];
            $i = 0;
            foreach ($filtro_origen_seguro as $origen_val) {
                $placeholder_name = ":origen_" . $i; // e.g., :origen_0, :origen_1
                $origen_placeholders_sql[] = $placeholder_name;
                $params_to_bind[$placeholder_name] = $origen_val;
                $i++;
            }
            if (!empty($origen_placeholders_sql)) {
                $where_clauses[] = "origen IN (" . implode(',', $origen_placeholders_sql) . ")";
            }
        }
    }

    // Filtro por fabricante (búsqueda parcial con placeholder nombrado)
    if (!empty($filtro_fabricante_get)) { // Ya estaba correcto usando $filtro_fabricante_get
        $where_clauses[] = "fabricante LIKE :fabricante_like";
        $params_to_bind[':fabricante_like'] = "%" . $filtro_fabricante_get . "%";
    }

    // Combinar cláusulas WHERE
    $sql_where = !empty($where_clauses) ? " AND " . implode(' AND ', $where_clauses) : '';

    // --- Calcular Total de Productos (con filtros) ---
    $sql_total = "SELECT COUNT(*) " . $sql_base . $sql_where;
    $stmt_total = $pdo->prepare($sql_total);
    
    $stmt_total->execute($params_to_bind); // Ejecutar con los parámetros de filtro actuales
    $total_productos = (int)$stmt_total->fetchColumn();
    $total_paginas = ($producto_por_pagina > 0) ? ceil($total_productos / $producto_por_pagina) : 0;


    // --- Construir Consulta SQL Principal (con filtros, ordenamiento y paginación) ---
    $sql_select = "SELECT id_prod, nombre, categoria, subcategoria, descripcion, precio, cantidad_alm, fabricante, origen, imgpath ";
    $sql_order = "";
    if ($orden_precio === 'asc') {
        $sql_order = " ORDER BY precio ASC";
    } elseif ($orden_precio === 'desc') {
        $sql_order = " ORDER BY precio DESC";
    } else {
        $sql_order = " ORDER BY nombre ASC, id_prod DESC"; // Orden por defecto más consistente
    }

    $sql_limit = " LIMIT :limit OFFSET :offset";

    $sql = $sql_select . $sql_base . $sql_where . $sql_order . $sql_limit;
    $debug_sql = $sql; // Guardar SQL para depuración

    // --- Preparar, Bindear y Ejecutar Consulta Principal ---
    $stmt = $pdo->prepare($sql);

    // Añadir parámetros de paginación al array $params_to_bind
    // Estos se añaden DESPUÉS de que $params_to_bind se usó para la consulta COUNT(*)
    $params_to_bind_for_main_query = $params_to_bind; // Copiar parámetros de filtro
    $params_to_bind_for_main_query[':limit'] = $producto_por_pagina; // Añadir limit
    $params_to_bind_for_main_query[':offset'] = $offset;          // Añadir offset
    
    $debug_params = $params_to_bind_for_main_query; // Guardar parámetros para depuración

    // Bindear todos los parámetros nombrados para la consulta principal
    foreach ($params_to_bind_for_main_query as $placeholder => $value) {
        if ($placeholder === ':limit' || $placeholder === ':offset') {
            $stmt->bindValue($placeholder, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($placeholder, $value); 
        }
    }
    
    $stmt->execute(); // Se ejecuta sin pasar parámetros aquí porque ya fueron bindeados.
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Mensaje de error más detallado para el log
    $log_message = "Error de base de datos en molido.php: " . $e->getMessage() . PHP_EOL;
    $log_message .= "SQL Intentado: " . ($debug_sql ?: 'No SQL generado') . PHP_EOL;
    $log_message .= "Parámetros Bindeados: " . print_r($debug_params, true) . PHP_EOL; // Muestra los parámetros que se intentaron usar con la SQL principal
    $log_message .= "Traza del Error: " . $e->getTraceAsString() . PHP_EOL;
    error_log($log_message); 
    
    $error_db = "Hubo un problema al cargar los productos. Inténtalo más tarde.";
    // Para depuración en pantalla (SOLO EN ENTORNO DE DESARROLLO, NUNCA EN PRODUCCIÓN):
    // $error_db = "DEBUG: " . $e->getMessage() . "<br>SQL: ".htmlspecialchars($debug_sql)." <br>PARAMS: " . htmlspecialchars(print_r($debug_params, true));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="molido.css">

    <title>Coffee Cat</title>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cup-hot-fill"></i> Coffee Cat
            </a>
            
            <div class="d-flex align-items-center order-lg-3 ms-auto">
                <a href="#" class="nav-link nav-icon">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-lg-inline">Cuenta</span>
                </a>
                <a href="#" class="nav-link nav-icon">
                    <i class="bi bi-cart3"></i>
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
                            <i class="bi bi-shop-window me-1"></i>producto
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="grano.php"> <i class="bi bi-cup-straw me-2"></i>Café en Grano
                            </a></li>
                            <li><a class="dropdown-item" href="molido.php">
                                <i class="bi bi-cup"></i>Café Molido
                            </a></li>
                            <li><a class="dropdown-item" href="accesorio.php"> <i class="bi bi-funnel-fill me-2"></i>Accesorios
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


    <main class="container mt-4 mb-5">
        <div class="row">

            <aside class="col-lg-3 mb-4">
                 <div class="sticky-top" style="top: 100px;"> <h4><i class="bi bi-funnel"></i> Filtrar Productos</h4>
                    <hr>
                    <form method="GET" action="molido.php" id="filterForm">
                        <div class="mb-4">
                            <h6><i class="bi bi-globe-americas"></i> Origen</h6>
                            <?php if (!empty($origenes_disponibles)): ?>
                                <?php foreach ($origenes_disponibles as $origen): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="origen[]"
                                               id="origen-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $origen))) ?>"
                                               value="<?php echo htmlspecialchars($origen) ?>"
                                               <?php // CORRECCIÓN AQUÍ: Usar $filtro_origen_get
                                               echo in_array($origen, $filtro_origen_get) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="origen-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $origen))) ?>">
                                            <?php echo htmlspecialchars($origen) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">No hay orígenes definidos.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h6><i class="bi bi-buildings"></i> Fabricante</h6>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       name="fabricante"
                                       placeholder="Buscar fabricante..."
                                       value="<?php // CORRECCIÓN AQUÍ: Usar $filtro_fabricante_get
                                       echo htmlspecialchars($filtro_fabricante_get) ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6><i class="bi bi-sort-down"></i> Ordenar por Precio</h6>
                            <select class="form-select" name="orden">
                                <option value="" <?php echo empty($orden_precio) ? 'selected' : '' ?>>Por defecto</option>
                                <option value="asc" <?php echo $orden_precio === 'asc' ? 'selected' : '' ?>>Menor a mayor</option>
                                <option value="desc" <?php echo $orden_precio === 'desc' ? 'selected' : '' ?>>Mayor a menor</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Aplicar Filtros
                        </button>
                        <a href="molido.php" class="btn btn-outline-secondary w-100 mt-2">
                             <i class="bi bi-x-lg"></i> Limpiar Filtros
                        </a>
                    </form>
                </div>
            </aside>

            <section class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="display-6 mb-0"><i class="bi bi-cup"></i> Café Molido</h1>
                    <span class="badge bg-secondary rounded-pill">
                        <?php echo $total_productos; ?> producto<?php echo ($total_productos != 1) ? 's' : ''; ?>
                    </span>
                </div>

                 <?php if (isset($error_db)): ?>
                    <div class="alert alert-danger"><?php echo $error_db; ?></div>
                 <?php endif; ?>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4" id="productGrid">
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $producto): ?>
                            <div class="col">
                                <div class="card h-100 product-card">
                                    <a href="producto_detalle.php?id=<?php echo $producto['id_prod']; ?>" class="text-decoration-none text-dark">
                                        <div class="card-img-top"
                                             style="background-image: url('<?php echo !empty($producto['imgpath']) ? htmlspecialchars($producto['imgpath']) : 'img/placeholder.png'; ?>');
                                                    height: 200px;
                                                    background-size: cover;
                                                    background-position: center;">
                                        </div>
                                    </a>
                                    <div class="card-body d-flex flex-column">
                                        <div class="mb-2">
                                            <h5 class="card-title mb-1">
                                                <a href="producto_detalle.php?id=<?php echo $producto['id_prod']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($producto['nombre']); ?>
                                                </a>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="bi bi-buildings"></i> <?php echo htmlspecialchars($producto['fabricante'] ?: 'N/A'); ?>
                                            </small>
                                            <br>
                                             <small class="text-muted">
                                                 <i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($producto['origen'] ?: 'N/D'); ?>
                                             </small>
                                        </div>

                                        <p class="card-text mt-auto mb-2">
                                            <span class="h5 text-primary">
                                                $<?php echo number_format($producto['precio'], 2); ?> MXN
                                            </span>
                                            <span class="badge float-end <?php echo $producto['cantidad_alm'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $producto['cantidad_alm'] > 0 ? 'Disponible' : 'Agotado'; ?>
                                            </span>
                                        </p>
                                        <div class="mt-2 d-grid gap-2 d-sm-flex justify-content-sm-between">
                                            <a href="producto_detalle.php?id=<?php echo $producto['id_prod']; ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                                 <i class="bi bi-eye"></i> Ver
                                            </a>
                                             <button class="btn btn-primary btn-sm flex-grow-1 <?php echo $producto['cantidad_alm'] <= 0 ? 'disabled' : ''; ?>"
                                                     onclick="addToCart(<?php echo $producto['id_prod']; ?>)"> <i class="bi bi-cart-plus"></i> Añadir
                                             </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <?php if (!isset($error_db)): // Solo muestra este mensaje si no hay un error de DB ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>No se encontraron productos que coincidan con los filtros seleccionados.
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Paginación de productos" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>">
                                    &laquo; Anterior
                                </a>
                            </li>

                            <?php
                            $rango = 2;
                            $inicio = max(1, $pagina_actual - $rango);
                            $fin = min($total_paginas, $pagina_actual + $rango);

                            if ($inicio > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['pagina' => 1])).'">1</a></li>';
                                if ($inicio > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $inicio; $i <= $fin; $i++): ?>
                                <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor;

                            if ($fin < $total_paginas) {
                                if ($fin < $total_paginas - 1) {
                                     echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['pagina' => $total_paginas])).'">'.$total_paginas.'</a></li>';
                            }
                            ?>

                            <li class="page-item <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>">
                                    Siguiente &raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            </section> 
        </div> 
    </main> 
    
    <script>
        function addToCart(productId) {
            console.log("Añadir al carrito producto ID:", productId);
            alert("Producto " + productId + " añadido (simulación)");
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>