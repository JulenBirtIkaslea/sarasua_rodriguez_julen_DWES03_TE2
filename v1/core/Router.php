<?php

class Router
{
    // Array donde se guardan todas las rutas definidas
    protected array $routes = [];

    // Aquí se guardan los parámetros de la ruta que ha hecho match
    // (controller, action y posibles params como id)
    protected array $params = [];

    /**
     * Añade una ruta al router
     * $route  → patrón de la URL (ej: /public/producto/get/{id})
     * $params → información asociada a la ruta (controller y action)
     */
    public function add(string $route, array $params): void
    {
        $this->routes[$route] = $params;
    }

    /**
     * Devuelve todas las rutas registradas
     * (útil para depurar o comprobar qué rutas existen)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /*
    Comprueba si la URL solicitada coincide con alguna ruta definida.
    Soporta parámetros dinámicos como {id}.

    Ejemplo:
    Ruta definida: /public/producto/get/{id}
    URL recibida:  /public/producto/get/3
    */
    public function matchRoutes(string $path): bool
    {
        // Recorremos todas las rutas registradas
        foreach ($this->routes as $route => $params) {

            // Convertimos {id} en una expresión regular
            // /public/producto/get/{id} → /public/producto/get/(?P<id>[^/]+)
            $pattern = preg_replace(
                '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                '(?P<$1>[^/]+)',
                $route
            );

            // Añadimos delimitadores y anclamos la expresión
            $pattern = '#^' . $pattern . '$#';

            // Comprobamos si la URL encaja con la ruta
            if (preg_match($pattern, $path, $matches)) {

                // Aquí guardamos solo los parámetros con nombre (ej: id)
                $routeParams = [];

                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $routeParams[$key] = $value;
                    }
                }

                // Guardamos la información de la ruta encontrada
                $this->params = $params;

                // Añadimos los parámetros dinámicos (ej: id)
                $this->params['routeParams'] = $routeParams;

                // Ruta encontrada
                return true;
            }
        }

        // Ninguna ruta coincide
        return false;
    }

    /**
     * Devuelve los parámetros de la ruta que ha hecho match
     * (controller, action y routeParams)
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
