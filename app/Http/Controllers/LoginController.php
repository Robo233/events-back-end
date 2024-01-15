<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LoginController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGoogleCallback()
    {
        try {
            Log::info('Inside handleGoogleCallback');
            $user = Socialite::driver('google')->user();
            Log::info('handle');
            /**
             * 
             * @var User $appUser
             */
            $appUser = $this->handleSocialUser('google', $user);

            // Logging user details for debugging
            Log::info('User from Google: ', ['user' => $user]);

            // Use stored avatar if available, else use Google's avatar
            $avatarUrl = $appUser->avatar ? asset($appUser->avatar) : $user->getAvatar();

            Log::info('Avatar URL: ', ['avatarUrl' => $avatarUrl]);

            // Return user back to your application's page
            $frontendUrl = 'http://localhost:8080/profile';
            return redirect("$frontendUrl?name={$appUser->name}&email={$appUser->email}&avatar={$avatarUrl}&id={$appUser->id}");
        } catch (\Exception $e) {
            Log::error('Error in handleGoogleCallback: ' . $e->getMessage(), [
                'error' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect('/login')->with('error', 'Failed to authenticate with Google');
        }
    }

    /**
     * Handle a social user.
     *
     * @param string $provider
     * @param \Laravel\Socialite\Contracts\User $socialUser
     * @return void
     */
    protected function handleSocialUser($provider, $socialUser)
    {
        // First, try to find the user by email
        $user = User::where('email', $socialUser->getEmail())->first();

        // If the user is found, update the provider details
        if ($user) {
            $user->provider = $provider;
            $user->provider_id = $socialUser->getId();
        } else {
            // Create a new user if not found
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'password' => bcrypt(Str::random(40)),
            ]);
        }

        // Handle avatar if available
        if ($socialUser->getAvatar() && empty($user->avatar)) {
            $this->saveUserAvatar($user, $socialUser->getAvatar());
        }

        $user->save();

        return $user;
    }

    protected function saveUserAvatar($user, $avatarUrl)
    {
        Log::info('Saving user avatar from URL.');

        // Define the storage path and image name
        $imageName = 'avatar.jpg'; // Fixed name for the avatar
        $storagePath = 'users/' . $user->id;

        // Check if directory exists and create if not
        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
            Log::info('Directory created: ' . $storagePath);
        }

        // Save the avatar from URL to the storage path
        $contents = file_get_contents($avatarUrl);
        Storage::disk('public')->put($storagePath . '/' . $imageName, $contents);
        Log::info('Avatar saved to storage.');

        // Update user's avatar path in database
        $user->avatar = 'storage/users/' . $user->id . '/' . $imageName;
        $user->save();

        Log::info('User avatar path updated in database.');
    }
}
