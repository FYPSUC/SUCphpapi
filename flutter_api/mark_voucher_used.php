<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents("php://input"), true);

$voucherID = $data['voucherID'];
$firebaseUID = $data['firebaseUID'];
$transactionID = $data['transactionID'] ?? null;

if (!$voucherID || !$firebaseUID) {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$sql = "UPDATE uservoucher 
        SET UsedAt = NOW(), Status = 'used', TransactionID = ? 
        WHERE VoucherID = ? AND FirebaseUID = ? AND Status = 'claimed'
        ORDER BY ClaimedAt ASC 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $transactionID, $voucherID, $firebaseUID);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
?>
