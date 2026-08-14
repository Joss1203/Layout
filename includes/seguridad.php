<?php
declare(strict_types=1);
function csrfToken(): string
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(24));
    }
    return $_SESSION["csrf"];
}
function validarCsrf(): void
{
    if (!hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")) {
        http_response_code(419);
        exit("La sesión del formulario expiró. Regrese e intente de nuevo.");
    }
}
function subirImagen(string $campo, string $carpeta, string $prefijo): ?string
{
    if (
        empty($_FILES[$campo]) ||
        $_FILES[$campo]["error"] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }
    if (
        $_FILES[$campo]["error"] !== UPLOAD_ERR_OK ||
        $_FILES[$campo]["size"] > 5 * 1024 * 1024
    ) {
        throw new RuntimeException("La imagen no pudo cargarse o supera 5 MB.");
    }
    $tipos = [
        "image/png" => "png",
        "image/jpeg" => "jpg",
        "image/webp" => "webp",
        "image/gif" => "gif",
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$campo]["tmp_name"]);
    if (!isset($tipos[$mime])) {
        throw new RuntimeException(
            "Formato no permitido. Use PNG, JPG, WEBP o GIF.",
        );
    }
    $dir = __DIR__ . "/../HTML/admin/uploads/" . $carpeta;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name =
        preg_replace("/[^a-z0-9_-]/i", "-", $prefijo) .
        "-" .
        bin2hex(random_bytes(6)) .
        "." .
        $tipos[$mime];
    if (!move_uploaded_file($_FILES[$campo]["tmp_name"], $dir . "/" . $name)) {
        throw new RuntimeException("No fue posible guardar la imagen.");
    }
    return "HTML/admin/uploads/" . $carpeta . "/" . $name;
}
