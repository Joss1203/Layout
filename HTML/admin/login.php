<?php
declare(strict_types=1);

$sessionLifetime = 60 * 60 * 24 * 30;
ini_set("session.gc_maxlifetime", (string) $sessionLifetime);
session_set_cookie_params([
    "lifetime" => $sessionLifetime,
    "path" => "/",
    "httponly" => true,
    "samesite" => "Lax",
]);
session_start();
require __DIR__ . "/../../includes/conexion.php";

if (!empty($_SESSION["usuario_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $s = $conexion->prepare(
        "SELECT * FROM usuarios WHERE usuario=? AND activo=1 LIMIT 1",
    );
    $s->execute([trim($_POST["usuario"] ?? "")]);
    $u = $s->fetch();
    if ($u && password_verify($_POST["password"] ?? "", $u["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION = [
            "usuario_id" => (int) $u["id"],
            "usuario" => $u["usuario"],
            "rol" => $u["rol"],
        ];
        header("Location: dashboard.php");
        exit();
    }
    $error = "Usuario o contraseña incorrectos.";
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso | FAMEX</title>
    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
    <main class="admin-login">
        <form method="post" class="admin-card">
            <a href="https://f-airmexico.com.mx/es">
                <img src="../../IMG/Logo_FAMEX-2027.png" alt="FAMEX" class="admin-logo" >
            </a>
            <h1>Acceso al sistema</h1>
            <p>Personal autorizado</p>

            <?php if ($error): ?>
                <div class="admin-alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <label>
                Usuario
                <input name="usuario" autocomplete="username" required>
            </label>
            <label>
                Contraseña
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button class="admin-btn accent">Ingresar</button>
            <a class="admin-btn secondary" href="../../index.php">Volver</a>
        </form>
    </main>
</body>
</html>
