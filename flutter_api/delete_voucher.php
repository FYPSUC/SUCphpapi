<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents("php://input"));
$voucherID = $data->voucherID;

$sql = "DELETE FROM voucher WHERE VoucherID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $voucherID);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>
