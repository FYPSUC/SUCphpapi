<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$firebaseUid = $_GET['firebase_uid'];

$query = "SELECT VoucherID FROM UserVoucher WHERE FirebaseUID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $firebaseUid);
$stmt->execute();
$result = $stmt->get_result();

$collectedIds = [];
while ($row = $result->fetch_assoc()) {
    $collectedIds[] = (int)$row['VoucherID'];
}

echo json_encode($collectedIds);
?>
