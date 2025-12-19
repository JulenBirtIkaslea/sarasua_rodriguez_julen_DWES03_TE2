<?php
// Cargamos el Router y el controlador que vamos a usar
require '../core/Router.php';
require '../app/controllers/ProductoController.php';

/**
 * Función auxiliar para devolver respuestas en JSON
 * La usamos sobre todo para errores generales (ej: URL no válida)
 * Así evitamos repetir código
 */
function respondJson(string $status, int $code, string $message, $data = null): void
{
    // Establece el código HTTP de la respuesta
    http_response_code($code);

    // Estructura estándar de la respuesta
    $payload = [
        'status' => $status,
        'code' => $code,
        'message' => $message,
    ];

    // El campo data solo se añade si existe
    if ($data !== null) {
        $payload['data'] = $data;
    }

    // Convertimos el array a JSON y lo enviamos
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit; // cortamos la ejecución
}

// -------------------------
// OBTENCIÓN DE LA URL (forma vista en clase)
// -------------------------
// La ruta llega a través del QUERY_STRING por el .htaccess
$url = $_SERVER['QUERY_STRING'] ?? '';

// Guardamos el contenido del body para no leer php://input varias veces
// Se usará en POST y PUT
$content = file_get_contents("php://input");

// Dividimos la URL en partes usando /
$urlParams = explode('/', $url);

// Array donde guardamos la info de la URL
$urlArray = array(
    'HTTP'       => $_SERVER['REQUEST_METHOD'] ?? 'GET', // método HTTP
    'path'       => $url,                                // ruta completa
    'controller' => '',                                  // nombre del controller
    'action'     => '',                                  // método a ejecutar
    'params'     => ''                                   // parámetro (id)
);

// Ejemplo de URL: /public/producto/get/3
// [0]=, [1]=public, [2]=producto, [3]=get, [4]=3
if (!empty($urlParams[2])) {
    // Controller en mayúscula inicial
    $urlArray['controller'] = ucwords($urlParams[2]);

    if (!empty($urlParams[3])) {
        $urlArray['action'] = $urlParams[3];

        if (!empty($urlParams[4])) {
            $urlArray['params'] = $urlParams[4]; // id
        }
    } else {
        // Acción por defecto
        $urlArray['action'] = 'index';
    }
} else {
    // Caso base (no se usa realmente en la API)
    $urlArray['controller'] = 'Home';
    $urlArray['action'] = 'index';
}

// -------------------------
// DEFINICIÓN DE RUTAS (Router)
// -------------------------
$router = new Router();

// GET todos los productos
$router->add('/public/producto/get', array(
    'controller' => 'ProductoController',
    'action' => 'getAllProductos'
));

// GET producto por ID
$router->add('/public/producto/get/{id}', array(
    'controller' => 'ProductoController',
    'action' => 'getProductoById'
));

// POST crear producto
$router->add('/public/producto/create', array(
    'controller' => 'ProductoController',
    'action' => 'createProducto'
));

// PUT actualizar producto
$router->add('/public/producto/update/{id}', array(
    'controller' => 'ProductoController',
    'action' => 'updateProducto'
));

// DELETE borrar producto
$router->add('/public/producto/delete/{id}', array(
    'controller' => 'ProductoController',
    'action' => 'deleteProducto'
));

// -------------------------
// BLOQUE PRINCIPAL (ESTILO EXACTO VISTO EN CLASE)
// -------------------------
$params = [];

// Comprobamos si la ruta existe en el router
if ($router->matchRoutes($url)) {

    // Obtenemos el método HTTP actual
    $method = $_SERVER['REQUEST_METHOD'];

    // Inicializamos el array de parámetros
    $params = [];

    // IMPORTANTE:
    // No usar intval($urlArray['params']) ?? null
    // porque intval(null) devuelve 0
    $idParam = (isset($urlArray['params']) && $urlArray['params'] !== '')
        ? (int)$urlArray['params']
        : null;

    // Asignamos parámetros según el método HTTP
    if ($method === 'GET') {
        // Si hay id → get por id
        // Si no hay id → getAll
        if ($idParam !== null) {
            $params[] = $idParam;
        }

    } elseif ($method === 'POST') {
        // POST recibe el body en JSON (lo hemos leído antes en $content)
        $params[] = json_decode($content, true);

    } elseif ($method === 'PUT') {
        // PUT recibe id + body (body ya está en $content)
        $params[] = $idParam;
        $params[] = json_decode($content, true);

    } elseif ($method === 'DELETE') {
        // DELETE solo necesita el id
        $params[] = $idParam;
    }

} else {
    // EJERCICIO 5:
    // Si la ruta no existe → 404
    respondJson('error', 404, 'Error de URL no válida: ruta no definida');
}

// -------------------------
// EJECUCIÓN DEL CONTROLLER Y ACTION
// -------------------------
$controller = $router->getParams()['controller'];
$action = $router->getParams()['action'];

// Creamos el controller dinámicamente
$controller = new $controller;

// Comprobamos que el método existe
if (method_exists($controller, $action)) {

    // Llamamos al método pasando los parámetros
    $resp = call_user_func_array([$controller, $action], $params);

    // Devolvemos la respuesta JSON
    http_response_code((int)$resp['code']);
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// Caso extremo: método no encontrado (no debería ocurrir)
respondJson('error', 404, 'Error de URL no válida: ruta no definida');
