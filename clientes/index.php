<?php
// 1. Configuración y Seguridad
require_once '../includes/config.php';

// Verificamos si está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// --------------------------------------------------------------------------
// [CONTROL DE ROLES]
// --------------------------------------------------------------------------
// Definimos una variable booleana simple para usar en el HTML
// Si el rol es 'admin', esto es TRUE. Si es 'operador', es FALSE.
$es_admin = ($_SESSION['rol'] === 'admin');

// --------------------------------------------------------------------------
// [LÓGICA] BÚSQUEDA Y LISTADO
// --------------------------------------------------------------------------
$busqueda = $_GET['q'] ?? '';

// SQL "Senior": Traemos clientes y sus especialidades concatenadas en un string
// Ejemplo resultado: "Juan", "Perez", "Frontend, Backend"
$sql = "SELECT 
            c.id, 
            c.nombre, 
            c.apellido, 
            c.fecha_nacimiento, 
            c.fotoPerfil, 
            GROUP_CONCAT(e.nombre SEPARATOR ', ') as especialidades
        FROM clientes c
        LEFT JOIN cliente_especialidad ce ON c.id = ce.cliente_id
        LEFT JOIN especialidades e ON ce.especialidad_id = e.id";

// Si hay búsqueda, filtramos
if ($busqueda) {
    $sql .= " WHERE c.nombre LIKE :q OR c.apellido LIKE :q";
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    if ($busqueda) {
        $stmt->bindValue(':q', "%$busqueda%", PDO::PARAM_STR);
    }
    $stmt->execute();
    $clientes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar listado: " . $e->getMessage());
}

// 3. Incluimos el Header
require_once '../includes/header.php';
?>

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <h1><i class="fa-solid fa-users-viewfinder"></i> Gestión de Clientes</h1>
        <a href="crear.php" class="btn btn-success">
            <i class="fa-solid fa-plus"></i> Nuevo Cliente
        </a>
    </div>



    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col" class="text-center">ID</th>
                            <th scope="col" class="text-center">Foto</th>
                            <th scope="col">Nombre Completo</th>
                            <th scope="col">Edad</th>
                            <th scope="col">Especialidades</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clientes) > 0): ?>
                            <?php foreach ($clientes as $cli): ?>
                                <tr>
                                    <td class="text-center text-muted small">#<?php echo $cli['id']; ?></td>
                                    
                                    <td class="text-center">
                                        <?php 
                                            // Lógica de visualización de foto
                                            $ruta_foto = $cli['fotoPerfil'] 
                                                ? '../uploads/' . $cli['fotoPerfil'] 
                                                : 'https://ui-avatars.com/api/?name='.$cli['nombre'].'+'.$cli['apellido'].'&background=random';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($ruta_foto); ?>" 
                                             alt="Foto" 
                                             class="rounded-circle border" 
                                             width="45" height="45" 
                                             style="object-fit: cover;">
                                    </td>
                                    
                                    <td class="fw-bold">
                                        <?php echo htmlspecialchars($cli['nombre'] . ' ' . $cli['apellido']); ?>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                            // Calculamos edad al vuelo
                                            if ($cli['fecha_nacimiento']) {
                                                $dob = new DateTime($cli['fecha_nacimiento']);
                                                $now = new DateTime();
                                                echo $dob->diff($now)->y . ' años';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                        ?>
                                    </td>
                                    
                                    <td>
                                        <?php if ($cli['especialidades']): ?>
                                            <?php 
                                                $tags = explode(', ', $cli['especialidades']);
                                                foreach($tags as $tag): 
                                            ?>
                                                <span class="badge bg-info text-dark border border-info-subtle">
                                                    <?php echo htmlspecialchars($tag); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border">General</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="editar.php?id=<?php echo $cli['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>

                                            <?php if ($es_admin): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmarEliminacion(<?php echo $cli['id']; ?>)"
                                                        title="Eliminar">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-ghost fa-2x mb-3"></i><br>
                                    No se encontraron clientes registrados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="form-eliminar" action="eliminar.php" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="id" id="input-eliminar-id">
</form>

<script>
    // Función JS para manejar el borrado seguro
    function confirmarEliminacion(id) {
        if (confirm('¡Atención Admin!\n\n¿Estás seguro de que querés borrar este cliente?\nEsta acción es irreversible y borrará sus fotos y relaciones.')) {
            // Ponemos el ID en el form oculto y lo enviamos
            document.getElementById('input-eliminar-id').value = id;
            document.getElementById('form-eliminar').submit();
        }
    }
</script>

<?php
// 4. Incluimos el Footer
require_once '../includes/footer.php';
?>