<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Visibility_Service
{
    public static function from_ephemeris($payload)
    {
        $position = isset($payload['position']) && is_array($payload['position']) ? $payload['position'] : array();
        $altitude = isset($position['altitude']) ? $position['altitude'] : null;
        $context = isset($payload['context']) && is_array($payload['context']) ? $payload['context'] : array();

        return self::from_conditions($altitude, $context);
    }

    public static function from_conditions($altitude, $context = array())
    {
        if ($altitude === null || !is_numeric($altitude)) {
            return array(
                'status' => 'unavailable',
                'label' => 'داده موجود نیست',
                'score' => null,
                'reason' => 'ارتفاع جرم از افق در دسترس نیست.',
                'bestWindow' => null,
            );
        }

        $altitude = (float) $altitude;
        $sun_altitude = isset($context['sunAltitude']) && is_numeric($context['sunAltitude']) ? (float) $context['sunAltitude'] : null;
        $moon_illumination = isset($context['moonIllumination']) && is_numeric($context['moonIllumination']) ? (float) $context['moonIllumination'] : null;
        $cloud_cover = isset($context['cloudCover']) && is_numeric($context['cloudCover']) ? (float) $context['cloudCover'] : null;

        if ($sun_altitude !== null && $sun_altitude > -6) {
            return self::result('daylight', 'نامناسب', 0, 'خورشید برای رصد سیاره در آسمان بسیار نزدیک یا بالای افق است.');
        }

        if ($altitude <= 0) {
            return self::result('not-visible', 'زیر افق', 0, 'جرم در زمان محاسبه زیر افق است.');
        }

        $score = min(96, max(8, (int) round(($altitude / 55) * 100)));
        if ($sun_altitude !== null && $sun_altitude > -12) {
            $score -= 18;
        }
        if ($moon_illumination !== null && $moon_illumination > 70) {
            $score -= 5;
        }
        if ($cloud_cover !== null && $cloud_cover > 65) {
            $score -= 22;
        } elseif ($cloud_cover !== null && $cloud_cover > 35) {
            $score -= 10;
        }

        $score = min(96, max(0, $score));
        if ($score >= 76) {
            return self::result('excellent', 'عالی', $score, 'امتیاز از ارتفاع واقعی جرم و شرایط موجود محاسبه شده است.');
        }
        if ($score >= 58) {
            return self::result('visible', 'قابل مشاهده', $score, 'جرم بالای افق است و شرایط کلی برای رصد عمومی قابل قبول است.');
        }
        if ($score >= 35) {
            return self::result('limited', 'محدود', $score, 'ارتفاع یا شرایط آسمان رصد را محدود می‌کند.');
        }
        return self::result('poor', 'نامناسب', $score, 'در این زمان برای رصد عمومی پیشنهاد نمی‌شود.');
    }

    private static function result($status, $label, $score, $reason)
    {
        return array(
            'status' => $status,
            'label' => $label,
            'score' => $score,
            'reason' => $reason,
            'bestWindow' => null,
        );
    }
}
