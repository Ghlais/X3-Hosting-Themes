@if (!request()->is('admin', 'admin/*'))
    <meta name="x3-rackline-brand" content="X3-Hosting">
    <link rel="icon" href="{{ asset('x3-rackline/v1/mark.svg') }}" type="image/svg+xml" sizes="any">
    <link rel="stylesheet" href="{{ asset('x3-rackline/v1/glass-1.1.1.css') }}">
    <script defer src="{{ asset('x3-rackline/v1/glass-1.1.1.js') }}"></script>
@endif
