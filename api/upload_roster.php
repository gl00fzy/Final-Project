<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['roster_file'])) {
    $file = $_FILES['roster_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
        exit;
    }

    $handle = fopen($file['tmp_name'], "r");
    if ($handle !== FALSE) {
        // Skip header row if exists
        $first_row = fgetcsv($handle, 1000, ",");
        $has_header = false;
        
        // Check if first row is header
        if ($first_row && (strtolower(trim($first_row[0])) === 'student_id' || strtolower(trim($first_row[0])) === 'รหัสนิสิต')) {
            $has_header = true;
        } else {
            // Not header, reset pointer
            rewind($handle);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO students (student_id, name) VALUES (?, ?) ON CONFLICT(student_id) DO UPDATE SET name=excluded.name");
            $count = 0;
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Ensure at least 2 columns
                if (count($data) >= 2) {
                    $student_id = trim($data[0]);
                    $name = trim($data[1]);
                    
                    if (!empty($student_id) && !empty($name)) {
                        $stmt->execute([$student_id, $name]);
                        $count++;
                    }
                }
            }
            $pdo->commit();
            fclose($handle);
            
            echo json_encode(['status' => 'success', 'message' => "อัปเดตรายชื่อสำเร็จ $count คน"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            fclose($handle);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Cannot read file']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
