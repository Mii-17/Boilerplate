<h1>Log in</h1>

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <p>{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        @error('password')
            <p>{{ $message }}</p>
        @enderror
    </div>

    @if (session('error'))
        <p>{{ session('error') }}</p>
    @endif

    <button type="submit">Log in</button>
</form>

<p><a href="{{ route('register') }}">Create an account</a></p>
