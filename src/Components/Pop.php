<?php

namespace Duitku\Laravel\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pop extends Component
{
    public function __construct(
        public string $reference,
        public string $buttonText = 'Bayar Sekarang',
    ) {}

    public function render(): View
    {
        return $this->view('duitku::components.pop');
    }
}
