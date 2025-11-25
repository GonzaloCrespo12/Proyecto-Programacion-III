<?php
// clientes/index.php
require_once '../includes/config.php';
require_once '../includes/require_login.php';

$es_admin = ($_SESSION['rol'] === 'admin');

// CONFIGURACIÓN DE PAGINACIÓN Y BÚSQUEDA
$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$busqueda = $_GET['q'] ?? '';
$inicio = ($pagina_actual - 1) * $registros_por_pagina;

try {
    // PASO A: Contar TOTAL (Solo activos)
    $sql_count = "SELECT COUNT(*) FROM clientes c WHERE c.activo = 1";
    if ($busqueda) {
        $sql_count .= " AND (c.nombre LIKE :qn OR c.apellido LIKE :qa)";
    }
    $stmt_count = $pdo->prepare($sql_count);
    if ($busqueda) {
        $termino = "%$busqueda%";
        $stmt_count->bindValue(':qn', $termino, PDO::PARAM_STR);
        $stmt_count->bindValue(':qa', $termino, PDO::PARAM_STR);
    }
    $stmt_count->execute();
    $total_registros = $stmt_count->fetchColumn();
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // PASO B: Traer DATOS
    $sql = "SELECT 
                c.id, c.nombre, c.apellido, c.fecha_nacimiento, c.fotoPerfil, 
                GROUP_CONCAT(e.nombre SEPARATOR ', ') as especialidades
            FROM clientes c
            LEFT JOIN cliente_especialidad ce ON c.id = ce.cliente_id
            LEFT JOIN especialidades e ON ce.especialidad_id = e.id
            WHERE c.activo = 1";

    if ($busqueda) {
        $sql .= " AND (c.nombre LIKE :qn OR c.apellido LIKE :qa)";
    }
    $sql .= " GROUP BY c.id ORDER BY c.id DESC LIMIT :limite OFFSET :inicio";

    $stmt = $pdo->prepare($sql);
    if ($busqueda) {
        $termino = "%$busqueda%";
        $stmt->bindValue(':qn', $termino, PDO::PARAM_STR);
        $stmt->bindValue(':qa', $termino, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limite', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->execute();
    $clientes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error crítico: " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<div class="container-fluid px-4 mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Clientes</h2>
            <p class="text-muted small mb-0">Gestiona tu cartera de usuarios activos</p>
        </div>
        <a href="crear.php" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="fa-solid fa-plus me-2"></i> Nuevo Cliente
        </a>
    </div>

    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show shadow-sm border-0 rounded-3">
            <?= h($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-2">
            <form action="" method="GET" class="d-flex w-100">
                <div class="input-group border-0">
                    <span class="input-group-text bg-white border-0 ps-3 text-muted">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-0 shadow-none bg-white"
                        placeholder="Buscar por nombre, apellido..."
                        value="<?= h($busqueda) ?>">

                    <?php if ($busqueda): ?>
                        <a href="index.php" class="btn btn-light border-0 text-muted" title="Limpiar">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary rounded-pill m-1 px-4">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th scope="col" class="text-center py-3 text-uppercase small fw-bold border-0">#ID</th>
                        <th scope="col" class="text-center py-3 text-uppercase small fw-bold border-0">Foto</th>
                        <th scope="col" class="py-3 text-uppercase small fw-bold border-0">Cliente</th>
                        <th scope="col" class="py-3 text-uppercase small fw-bold border-0">Edad</th>
                        <th scope="col" class="py-3 text-uppercase small fw-bold border-0">Especialidades</th>
                        <th scope="col" class="text-end py-3 text-uppercase small fw-bold border-0 pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (count($clientes) > 0): ?>
                        <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td class="text-center text-muted small border-bottom-0">
                                    <?= h($cli['id']) ?>
                                </td>

                                <td class="text-center border-bottom-0">
                                    <?php if ($cli['fotoPerfil']): ?>
                                        <img src="../uploads/<?= h($cli['fotoPerfil']) ?>"
                                            class="rounded-circle shadow-sm object-fit-cover"
                                            width="40" height="40">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto text-primary fw-bold"
                                            style="width: 40px; height: 40px; font-size: 0.8rem;">
                                            <?= h(strtoupper(substr($cli['nombre'], 0, 1) . substr($cli['apellido'], 0, 1))) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="border-bottom-0">
                                    <div class="fw-bold text-dark"><?= h($cli['nombre'] . ' ' . $cli['apellido']) ?></div>
                                </td>

                                <td class="text-muted border-bottom-0">
                                    <?php
                                    if ($cli['fecha_nacimiento']) {
                                        $dob = new DateTime($cli['fecha_nacimiento']);
                                        $now = new DateTime();
                                        echo $dob->diff($now)->y . ' años';
                                    } else {
                                        echo '<span class="text-muted opacity-50">-</span>';
                                    }
                                    ?>
                                </td>

                                <td class="border-bottom-0">
                                    <?php if ($cli['especialidades']): ?>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <?php foreach (explode(', ', $cli['especialidades']) as $tag): ?>
                                                <span class="badge bg-light text-secondary border rounded-pill fw-normal">
                                                    <?= h($tag) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border rounded-pill fw-normal">General</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end border-bottom-0 pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="editar.php?id=<?= $cli['id'] ?>"
                                            class="btn btn-sm btn-light text-primary border rounded-circle shadow-sm"
                                            style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                                            title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <?php if ($es_admin): ?>
                                            <button type="button"
                                                class="btn btn-sm btn-light text-danger border rounded-circle shadow-sm"
                                                style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                                                onclick="confirmarEliminacion(<?= $cli['id'] ?>)"
                                                title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted opacity-50 mb-2">
                                    <i class="fa-solid fa-magnifying-glass fa-3x"></i>
                                </div>
                                <p class="text-muted mb-0">No se encontraron clientes activos.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 1): ?>
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Página <strong><?= $pagina_actual ?></strong> de <strong><?= $total_paginas ?></strong>
                </small>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link border-0 rounded-start-pill text-secondary" href="?pagina=<?= $pagina_actual - 1 ?>&q=<?= h($busqueda) ?>">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item">
                                <a class="page-link border-0 <?= ($i == $pagina_actual) ? 'bg-primary text-white shadow-sm rounded-circle' : 'text-secondary' ?> mx-1 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;"
                                    href="?pagina=<?= $i ?>&q=<?= h($busqueda) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                            <a class="page-link border-0 rounded-end-pill text-secondary" href="?pagina=<?= $pagina_actual + 1 ?>&q=<?= h($busqueda) ?>">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<form id="form-eliminar" action="eliminar.php" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="id" id="input-eliminar-id">
</form>

<script>
    function confirmarEliminacion(id) {
        if (confirm('¿Seguro que querés desactivar este cliente?\nPasará a la papelera.')) {
            document.getElementById('input-eliminar-id').value = id;
            document.getElementById('form-eliminar').submit();
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>