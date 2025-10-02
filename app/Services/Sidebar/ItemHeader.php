<?php

namespace App\Services\Sidebar;

class ItemHeader implements ItemInterface
{
    private string $title;
    private array $can;

    public function __construct(string $title, array $can = [])
    {
        $this->title = $title;
        $this->can = $can;
    }

    public function render(): string
    {
        return <<<HTML
        <div class="px-2 py-2 text-xs font-semibold text-gray-500 uppercase">
            {$this->title}
        </div>
        HTML;
    }

    public function authorize(): bool
    {
        return true;
    }
}
