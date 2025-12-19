<?php

class Router
{
    protected array $routes = [];
    protected array $params = [];

    public function add(string $route, array $params): void
    {
        $this->routes[$route] = $params;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    /*
      Match con soporte para {id} y otros params: /algo/{id}
    */
    public function matchRoutes(string $path): bool
    {
        foreach ($this->routes as $route => $params) {

            // Convertir /public/producto/get/{id} a regex
            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $routeParams = [];

                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $routeParams[$key] = $value;
                    }
                }

                $this->params = $params;
                $this->params['routeParams'] = $routeParams;

                return true;
            }
        }

        return false;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
