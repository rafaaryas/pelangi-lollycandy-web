<?php

namespace App\Support;

class BusinessContact
{
    public static function whatsAppUrl(?string $message = null): ?string
    {
        $number = config('business.whatsapp_number');
        if (! is_string($number) || ! preg_match('/^62[1-9][0-9]{7,13}$/', $number)) {
            return null;
        }

        $url = 'https://wa.me/'.$number;

        return $message === null ? $url : $url.'?text='.urlencode($message);
    }
}
