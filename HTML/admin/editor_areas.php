<?php
require __DIR__ . "/../../includes/validar_sesion.php";
exigirAdmin();
require __DIR__ . "/../../includes/conexion.php";
require __DIR__ . "/../../includes/seguridad.php";
$error = "";
$ok = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCsrf();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "delete_zone") {
            $id = (int) ($_POST["selected_id"] ?? 0);
            if (!$id) {
                throw new RuntimeException(
                    "Selecciona una zona para eliminar.",
                );
            }
            $conexion
                ->prepare(
                    "UPDATE pabellones SET area_x=NULL,area_y=NULL,area_ancho=NULL,area_alto=NULL WHERE id=?",
                )
                ->execute([$id]);
            $ok =
                "Selección eliminada. El pabellón y sus espacios se conservaron.";
        } elseif ($action === "add_zone") {
            $id = (int) ($_POST["pabellon_sin_zona"] ?? 0);
            if (!$id) {
                throw new RuntimeException("Selecciona un pabellón.");
            }
            $conexion
                ->prepare(
                    "UPDATE pabellones SET area_x=900,area_y=600,area_ancho=120,area_alto=70 WHERE id=? AND area_x IS NULL",
                )
                ->execute([$id]);
            $ok = "Nueva selección creada. Ahora puedes colocarla en el mapa.";
        } elseif ($action === "zones") {
            $zones = json_decode(
                $_POST["zones"] ?? "[]",
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $q = $conexion->prepare(
                "UPDATE pabellones SET area_x=?,area_y=?,area_ancho=?,area_alto=? WHERE id=?",
            );
            foreach ($zones as $z) {
                $q->execute([
                    max(0, (float) $z["x"]),
                    max(0, (float) $z["y"]),
                    max(10, (float) $z["ancho"]),
                    max(10, (float) $z["alto"]),
                    (int) $z["id"],
                ]);
            }
            $ok = "Zonas actualizadas correctamente.";
        } elseif ($action === "create") {
            $clave = strtoupper(
                preg_replace("/[^A-Z0-9]/i", "", trim($_POST["clave"] ?? "")),
            );
            $nombre = trim($_POST["nombre"] ?? "");
            if ($clave === "" || strlen($clave) > 10 || $nombre === "") {
                throw new RuntimeException(
                    "Escribe una clave válida y el nombre del pabellón.",
                );
            }
            $imagen = subirImagen("plano_pabellon", "mapas", $clave);
            $ancho = 1200;
            $alto = 800;
            if (
                $imagen &&
                ($size = getimagesize(__DIR__ . "/../../" . $imagen))
            ) {
                $ancho = $size[0];
                $alto = $size[1];
            }
            $orden = (int) $conexion
                ->query("SELECT COALESCE(MAX(orden),0)+1 FROM pabellones")
                ->fetchColumn();
            $q = $conexion->prepare(
                "INSERT INTO pabellones (clave,nombre,imagen,ancho,alto,orden,activo,area_x,area_y,area_ancho,area_alto) VALUES (?,?,?,?,?,?,1,900,600,120,70)",
            );
            $q->execute([$clave, $nombre, $imagen, $ancho, $alto, $orden]);
            $ok =
                "Pabellón creado. Su nueva zona aparece en el mapa y puedes moverla.";
        } elseif ($action === "image") {
            $imagen = subirImagen("imagen", "mapas", "areas");
            if (!$imagen) {
                throw new RuntimeException("Selecciona una imagen.");
            }
            $conexion
                ->prepare(
                    "UPDATE configuracion SET valor=? WHERE clave='mapa_areas'",
                )
                ->execute([$imagen]);
            $ok = "Imagen general actualizada.";
        }
    } catch (Throwable $e) {
        $error =
            $e instanceof PDOException
                ? "La clave ya existe. Usa una clave diferente."
                : $e->getMessage();
    }
}
$todos = $conexion
    ->query(
        "SELECT id,clave,nombre,area_x,area_y,area_ancho,area_alto FROM pabellones WHERE activo=1 ORDER BY orden",
    )
    ->fetchAll();
$pabellones = array_values(
    array_filter($todos, fn($p) => $p["area_x"] !== null),
);
$sinZona = array_values(array_filter($todos, fn($p) => $p["area_x"] === null));
$mapa =
    $conexion
        ->query("SELECT valor FROM configuracion WHERE clave='mapa_areas'")
        ->fetchColumn() ?:
    "IMG/Areas.png";
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editar Áreas de exposición</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="overview.css">
    <link rel="stylesheet" href="area-editor.css">
</head>
<body class="admin-body">
    <main class="editor-shell">
        <header class="admin-topbar">
            <div>
                <span class="admin-kicker">Editor visual</span>
                <h1>Áreas de exposición</h1>
                <p>Selecciona, mueve y redimensiona una zona sin perder su posición.</p>
            </div>
            <a class="admin-btn secondary" href="dashboard.php">Terminar</a>
        </header>
<?php
if ($error): ?><div class="admin-alert"><?= htmlspecialchars(
    $error,
) ?></div><?php endif;
if ($ok): ?><div class="admin-success"><?= htmlspecialchars(
    $ok,
) ?></div><?php endif;
?>
<section class="area-tools">
 <article class="admin-panel">
    <h2>Cambiar plano general</h2>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="image">
        <label>Imagen de Áreas de exposición<input type="file" name="imagen" accept="image/png,image/jpeg,image/webp,image/gif" required>
    </label>
    <button class="admin-btn">Guardar nueva imagen</button>
</form>
</article>
        <article class="admin-panel">
        <h2>Crear nuevo pabellón</h2>
        <form method="post" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-pair">
                <label>Clave<input name="clave" maxlength="10" placeholder="Ej. E" required>
            </label>
            <label>Nombre<input name="nombre" maxlength="120" placeholder="Pabellón E" required>
        </label>
    </div>
    <label>Plano del nuevo pabellón (opcional)<input type="file" name="plano_pabellon" accept="image/png,image/jpeg,image/webp,image/gif">
</label>
    <button class="admin-btn accent">Crear pabellón y zona</button>
</form>
</article>
</section>
<div class="area-editor-layout">
<section class="area-canvas" id="areaCanvas"><img src="../../<?= htmlspecialchars(
    $mapa,
) ?>" alt="Áreas de exposición" draggable="false"><div id="areaBoxes"></div>
</section>
<aside class="admin-panel sticky-properties">
<h2>Zona seleccionada</h2>
<form id="areaForm" method="post" class="admin-form">
<input type="hidden" name="csrf" value="<?= csrfToken() ?>">
<input type="hidden" name="zones">
<input type="hidden" name="selected_id" id="selectedAreaId">
<label>Pabellón<select id="areaSelect"><?php foreach (
    $pabellones
    as $p
): ?><option value="<?= $p["id"] ?>"><?= htmlspecialchars(
    $p["nombre"],
) ?> (<?= htmlspecialchars(
     $p["clave"],
 ) ?>)</option><?php endforeach; ?></select></label>
<div class="coordinate-grid"><label>X<input id="areaX" type="number" min="0" max="1200" step="0.1"></label><label>Y<input id="areaY" type="number" min="0" max="800" step="0.1"></label><label>Ancho<input id="areaWidth" type="number" min="10" max="1200" step="0.1"></label><label>Alto<input id="areaHeight" type="number" min="10" max="800" step="0.1"></label></div>
<p>Arrastra el recuadro para moverlo. Arrastra el control de la esquina para cambiar su tamaño.</p>
<div class="admin-actions"><button class="admin-btn" name="action" value="zones">Guardar posiciones</button><button class="admin-btn danger" name="action" value="delete_zone" formnovalidate onclick="return confirm('¿Eliminar esta selección del mapa? El pabellón se conservará.')">Eliminar selección</button></div>
</form>
<?php if (
    $sinZona
): ?><hr><h3>Selecciones eliminadas</h3><form method="post" class="admin-form"><input type="hidden" name="csrf" value="<?= csrfToken() ?>"><input type="hidden" name="action" value="add_zone"><label>Pabellón sin zona<select name="pabellon_sin_zona"><?php foreach (
    $sinZona
    as $p
): ?><option value="<?= $p["id"] ?>"><?= htmlspecialchars(
    $p["nombre"],
) ?></option><?php endforeach; ?></select></label><button class="admin-btn secondary">Crear selección nuevamente</button></form><?php endif; ?>
</aside>
</div>
</main>
<script>window.AREA_ZONES=<?= json_encode(
    $pabellones,
    JSON_UNESCAPED_UNICODE,
) ?>;
</script>
<script src="../../JS/area-editor.js"></script>
</body>
</html>
