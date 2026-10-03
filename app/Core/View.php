<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $viewsPath = '';
    private array $data = [];
    private ?string $layout = 'layout/main';
    private array $sections = [];
    private ?string $currentSection = null;

    public static function init(string $path): void
    {
        self::$viewsPath = rtrim($path, '/') . '/';
    }

    public static function make(string $template, array $data = [], ?string $layout = null): string
    {
        $v = new self($data);
        $v->setLayout($layout);
        return $v->render($template, $data);
    }

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function setLayout(?string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function render(string $template, array $data = []): string
    {
        $mergedData = array_merge($this->data, $data, ['view' => $this]);
        extract($mergedData, EXTR_SKIP);

        $templateFile = self::$viewsPath . ltrim($template, '/') . '.php';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("View template not found: {$templateFile}");
        }

        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        if ($this->layout !== null) {
            $layoutFile = self::$viewsPath . ltrim($this->layout, '/') . '.php';
            if (file_exists($layoutFile)) {
                $layoutData = array_merge($mergedData, [
                    'content' => $content,
                    'view' => $this
                ]);
                extract($layoutData, EXTR_SKIP);
                ob_start();
                require $layoutFile;
                return ob_get_clean();
            }
        }

        return $content;
    }

    public function partial(string $partial, array $data = []): string
    {
        $mergedData = array_merge($this->data, $data, ['view' => $this]);
        extract($mergedData, EXTR_SKIP);

        $partialFile = self::$viewsPath . ltrim($partial, '/') . '.php';
        if (!file_exists($partialFile)) {
            return "<!-- Missing partial: {$partial} -->";
        }

        ob_start();
        require $partialFile;
        return ob_get_clean();
    }

    public function startSection(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new \LogicException("No active section to end.");
        }
        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    public function getSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }
}
