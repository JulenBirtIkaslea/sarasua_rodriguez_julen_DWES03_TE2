<?php
require '../core/Router.php';
require '../app/controllers/ProductoController.php';

/**
 * Respuesta JSON estándar (Ej 4 + Ej 5)
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
// 1) URL (como en clase) + asegurar que es un PATH válido
// ------------------------------------------------------------
$url = $_SERVER['QUERY_STRING'] ?? '';
$url = explode('&', $url)[0]; // por si viene con QSA
$url = trim($url);

if ($url === '') {
    respondJson('success', 200, 'API running', ['version' => 'v1']);
}

// ------------------------------------------------------------
// 2) Construcción urlArray (como en clase)
// ------------------------------------------------------------
$urlParams = explode('/', $url);

$urlArray = array(
    'HTTP'       => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    'path'       => $url,
    'controller' => '',
    'action'     => '',
    'params'     => ''
);

// OJO: aquí la URL típica es /public/producto/get  -> [0]=, [1]=public, [2]=producto, [3]=get, [4]=id
if (!empty($urlParams[2])) {
    $urlArray['controller'] = ucwords($urlParams[2]);

    if (!empty($urlParams[3])) {
        $urlArray['action'] = $urlParams[3];

        if (!empty($urlParams[4])) {
            $urlArray['params'] = $urlParams[4];
        }
    } else {
        $urlArray['action'] = 'index';
    }
} else {
    $urlArray['controller'] = 'Home';
    $urlArray['action'] = 'index';
}

// ------------------------------------------------------------
// 3) Router + rutas
// ------------------------------------------------------------
$router = new Router();

$router->add('/public/producto/get', array(
    'controller' => 'ProductoController',
    'action' => 'getAllProductos'
));

$router->add('/public/producto/get/{id}', array(
    'controller' => 'ProductoController',
    'action' => 'getProductoById'
));

$router->add('/public/producto/create', array(
    'controller' => 'ProductoController',
    'action' => 'createProducto'
));

$router->add('/public/producto/update/{id}', array(
    'controller' => 'ProductoController',
    'action' => 'updateProducto'
));

$router->add('/public/producto/delete/{id}', array(
    'controller' => 'ProductoController',
    'action' => 'deleteProducto'
));

// ------------------------------------------------------------
// 4) MATCH ROUTES (CORRECTO para tu Router: string path)
// Ejercicio 5: URL no válida -> 404 JSON
// ------------------------------------------------------------
if (!$router->matchRoutes($url)) {
    respondJson('error', 404, 'Error de URL no válida: ruta no definida', [
        'path' => $url
    ]);
}

// ------------------------------------------------------------
// 5) Params según método (estilo clase, pero sin bug del intval)
// ------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$params = [];

if ($method === 'GET') {
    // Si hay id en URL, lo pasamos; si no, no pasamos nada
    if (isset($urlArray['params']) && $urlArray['params'] !== '') {
        $params[] = (int)$urlArray['params'];
    }

} elseif ($method === 'POST') {
    $json = file_get_contents('php://input');
    $body = json_decode($json, true);

    if (!is_array($body)) {
        respondJson('error', 400, 'Body JSON inválido');
    }

    $params[] = $body;

} elseif ($method === 'PUT') {
    if (!isset($urlArray['params']) || $urlArray['params'] === '') {
        respondJson('error', 400, 'Falta ID en la URL para actualizar');
    }

    $id = (int)$urlArray['params'];
    $json = file_get_contents('php://input');
    $body = json_decode($json, true);

    if (!is_array($body)) {
        respondJson('error', 400, 'Body JSON inválido');
    }

    $params[] = $id;
    $params[] = $body;

} elseif ($method === 'DELETE') {
    if (!isset($urlArray['params']) || $urlArray['params'] === '') {
        respondJson('error', 400, 'Falta ID en la URL para borrar');
    }

    $params[] = (int)$urlArray['params'];
}

// ------------------------------------------------------------
// 6) Ejecutar Controller / Action desde Router (como en clase)
// ------------------------------------------------------------
$routeInfo = $router->getParams();
$controllerName = $routeInfo['controller'] ?? null;
$action = $routeInfo['action'] ?? null;

if (!$controllerName || !$action) {
    respondJson('error', 500, 'Ruta mal configurada (controller/action missing)');
}

if (!class_exists($controllerName)) {
    respondJson('error', 500, "Controller no encontrado: $controllerName");
}

$controller = new $controllerName();

if (!method_exists($controller, $action)) {
    respondJson('error', 500, "Action no encontrada: $action");
}

try {
    $resp = call_user_func_array([$controller, $action], $params);

    if (!is_array($resp) || !isset($resp['status'], $resp['code'], $resp['message'])) {
        respondJson('error', 500, 'Respuesta inválida del controller');
    }

    http_response_code((int)$resp['code']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    respondJson('error', 500, 'Internal server error', [
        'error' => $e->getMessage()
    ]);
}
