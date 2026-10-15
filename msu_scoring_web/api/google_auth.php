<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credential = $_POST['credential'] ?? '';
    
    if (empty($credential)) {
        echo json_encode(['status' => 'error', 'message' => 'Token is required']);
        exit;
    }

    // Verify token with Google's tokeninfo endpoint using cURL
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถตรวจสอบสิทธิ์กับ Google ได้']);
        exit;
    }

    $payload = json_decode($response, true);
    
    if (!$payload || !isset($payload['email'])) {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลผู้ใช้จาก Google ไม่ถูกต้อง']);
        exit;
    }


    $email = $payload['email'];
    $google_id = $payload['sub'];
    $name = $payload['name'] ?? 'อาจารย์ (Google)';
    
    // Check if domain restriction is enabled (ALLOW_ALL_GOOGLE_DOMAINS toggle)
    $allow_all_domains = filter_var(env('ALLOW_ALL_GOOGLE_DOMAINS', 'true'), FILTER_VALIDATE_BOOLEAN);

    if (!$allow_all_domains && !preg_match('/@msu\.ac\.th$/i', $email)) {
        echo json_encode(['status' => 'error', 'message' => 'อนุญาตให้เข้าสู่ระบบเฉพาะอีเมลของมหาวิทยาลัย (@msu.ac.th) เท่านั้น']);
        exit;
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? OR username = ?");
    $stmt->execute([$google_id, $email, $email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status'] === 'pending') {
            if ($allow_all_domains) {
                // Auto-activate existing pending accounts when allow_all_domains is active
                $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")->execute([$user['user_id']]);
                $user['status'] = 'active';
            } else {
                echo json_encode(['status' => 'pending', 'message' => 'บัญชีของคุณรอการอนุมัติจากผู้ดูแลระบบ กรุณาติดต่อ Admin']);
                exit;
            }
        }
        if ($user['status'] === 'suspended') {
            echo json_encode(['status' => 'error', 'message' => 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ']);
            exit;
        }

        // Ensure sample exam exists for evaluator if account has no exams yet
        if ($allow_all_domains) {
            seed_sample_exam_if_empty($pdo, (int)$user['user_id']);
        }

        // Log them in
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name']    = $user['name'] ?: $name;
        $_SESSION['role']    = $user['role'] ?? 'user';
        generate_csrf_token();
        
        // Update google_id if it's missing
        if (empty($user['google_id'])) {
            $update = $pdo->prepare("UPDATE users SET google_id = ?, auth_provider = 'google', email = ? WHERE user_id = ?");
            $update->execute([$google_id, $email, $user['user_id']]);
        }
        
        echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบสำเร็จ']);
    } else {
        // Register new user
        $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // random password
        $initial_status = $allow_all_domains ? 'active' : 'pending';
        $username = mb_substr($email, 0, 100);

        $insert = $pdo->prepare("INSERT INTO users (username, password, name, role, email, google_id, auth_provider, status) VALUES (?, ?, ?, 'user', ?, ?, 'google', ?)");
        if ($insert->execute([$username, $random_password, $name, $email, $google_id, $initial_status])) {
            if ($initial_status === 'active') {
                $newUserId = (int)$pdo->lastInsertId();

                // Seed sample exam for evaluator
                seed_sample_exam_if_empty($pdo, $newUserId);

                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['name']    = $name;
                $_SESSION['role']    = 'user';
                generate_csrf_token();
                echo json_encode(['status' => 'success', 'message' => 'เข้าสู่ระบบสำเร็จ (สร้างบัญชีและเปิดใช้งานเรียบร้อย)']);
            } else {
                echo json_encode(['status' => 'pending', 'message' => 'สมัครสมาชิกด้วย Google สำเร็จ กรุณารอการอนุมัติจากผู้ดูแลระบบ']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถสร้างบัญชีผู้ใช้ได้']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
