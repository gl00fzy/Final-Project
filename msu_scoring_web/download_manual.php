<?php
/**
 * MSU Scoring - Download User Manual (.docx)
 * Secure, clean UTF-8 download endpoint for Microsoft Word User Manual
 */
$filepath = __DIR__ . '/คู่มือการใช้งาน_ระบบตรวจข้อสอบ_MSU_Scoring.docx';

if (!file_exists($filepath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    die('ไม่พบไฟล์คู่มือการใช้งาน กรุณาติดต่อผู้ดูแลระบบ');
}

$filename = 'คู่มือการใช้งาน_ระบบตรวจข้อสอบ_MSU_Scoring.docx';
$filesize = filesize($filepath);

// Clean any previous output buffer to avoid corrupting binary docx stream
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $filesize);

readfile($filepath);
exit;
