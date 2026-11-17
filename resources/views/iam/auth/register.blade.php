<h1>Create an account</h1>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div>
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
        @error('name')
            <p>{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
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

    <div>
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required>
    </div>

    @if (session('error'))
        <p>{{ session('error') }}</p>
    @endif

    <button type="submit">Register</button>
</form>

<p><a href="{{ route('login') }}">Back to login</a></p>
