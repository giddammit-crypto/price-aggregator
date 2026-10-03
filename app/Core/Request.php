<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $queryParams;
    private array $postParams;
    private array $serverParams;
    private array $cookies;
    private array $attributes = [];

    public function __construct(
        ?string $method = null,
        ?string $uri = null,
        array $queryParams = [],
        array $postParams = [],
        array $serverParams = [],
        array $cookies = []
    ) {
        $this->serverParams = $serverParams ?: $_SERVER;
        $this->method = strtoupper($method ?: ($this->serverParams['REQUEST_METHOD'] ?? 'GET'));
        $this->uri = $uri ?: ($this->serverParams['REQUEST_URI'] ?? '/');
        
        $parsedUrl = parse_url($this->uri);
        $this->path = $parsedUrl['path'] ?? '/';
        $this->path = rtrim($this->path, '/') ?: '/';

        $this->queryParams = $queryParams;
        $this->postParams = $postParams;
        $this->cookies = $cookies;
    }

    public static function createFromGlobals(): self
    {
        return new self(null, null, $_GET, $_POST, $_SERVER, $_COOKIE);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isMethod(string $method): bool
    {
        return strcasecmp($this->method, $method) === 0;
    }

    public function isAjax(): bool
    {
        return ($this->serverParams['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($this->serverParams['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getPost(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function getPostParams(): array
    {
        return $this->postParams;
    }

    public function getClientIp(): string
    {
        // Forwarded headers are client-controlled unless the web server is explicitly trusted.
        return $this->serverParams['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent(): string
    {
        return $this->serverParams['HTTP_USER_AGENT'] ?? '';
    }

    public function getHeader(string $name): ?string
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->serverParams[$headerKey] ?? null;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
