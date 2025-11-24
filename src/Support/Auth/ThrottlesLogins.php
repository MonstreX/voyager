<?php

namespace TCG\Voyager\Support\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ThrottlesLogins
{
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request),
            $this->maxAttempts()
        );
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        $this->limiter()->hit(
            $this->throttleKey($request),
            $this->decayMinutes() * 60
        );
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    protected function clearLoginAttempts(Request $request): void
    {
        $this->limiter()->clear($this->throttleKey($request));
    }

    protected function fireLockoutEvent(Request $request): void
    {
        event(new Lockout($request));
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input($this->username())).'|'.$request->ip());
    }

    protected function limiter(): RateLimiter
    {
        return app(RateLimiter::class);
    }

    public function maxAttempts(): int
    {
        return property_exists($this, 'maxAttempts') ? $this->maxAttempts : 5;
    }

    public function decayMinutes(): int
    {
        return property_exists($this, 'decayMinutes') ? $this->decayMinutes : 1;
    }
}
