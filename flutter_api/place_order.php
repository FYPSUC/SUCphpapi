<?php
header('Content-Type: application/json'); // userplaceorder
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents("php://input"), true);

$firebase_uid = $data['firebase_uid'] ?? null;
$vendor_uid = $data['vendor_uid'] ?? null;
$total_amount = $data['total'] ?? 0;
$voucher_id = $data['voucher_id'] ?? null;
$order_items = $data['items'] ?? [];

try {
    if (!$firebase_uid || !$vendor_uid || !$total_amount || empty($order_items)) {
        throw new Exception("Missing required fields");
    }

    // Voucher null 检查
    if ($voucher_id === '' || $voucher_id === null) {
        $voucher_id = null;
    } else {
        $voucher_id = intval($voucher_id);
    }

    // 插入订单
    if ($voucher_id === null) {
        $stmt = $conn->prepare("INSERT INTO `order` (UserID, VendorID, TotalAmount, Status, OrderDate)
                                VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("ssd", $firebase_uid, $vendor_uid, $total_amount);
    } else {
        $stmt = $conn->prepare("INSERT INTO `order` (UserID, VendorID, TotalAmount, VoucherID, Status, OrderDate)
                                VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("ssdi", $firebase_uid, $vendor_uid, $total_amount, $voucher_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert order: " . $stmt->error);
    }

    $order_id = $stmt->insert_id;

    // 插入订单项
    $stmt_item = $conn->prepare("INSERT INTO orderitem (OrderID, ProductID, Quantity, UnitPrice)
                                 VALUES (?, ?, ?, ?)");
    foreach ($order_items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $unit_price = $item['unit_price'];

        $stmt_item->bind_param("iiid", $order_id, $product_id, $quantity, $unit_price);
        if (!$stmt_item->execute()) {
            throw new Exception("Failed to insert order item: " . $stmt_item->error);
        }
    }


    echo json_encode([
        "success" => true,
        "message" => "Order placed successfully",
        "order_id" => $order_id
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
