<?php
define('SECURE_ACCESS', true);
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth/session.php';

// Parse le chemin
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = substr($uri, strlen(BASE_URL));
$path = '/' . trim($path ?: '/', '/');
if ($path !== '/')
    $path = rtrim($path, '/');

$method = $_SERVER['REQUEST_METHOD'];

// Routes publiques (pas besoin d'être connecté)
$publicPaths = ['/login', '/register', '/logout'];

if (!in_array($path, $publicPaths)) {
    requireLogin();
}

// Table de routage — chemins simples, include dans le scope global ($mysqli accessible partout)
$routes = [
    'GET:/' => 'modules/home.php',
    'GET:/login' => 'modules/auth/login.php',
    'POST:/login' => 'modules/auth/process.php',
    'GET:/register' => 'modules/auth/register.php',
    'POST:/register' => 'modules/auth/process.php',
    'GET:/logout' => 'modules/auth/logout.php',
    'GET:/settings' => 'modules/settings/index.php',
    'POST:/settings' => 'modules/settings/process.php',
    'GET:/books' => 'modules/books/index.php',
    'POST:/books' => 'modules/books/process.php',
    'GET:/members' => 'modules/members/index.php',
    'POST:/members' => 'modules/members/process.php',
    'GET:/download' => 'modules/books/download.php',
    'GET:/read' => 'modules/books/read.php',
];

$key = $method . ':' . $path;

// Détection des routes dynamiques (ex: /books/123)
if ($method === 'GET' && preg_match('#^/books/(\d+)$#', $path, $matches)) {
    $bookId = (int)$matches[1];
    include __DIR__ . '/modules/books/details.php';
    exit;
}

if (isset($routes[$key])) {
    include __DIR__ . '/' . $routes[$key];
} else {
    http_response_code(404);
    $errorCode = 404;
    include __DIR__ . '/includes/layout/error_page.php';
}
