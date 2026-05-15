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
    <link rel="icon" type="image/png" href="favicon_pic/favicon_for_web.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-['Inter']">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">OMR System</h1>
                <p class="text-gray-500">ระบบตรวจข้อสอบแบบปรนัย</p>
            </div>
            
            <div id="loginAlert" class="hidden mb-6 p-4 rounded-lg bg-red-50 text-red-600 border border-red-200 text-sm"></div>

            <form id="loginForm" class="flex flex-col gap-5">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้งาน</label>
                    <input type="text" id="username" name="username" required placeholder="เช่น teacher_demo" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required placeholder="รหัสผ่าน (password123)" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-3 px-6 rounded-xl transition-colors mt-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">เข้าสู่ระบบ</button>
            </form>
        </div>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">ยังไม่มีบัญชีผู้ใช้งาน? <a href="register.php" class="text-yellow-600 font-semibold hover:text-yellow-700 hover:underline transition-colors">สมัครสมาชิก</a></p>
        </div>
        
        <p class="mt-8 text-sm text-gray-400">Powered by Advanced Agentic AI</p>
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
                    alert.classList.remove('hidden');
                    alert.classList.add('block');
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
</body>
</html>
