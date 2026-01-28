<script src="{{ \Duitku\Laravel\Facades\Duitku::pop()->scriptUrl() }}"></script>

<button {{ $attributes->merge(['onclick' => "checkout.process('$reference')"]) }}>
  {{ $slot->isEmpty() ? 'Bayar Sekarang' : $slot }}
</button>