<?php
require __DIR__ . "/../../includes/validar_sesion.php";
exigirAdmin();
require __DIR__ . "/../../includes/conexion.php";
require __DIR__ . "/../../includes/seguridad.php";
$error = "";
$ok = "";
function adminActivos(PDO $db): int
{
    return (int) $db
        ->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin' AND activo=1")
        ->fetchColumn();
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validarCsrf();
    $action = $_POST["action"] ?? "create";
    try {
        if ($action === "create") {
            $user = trim($_POST["usuario"] ?? "");
            $pass = $_POST["password"] ?? "";
            $rol = $_POST["rol"] ?? "operador";
            if (strlen($user) < 3 || strlen($pass) < 10) {
                throw new RuntimeException(
                    "Usa un nombre de al menos 3 caracteres y una contraseña de al menos 10.",
                );
            }
            if (!in_array($rol, ["admin", "operador"], true)) {
                throw new RuntimeException("Rol inválido.");
            }
            $conexion
                ->prepare(
                    "INSERT INTO usuarios (usuario,password_hash,rol) VALUES (?,?,?)",
                )
                ->execute([
                    $user,
                    password_hash($pass, PASSWORD_DEFAULT),
                    $rol,
                ]);
            $ok = "Usuario creado.";
        } elseif ($action === "update") {
            $id = (int) ($_POST["id"] ?? 0);
            $s = $conexion->prepare("SELECT * FROM usuarios WHERE id=?");
            $s->execute([$id]);
            $actual = $s->fetch();
            if (!$actual) {
                throw new RuntimeException("Usuario no encontrado.");
            }
            $user = trim($_POST["usuario"] ?? "");
            $rol = $_POST["rol"] ?? "operador";
            $pass = $_POST["password"] ?? "";
            if (
                strlen($user) < 3 ||
                !in_array($rol, ["admin", "operador"], true)
            ) {
                throw new RuntimeException("Datos de usuario inválidos.");
            }
            if (
                $actual["rol"] === "admin" &&
                $rol !== "admin" &&
                $actual["activo"] &&
                adminActivos($conexion) <= 1
            ) {
                throw new RuntimeException(
                    "No puedes cambiar el rol del último administrador activo.",
                );
            }
            if ($pass !== "" && strlen($pass) < 10) {
                throw new RuntimeException(
                    "La nueva contraseña debe tener al menos 10 caracteres.",
                );
            }
            if ($pass !== "") {
                $conexion
                    ->prepare(
                        "UPDATE usuarios SET usuario=?,rol=?,password_hash=? WHERE id=?",
                    )
                    ->execute([
                        $user,
                        $rol,
                        password_hash($pass, PASSWORD_DEFAULT),
                        $id,
                    ]);
            } else {
                $conexion
                    ->prepare("UPDATE usuarios SET usuario=?,rol=? WHERE id=?")
                    ->execute([$user, $rol, $id]);
            }
            if ($id === (int) $_SESSION["usuario_id"]) {
                $_SESSION["usuario"] = $user;
                $_SESSION["rol"] = $rol;
            }
            $ok = "Usuario actualizado.";
        } elseif ($action === "deactivate") {
            $id = (int) ($_POST["id"] ?? 0);
            if ($id === (int) $_SESSION["usuario_id"]) {
                throw new RuntimeException(
                    "No puedes eliminar tu propia sesión.",
                );
            }
            $s = $conexion->prepare("SELECT * FROM usuarios WHERE id=?");
            $s->execute([$id]);
            $actual = $s->fetch();
            if (!$actual) {
                throw new RuntimeException("Usuario no encontrado.");
            }
            if (
                $actual["rol"] === "admin" &&
                $actual["activo"] &&
                adminActivos($conexion) <= 1
            ) {
                throw new RuntimeException(
                    "No puedes eliminar al último administrador activo.",
                );
            }
            $conexion
                ->prepare("UPDATE usuarios SET activo=0 WHERE id=?")
                ->execute([$id]);
            $ok = "Usuario eliminado de los accesos. Puede restaurarse.";
        } elseif ($action === "activate") {
            $conexion
                ->prepare("UPDATE usuarios SET activo=1 WHERE id=?")
                ->execute([(int) $_POST["id"]]);
            $ok = "Usuario restaurado.";
        }
    } catch (Throwable $e) {
        $error =
            $e instanceof PDOException
                ? "No fue posible guardar. El nombre de usuario puede estar repetido."
                : $e->getMessage();
    }
}
$users = $conexion
    ->query(
        "SELECT id,usuario,rol,activo FROM usuarios ORDER BY activo DESC,usuario",
    )
    ->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Usuarios | FAMEX</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="users.css">
</head>
<body class="admin-body">
    <main class="admin-shell">
        <header class="admin-topbar">
            <div>
                <span class="admin-kicker">Seguridad</span>
                <h1>Usuarios internos</h1>
            </div>
            <a class="admin-btn secondary" href="dashboard.php">Volver</a>
        </header>
<?php
if ($error): ?><div class="admin-alert"><?= htmlspecialchars(
    $error,
) ?></div><?php endif;
if ($ok): ?><div class="admin-success"><?= htmlspecialchars(
    $ok,
) ?></div><?php endif;
?>
<section class="admin-panel">
    <h2>Crear usuario</h2>
    <form method="post" class="filter-bar">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="create">
        <label>Usuario<input name="usuario" minlength="3" required></label>
        <label>Contraseña temporal<input type="password" name="password" minlength="10" required></label>
        <label>Rol<select name="rol"><option value="operador">Operador</option><option value="admin">Administrador</option></select></label>
        <button class="admin-btn">Crear usuario</button>
    </form>
</section>
<section class="user-grid"><?php foreach (
    $users
    as $u
): ?><article class="admin-panel user-card <?= $u["activo"]
    ? ""
    : "inactive-user" ?>">
<div class="user-card-head">
<div><span class="pill <?= $u[
    "activo"
]
    ? "free"
    : "busy" ?>"><?= $u["activo"]
    ? "Activo"
    : "Eliminado" ?></span><h2><?= htmlspecialchars(
    $u["usuario"],
) ?></h2></div>
<span class="pill"><?= htmlspecialchars(
    ucfirst($u["rol"]),
) ?></span>
</div>
<form method="post" class="admin-form">
<input type="hidden" name="csrf" value="<?= csrfToken() ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="<?= $u[
    "id"
] ?>"><label>Nombre de usuario<input name="usuario" value="<?= htmlspecialchars(
    $u["usuario"],
) ?>" minlength="3" required></label><label>Rol<select name="rol"><option value="operador" <?= $u[
    "rol"
] === "operador"
    ? "selected"
    : "" ?>>Operador</option><option value="admin" <?= $u["rol"] === "admin"
    ? "selected"
    : "" ?>>Administrador</option></select></label>
<label>Nueva contraseña<input type="password" name="password" minlength="10" placeholder="Dejar vacío para conservarla"></label>
<button class="admin-btn">Guardar cambios</button>
</form>
<form method="post" class="user-state-form">
<input type="hidden" name="csrf" value="<?= csrfToken() ?>">
<input type="hidden" name="id" value="<?= $u[
    "id"
] ?>"><?php if (
    $u["activo"]
): ?><button class="admin-btn danger" name="action" value="deactivate" <?= $u[
    "id"
] === $_SESSION["usuario_id"]
    ? "disabled"
    : "" ?> onclick="return confirm('¿Eliminar el acceso de este usuario?')">Eliminar usuario</button><?php else: ?><button class="admin-btn secondary" name="action" value="activate">Restaurar usuario</button><?php endif; ?>
</form>
</article><?php endforeach; ?></section>
</main>
</body>
</html>
