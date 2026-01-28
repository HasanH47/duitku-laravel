<?php

namespace Duitku\Laravel\Components;

use Illuminate\View\Component;

class Pop extends Component
{
    public function __construct(
        public string $reference
    ) {}

    public function render()
    {
        return view('duitku::components.pop');
    }
}
