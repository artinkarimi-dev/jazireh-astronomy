<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Jazireh_Horizons_Provider
{
    private const API_URL = 'https://ssd.jpl.nasa.gov/api/horizons.api';

    private const COMMANDS = array(
        'mercury' => '199',
        'venus' => '299',
        'moon' => '301',
        'mars' => '499',
        'jupiter' => '599',
        'saturn' => '699',
        'uranus' => '799',
        'neptune' => '899',
        'sun' => '10',
    );

    public function object($object, $observer, $timestamp = null, $mode = 'observer')
    {
        $timestamp = $timestamp ?: current_time('timestamp', true);
        $object = sanitize_key((string) $object);
        $observer = Jazireh_Ephemeris::normalize_observer($observer);

        if (!isset(self::COMMANDS[$object])) {
            return Jazireh_Ephemeris::unavailable_contract($object, $observer, $timestamp, 'unsupported object for Horizons provider', 'jpl-horizons');
        }

        $url = $this->build_url($object, $observer, $timestamp);
        $response = wp_remote_get($url, array(
            'timeout' => $this->timeout_seconds($mode, $object),
            'redirection' => 2,
            'user-agent' => 'Jazireh Astronomy WordPress/' . (defined('JAZIREH_CORE_VERSION') ? JAZIREH_CORE_VERSION : '1.0.0'),
        ));

        if (is_wp_error($response)) {
            return Jazireh_Ephemeris::unavailable_contract($object, $observer, $timestamp, $response->get_error_message(), 'jpl-horizons');
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300 || !is_array($body) || empty($body['result'])) {
            return Jazireh_Ephemeris::unavailable_contract($object, $observer, $timestamp, 'invalid Horizons response', 'jpl-horizons');
        }

        $parsed = $this->parse_result((string) $body['result']);
        if (empty($parsed['hasData'])) {
            return Jazireh_Ephemeris::unavailable_contract($object, $observer, $timestamp, 'Horizons returned no parseable ephemeris row', 'jpl-horizons');
        }

        return Jazireh_Ephemeris::contract(array(
            'object' => $object,
            'observer' => $observer,
            'timestamp' => $timestamp,
            'source' => 'jpl-horizons',
            'method' => 'observer-ephemeris',
            'accuracy' => 'authoritative',
            'confidence' => 'high',
            'position' => array(
                'rightAscension' => $parsed['rightAscension'],
                'declination' => $parsed['declination'],
                'altitude' => $parsed['altitude'],
                'azimuth' => $parsed['azimuth'],
                'distanceAu' => $parsed['distanceAu'],
            ),
            'appearance' => array(
                'magnitude' => $parsed['magnitude'],
                'elongation' => null,
                'illumination' => $parsed['illumination'],
                'phaseAngle' => $parsed['phaseAngle'],
                'angularDiameter' => $parsed['angularDiameter'],
            ),
            'raw' => array(
                'provider' => 'jpl-horizons',
                'labels' => $parsed['labels'],
            ),
        ));
    }

    private function build_url($object, $observer, $timestamp)
    {
        $start = gmdate('Y-M-d H:i', $timestamp);
        $stop = gmdate('Y-M-d H:i', $timestamp + HOUR_IN_SECONDS);
        $longitude = (float) $observer['longitude'];
        $latitude = (float) $observer['latitude'];
        $elevation = isset($observer['elevationMeters']) && $observer['elevationMeters'] !== null ? ((float) $observer['elevationMeters'] / 1000) : 0;

        return add_query_arg(array(
            'format' => 'json',
            'COMMAND' => "'" . self::COMMANDS[$object] . "'",
            'OBJ_DATA' => 'NO',
            'MAKE_EPHEM' => 'YES',
            'EPHEM_TYPE' => 'OBSERVER',
            'CENTER' => 'coord@399',
            'COORD_TYPE' => 'GEODETIC',
            'SITE_COORD' => "'" . $longitude . ',' . $latitude . ',' . $elevation . "'",
            'START_TIME' => "'" . $start . "'",
            'STOP_TIME' => "'" . $stop . "'",
            'STEP_SIZE' => "'1 h'",
            'QUANTITIES' => $object === 'moon' ? "'1,3,4,9,10,13,20,24'" : "'1,3,4,9,20'",
            'CSV_FORMAT' => 'YES',
            'REF_SYSTEM' => 'ICRF',
            'APPARENT' => 'AIRLESS',
        ), self::API_URL);
    }

    private function parse_result($result)
    {
        $lines = preg_split('/\r\n|\r|\n/', $result);
        $inside = false;
        $labels = array();

        foreach ($lines as $index => $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '$$SOE') {
                $inside = true;
                $labels = $this->find_labels($lines, $index);
                continue;
            }
            if ($trimmed === '$$EOE') {
                break;
            }
            if (!$inside || $trimmed === '') {
                continue;
            }

            $values = str_getcsv($trimmed);
            return $this->map_values($labels, $values);
        }

        return $this->empty_parse(false, $labels);
    }

    private function find_labels($lines, $soe_index)
    {
        for ($i = $soe_index - 1; $i >= 0; $i--) {
            $line = trim((string) $lines[$i]);
            if ($line === '' || strpos($line, ',') === false || stripos($line, 'Date') === false) {
                continue;
            }
            return array_map('trim', str_getcsv($line));
        }
        return array();
    }

    private function map_values($labels, $values)
    {
        $mapped = $this->empty_parse(true, $labels);
        foreach ($labels as $index => $label) {
            $value = isset($values[$index]) ? trim((string) $values[$index]) : '';
            $key = strtolower(preg_replace('/\s+/', ' ', $label));

            if (strpos($key, 'r.a.') !== false && $mapped['rightAscension'] === null) {
                $mapped['rightAscension'] = $this->numeric_or_text($value);
            } elseif (strpos($key, 'dec') !== false && $mapped['declination'] === null) {
                $mapped['declination'] = $this->numeric_or_text($value);
            } elseif ((strpos($key, 'a-app') !== false || strpos($key, 'az') !== false) && $mapped['azimuth'] === null) {
                $mapped['azimuth'] = $this->float_or_null($value);
            } elseif ((strpos($key, 'e-app') !== false || strpos($key, 'elev') !== false) && $mapped['altitude'] === null) {
                $mapped['altitude'] = $this->float_or_null($value);
            } elseif ((strpos($key, 'delta') !== false || strpos($key, 'range') !== false) && $mapped['distanceAu'] === null) {
                $mapped['distanceAu'] = $this->float_or_null($value);
            } elseif ((strpos($key, 'apmag') !== false || strpos($key, 'mag') !== false) && $mapped['magnitude'] === null) {
                $mapped['magnitude'] = $this->float_or_null($value);
            } elseif (strpos($key, 'illu') !== false && $mapped['illumination'] === null) {
                $mapped['illumination'] = $this->float_or_null($value);
            } elseif ((strpos($key, 's-t-o') !== false || strpos($key, 'phase') !== false) && $mapped['phaseAngle'] === null) {
                $mapped['phaseAngle'] = $this->float_or_null($value);
            } elseif (strpos($key, 'ang-diam') !== false && $mapped['angularDiameter'] === null) {
                $mapped['angularDiameter'] = $this->float_or_null($value);
            }
        }

        return $mapped;
    }

    private function empty_parse($has_data, $labels)
    {
        return array(
            'hasData' => (bool) $has_data,
            'rightAscension' => null,
            'declination' => null,
            'altitude' => null,
            'azimuth' => null,
            'distanceAu' => null,
            'magnitude' => null,
            'illumination' => null,
            'phaseAngle' => null,
            'angularDiameter' => null,
            'labels' => $labels,
        );
    }

    private function float_or_null($value)
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function numeric_or_text($value)
    {
        return is_numeric($value) ? (float) $value : ($value !== '' ? sanitize_text_field($value) : null);
    }

    private function timeout_seconds($mode, $object)
    {
        $mode = sanitize_key((string) $mode);
        $object = sanitize_key((string) $object);

        if ($mode === 'moon-trend') {
            return 8;
        }

        if ($object === 'moon' || $object === 'sun') {
            return 12;
        }

        if ($mode === 'planet-current' || $mode === 'planet-visibility-sun') {
            return 2;
        }

        return 12;
    }
}
