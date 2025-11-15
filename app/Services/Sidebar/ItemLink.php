<?php

namespace App\Services\Sidebar;
use Illuminate\Support\Facades\Auth;

class ItemLink implements ItemInterface
{

    private string $title;
    private string $icon;
    private string $url;
    private bool $active;
    private array $can;

    public function __construct(string $title, string $url, string $icon, bool $active, array $can)
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->url = $url;
        $this->active = $active;
        $this->can = $can;
    }

    public function render(): string
    {
        $activeClass = $this->active ? 'bg-gray-100' : '';
        return <<<HTML
             <a href="{$this->url}" wire:navigate class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {$activeClass}">
                <span class="w-6 h-6 inline-flex justify-center items-center rounded-full bg-gray-200 text-gray-500">
                    <i class="{$this->icon}"></i>
                </span>
                <span class="ms-3">{$this->title}</span>
            </a>
        HTML;
    }

    public function authorize(): bool
    {
        if (empty($this->can)) {
            return true;
        }
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        foreach ($this->can as $perm) {
            if ($user->can($perm)) {
                return true;
            }
        }
        return false;
    }
}
