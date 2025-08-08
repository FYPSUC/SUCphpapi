<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
include __DIR__ . '/config.php';

$voucherId = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
$firebaseUid = $_POST['firebase_uid'] ?? '';

if (!$voucherId || !$firebaseUid) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

// 确认 voucher 存在
$checkVoucher = $conn->prepare("SELECT 1 FROM voucher WHERE VoucherID = ?");
$checkVoucher->bind_param("i", $voucherId);
$checkVoucher->execute();
$result = $checkVoucher->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid voucher']);
    exit;
}

// 检查是否已领取
$checkQuery = "SELECT * FROM UserVoucher WHERE VoucherID = ? AND FirebaseUID = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("is", $voucherId, $firebaseUid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already claimed']);
    exit;
}

// 插入记录
$insertQuery = "INSERT INTO UserVoucher (VoucherID, FirebaseUID, ClaimedAt, Status) VALUES (?, ?, NOW(), 'claimed')";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("is", $voucherId, $firebaseUid);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
}
