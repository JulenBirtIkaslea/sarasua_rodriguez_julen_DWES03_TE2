<?php
require '../core/Router.php';
require '../app/controllers/ProductoController.php';

$url = $_SERVER['QUERY_STRING'];
echo 'URL= ' . $url . '<br>';

$content = file_get_contents("php://input");

// 2. Video: Routing
$router = new Router();

// RUTAS PRODUCTO
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


//Partimos la URL en sus partes
$urlParams = explode('/', $url);

//Array que contendrá la información de la URL
$urlArray = array(
    'HTTP'       => $_SERVER['REQUEST_METHOD'],
    'path'       => $url,
    'controller' => '',
    'action'     => '',
    'params'     => ''
);

//Rellena el array con la información de la URL si existe y sino manda al controlador y acción por defecto
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


// Mostramos las rutas
echo '<pre>';
print_r($urlArray) . '<br>';
echo '</pre>';

//EXPLICA
if ($router->matchRoutes($urlArray)) {

    // Verifica el método HTTP de la solicitud
    $method = $_SERVER['REQUEST_METHOD'];

    // Define los parámetros según el método HTTP
    $params = [];

    if ($method === 'GET') {
        $params[] = intval($urlArray['params']) ?? null;

    } elseif ($method === 'POST') {
        $json = file_get_contents('php://input');
        $params[] = json_decode($json, true);

    } elseif ($method === 'PUT') {
        $id = intval($urlArray['params']) ?? null;
        $json = file_get_contents('php://input');
        $params[] = $id;
        $params[] = json_decode($json, true);

    } elseif ($method === 'DELETE') {
        $params[] = intval($urlArray['params']) ?? null;
    }

    $controller = $router->getParams()['controller'];
    $action = $router->getParams()['action'];

    $controller = new $controller;

    if (method_exists($controller, $action)) {
        // Invoca el método con los parámetros
        $resp = call_user_func_array([$controller, $action], $params);
    } else {
        echo "No route found for URL '$url'";
    }

} else {
    echo "No route found for URL '$url'";
}
