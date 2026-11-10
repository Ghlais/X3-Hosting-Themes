@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<x-app-layout>

    <div
        class="mx-auto flex flex-col gap-2 mt-4 shadow-sm px-6 sm:px-14 py-10 bg-background-secondary rounded-md xl:max-w-[60%] w-full">
        <h1 class="text-2xl">
            {{ str_starts_with(app()->getLocale(), 'ar') ? 'طلب تفويض' : 'Authorization Request' }}
        </h1>
        <!-- Introduction -->
        <p class="mt-2"><strong>{{ $client->name }}</strong> {{ str_starts_with(app()->getLocale(), 'ar') ? 'يطلب إذناً للوصول إلى حسابك.' : 'is requesting permission to access your account.' }}</p>

        <!-- Scope List -->
        @if (count($scopes) > 0)
        <div class="scopes">
            <p><strong>{{ $x3['literal_15'] }}</strong></p>

            <ul class="list-disc list-inside">
                @foreach ($scopes as $scope)
                <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="flex flex-row gap-2 mt-4">

            <!-- Cancel Button -->
            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')

                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <x-button.danger class="btn btn-danger">{{ $x3['literal_13'] }}</x-button.danger>
            </form>

            <!-- Authorize Button -->
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf

                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <x-button.primary type="submit" class="btn btn-success btn-approve">{{ $x3['literal_14'] }}</x-button.primary>
            </form>
        </div>

</x-app-layout>