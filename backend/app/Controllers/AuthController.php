<?php

class AuthController
{
    public function login()
    {
        $data = Request::json();
        $errors = Validator::required($data, array('email', 'password'));
        if (!empty($errors)) {
            Response::error('اطلاعات ورود کامل نیست.', 422, $errors);
        }

        $email = strtolower(Validator::clean($data['email']));
        $ip = Request::ip();
        $pdo = Database::connection();

        $attempt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND success = 0');
        $attempt->execute(array($ip));
        if ((int) $attempt->fetchColumn() >= 8) {
            Response::error('تعداد تلاش‌های ناموفق زیاد است. ۱۵ دقیقه بعد دوباره امتحان کنید.', 429);
        }

        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute(array($email));
        $user = $stmt->fetch();
        $valid = $user && $user['status'] === 'active' && password_verify($data['password'], $user['password_hash']);

        $log = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, success, attempted_at) VALUES (?, ?, ?, NOW())');
        $log->execute(array($email, $ip, $valid ? 1 : 0));

        if (!$valid) {
            Response::error('ایمیل یا رمز عبور صحیح نیست.', 401);
        }

        $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute(array($user['id']));
        unset($user['password_hash']);
        Response::json(array(
            'success' => true,
            'message' => 'ورود موفق بود.',
            'token' => Auth::issueToken($user),
            'user' => $user
        ));
    }

    public function me()
    {
        $user = Auth::requireAdmin();
        Response::success($user);
    }
}
