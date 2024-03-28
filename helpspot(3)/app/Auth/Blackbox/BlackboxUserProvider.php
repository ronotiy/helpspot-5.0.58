<?php

namespace HS\Auth\Blackbox;

use Illuminate\Support\Facades\Log;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class BlackboxUserProvider extends EloquentUserProvider
{
    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $canProceed = true;
        $customCodeFile = customCodePath('BlackBox.php');

        if (! file_exists($customCodeFile)) {
            Log::error('BlackBox is enabled but file '.$customCodeFile.' was not found.');
            errorLog('BlackBox is enabled but file '.$customCodeFile.' was not found.', 'authentication', __FILE__, __LINE__);
            $canProceed = false;
        }

        require_once $customCodeFile;

        if (! function_exists('BlackBox')) {
            Log::error('BlackBox is enabled but function "BlackBox" was not found within file '.$customCodeFile.' was not found.');
            errorLog('BlackBox is enabled but function "BlackBox" was not found within file '.$customCodeFile.' was not found.', 'authentication', __FILE__, __LINE__);
            $canProceed = false;
        }

        if ($canProceed) {
            return BlackBox($credentials['sUsername'], $credentials['password']);
        }

        // Fall back to internal HelpSpot authentication
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }
}
