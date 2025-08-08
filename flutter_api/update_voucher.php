<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents('php://input'), true);
error_log(print_r($data, true));

$voucherId = $data['voucherId'];
$name = $data['name'];
$discount = floatval($data['discount']);
$expiredDate = $data['expiredDate'];

$response = [];

$sql = "UPDATE voucher SET VoucherName = ?, DiscountAmount = ?, ExpiredDate = ? WHERE VoucherID = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response['success'] = false;
    $response['error'] = "Prepare failed: " . $conn->error;
    echo json_encode($response);
    exit;
}

$stmt->bind_param("sdsi", $name, $discount, $expiredDate, $voucherId);

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['success'] = false;
    $response['error'] = $stmt->error;
}

echo json_encode($response);
?>
