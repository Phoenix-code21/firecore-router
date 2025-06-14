# Firecore Router

Um roteador simples, leve e flexível para aplicações PHP, com suporte a middlewares, rotas nomeadas e grupos com prefixo e namespace.

## 🧩 Instalação

Você pode instalar via [Composer](https://getcomposer.org):

```bash
composer require phoenix-code21/firecore-router
```

🚀 Exemplo de uso

```bash
use Firecore\Router\Router;

$router = new Router;

// Base opcional (ex: subdiretório)
$router->setBasePath('/meu-projeto');

// Rota GET simples
$router->get('/', function () {
    echo 'Página inicial';
});

// Rota com parâmetros
$router->get('/user/{id}', function ($id) {
    echo "Usuário: {$id}";
});

// Rotas com grupo e namespace
$router->namespace('App\\Controllers');
$router->group('/admin', function ($router) {
    $router->get('/dashboard', 'DashboardController@index');
});

// Middlewares
$router->middleware('App\\Middleware\\Auth@handle')
       ->get('/painel', function () {
           echo 'Área protegida';
       });

// Erro 404 personalizado
$router->setError('/erro', 404, function () {
    echo 'Página não encontrada';
});

// Executa o roteador
$router->dispatch();

```

⚙️ Recursos

✅ Rotas com GET e POST

✅ Grupos com prefixo e namespace

✅ Middlewares encadeáveis

✅ Rotas nomeadas com name() e getRoute()

✅ Tratamento de erros por código (ex: 404)

✅ PSR-4 autoload e compatível com Composer

🧪 Testes

Execute os testes com:

composer test

Os testes usam PHPUnit e estão localizados em tests/.

📄 Licença: MIT License
