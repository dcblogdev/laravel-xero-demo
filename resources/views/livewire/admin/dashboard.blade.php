<div>
    <h1>{{ __('Dashboard') }}</h1>

    <div class="card">
        {{ __("You're logged in!") }}
    </div>

    @if (! \Dcblogdev\Xero\Facades\Xero::isConnected())
        <p>Not connected</p>
    @else
        <p>Connected</p>
    @endif

</div>
