<?php

class Auth
{
    public static function issueToken($user)
    {
        $now = time();
        $ttl = (int) Config::get('JWT_TTL', 86400);
        $header = array('alg' => 'HS256', 'typ' => 'JWT');
        $payload = array(
            'sub' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => $now,
            'exp' => $now + $ttl
        );

        $segments = array(
            self::base64UrlEncode(json_encode($header)),
            self::base64UrlEncode(json_encode($payload))
        );
        $signature = hash_hmac('sha256', implode('.', $segments), self::signingSecret(), true);
        $segments[] = self::base64UrlEncode($signature);
        return implode('.', $segments);
    }

    public static function decodeToken($token)
    {
        $parts = explode('.', (string) $token);
        if (count($parts) !== 3) {
            return null;
        }

        $signature = self::base64UrlDecode($parts[2]);
        $expected = hash_hmac('sha256', $parts[0] . '.' . $parts[1], self::signingSecret(), true);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        if (!is_array($payload) || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function user()
    {
        $payload = self::decodeToken(Request::bearerToken());
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }

        $stmt = Database::connection()->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute(array((int) $payload['sub']));
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function requireAdmin()
    {
        $user = self::user();
        if (!$user) {
            Response::error('برای دسترسی به این بخش وارد شوید.', 401);
        }
        if ($user['role'] !== 'admin' || $user['status'] !== 'active') {
            Response::error('دسترسی شما مجاز نیست.', 403);
        }
        return $user;
    }

    private static function signingSecret()
    {
        $secret = (string) Config::get('JWT_SECRET', '');
        if (strlen($secret) < 32 || strpos($secret, 'replace_with_') === 0) {
            throw new RuntimeException('JWT_SECRET must be configured with at least 32 random characters.');
        }
        return $secret;
    }

    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
