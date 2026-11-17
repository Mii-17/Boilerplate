<h1>Change your email</h1>

<form method="POST" action="{{ route('email.update') }}">
    @csrf
    @method('PATCH')

    <div>
        <label for="email">New email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <p>{{ $message }}</p>
        @enderror
    </div>

    @if (session('error'))
        <p>{{ session('error') }}</p>
    @endif

    <button type="submit">Update email</button>
</form>
