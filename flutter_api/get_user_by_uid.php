<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/config.php';

$uid = $_POST['uid'] ?? '';

if (empty($uid)) {
    echo json_encode(["success" => false, "message" => "UID is required"]);
    exit;
}

$stmt = $conn->prepare("SELECT username, email, role, Image, SixDigitPassword FROM users WHERE FirebaseUID = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // 🔍 查 vault 表获取余额
    $stmt2 = $conn->prepare("SELECT Balance FROM vault WHERE OwnerID = ? AND OwnerType = 'User'");
    $stmt2->bind_param("s", $uid);
    $stmt2->execute();
    $vaultResult = $stmt2->get_result();
    $vaultRow = $vaultResult->fetch_assoc();

    $balance = $vaultRow ? floatval($vaultRow['Balance']) : 0;

    // 🔄 添加 balance 字段
    $user['balance'] = $balance;

    echo json_encode(["success" => true, "user" => $user]);
} else {
    echo json_encode(["success" => false, "message" => "User not found in users table"]);
}

$conn->close();
