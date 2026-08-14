<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . "/includes/conexion.php";

$id = (int) ($_GET["id"] ?? 0);
$s = $conexion->prepare("SELECT * FROM pabellones WHERE id=? AND activo=1");
$s->execute([$id]);
$p = $s->fetch();

if (!$p) {
    http_response_code(404);
    exit("Área no encontrada");
}

$pabellones = $conexion
    ->query(
        "SELECT id,nombre FROM pabellones WHERE activo=1 ORDER BY orden,nombre",
    )
    ->fetchAll();
$rol = $_SESSION["rol"] ?? "";
$sesionActiva = !empty($_SESSION["usuario_id"]);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($p["nombre"]) ?> | FAMEX</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/pavilion.css">
</head>
<body>
    <header class="site-header compact">
        <a class="back-link" href="index.php">← Volver</a>
        <div>
            
            <h1><?= htmlspecialchars($p["nombre"]) ?></h1>
        </div>
        <?php if (!$sesionActiva): ?>
            <a
                class="access-link icon-access-link"
                href="HTML/admin/login.php"
                aria-label="Iniciar sesión"
                title="Iniciar sesión"
            >
                <span class="login-arrow-icon" aria-hidden="true"></span>
            </a>
        <?php endif; ?>
    </header>

    <main class="pavilion-layout">
        <aside class="availability">
            <div class="pavilion-brand">
                
                <h2><?= htmlspecialchars($p["nombre"]) ?></h2>
            </div>

            <div class="category-code">
                <h3>Código de color</h3>
                <span class="premium"><i></i>Premium</span>
                <span class="standard"><i></i>Estándar</span>
                <span class="economic"><i></i>Económico</span>
                <span class="pymes"><i></i>PyMES</span>
                <span class="uncategorized"><i></i>Sin categoría</span>
                <span class="occupied"><i></i>Ocupado</span>
            </div>

            <h3>Espacios</h3>
            <div class="filters">
                <select id="filtroPabellon" aria-label="Filtrar por pabellón">
                    <?php foreach ($pabellones as $area): ?>
                        <option
                            value="<?= $area["id"] ?>"
                            <?= $area["id"] == $id ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($area["nombre"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select
                    id="filtroCategoria"
                    class="category-filter"
                    aria-label="Filtrar por categoría"
                >
                    <option value="">Todas las categorías</option>
                    <option class="premium">Premium</option>
                    <option value="Estandar" class="standard">Estándar</option>
                    <option value="Economico" class="economic">Económico</option>
                    <option class="pymes">PyMES</option>
                    <option value="Sin categoria" class="uncategorized">Sin categoría</option>
                </select>

                <select id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option>Disponible</option>
                    <option>Ocupado</option>
                </select>
            </div>

            <div id="standList" class="stand-list"></div>
        </aside>

        <section class="map-panel">
            <div class="map-toolbar" aria-label="Controles de mapa">
                <button
                    type="button"
                    class="map-tool-button"
                    data-zoom="in"
                    aria-label="Acercar mapa"
                    title="Acercar mapa"
                >
                    +
                </button>
                <button
                    type="button"
                    class="map-tool-button"
                    data-zoom="out"
                    aria-label="Alejar mapa"
                    title="Alejar mapa"
                >
                    −
                </button>
            </div>

            <div class="map-viewport">
                <div
                    class="public-map <?= $p["imagen"] ? "" : "without-map" ?>"
                    data-pavilion="<?= $id ?>"
                    style="aspect-ratio:<?= (int) $p["ancho"] ?>/<?= (int) $p["alto"] ?>"
                >
                    <?php if ($p["imagen"]): ?>
                        <img
                            src="<?= htmlspecialchars($p["imagen"]) ?>"
                            alt="Mapa de <?= htmlspecialchars($p["nombre"]) ?>"
                        >
                    <?php else: ?>
                        <div class="map-empty">
                            <strong>Plano de <?= htmlspecialchars($p["nombre"]) ?> pendiente</strong>
                            <span>El administrador debe cargar la imagen correspondiente.</span>
                        </div>
                    <?php endif; ?>

                    <svg
                        id="standOverlay"
                        viewBox="0 0 <?= (int) $p["ancho"] ?> <?= (int) $p["alto"] ?>"
                        preserveAspectRatio="none"
                    ></svg>
                </div>
            </div>
        </section>
    </main>

    <dialog id="standDialog">
        <button class="dialog-close" aria-label="Cerrar">×</button>
        <div id="standDetail"></div>
    </dialog>

    <div id="noticeToast" class="notice-toast" role="status" aria-live="polite"></div>

    <script>
        window.PABELLON_ID = <?= $id ?>;
        window.PAVILION_KEY = <?= json_encode($p["clave"]) ?>;
        window.PAVILION_WIDTH = <?= (int) $p["ancho"] ?>;
        window.EDIT_URL = <?= json_encode($rol ? "HTML/admin/editar_stand.php?id=" : null) ?>;
    </script>
    <script src="JS/public-map.js"></script>
</body>
</html>
