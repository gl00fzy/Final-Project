<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบตรวจข้อสอบแบบปรนัย</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container" style="min-height: 100vh; display: flex; flex-direction: column; justify-content: center;">
        <div class="card shadow-lg">
            <div class="text-center mb-4">
                <h1 style="color: var(--primary-color);">OMR System</h1>
                <p>ระบบตรวจข้อสอบแบบปรนัย</p>
            </div>
            
            <div id="loginAlert" class="alert alert-error" style="display: none;"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้งาน</label>
                    <input type="text" id="username" name="username" required placeholder="เช่น teacher_demo">
                </div>
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required placeholder="รหัสผ่าน (password123)">
                </div>
                <button type="submit" class="btn btn-primary mt-4">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    window.location.href = 'dashboard.php';
                } else {
                    const alert = document.getElementById('loginAlert');
                    alert.textContent = data.message;
                    alert.style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>
