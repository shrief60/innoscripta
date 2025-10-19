<?php

namespace App\Utils;

use Carbon\Carbon;

class DateTimeHelper
{
    /**
     * Parse datetime string to MySQL format (Y-m-d H:i:s)
     * 
     * @param string|null $datetime
     * @return string|null
     */
    public static function parseDateTime(?string $datetime): ?string
    {
        if (empty($datetime)) {
            return null;
        }

        try {
            return Carbon::parse($datetime)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

}

