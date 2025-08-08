<?php
require __DIR__ . '/vendor/autoload.php'; // Firebase SDK 自动加载器
use Kreait\Firebase\Factory;

$token = $_GET['token'] ?? '';
if (!$token) {
    die('Token is missing');
}

require 'config.php'; // 数据库连接

// 1. 查找 token 是否有效
$stmt = $conn->prepare("SELECT * FROM reset_tokens WHERE token = ?");
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

// ✅ 2. 初始化 Firebase Admin SDK
$factory = (new Factory)->withServiceAccount(__DIR__.'/firebase_credentials.json');
$auth = $factory->createAuth();

// ✅ 3. 根据 email 找到用户 UID
try {
    $user = $auth->getUserByEmail($email);
    $uid = $user->uid;

    // ✅ 4. 更新密码
    $auth->changeUserPassword($uid, $newPassword);

    // ✅ 5. 删除 token
    $stmt = $conn->prepare("DELETE FROM reset_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    echo "<h3>✅ Password has been successfully reset. You can now log in using your new password.</h3>";
} catch (Exception $e) {
    echo "❌ Failed to reset password: " . $e->getMessage();
}
