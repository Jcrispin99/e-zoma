<?php

namespace App\Services\Sidebar;

interface ItemInterface
{
    public function render(): string;
    public function authorize(): bool;
}
