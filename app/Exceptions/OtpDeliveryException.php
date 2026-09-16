<?php

namespace App\Exceptions;

/**
 * An OTP request was made and its code can be verified, but no mail channel
 * managed to deliver it. Carries the request and code for callers that have a
 * fallback of their own (the super-admin login logs the code).
 */
class OtpDeliveryException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $challenge,
        public readonly string $otp,
    ) {
        parent::__construct($message);
    }
}
