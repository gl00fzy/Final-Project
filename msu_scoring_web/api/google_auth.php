<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

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
    
    // Check for @msu.ac.th domain
    if (!preg_match('/@msu\.ac\.th$/i', $email)) {
        echo json_encode(['status' => 'error', 'message' => 'อนุญาตให้เข้าสู่ระบบเฉพาะอีเมลของมหาวิทยาลัย (@msu.ac.th) เท่านั้น']);
        exit;
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Log them in
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'] ?? 'user';
        
        // Update google_id if it's missing
        if (empty($user['google_id'])) {
            $update = $pdo->prepare("UPDATE users SET google_id = ?, auth_provider = 'google', email = ? WHERE user_id = ?");
            $update->execute([$google_id, $email, $user['user_id']]);
        }
        
        echo json_encode(['status' => 'success']);
    } else {
        // Register new user
        $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // random password
        $insert = $pdo->prepare("INSERT INTO users (username, password, name, role, email, google_id, auth_provider) VALUES (?, ?, ?, 'user', ?, ?, 'google')");
        if ($insert->execute([$email, $random_password, $name, $email, $google_id])) {
            $new_user_id = $pdo->lastInsertId();
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['name']    = $name;
            $_SESSION['role']    = 'user';
            
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถสร้างบัญชีผู้ใช้ได้']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
