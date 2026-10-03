<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';
    private static ?string $cspNonce = null;

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    public static function getCspNonce(): string
    {
        if (self::$cspNonce === null) {
            self::$cspNonce = base64_encode(random_bytes(16));
        }
        return self::$cspNonce;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public static function html(string $html, int $status = 200, array $headers = []): self
    {
        $res = new self($html, $status, $headers);
        $res->setHeader('Content-Type', 'text/html; charset=UTF-8');
        return $res;
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $res = new self($json, $status, $headers);
        $res->setHeader('Content-Type', 'application/json; charset=UTF-8');
        return $res;
    }

    public static function redirect(string $url, int $status = 302, array $headers = []): self
    {
        $res = new self('', $status, $headers);
        $res->setHeader('Location', $url);
        return $res;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            // Default Security Headers (OWASP)
            $nonce = self::getCspNonce();
            $defaultHeaders = [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'nonce-{$nonce}' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self';",
            ];

            foreach ($defaultHeaders as $k => $v) {
                if (!isset($this->headers[$k])) {
                    header("$k: $v");
                }
            }

            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        echo $this->content;
    }
}
