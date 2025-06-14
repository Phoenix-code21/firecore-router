<?php

namespace Firecore\Tests;

use PHPUnit\Framework\TestCase;
use Firecore\Router\Router;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->router = new Router();
    }

    public function testBasicGetRoute()
    {
        ob_start();
        $this->router->get('/', function () {
            echo "Home";
        });
        $this->router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals("Home", $output);
    }

    public function testRouteWithParameter()
    {
        $_SERVER['REQUEST_URI'] = '/user/99';
        $this->router = new Router();

        ob_start();
        $this->router->get('/user/{id}', function ($id) {
            echo "User ID: " . $id;
        });
        $this->router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals("User ID: 99", $output);
    }

    public function testRouteGrouping()
    {
        $_SERVER['REQUEST_URI'] = '/admin/dashboard';
        $this->router = new Router();

        ob_start();
        $this->router->group('/admin', function ($router) {
            $router->get('/dashboard', function () {
                echo "Admin Dashboard";
            });
        });
        $this->router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals("Admin Dashboard", $output);
    }

    public function testMiddlewareExecution()
    {
        $_SERVER['REQUEST_URI'] = '/painel';
        $this->router = new Router();

        require_once __DIR__ . '/_FakeMiddleware.php';

        ob_start();
        $this->router->middleware('FakeMiddleware@handle')
            ->get('/painel', function () {
                echo "Conteúdo";
            });
        $this->router->dispatch();
        $output = ob_get_clean();

        $this->assertStringContainsString("Middleware executado!", $output);
        $this->assertStringContainsString("Conteúdo", $output);
    }

    public function testRouteNotFound()
    {
        $_SERVER['REQUEST_URI'] = '/inexistente';
        $this->router = new Router();

        ob_start();
        $this->router->setError('/404', 404, function () {
            echo "Página não encontrada";
        });
        $this->router->dispatch();
        $output = ob_get_clean();

        $this->assertEquals("Página não encontrada", $output);
    }
}
