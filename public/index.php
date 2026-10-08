<?php

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

require_once __DIR__ . '/../vendor/autoload.php';

\RedBeanPHP\R::setup('mysql:host=127.0.0.1;dbname=Binsta;charset=utf8mb4', 'root', '');
\RedBeanPHP\R::freeze(false);

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views');
$twig = new \Twig\Environment($loader);
$twig->addGlobal('csrfToken', $_SESSION['csrf_token']);


$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$url = trim($requestUri, '/');
$parts = !empty($url) ? explode('/', $url) : [];

$controllerName = !empty($parts[0]) ? strtolower($parts[0]) : 'user';
$method = !empty($parts[1]) ? strtolower($parts[1]) : 'login';

$fullControllerName = ucfirst($controllerName) . 'Controller';

if (!class_exists($fullControllerName)) {
    error(404, 'deze pagina bestaat niet.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $method ? $method . 'Post' : 'indexPost';

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        error(403, 'Ongeldig of verlopen formulier. Ververs de pagina en probeer het opnieuw.');
    }
}

$controller = new $fullControllerName($twig);

if (!method_exists($controller, $method) || !is_callable([$controller, $method])) {
    error(404, 'deze methode bestaat niet.');
}

$controller->$method();
