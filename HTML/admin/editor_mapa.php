<?php
require __DIR__ . "/../../includes/validar_sesion.php";
exigirAdmin();
require __DIR__ . "/../../includes/conexion.php";
require __DIR__ . "/../../includes/seguridad.php";
$numeroFontSizeDisponible = asegurarNumeroFontSize($conexion, $config["driver"] ?? "sqlite");
header("Cache-Control: no-store, no-cache, must-revalidate");
$pid = (int) ($_GET["id"] ?? ($_POST["pabellon_id"] ?? 0));
$s = $conexion->prepare("SELECT * FROM pabellones WHERE id=?");
$s->execute([$pid]);
$p = $s->fetch();
if (!$p) {
    exit("Pabellón no encontrado.");
}
if (!$p["imagen"] && $_SERVER["REQUEST_METHOD"] === "GET") {
    header("Location: pabellones.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");
    validarCsrf();
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "delete") {
            $ids = json_decode($_POST["stand_ids"] ?? "[]", true);
            if (!is_array($ids) || !$ids) {
                $ids = [(int) ($_POST["stand_id"] ?? 0)];
            }
            $ids = array_values(
                array_unique(
                    array_filter(array_map("intval", $ids), fn($id) => $id > 0),
                ),
            );
            if (!$ids) {
                throw new RuntimeException(
                    "Selecciona al menos un recuadro para eliminar.",
                );
            }
            $placeholders = implode(",", array_fill(0, count($ids), "?"));
            $q = $conexion->prepare(
                "DELETE FROM stands WHERE pabellon_id=? AND id IN ($placeholders)",
            );
            $q->execute(array_merge([$pid], $ids));
            echo json_encode(["ok" => true, "deleted" => $q->rowCount()]);
            exit();
        }
        $id = (int) ($_POST["stand_id"] ?? 0);
        $numero = trim($_POST["numero"] ?? "");
        if ($numero === "") {
            throw new RuntimeException("Escriba el número del espacio.");
        }
        $clave = strtoupper(
            preg_replace("/[^A-Z0-9]/i", "", $p["clave"] . $numero),
        );
        $numeroFontSizeRaw = trim($_POST["numero_font_size"] ?? "");
        $numeroFontSize = $numeroFontSizeDisponible && $numeroFontSizeRaw !== ""
            ? min(120, max(4, (float) $numeroFontSizeRaw))
            : null;
        $vals = [
            $clave,
            $numero,
            $p["clave"] === "CH"
                ? "Sin categoria"
                : $_POST["categoria"] ?? "Premium",
            round((float) $_POST["x"], 1),
            round((float) $_POST["y"], 1),
            round(max(5, (float) $_POST["ancho"]), 1),
            round(max(5, (float) $_POST["alto"]), 1),
        ];
        if ($numeroFontSizeDisponible) {
            $vals[] = $numeroFontSize;
        } elseif ($numeroFontSizeRaw !== "") {
            throw new RuntimeException(
                "Hostinger no permitió crear la columna numero_font_size. Ejecuta: ALTER TABLE stands ADD COLUMN numero_font_size DECIMAL(10,3) NULL;",
            );
        }
        $vals[] = $pid;
        if ($id) {
            $sql = $numeroFontSizeDisponible
                ? "UPDATE stands SET clave=?,numero=?,categoria=?,x=?,y=?,ancho=?,alto=?,numero_font_size=? WHERE pabellon_id=? AND id=?"
                : "UPDATE stands SET clave=?,numero=?,categoria=?,x=?,y=?,ancho=?,alto=? WHERE pabellon_id=? AND id=?";
            $q = $conexion->prepare($sql);
            $vals[] = $id;
            $q->execute($vals);
        } else {
            $sql = $numeroFontSizeDisponible
                ? "INSERT INTO stands (clave,numero,categoria,x,y,ancho,alto,numero_font_size,pabellon_id,estado,bloqueado) VALUES (?,?,?,?,?,?,?,?,?,'Disponible',0)"
                : "INSERT INTO stands (clave,numero,categoria,x,y,ancho,alto,pabellon_id,estado,bloqueado) VALUES (?,?,?,?,?,?,?,?,'Disponible',0)";
            $q = $conexion->prepare($sql);
            $q->execute($vals);
            $id = (int) $conexion->lastInsertId();
        }
        $savedQuery = $conexion->prepare(
            "SELECT * FROM stands WHERE id=? AND pabellon_id=?",
        );
        $savedQuery->execute([$id, $pid]);
        $savedStand = $savedQuery->fetch();
        if (!$savedStand) {
            throw new RuntimeException(
                "No fue posible confirmar el recuadro guardado.",
            );
        }
        echo json_encode([
            "ok" => true,
            "id" => $id,
            "stand" => $savedStand,
        ]);
    } catch (Throwable $e) {
        http_response_code(422);
        echo json_encode(["ok" => false, "error" => $e->getMessage()]);
    }
    exit();
}
$q = $conexion->prepare(
    "SELECT * FROM stands WHERE pabellon_id=? AND x IS NOT NULL ORDER BY numero",
);
$q->execute([$pid]);
$stands = $q->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Editor <?= htmlspecialchars(
    $p["nombre"],
) ?></title>
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
    <main class="editor-shell">
        <header class="admin-topbar">
            <div>
                <span class="admin-kicker">Editor visual</span>
                <h1><?= htmlspecialchars($p["nombre"]) ?></h1>
                <p>Modo normal para agregar, arrastra sobre el plano para dibujar un recuadro nuevo.</p>
        <p class="admin-hint">Edición avanzada para seleccionar, mover, ajustar, guardar o eliminar uno o varios recuadros.</p>
        <p class="admin-hint">Usa Ctrl, Cmd o Shift + clic para seleccionar varios, presiona Esc para cancelar la selección.</p>
    </div>
    <div class="admin-actions">
        <button type="button" id="advancedEdit" class="admin-btn advanced-edit-button" aria-pressed="false">
            Edición avanzada
        </button>
        
            <button type="button" id="deleteBox" class="admin-btn danger" hidden>
                Eliminar recuadro
            </button>
                <a class="admin-btn secondary" id="finishEditor" href="dashboard.php">Volver</a>
            </div>
        </header>
        <div class="editor-layout">
            <section class="editor-canvas" id="editorCanvas" style="aspect-ratio:<?= (int) $p[
    "ancho"
] ?>/<?= (int) $p["alto"] ?>"><img src="../../<?= htmlspecialchars(
    $p["imagen"] ?? "IMG/Areas.png",
) ?>" draggable="false" alt=""><svg id="editorOverlay" viewBox="0 0 <?= (int) $p[
    "ancho"
] ?> <?= (int) $p[
     "alto"
 ] ?>" preserveAspectRatio="none"></svg>
            </section>
            <aside class="admin-panel">
            <h2>Propiedades</h2>
            <form id="boxForm" class="admin-form">
                <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                <input type="hidden" name="pabellon_id" value="<?= $pid ?>">
        <input type="hidden" name="stand_id">
        <input type="hidden" name="action" value="save">
        <label>Número<input name="numero" placeholder="Ej. A-01" required>
        </label>
        <label>Tamaño número<input name="numero_font_size" type="number" min="4" max="120" step="1" placeholder="Auto">
        </label>
        <div class="bulk-font-control">
            <label>Tamaño para todos<input id="globalNumberFontSize" type="number" min="4" max="120" step="1" placeholder="Ej. 12"></label>
            <button type="button" id="applyGlobalNumberFont" class="admin-btn secondary">Aplicar a todos</button>
        </div>
        <label>Categoría<select name="categoria">
            <option>Premium</option>
            <option value="Estandar">Estándar</option>
            <option value="Economico">Económico</option>
            <option>PyMES</option>
        </select></label>
        <?php foreach (
    ["x" => "X", "y" => "Y", "ancho" => "Ancho", "alto" => "Alto"]
    as $n => $l
): ?><label><?= $l ?><input type="number" step="0.1" name="<?= $n ?>" required>
        </label><?php endforeach; ?>
        <button type="button" id="saveSelection" class="admin-btn advanced-save-button" hidden>
            Guardar cambios
        </button>
        <p id="selectionStatus">
            Arrastra sobre el plano para dibujar un recuadro nuevo.
        </p>
        </form>
        </aside>
    </div>
</main>
<script>window.MAP_EDITOR={width:<?= (int) $p[
    "ancho"
] ?>,height:<?= (int) $p["alto"] ?>,stands:<?= json_encode(
    $stands,
    JSON_UNESCAPED_UNICODE,
) ?>};
</script>
<script src="../../JS/map-editor.js?v=<?= filemtime(__DIR__ . "/../../JS/map-editor.js") ?>"></script>
</body>
</html>
