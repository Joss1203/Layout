<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . "/includes/conexion.php";

$pabellones = $conexion
    ->query("SELECT * FROM pabellones WHERE activo=1 ORDER BY orden,nombre")
    ->fetchAll();
$sesionActiva = !empty($_SESSION["usuario_id"]);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chalets y pabellones | FAMEX 2027</title>
    <link rel="stylesheet" href="CSS/style.css">
    <link rel="stylesheet" href="CSS/pavilion.css">
</head>
<body class="home-page">
    <header class="site-header compact">
        <img src="IMG/Logo_FAMEX-2027.png" alt="FAMEX 2027" class="logo" href="https://f-airmexico.com.mx/es" style="width: 100px; height: 80px; object-fit: contain;">
        <div>
            <span class="eyebrow">Mapa comercial</span>
            <h1 class="hero-title">Áreas de exposición</h1>
            <p>Consulta espacios disponibles, ocupados y su categoría.</p>
        </div>
        <a
            class="access-link icon-access-link"
            href="HTML/admin/login.php"
            aria-label="Iniciar sesión"
            title="Iniciar sesión"
        >
            <span class="login-arrow-icon" aria-hidden="true"></span>
        </a>
    </header>

    <main class="landing">
        <section class="hero-copy">
            <h2>Selecciona un pabellón o Chalets</h2>
            <p>Ubica el área en el plano general y selecciona una opción para consultar su disponibilidad.</p>
        </section>

        <section class="overview-panel">
            <?php
            $rutaBase = "";
            $modoGestion = false;
            include __DIR__ . "/includes/mapa_general.php";
            ?>
        </section>

        <section class="area-grid">
            <?php foreach ($pabellones as $p): ?>
                <a
                    class="area-card <?= $p["clave"] === "CH" ? "featured" : "" ?>"
                    href="pabellon.php?id=<?= (int) $p["id"] ?>"
                >
                    <span class="area-key"><?= htmlspecialchars($p["clave"]) ?></span>
                    <h3><?= htmlspecialchars($p["nombre"]) ?></h3>
                    <span>Ver disponibilidad →</span>
                </a>
            <?php endforeach; ?>
        </section>
    </main>

    <footer>FAMEX 2027</footer>
</body>
</html>
