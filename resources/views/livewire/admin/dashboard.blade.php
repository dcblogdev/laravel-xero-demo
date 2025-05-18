<div>
    <h1>{{ __('Dashboard') }}</h1>

    <div class="card">
        {{ __("You're logged in!") }}
    </div>

    @if (! \Dcblogdev\Xero\Facades\Xero::isConnected())
        <p>You are not connect to Xero, please <a href="{{ route('xero.connect') }}" variant="primary">connect</a></p>
    @endif

</div>
