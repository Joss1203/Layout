<?php
require __DIR__ . "/../../includes/validar_sesion.php";
require __DIR__ . "/../../includes/conexion.php";

$pid = (int) ($_GET["pabellon"] ?? 0);
$q = trim($_GET["q"] ?? "");
$categoria = trim($_GET["categoria"] ?? "");
$estado = trim($_GET["estado"] ?? "");
$categorias = [
    "Premium" => "Premium",
    "Estandar" => "Estándar",
    "Economico" => "Económico",
    "PyMES" => "PyMES",
    "Sin categoria" => "Sin categoría",
];
$categoriaClave = static function (string $valor): string {
    return strtolower(strtr(trim($valor), [
        "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u",
        "Á" => "a", "É" => "e", "Í" => "i", "Ó" => "o", "Ú" => "u",
    ]));
};
$etiquetaCategoria = static function (string $valor) use ($categorias, $categoriaClave): string {
    foreach ($categorias as $guardado => $etiqueta) {
        if ($categoriaClave($guardado) === $categoriaClave($valor)) {
            return $etiqueta;
        }
    }
    return $valor;
};
$categoriaSeleccionada = $categoriaClave($categoria);
$pabs = $conexion
    ->query("SELECT id,nombre FROM pabellones WHERE activo=1 ORDER BY orden")
    ->fetchAll();
$pabellon = null;

if ($pid) {
    $ps = $conexion->prepare("SELECT * FROM pabellones WHERE id=?");
    $ps->execute([$pid]);
    $pabellon = $ps->fetch();
}

$where = [];
$args = [];

if ($pid) {
    $where[] = "s.pabellon_id=?";
    $args[] = $pid;
}

if ($q !== "") {
    $where[] = "(s.numero LIKE ? OR s.empresa LIKE ?)";
    $args[] = "%$q%";
    $args[] = "%$q%";
}

$sql =
    "SELECT s.*,p.nombre pabellon FROM stands s LEFT JOIN pabellones p ON p.id=s.pabellon_id" .
    ($where ? " WHERE " . implode(" AND ", $where) : "") .
    " ORDER BY p.orden,s.numero";
$st = $conexion->prepare($sql);
$st->execute($args);
$stands = $st->fetchAll();
$stands = array_values(array_filter($stands, static function (array $stand) use ($categoriaSeleccionada, $estado, $categoriaClave): bool {
    return ($categoriaSeleccionada === "" || $categoriaClave((string) $stand["categoria"]) === $categoriaSeleccionada)
        && ($estado === "" || $stand["estado"] === $estado);
}));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gestionar ocupabilidad | FAMEX</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="overview.css">
    <link rel="stylesheet" href="stands-map.css">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <header class="admin-topbar">
            <h1>Gestionar espacios</h1>
            <nav class="admin-nav">
                <a class="admin-btn secondary" href="dashboard.php"> Inicio</a>
                <?php if ($rolActual === "admin" && $pid): ?>
                    <a class="admin-btn accent" href="editor_mapa.php?id=<?= $pid ?>">Editar recuadros</a>
                <?php endif; ?>
                <a class="admin-btn danger" href="logout.php">Salir</a>
            </nav>
        </header>

        <section class="admin-panel">
            <form class="filter-bar">
                <label>
                    Pabellón
                    <select name="pabellon">
                        <option value="0">Todos</option>
                        <?php foreach ($pabs as $p): ?>
                            <option
                                value="<?= $p["id"] ?>"
                                <?= $pid === $p["id"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($p["nombre"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Buscar
                    <input
                        name="q"
                        value="<?= htmlspecialchars($q) ?>"
                        placeholder="Número o empresa"
                    >
                </label>
                <label>
                    Categoría
                    <select name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $valor => $texto): ?>
                            <option value="<?= htmlspecialchars($valor) ?>" <?= $categoriaSeleccionada === $categoriaClave($valor) ? "selected" : "" ?>>
                                <?= htmlspecialchars($texto) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Estado
                    <select name="estado">
                        <option value="">Todos</option>
                        <option value="Disponible" <?= $estado === "Disponible" ? "selected" : "" ?>>Disponible</option>
                        <option value="Ocupado" <?= $estado === "Ocupado" ? "selected" : "" ?>>Ocupado</option>
                    </select>
                </label>
                <button class="admin-btn">Filtrar</button>
                <a class="admin-btn secondary" href="stands.php">Limpiar</a>
            </form>
        </section>

        <?php if ($pabellon): ?>
            <section class="admin-panel management-map-panel">
                <div class="management-map-head">
                    <div>
                        <span class="admin-kicker">Mapa seleccionable</span>
                        <h2><?= htmlspecialchars($pabellon["nombre"]) ?></h2>
                        <p>Selecciona un recuadro para editar el espacio.</p>
                    </div>
                    <div class="mini-legend">
                        <span class="premium">Premium</span>
                        <span class="standard">Estándar</span>
                        <span class="economic">Económico</span>
                        <span class="pymes">PyMES</span>
                        <span class="uncategorized">Sin categoría</span>
                    </div>
                </div>

                <div
                    class="management-map"
                    style="aspect-ratio:<?= (int) $pabellon["ancho"] ?>/<?= (int) $pabellon["alto"] ?>"
                >
                    <img
                        src="../../<?= htmlspecialchars($pabellon["imagen"]) ?>"
                        alt="Plano de <?= htmlspecialchars($pabellon["nombre"]) ?>"
                    >
                    <svg
                        id="managementOverlay"
                        viewBox="0 0 <?= (int) $pabellon["ancho"] ?> <?= (int) $pabellon["alto"] ?>"
                        preserveAspectRatio="none"
                    ></svg>
                </div>
            </section>
            <script>
                window.MANAGEMENT_PAVILION = <?= $pid ?>;
                window.MANAGEMENT_PAVILION_KEY = <?= json_encode($pabellon["clave"]) ?>;
                window.MANAGEMENT_FILTERS = <?= json_encode([
                    "q" => $q,
                    "categoria" => $categoria,
                    "estado" => $estado,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            </script>
            <script>
                window.MANAGEMENT_PAVILION_WIDTH = <?= (int) $pabellon["ancho"] ?>;
            </script>
            <script src="../../JS/admin-stands-map.js?v=<?= filemtime(__DIR__ . "/../../JS/admin-stands-map.js") ?>"></script>
        <?php endif; ?>

        <section class="admin-panel">
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pabellón</th>
                            <th>Espacio</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Empresa</th>
                            <th>Logo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stands as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s["pabellon"] ?? "Sin asignar") ?></td>
                                <td><b><?= htmlspecialchars($s["numero"]) ?></b></td>
                                <td><?= htmlspecialchars($etiquetaCategoria((string) $s["categoria"])) ?></td>
                                <td>
                                    <span class="pill <?= $s["estado"] === "Ocupado" ? "busy" : "free" ?>">
                                        <?= htmlspecialchars($s["estado"]) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($s["empresa"] ?? "—") ?></td>
                                <td><?= $s["logo"] ? "Sí" : "No" ?></td>
                                <td>
                                    <a class="admin-btn" href="editar_stand.php?id=<?= $s["id"] ?>">
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!$stands): ?>
                    <p>No hay resultados. El administrador puede crear recuadros desde el editor de mapas.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
