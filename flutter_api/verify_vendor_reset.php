<?php
require __DIR__ . '/vendor/autoload.php';
use Kreait\Firebase\Factory;

$token = $_GET['token'] ?? '';
if (!$token) {
    die('Token is missing');
}

require 'config.php';

$stmt = $conn->prepare("SELECT * FROM reset_tokens_vendor WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Invalid token');
}

$row = $result->fetch_assoc();
$email = $row['email'];
$newPassword = $row['new_password'];
$expireAt = strtotime($row['expire_at']);

if (time() > $expireAt) {
    die('Token expired');
}

// 初始化 Firebase Admin
$factory = (new Factory)->withServiceAccount(__DIR__.'/firebase_credentials.json');
$auth = $factory->createAuth();

try {
    $user = $auth->getUserByEmail($email);
    $uid = $user->uid;

    $auth->changeUserPassword($uid, $newPassword);

    // 删除 token
    $stmt = $conn->prepare("DELETE FROM reset_tokens_vendor WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    echo "<h3>✅ Vendor Password reset successfully.</h3>";
} catch (Exception $e) {
    echo "❌ Failed to reset Vendor password: " . $e->getMessage();
}
