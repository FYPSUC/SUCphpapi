<?php
include 'config.php';
header('Content-Type: application/json');

$uid = $_POST['uid'] ?? '';

if (empty($uid)) {
    echo json_encode(["success" => false, "message" => "UID is required"]);
    exit;
}

// ✅ 查 vendor 表中的所有需要字段
$stmt = $conn->prepare("SELECT Name, Email, role, ShopName, PickupAddress, AdShopImage, Image, SixDigitPassword FROM vendor WHERE FirebaseUID = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($vendor = $result->fetch_assoc()) {
    echo json_encode(["success" => true, "user" => $vendor]); 
} else {
    echo json_encode(["success" => false, "message" => "Vendor not found in vendor table"]);
}

$conn->close();
?>
