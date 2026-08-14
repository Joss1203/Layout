<?php
require __DIR__ . "/../../includes/validar_sesion.php";
require __DIR__ . "/../../includes/conexion.php";

$stats = $conexion
    ->query(
        "SELECT COUNT(*) total,SUM(CASE WHEN estado='Ocupado' THEN 1 ELSE 0 END) ocupados FROM stands",
    )
    ->fetch();
$solicitudesPendientes = (int) $conexion
    ->query("SELECT COUNT(*) FROM solicitudes_reserva WHERE estado='Pendiente'")
    ->fetchColumn();
$pabellones = $conexion
    ->query("SELECT * FROM pabellones WHERE activo=1 ORDER BY orden,nombre")
    ->fetchAll();
$pabs = count($pabellones);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Panel FAMEX</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="../../CSS/pavilion.css">
    <link rel="stylesheet" href="overview.css">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <header class="admin-topbar">
            <div>
                <span class="admin-kicker"><?= htmlspecialchars(ucfirst($rolActual)) ?></span>
                <h1>Hola, <?= htmlspecialchars($_SESSION["usuario"]) ?></h1>
            </div>
            <nav class="admin-nav">
                
                <?php if ($rolActual === "admin"): ?>
                    <a class="admin-btn" href="pabellones.php">Editar imágenes de pabellones</a>
                    <a class="admin-btn" href="usuarios.php">Administrar Usuarios</a>
                <?php endif; ?>
                <a class="admin-btn accent" href="solicitudes.php">
                    Solicitudes <?= $solicitudesPendientes ? "(" . $solicitudesPendientes . ")" : "" ?>
                </a>
                <a class="admin-btn secondary" href="../../index.php">Vista del público</a>
                <a class="admin-btn danger" href="logout.php">Salir</a>
            </nav>
        </header>

        <section class="admin-panel admin-overview">
            <div>
                
                <h2>Áreas de exposición</h2>
                <p>Selecciona un pabellón o Chalets directamente en el plano para gestionar sus espacios.
                </p>
                <?php if ($rolActual === "admin"): ?>
                    <a class="admin-btn accent" href="editor_areas.php">Editar Áreas de exposición</a>
                <?php endif; ?>
            </div>
            <?php
            $rutaBase = "../../";
            $modoGestion = true;
            include __DIR__ . "/../../includes/mapa_general.php";
            ?>
        </section>


        <section class="admin-panel">
            <h2>Funciones de tu perfil</h2>
            <?php if ($rolActual === "admin"): ?>
                <p>Como administrador puedes cambiar mapas, dibujar o eliminar recuadros, gestionar ocupación, logotipos y usuarios.</p>
            <?php else: ?>
                <p>Como operador puedes filtrar espacios por pabellón, asignar empresa y logotipo, marcar un espacio ocupado o liberarlo. También puedes revisar solicitudes entrantes y cambiar su estado a pendiente, validado o rechazado.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
