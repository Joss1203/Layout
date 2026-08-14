<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionLifetime = 60 * 60 * 24 * 30;
    ini_set("session.gc_maxlifetime", (string) $sessionLifetime);
    session_set_cookie_params([
        "lifetime" => $sessionLifetime,
        "path" => "/",
        "httponly" => true,
        "samesite" => "Lax",
    ]);
    session_start();
}
if (empty($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}
$rolActual = $_SESSION["rol"] ?? "";
function exigirAdmin(): void
{
    if (($_SESSION["rol"] ?? "") !== "admin") {
        http_response_code(403);
        exit("Acceso exclusivo para administradores.");
    }
}
