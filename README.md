# Laravel Socialite SpareBank 1

This is a custom provider for OAuth login with [SpareBank1](https://www.sparebank1.no)

## Installation

To install the package via composer, run the following command:

```bash
composer require devshaded/sparebank1-socialite-provider
```

## Configuration
Once the package is installed, register the provider in `config/services.php` as follows:
```php
'sb1' => [
    'client_id' => env('SB1_CLIENT_ID'),
    'client_secret' => env('SB1_CLIENT_SECRET'),
    'finInstId' => env('SB1_FIN_INST_ID'),
    'redirect' => env('SB1_REDIRECT_URI'),
],
```

## Usage
To initiate the OAuth login with SpareBank1, you can use the following code to redirect users to the authentication page:

```php
return Socialite::driver('sp1')->redirect();
```

To handle the callback after the user has authenticated:

```php
$user = Socialite::driver('sp1')->user();
```

## Environment Variables
Ensure you have added the following variables to your `.env` file:
```bash
SB1_CLIENT_ID=your-client-id
SB1_CLIENT_SECRET=your-client-secret
SB1_REDIRECT_URI=your-redirect-url
SB1_FIN_INST_ID=fid-ringerike-hadeland
```

Replace your-client-id, your-client-secret, and your-redirect-url with the appropriate values from your SpareBank1 OAuth credentials.

## Example
```php
Route::get('/oauth/redirect', function () {
    return Socialite::driver('sb1')->redirect();
});

Route::get('/oauth/callback', function () {
    $user = Socialite::driver('sb1')->user();

    $existingUser = User::where('email', $user->getEmail())->first();

    if ($existingUser) {
        $existingUser->update([
            'firstname' => $user->user['firstname'],
            'lastname' => $user->user['lastname'],
            'email' => $user->getEmail(),
            'sub' => $user->user['sub'],
            'dob' => $user->user['dateOfbirth'],
            'phone' => $user->user['mobilePhoneNumber'],
        ]);

        $existingUser->accessToken()->update([
            'token' => $user->token,
            'refresh_token' => $user->refreshToken,
            'expires_in' => $user->expiresIn,
        ]);
    } else {
        $newUser = User::create([
            'firstname' => $user->user['firstname'],
            'lastname' => $user->user['lastname'],
            'email' => $user->getEmail(),
            'sub' => $user->user['sub'],
            'dob' => $user->user['dateOfbirth'],
            'phone' => $user->user['mobilePhoneNumber'],
        ]);

        $newUser->accessToken()->create([
            'token' => $user->token,
            'refresh_token' => $user->refreshToken,
            'expires_in' => $user->expiresIn,
        ]);
    }
});
```

## License
This package is open-sourced software licensed under the MIT license.
