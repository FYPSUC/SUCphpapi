<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents("php://input"), true);

$order_id = $data['order_id'] ?? null;
$new_status = $data['status'] ?? null;

try {
    if (!$order_id || !$new_status) {
        throw new Exception("Missing order_id or status");
    }

    // 1️⃣ 更新订单状态
    $stmt = $conn->prepare("UPDATE `order` SET Status = ? WHERE OrderID = ?");
    $stmt->bind_param("si", $new_status, $order_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update order status: " . $stmt->error);
    }

    // 2️⃣ 查询该订单的 UserID
    $stmt = $conn->prepare("SELECT UserID FROM `order` WHERE OrderID = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row || !isset($row['UserID'])) {
        throw new Exception("User not found for the order");
    }

    $userID = $row['UserID'];

    // 3️⃣ 写入通知到 notifications 表（你需要确保有这个表）
    $message = "Your order #$order_id has been marked as '$new_status'";
    $stmt = $conn->prepare("INSERT INTO notifications (UserID, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $userID, $message);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Order status updated and user notified"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
