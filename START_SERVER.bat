@echo off
title MSU Scoring Server Launcher
chcp 65001 >nul
cls
echo ===================================================
echo     MSU Scoring — ระบบตรวจข้อสอบ (Emergency Launcher)
echo ===================================================
echo กำลังเริ่มต้นระบบทั้งหมด...
echo.

:: 1. เริ่มต้น MySQL
echo [1/4] ตรวจสอบและเริ่มทำงาน MySQL...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo   - MySQL ทำงานอยู่แล้ว
) else (
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    echo   - สั่งเปิด MySQL เรียบร้อย
)
timeout /t 2 >nul

:: 2. เริ่มต้น Python OMR Engine
echo [2/4] ตรวจสอบและเริ่มทำงาน Python OMR Service (Port 8000)...
start "MSU OMR Python Engine" /MIN python -m uvicorn omr_service:app --app-dir "C:\Final Project\msu_scoring_web\python" --host 127.0.0.1 --port 8000
echo   - สั่งเปิด Python OMR Service เรียบร้อย

:: 3. เริ่มต้น PHP Web Server
echo [3/4] ตรวจสอบและเริ่มทำงาน Web Application (Port 8080)...
start "MSU Web Application" /MIN "C:\xampp\php\php.exe" -S 0.0.0.0:8080 -t "C:\Final Project\msu_scoring_web"
echo   - สั่งเปิด Web Application เรียบร้อย

:: 4. เริ่มต้น Ngrok Tunnel
echo [4/4] เริ่มต้น Ngrok Tunnel สำหรับลิงก์ภายนอก...
start "MSU Ngrok Tunnel" ngrok http 8080

timeout /t 2 >nul
echo.
echo ===================================================
echo  ระบบพร้อมใช้งานแล้ว 100%!
echo  - เปิดใช้งานในเครื่อง: http://localhost:8080
echo  - เข้าสู่ระบบด้วยปุ่ม: 1-Click Demo Login
echo ===================================================
echo.

:: เปิด Browser อัตโนมัติ
start http://localhost:8080

pause
