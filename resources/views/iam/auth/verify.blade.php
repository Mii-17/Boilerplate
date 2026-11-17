<h1>Verify your email address</h1>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

@if (session('error'))
    <p>{{ session('error') }}</p>
@endif

<p>Please check your inbox and follow the verification link.</p>

<p><a href="{{ route('email.change') }}">Use a different email address</a></p>

<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Log out</button>
</form>
