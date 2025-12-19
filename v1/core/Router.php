<?php

class Router {
    protected $routes = array();
    protected $params = array();

    public function add($route, $params) {
        $this->routes[$route] = $params;
    }

    public function getRoutes() {
        return $this->routes;
    }

    // Router con soporte para {id}
    public function matchRoutes($url)
    {
        foreach ($this->routes as $route => $params)
        {
            $pattern = str_replace(['{id}', '/'], ['([0-9]+)', '\/'], $route);
            $pattern = '/^' . $pattern . '$/';

            if (preg_match($pattern, $url['path'])) {
                $this->params = $params;
                return true;
            }
        }

        return false;
    }

    public function getParams() {
        return $this->params;
    }
}
