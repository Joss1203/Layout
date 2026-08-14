<?php
require __DIR__ . "/../../includes/validar_sesion.php";
require __DIR__ . "/../../includes/conexion.php";
require __DIR__ . "/../../includes/seguridad.php";

$estados = ["Pendiente", "Validado", "Rechazado"];
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCsrf();
    $id = (int) ($_POST["id"] ?? 0);
    $estado = trim($_POST["estado"] ?? "");

    if ($id && in_array($estado, $estados, true)) {
        $actualizar = $conexion->prepare(
            "UPDATE solicitudes_reserva SET estado=?,actualizado_en=CURRENT_TIMESTAMP WHERE id=?",
        );
        $actualizar->execute([$estado, $id]);
        $mensaje = "Estado actualizado.";
    }
}

$estadoFiltro = trim($_GET["estado"] ?? "");
$q = trim($_GET["q"] ?? "");
$where = [];
$args = [];

if (in_array($estadoFiltro, $estados, true)) {
    $where[] = "estado=?";
    $args[] = $estadoFiltro;
}

if ($q !== "") {
    $where[] = "(empresa LIKE ? OR rfc LIKE ? OR correo LIKE ? OR stand LIKE ?)";
    $args[] = "%$q%";
    $args[] = "%$q%";
    $args[] = "%$q%";
    $args[] = "%$q%";
}

$sql =
    "SELECT * FROM solicitudes_reserva" .
    ($where ? " WHERE " . implode(" AND ", $where) : "") .
    " ORDER BY CASE estado WHEN 'Pendiente' THEN 0 WHEN 'Validado' THEN 1 ELSE 2 END, creado_en DESC";
$consulta = $conexion->prepare($sql);
$consulta->execute($args);
$solicitudes = $consulta->fetchAll();

$pendientes = (int) $conexion
    ->query("SELECT COUNT(*) FROM solicitudes_reserva WHERE estado='Pendiente'")
    ->fetchColumn();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Solicitudes de reserva | FAMEX</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <header class="admin-topbar">
            <div>
                <span class="admin-kicker">Operador</span>
                <h1>Solicitudes de reserva</h1>
            </div>
            <nav class="admin-nav">
                <a class="admin-btn secondary" href="dashboard.php">Panel general</a>
                <a class="admin-btn danger" href="logout.php">Salir</a>
            </nav>
        </header>

        <?php if ($mensaje): ?>
            <p class="admin-success"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <section class="admin-panel request-summary">
            <div>
                <span class="admin-kicker">Notificaciones</span>
                <h2><?= $pendientes ?> pendientes</h2>
                <p>Revisa las solicitudes que llegan desde el formulario público y actualiza su estado.</p>
            </div>
        </section>

        <section class="admin-panel">
            <form class="filter-bar">
                <label>
                    Estado
                    <select name="estado">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado ?>" <?= $estadoFiltro === $estado ? "selected" : "" ?>>
                                <?= $estado ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Buscar
                    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Empresa, RFC, correo o stand">
                </label>
                <button class="admin-btn">Filtrar</button>
                <a class="admin-btn secondary" href="solicitudes.php">Limpiar</a>
            </form>
        </section>

        <section class="request-list">
            <?php foreach ($solicitudes as $solicitud): ?>
                <?php
                $extras = json_decode($solicitud["stands_adicionales"] ?? "[]", true);
                $extras = is_array($extras) ? $extras : [];
                $estadoClase = strtolower($solicitud["estado"]);
                ?>
                <article class="admin-panel request-card">
                    <div class="request-card-head">
                        <div>
                            <span class="admin-kicker"><?= htmlspecialchars($solicitud["creado_en"]) ?></span>
                            <h2><?= htmlspecialchars($solicitud["empresa"]) ?></h2>
                        </div>
                        <span class="pill request-<?= htmlspecialchars($estadoClase) ?>">
                            <?= htmlspecialchars($solicitud["estado"]) ?>
                        </span>
                    </div>

                    <div class="request-grid">
                        <p><b>Stand:</b> <?= htmlspecialchars($solicitud["stand"]) ?></p>
                        <p><b>RFC:</b> <?= htmlspecialchars($solicitud["rfc"]) ?></p>
                        <p><b>Teléfono:</b> <?= htmlspecialchars($solicitud["lada"] . " " . $solicitud["telefono"]) ?></p>
                        <p><b>Correo:</b> <?= htmlspecialchars($solicitud["correo"]) ?></p>
                        <p><b>Operación:</b> <?= htmlspecialchars($solicitud["operacion"]) ?></p>
                        <p><b>País:</b> <?= htmlspecialchars($solicitud["pais"]) ?></p>
                        <p><b>Ciudad:</b> <?= htmlspecialchars($solicitud["ciudad"]) ?></p>
                        <p><b>C.P.:</b> <?= htmlspecialchars($solicitud["codigo_postal"]) ?></p>
                        <p class="full-row"><b>Dirección:</b> <?= htmlspecialchars($solicitud["direccion"]) ?></p>
                        <p><b>Representante:</b> <?= htmlspecialchars($solicitud["representante"]) ?></p>
                        <p><b>Puesto:</b> <?= htmlspecialchars($solicitud["puesto"]) ?></p>
                        <p><b>Tel. representante:</b> <?= htmlspecialchars($solicitud["lada_representante"] . " " . $solicitud["telefono_representante"]) ?></p>
                        <p><b>Web:</b> <?= htmlspecialchars($solicitud["web"] ?: "—") ?></p>
                    </div>

                    <?php if ($extras): ?>
                        <div class="request-extra">
                            <b>Stands adicionales:</b>
                            <?php foreach ($extras as $extra): ?>
                                <span><?= htmlspecialchars(($extra["pabellon"] ?? "") . " - " . ($extra["numero"] ?? "")) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (trim($solicitud["comentarios"] ?? "") !== ""): ?>
                        <p class="request-comments"><b>Comentarios:</b> <?= htmlspecialchars($solicitud["comentarios"]) ?></p>
                    <?php endif; ?>

                    <form method="post" class="request-status-form">
                        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                        <input type="hidden" name="id" value="<?= (int) $solicitud["id"] ?>">
                        <label>
                            Estado
                            <select name="estado">
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= $estado ?>" <?= $solicitud["estado"] === $estado ? "selected" : "" ?>>
                                        <?= $estado ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="admin-btn accent">Guardar estado</button>
                    </form>
                </article>
            <?php endforeach; ?>

            <?php if (!$solicitudes): ?>
                <section class="admin-panel">
                    <p>No hay solicitudes con los filtros seleccionados.</p>
                </section>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
