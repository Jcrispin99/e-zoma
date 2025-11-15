<?php

namespace App\Services\Sidebar;

class ItemGroup implements ItemInterface
{
    protected string $title;
    protected string $icon;
    protected bool $active;
    protected array $items = [];

    public function __construct(string $title, string $icon, bool $active)
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->active = $active;
    }

    public function addItem(ItemInterface $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function render(): string
    {
        $open = $this->active ? 'true' : 'false';
        $authorized = array_filter($this->items, function (ItemInterface $item) {
            return $item->authorize();
        });
        if (empty($authorized)) {
            return '';
        }
        $itemsHtml = collect($authorized)
            ->map(function (ItemInterface $item) {
                return '<li class="pl-4">' . $item->render() . '</li>';
            })
            ->implode("\n");



        return <<<HTML
            <div x-data="{open: {$open}}">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center w-full p-2 text-base text-gray-900 transition duration-75 rounded-lg group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                >
                    <span class="w-6 h-6 inline-flex justify-center items-center rounded-full bg-gray-200 text-gray-500">
                        <i class="{$this->icon}"></i>
                    </span>
                    <span class="flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">
                        {$this->title}
                    </span>
                    <i
                        class="text-sm"
                        :class="{
                            'fa-solid fa-chevron-down': !open,
                            'fa-solid fa-chevron-up': open
                        }"
                    ></i>
                </button>
                <ul x-show="open" x-cloak class="py-2 space-y-2">
                    {$itemsHtml}
                </ul>
            </div>
        HTML;
    }

    public function authorize(): bool
    {
        foreach ($this->items as $item) {
            if ($item->authorize()) {
                return true;
            }
        }
        return false;
    }
}
