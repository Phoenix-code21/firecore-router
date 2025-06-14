<?php

namespace Firecore\Router;

/**
 * Firecore Router
 * @author <https://github.com/Phoenix-code21> 
 */
class Router
{
    private string $basePath;
    private string $prefixGroup = "";
    private string $namespace;

    private array $middlewares = [];
    private array $statusCode = [];
    private array $namespaces = [];
    private array $routes = [];

    /**
     * Configura um path base. Exemplo: /firecore-router
     * @param string $path
     * @author <https://github.com/Phoenix-code21>
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, "/");
    }

    /**
     * Define um namespace para seu escopo de rotas.
     * @param string $namespace
     * @author <https://github.com/Phoenix-code21>
     */
    public function namespace(string $namespace): void
    {
        $this->namespace = ($namespace ? ucwords($namespace) : null);
    }

    /**
     * Middlewares a serem executados.
     * Você pode definir uma callback ou um array de Middleware.
     * @param mixed $callback
     * @author <https://github.com/Phoenix-code21>
     */
    public function middleware(mixed $callback): self
    {
        if (is_callable($callback) || is_string($callback)) {
            $this->middlewares[] = $callback;
        }

        if (is_array($callback)) {
            foreach ($callback as $middleware) {
                $this->middlewares[] = $middleware;
            }
        }

        return $this;
    }

    /**
     * Define um grupo de rotas. Exemplo: /dashboard/home
     * @param string $prefix
     * @param callback $callback
     * @author <https://github.com/Phoenix-code21>
     */
    public function group(string $prefix, callable $callback): void
    {
        $defaultPrefix = $this->prefixGroup;
        $this->prefixGroup = $prefix;

        $callback($this);

        $this->prefixGroup = $defaultPrefix;
    }

    /**
     * Configura uma rota GET.
     * @param string $path
     * @param mixed $callback
     * @author <https://github.com/Phoenix-code21>
     */
    public function get(string $path, mixed $callback): self
    {
        $this->addRoute('GET', $path, $callback);
        return $this;
    }

    /**
     * Configura uma rota POST.
     * @param string $path
     * @param mixed $callback
     * @author <https://github.com/Phoenix-code21>
     */
    public function post(string $path, mixed $callback): self
    {
        $this->addRoute('POST', $path, $callback);
        return $this;
    }

    /**
     * Define um nome personalizado para sua rota.
     * @param string $name
     * @author <https://github.com/Phoenix-code21>
     */
    public function name(string $name): self
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $uri = ($this->basePath ?? '') . $this->getCurrentUri();
        $_SESSION["Firecore"]["Router"][$name] = ($uri ?? "") . "{$name}";

        return $this;
    }

    /**
     * Obtem uri completo da rota.
     * @param string $route_name
     * @author <https://github.com/Phoenix-code21>
     */
    public function getRoute(string $route_name): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return $_SESSION["Firecore"]["Router"][$route_name] ?? "";
    }

    /**
     * Define erro personalizado.
     * @param string $path
     * @param int $code
     * @param mixed $callback
     * @author <https://github.com/Phoenix-code21>
     */
    public function setError(string $path, int $code, mixed $callback): void
    {
        $this->statusCode[$code] = $path;
        self::get($path, $callback);
    }

    /**
     * @author <https://github.com/Phoenix-code21>
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route => $data) {
            $pattern = self::params($route);

            if (preg_match($pattern, $this->getCurrentUri(), $args)) {
                array_shift($args);

                $callback = $data['callback'];
                $middlewares = $data['middleware'] ?? [];

                $core = function () use ($callback, $method, $route, $args) {
                    if (is_callable($callback)) {
                        call_user_func_array($callback, $args);
                    } elseif (is_string($callback)) {
                        $namespace = $this->namespaces[$method][$route] ?? '';
                        [$class, $methodName] = explode("@", $callback);
                        $fullClass = $namespace . '\\' . $class;
                        call_user_func_array([new $fullClass, $methodName], $args);
                    }
                };

                $middlewareChain = array_reduce(
                    array_reverse($middlewares),
                    function ($next, $middleware) {
                        return function () use ($middleware, $next) {
                            [$class, $method] = explode("@", $middleware);
                            $instance = new $class;
                            $instance->$method($next);
                        };
                    },
                    $core
                );

                $middlewareChain();
                return;
            }
        }

        self::handleError(404);
    }

    private function addRoute(string $method, string $path, mixed $callback): void
    {
        $path = ($this->prefixGroup ?? '') . $path;

        $this->routes[$method][$path] = [
            'callback' => $callback,
            'middleware' => $this->middlewares,
        ];

        if (!empty($this->namespace)) {
            $this->namespaces[$method][$path] = $this->namespace;
        }

        $this->middlewares = [];
    }


    private function params(string $route): string
    {
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z0-9_]+)(?::([^}]+))?\}#',
            function ($matches) {
                $param = $matches[1];
                $regex = $matches[2] ?? '[^/]+';
                return '(' . $regex . ')';
            },
            $route
        );

        return '#^' . $pattern . '$#';
    }

    private function getCurrentUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!empty($this->basePath) && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        return '/' . trim($uri, '/');
    }

    private function handleError(int $code): void
    {
        if (!empty($this->statusCode[$code])) {
            $location = ($this->basePath ?? "") . $this->statusCode[$code];
            header("location: {$location}");
            return;
        }
    }
}
