<?php

namespace App\Livewire;

use Ijpatricio\Mingle\Concerns\InteractsWithMingles;
use Ijpatricio\Mingle\Contracts\HasMingles;
use Illuminate\Support\Collection;
use Livewire\Component;

class AiWidget extends Component implements HasMingles
{
    use InteractsWithMingles;

    public function component(): string
    {
        return 'resources/js/AiWidget/index.js';
    }

    public function mingleData(): array
    {   
        // dd($this->doubleIt(2));
        return [
            'message' => 'Message in a bottle',
        ];
    }

    public function doubleIt($amount)
    {
        return $amount * 2;
    }
}
