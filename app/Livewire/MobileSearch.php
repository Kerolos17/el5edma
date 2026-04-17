<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResults;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MobileSearch extends Component
{
    public bool $open = false;

    public string $query = '';

    public function openModal(): void
    {
        $this->open  = true;
        $this->query = '';
    }

    public function closeModal(): void
    {
        $this->open  = false;
        $this->query = '';
    }

    public function getResults(): ?GlobalSearchResults
    {
        $trimmed = trim($this->query);

        if (blank($trimmed) || mb_strlen($trimmed) < 2) {
            return null;
        }

        return Filament::getGlobalSearchProvider()?->getResults($trimmed);
    }

    public function render(): View
    {
        return view('livewire.mobile-search', [
            'results' => $this->getResults(),
        ]);
    }
}
