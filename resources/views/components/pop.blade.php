@once
    <script src="{{ \Duitku\Laravel\Facades\Duitku::pop()->scriptUrl() }}" defer></script>
    <script src="{{ asset('vendor/duitku/duitku-pop.js') }}" defer></script>
@endonce

<button {{ $attributes->merge(['type' => 'button', 'data-duitku-reference' => $reference]) }}>
  {{ $slot->isEmpty() ? $buttonText : $slot }}
</button>
