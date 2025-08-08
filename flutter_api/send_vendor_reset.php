<?php
require 'config.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gmail = $_POST['email'];
    $newPassword = $_POST['new_password'];

    // 生成 token 和过期时间
    $token = bin2hex(random_bytes(16));
    $expire = date("Y-m-d H:i:s", time() + 6); // 10分钟

    // 存入 reset_tokens 表（可与 User 共用）
   $stmt = $conn->prepare("INSERT INTO reset_tokens_vendor (email, token, new_password, expire_at) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]));
}

$stmt->bind_param("ssss", $gmail, $token, $newPassword, $expire);
if (!$stmt->execute()) {
    die(json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]));
}


    // 构造验证链接（注意替换域名/IP）
    $BASE_URL = "https://5a8accff9752.ngrok-free.app/flutter_api";
    $link = "$BASE_URL/verify_vendor_reset.php?token=$token";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sucfyp.app@gmail.com';
        $mail->Password = 'mljgnsruommrlpjk'; // ⚠️ 使用 16 位 App 密码
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('sucfyp.app@gmail.com', 'SUCFYP Vendor');
        $mail->addAddress($gmail);
        $mail->Subject = "Reset Your Vendor Password";
        $mail->Body = "Click the link to reset your Vendor password:\n$link";

        if ($mail->send()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mailer error: ' . $mail->ErrorInfo]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Mailer exception: ' . $e->getMessage(),
        ]);
    }
}
