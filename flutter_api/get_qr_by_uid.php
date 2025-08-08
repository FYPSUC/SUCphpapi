<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$uid = $_GET['uid'] ?? null;

if (!$uid) {
    echo json_encode(["success" => false, "message" => "UID is required"]);
    exit;
}

$stmt = $conn->prepare("SELECT QR_Data FROM qr WHERE OwnerID = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["success" => true, "qr_data" => $row['QR_Data']]);
} else {
    echo json_encode(["success" => false, "message" => "QR not found"]);
}

$stmt->close();
$conn->close();
