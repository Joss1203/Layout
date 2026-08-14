<?php
require __DIR__ . "/../../includes/validar_sesion.php";
exigirAdmin();
require __DIR__ . "/../../includes/conexion.php";
require __DIR__ . "/../../includes/seguridad.php";
$error = "";
$ok = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCsrf();
    $action = $_POST["action"] ?? "update";
    try {
        if ($action === "create") {
            $clave = strtoupper(
                preg_replace("/[^A-Z0-9]/i", "", trim($_POST["clave"] ?? "")),
            );
            $nombre = trim($_POST["nombre"] ?? "");
            if ($clave === "" || strlen($clave) > 10 || $nombre === "") {
                throw new RuntimeException(
                    "Escribe una clave válida y el nombre del pabellón.",
                );
            }
            $img = subirImagen("imagen", "mapas", $clave);
            $ancho = 1200;
            $alto = 800;
            if ($img && ($size = getimagesize(__DIR__ . "/../../" . $img))) {
                $ancho = $size[0];
                $alto = $size[1];
            }
            $orden = (int) $conexion
                ->query("SELECT COALESCE(MAX(orden),0)+1 FROM pabellones")
                ->fetchColumn();
            $conexion
                ->prepare(
                    "INSERT INTO pabellones (clave,nombre,imagen,ancho,alto,orden,activo,area_x,area_y,area_ancho,area_alto) VALUES (?,?,?,?,?,?,1,900,600,120,70)",
                )
                ->execute([$clave, $nombre, $img, $ancho, $alto, $orden]);
            $ok =
                "Pabellón creado correctamente. También se creó su selección en Áreas de exposición.";
        } elseif ($action === "deactivate") {
            $id = (int) ($_POST["id"] ?? 0);
            $conexion
                ->prepare("UPDATE pabellones SET activo=0 WHERE id=?")
                ->execute([$id]);
            $ok =
                "Pabellón eliminado de las vistas. Sus espacios se conservaron y puede restaurarse.";
        } elseif ($action === "activate") {
            $id = (int) ($_POST["id"] ?? 0);
            $conexion
                ->prepare("UPDATE pabellones SET activo=1 WHERE id=?")
                ->execute([$id]);
            $ok = "Pabellón restaurado correctamente.";
        } else {
            $id = (int) ($_POST["id"] ?? 0);
            $s = $conexion->prepare("SELECT * FROM pabellones WHERE id=?");
            $s->execute([$id]);
            $p = $s->fetch();
            if (!$p) {
                throw new RuntimeException("Pabellón no encontrado.");
            }
            $img = subirImagen("imagen", "mapas", $p["clave"]);
            $ancho = (int) $p["ancho"];
            $alto = (int) $p["alto"];
            if ($img && ($size = getimagesize(__DIR__ . "/../../" . $img))) {
                $ancho = $size[0];
                $alto = $size[1];
            }
            $conexion
                ->prepare(
                    "UPDATE pabellones SET nombre=?,imagen=COALESCE(?,imagen),ancho=?,alto=? WHERE id=?",
                )
                ->execute([trim($_POST["nombre"]), $img, $ancho, $alto, $id]);
            $ok = "Pabellón actualizado.";
        }
    } catch (Throwable $e) {
        $error =
            $e instanceof PDOException
                ? "No fue posible guardar. Verifica que la clave no esté repetida."
                : $e->getMessage();
    }
}
$pabs = $conexion
    ->query(
        "SELECT p.*,(SELECT COUNT(*) FROM stands s WHERE s.pabellon_id=p.id) total FROM pabellones p ORDER BY activo DESC,orden,nombre",
    )
    ->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pabellones y mapas | FAMEX</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="overview.css">
    <link rel="stylesheet" href="pavilions.css">
</head>
<body class="admin-body">
<main class="admin-shell">
<header class="admin-topbar"><div><span class="admin-kicker">Administración</span><h1>Pabellones y mapas</h1></div><nav class="admin-nav"><a class="admin-btn accent" href="editor_areas.php">Editar Áreas de exposición</a><a class="admin-btn secondary" href="dashboard.php">Volver</a></nav></header>
<?php
if ($error): ?><div class="admin-alert"><?= htmlspecialchars(
    $error,
) ?></div><?php endif;
if ($ok): ?><div class="admin-success"><?= htmlspecialchars(
    $ok,
) ?></div><?php endif;
?>
<section class="admin-panel"><h2>Agregar pabellón</h2><form method="post" enctype="multipart/form-data" class="filter-bar"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="create"><label>Clave<input name="clave" maxlength="10" placeholder="Ej. E" required></label><label>Nombre<input name="nombre" maxlength="120" placeholder="Pabellón E" required></label><label>Plano inicial (opcional)<input type="file" name="imagen" accept="image/png,image/jpeg,image/webp,image/gif"></label><button class="admin-btn accent">Crear pabellón</button></form><small>El pabellón aparecerá en Áreas de exposición; posteriormente podrás ajustar su selección.</small></section>
<section class="pavilion-admin-grid"><?php foreach (
    $pabs
    as $p
): ?><article class="admin-panel <?= $p["activo"]
    ? ""
    : "inactive-pavilion" ?>"><?php if (
    $p["imagen"]
): ?><img class="map-thumb" src="../../<?= htmlspecialchars(
    $p["imagen"],
) ?>" alt="Plano de <?= htmlspecialchars(
    $p["nombre"],
) ?>"><?php else: ?><div class="map-thumb empty">Sin plano asignado</div><?php endif; ?>
<div class="pavilion-state"><span class="pill <?= $p["activo"]
    ? "free"
    : "busy" ?>"><?= $p["activo"]
    ? "Activo"
    : "Eliminado de las vistas" ?></span></div>
<form method="post" enctype="multipart/form-data" class="admin-form"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $p[
    "id"
] ?>"><div class="form-pair"><label>Clave<input value="<?= htmlspecialchars(
    $p["clave"],
) ?>" disabled></label><label>Nombre<input name="nombre" value="<?= htmlspecialchars(
    $p["nombre"],
) ?>" required></label></div><label>Nueva imagen del plano<input type="file" name="imagen" accept="image/png,image/jpeg,image/webp,image/gif"></label><button class="admin-btn">Guardar imagen y datos</button><?php if (
    $p["imagen"]
): ?><a class="admin-btn accent" href="editor_mapa.php?id=<?= $p[
    "id"
] ?>">Editar recuadros (<?= $p["total"] ?>)</a><?php endif; ?></form>
<form method="post" class="pavilion-visibility-form" onsubmit="return confirm('¿Confirmas este cambio? Los espacios se conservarán.')"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="id" value="<?= $p[
    "id"
] ?>"><?php if (
    $p["activo"]
): ?><button class="admin-btn danger" name="action" value="deactivate">Eliminar pabellón de las vistas</button><?php else: ?><button class="admin-btn secondary" name="action" value="activate">Restaurar pabellón</button><?php endif; ?></form></article><?php endforeach; ?></section>
</main>
</body>
</html>
