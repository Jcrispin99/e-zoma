<?php

namespace App\View\Composers;

use App\Services\Sidebar\ItemGroup;
use App\Services\Sidebar\ItemHeader;
use App\Services\Sidebar\ItemLink;
use Illuminate\View\View;
use InvalidArgumentException;

class SidebarComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $item = collect(config('sidebar'))->map(function ($item) {
            return $this->parseItem($item);
        });

        $view->with('itemsSidebar', $item);
    }

    public function parseItem(array $item)
    {
        switch ($item['type']) {
            case 'header':

                $header = new ItemHeader(
                    title: $item['title'],
                    can: $item['can'] ?? [],
                );
                return $header;

                break;

            case 'link':
                $link = new ItemLink(
                    title: $item['title'],
                    icon: $item['icon'] ?? 'fa-regular fa-circle',
                    url: isset($item['route']) ? route($item['route']) : '#',
                    active: isset($item['active']) ? request()->routeIs($item['active']) : false,
                    can: $item['can'] ?? [],
                );
                return $link;

                break;

            case 'group':
                $group = new ItemGroup(
                    title: $item['title'],
                    icon: $item['icon'] ?? 'fa-regular fa-circle',
                    active: isset($item['active']) ? request()->routeIs($item['active']) : false,
                );

                foreach (($item['items'] ?? []) as $subItem) {
                    $group->addItem($this->parseItem($subItem));
                }
                return $group;

                break;
            default:

                throw new InvalidArgumentException('Invalid item type');

                break;
        }
    }
}
