<?php

class FakeMiddleware
{
    public function handle($next)
    {
        echo "Middleware executado! ";
        $next();
    }
}
