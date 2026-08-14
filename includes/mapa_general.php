<?php
// Requiere $pabellones. $rutaBase resuelve recursos y $modoGestion cambia el destino.
$rutaBase = $rutaBase ?? "";
$modoGestion = $modoGestion ?? false;
$pabellones = is_iterable($pabellones ?? null) ? $pabellones : [];
$mapaAreas = "IMG/Areas.png";

if (isset($conexion)) {
    $valor = $conexion
        ->query("SELECT valor FROM configuracion WHERE clave='mapa_areas'")
        ->fetchColumn();

    if ($valor) {
        $mapaAreas = $valor;
    }
}
?>
<div class="overview-map">
    <img
        src="<?= $rutaBase . htmlspecialchars($mapaAreas) ?>"
        alt="Mapa general de áreas de exposición FAMEX"
    >

    <svg
        viewBox="0 0 1200 800"
        preserveAspectRatio="none"
        aria-label="Selección de áreas"
    >
        <?php foreach ($pabellones as $area): ?>
            <?php
            if (($area["area_x"] ?? null) === null) {
                continue;
            }

            $href = $modoGestion
                ? "stands.php?pabellon=" . (int) $area["id"]
                : $rutaBase . "pabellon.php?id=" . (int) $area["id"];
            ?>
            <a
                href="<?= htmlspecialchars($href) ?>"
                aria-label="Abrir <?= htmlspecialchars($area["nombre"]) ?>"
            >
                <rect
                    class="overview-zone"
                    x="<?= $area["area_x"] ?>"
                    y="<?= $area["area_y"] ?>"
                    width="<?= $area["area_ancho"] ?>"
                    height="<?= $area["area_alto"] ?>"
                    rx="8"
                />
            </a>
        <?php endforeach; ?>
    </svg>
</div>
