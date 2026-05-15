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
    <title>สมัครสมาชิก - ระบบตรวจข้อสอบแบบปรนัย</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-['Inter']">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8 border border-gray-100">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">สมัครสมาชิก</h1>
                <p class="text-gray-500">สำหรับอาจารย์ผู้ใช้งานใหม่</p>
            </div>
            
            <div id="registerAlert" class="hidden mb-6 p-4 rounded-lg bg-red-50 text-red-600 border border-red-200 text-sm"></div>

            <form id="registerForm" class="flex flex-col gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                    <input type="text" id="name" name="name" required placeholder="เช่น อาจารย์ สมชาย" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" id="username" name="username" required placeholder="เช่น teacher_demo" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่าน</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="6" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="invite_code" class="block text-sm font-medium text-gray-700 mb-1">รหัสเชิญ (Invite Code)</label>
                    <input type="text" id="invite_code" name="invite_code" required placeholder="รหัสสำหรับยืนยันตัวตน" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold py-3 px-6 rounded-xl transition-colors mt-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2">สร้างบัญชีผู้ใช้</button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="index.php" class="text-yellow-600 font-semibold hover:text-yellow-700 hover:underline transition-colors">เข้าสู่ระบบ</a></p>
            </div>
        </div>
        
        <p class="mt-8 text-sm text-gray-400">Powered by Advanced Agentic AI</p>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const alert = document.getElementById('registerAlert');
            alert.classList.add('hidden');
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert.textContent = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
                alert.classList.remove('hidden');
                alert.classList.add('block');
                return;
            }
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('api/register_action.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Success! Redirect to login page
                    alert.textContent = data.message + " กรุณารอสักครู่...";
                    alert.classList.remove('bg-red-50', 'text-red-600', 'border-red-200');
                    alert.classList.add('bg-yellow-50', 'text-yellow-600', 'border-yellow-200', 'block');
                    alert.classList.remove('hidden');
                    
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    alert.textContent = data.message;
                    alert.classList.remove('hidden');
                    alert.classList.add('block');
                }
            } catch (error) {
                console.error('Error:', error);
                alert.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์';
                alert.classList.remove('hidden');
                alert.classList.add('block');
            }
        });
    </script>
</body>
</html>
