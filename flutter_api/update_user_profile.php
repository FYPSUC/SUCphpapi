<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$uid = $_POST['uid'];
$role = $_POST['role']; // 'user' or 'vendor'

// 初始化字段
$image_url = $_POST['image_url'] ?? null;
$Name = $_POST['Name']??null;
$adshop_url = $_POST['AdShopImage'] ?? null;
$six_digit_password = $_POST['SixDigitPassword'] ?? null;
$shop_name = $_POST['ShopName'] ?? null;
$pickup_address = $_POST['PickupAddress'] ?? null;
$username = $_POST['username'] ?? null;

if ($role === 'Vendor') {
    $check_sql = "SELECT Image, AdShopImage FROM vendor WHERE FirebaseUID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $uid);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$image_url && isset($row['Image'])) {
        $image_url = $row['Image'];
    }

    if (!$adshop_url && isset($row['AdShopImage'])) {
        $adshop_url = $row['AdShopImage'];
    }

    $sql = "UPDATE vendor SET ";
    $updates = [];
    $params = [];
    $types = "";

    if (!empty($image_url)) {
        $updates[] = "Image = ?";
        $params[] = $image_url;
        $types .= "s";
    }
    if (!empty($Name)) {
        $updates[] = "Name = ?";
        $params[] = $Name;
        $types .= "s";
    }
    if (!empty($adshop_url)) {
        $updates[] = "AdShopImage = ?";
        $params[] = $adshop_url;
        $types .= "s";
    }
    if (!empty($shop_name)) {
        $updates[] = "ShopName = ?";
        $params[] = $shop_name;
        $types .= "s";
    }
    if (!empty($pickup_address)) {
        $updates[] = "PickupAddress = ?";
        $params[] = $pickup_address;
        $types .= "s";
    }
    if (!empty($six_digit_password)) {
        $updates[] = "SixDigitPassword = ?";
        $params[] = $six_digit_password;
        $types .= "s";
    }

    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $sql .= implode(', ', $updates);
    $sql .= " WHERE FirebaseUID = ?";
    $params[] = $uid;
    $types .= "s";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => $conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => $stmt->error]);
    }
}else if ($role === 'User') {
    $check_sql = "SELECT Image FROM users WHERE FirebaseUID = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $uid);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$image_url && isset($row['Image'])) {
        $image_url = $row['Image'];
    }

    $sql = "UPDATE users SET Image = ?, username = ?";
    $params = [$image_url, $username];
    $types = "ss";

    if (!empty($six_digit_password)) {
        $sql .= ", SixDigitPassword = ?";
        $params[] = $six_digit_password;
        $types .= "s";
    }

    $sql .= " WHERE FirebaseUID = ?";
    $params[] = $uid;
    $types .= "s";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed', 'error' => $conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => $stmt->error]);
    }

} 
// ❌ 角色错误处理
else {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
}
?>
