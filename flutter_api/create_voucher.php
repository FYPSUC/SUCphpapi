<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents("php://input"));

$uid = $data->firebaseUID;
$name = $data->name;
$amount = $data->amount;
$expiredDate = $data->expiredDate;

$status = 'active';
$createdAt = date('Y-m-d H:i:s');

// 转换日期格式
$expiredDateFormatted = date('Y-m-d H:i:s', strtotime($expiredDate));

$sql = "INSERT INTO voucher (FirebaseUID, VoucherName, DiscountAmount, ExpiredDate, Status, CreatedAt) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssdsss", $uid, $name, $amount, $expiredDateFormatted, $status, $createdAt);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>
