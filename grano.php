<?php
// Start the session
session_start();
require 'conexion.php'; // Asegúrate que este archivo conecta a la DB 'cafe' y crea $pdo

// --- Configuración ---
$producto_por_pagina = 6; // Productos a mostrar por página
$subcategoria_actual = 'Grano'; // Definimos la subcategoría para esta página

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
    // Filtrando por la subcategoría actual y orígenes no nulos/vacíos
    $stmt_origenes = $pdo->prepare("SELECT DISTINCT origen FROM producto WHERE subcategoria = :subcategoria AND origen IS NOT NULL AND origen <> '' ORDER BY origen ASC");
    $stmt_origenes->bindParam(':subcategoria', $subcategoria_actual, PDO::PARAM_STR);
    $stmt_origenes->execute();
    $origenes_disponibles = $stmt_origenes->fetchAll(PDO::FETCH_COLUMN);

    // --- Construir Consulta SQL Base y Filtros ---
    $sql_base = "FROM producto WHERE subcategoria = :subcategoria_base"; // Usamos un placeholder para la subcategoría base
    $where_clauses = [];
    $params_to_bind = [':subcategoria_base' => $subcategoria_actual]; // Array asociativo para todos los parámetros nombrados

    // Filtro por origen (usando placeholders nombrados)
    if (!empty($filtro_origen_get)) {
        $filtro_origen_seguro = array_intersect($filtro_origen_get, $origenes_disponibles);
        if (!empty($filtro_origen_seguro)) {
            $origen_placeholders_sql = [];
            $i = 0;
            foreach ($filtro_origen_seguro as $origen_val) {
                $placeholder_name = ":origen_" . $i;
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
    if (!empty($filtro_fabricante_get)) {
        $where_clauses[] = "fabricante LIKE :fabricante_like";
        $params_to_bind[':fabricante_like'] = "%" . $filtro_fabricante_get . "%";
    }

    // Combinar cláusulas WHERE
    $sql_where = !empty($where_clauses) ? " AND " . implode(' AND ', $where_clauses) : '';

    // --- Calcular Total de Productos (con filtros) ---
    $sql_total = "SELECT COUNT(*) " . $sql_base . $sql_where;
    $stmt_total = $pdo->prepare($sql_total);
    
    // Bind de parámetros para la consulta de conteo (incluyendo la subcategoría base)
    $stmt_total->execute($params_to_bind); 
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
        $sql_order = " ORDER BY nombre ASC, id_prod DESC";
    }

    $sql_limit = " LIMIT :limit OFFSET :offset";

    $sql = $sql_select . $sql_base . $sql_where . $sql_order . $sql_limit;
    $debug_sql = $sql;

    // --- Preparar, Bindear y Ejecutar Consulta Principal ---
    $stmt = $pdo->prepare($sql);

    // Copiar parámetros de filtro y añadir los de paginación y la subcategoría base para la consulta principal
    $params_to_bind_for_main_query = $params_to_bind; 
    $params_to_bind_for_main_query[':limit'] = $producto_por_pagina;
    $params_to_bind_for_main_query[':offset'] = $offset;
    // Asegurarse que :subcategoria_base está en los parámetros para la consulta principal si no fue añadido antes
    // (ya está porque $params_to_bind_for_main_query es una copia de $params_to_bind que ya lo tiene)
    
    $debug_params = $params_to_bind_for_main_query;

    foreach ($params_to_bind_for_main_query as $placeholder => $value) {
        if ($placeholder === ':limit' || $placeholder === ':offset') {
            $stmt->bindValue($placeholder, $value, PDO::PARAM_INT);
        } else {
            // Para :subcategoria_base y otros filtros de texto
            $stmt->bindValue($placeholder, $value, PDO::PARAM_STR); 
        }
    }
    
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $log_message = "Error de base de datos en grano.php: " . $e->getMessage() . PHP_EOL;
    $log_message .= "SQL Intentado: " . ($debug_sql ?: 'No SQL generado') . PHP_EOL;
    $log_message .= "Parámetros Bindeados: " . print_r($debug_params, true) . PHP_EOL;
    $log_message .= "Traza del Error: " . $e->getTraceAsString() . PHP_EOL;
    error_log($log_message); 
    
    $error_db = "Hubo un problema al cargar los productos. Inténtalo más tarde.";
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
    <link rel="stylesheet" href="molido.css"> <title>Café en Grano - Coffee Cat</title>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-cup-hot-fill"></i> Coffee Cat
            </a>
            
            <div class="d-flex align-items-center order-lg-3 ms-auto">
                <a href="cuenta.php" class="nav-link nav-icon">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-lg-inline">Cuenta</span>
                </a>
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'carrito.php') ? 'active' : ''; ?>" href="carrito.php">
                    <i class="bi bi-cart3 me-1"></i>Carrito
                    <span id="cart-badge-count" class="badge rounded-pill bg-danger">
                        <?php echo $_SESSION['cart_item_count'] ?? 0; ?>
                    </span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <div class="collapse navbar-collapse order-lg-2" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"> <i class="bi bi-house-door me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop-window me-1"></i>Productos
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
                    <form method="GET" action="grano.php" id="filterForm"> 
                        <div class="mb-4">
                            <h6><i class="bi bi-globe-americas"></i> Origen</h6>
                            <?php if (!empty($origenes_disponibles)): ?>
                                <?php foreach ($origenes_disponibles as $origen_item): /* Renombrada variable para evitar conflicto con $producto['origen'] */ ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="origen[]"
                                               id="origen-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $origen_item))) ?>"
                                               value="<?php echo htmlspecialchars($origen_item) ?>"
                                               <?php echo in_array($origen_item, $filtro_origen_get) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="origen-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $origen_item))) ?>">
                                            <?php echo htmlspecialchars($origen_item) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">No hay orígenes definidos para esta categoría.</small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h6><i class="bi bi-buildings"></i> Fabricante</h6>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       name="fabricante"
                                       placeholder="Buscar fabricante..."
                                       value="<?php echo htmlspecialchars($filtro_fabricante_get) ?>">
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
                        <a href="grano.php" class="btn btn-outline-secondary w-100 mt-2"> 
                             <i class="bi bi-x-lg"></i> Limpiar Filtros
                        </a>
                    </form>
                </div>
            </aside>

            <section class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="display-6 mb-0"><i class="bi bi-cup-straw"></i> Café en Grano</h1>
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
                                             <form action="logica_carrito.php" method="POST" class="d-grid">
                                                <input type="hidden" name="action" value="agregar_al_carrito">
                                                <input type="hidden" name="id_prod" value="<?php echo $producto['id_prod']; ?>">
                                                <input type="hidden" name="cantidad" value="1"> <input type="hidden" name="pagina_retorno" value="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']); ?>">

                                                <button type="submit" class="btn btn-primary btn-sm <?php echo $producto['cantidad_alm'] <= 0 ? 'disabled' : ''; ?>" 
                                                        <?php echo $producto['cantidad_alm'] <= 0 ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-cart-plus"></i> Añadir
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <?php if (!isset($error_db)): ?>
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle me-2"></i>No se encontraron productos que coincidan con los filtros seleccionados para Café en Grano.
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
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
