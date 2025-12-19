<?php
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../app/controllers/ProductoController.php';

/**
 * Respuesta JSON estándar (Ejercicio 4 + 5)
 */
function respondJson(string $status, int $code, string $message, $data = null): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    $payload = [
        'status' => $status,
        'code' => $code,
        'message' => $message,
    ];

    if ($data !== null) {
        $payload['data'] = $data;
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------
// Obtener path desde QUERY_STRING (por el .htaccess con QSA)
// ------------------------------------------------------------
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';
$path = explode('&', $rawQuery)[0];
$path = trim($path);

if ($path === '') {
    respondJson('success', 200, 'API running', ['version' => 'v1']);
}

if ($path[0] !== '/') {
    $path = '/' . $path;
}

// ------------------------------------------------------------
// Router + rutas
// ------------------------------------------------------------
$router = new Router();

$router->add('/public/producto/get', [
    'controller' => 'ProductoController',
    'action' => 'getAllProductos',
    'method' => 'GET'
]);

$router->add('/public/producto/get/{id}', [
    'controller' => 'ProductoController',
    'action' => 'getProductoById',
    'method' => 'GET'
]);

$router->add('/public/producto/create', [
    'controller' => 'ProductoController',
    'action' => 'createProducto',
    'method' => 'POST'
]);

$router->add('/public/producto/update/{id}', [
    'controller' => 'ProductoController',
    'action' => 'updateProducto',
    'method' => 'PUT'
]);

$router->add('/public/producto/delete/{id}', [
    'controller' => 'ProductoController',
    'action' => 'deleteProducto',
    'method' => 'DELETE'
]);

// ------------------------------------------------------------
// Match de rutas (Ejercicio 5: URL no válida => 404)
// ------------------------------------------------------------
if (!$router->matchRoutes($path)) {
    respondJson(
        'error',
        404,
        'URL no válida: ruta no definida',
        ['path' => $path]
    );
}

$params = $router->getParams();

// Método esperado vs actual
$routeMethod = $params['method'] ?? null;
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($routeMethod && strtoupper($requestMethod) !== strtoupper($routeMethod)) {
    respondJson(
        'error',
        405,
        "Método no permitido. Esperado $routeMethod, recibido $requestMethod",
        ['path' => $path]
    );
}

// Controller / Action
$controllerName = $params['controller'] ?? null;
$action = $params['action'] ?? null;
$routeParams = $params['routeParams'] ?? [];

if (!$controllerName || !$action) {
    respondJson('error', 500, 'Ruta mal configurada (controller/action missing)');
}

if (!class_exists($controllerName)) {
    respondJson('error', 500, "Controller '$controllerName' no encontrado");
}

$controller = new $controllerName();

if (!method_exists($controller, $action)) {
    respondJson('error', 500, "Action '$action' no encontrada en '$controllerName'");
}

// Ejecutar acción
try {
    $result = call_user_func_array([$controller, $action], array_values($routeParams));

    if (!is_array($result) || !isset($result['status'], $result['code'], $result['message'])) {
        respondJson('error', 500, 'Respuesta inválida del controller');
    }

    http_response_code((int)$result['code']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    respondJson('error', 500, 'Internal server error', ['error' => $e->getMessage()]);
}
