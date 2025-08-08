<?php
header('Content-Type: application/json');
ob_clean(); // 清除缓冲区
error_reporting(0); // 临时关闭 PHP 报错（也可用 E_ERROR）

include __DIR__ . '/config.php'; 

//储存vendor history的完成过的订单

$order_id = $_POST['order_id'] ?? null;

try {
    if (!$order_id) throw new Exception("Missing order_id");

    // 获取订单主信息
    $stmt = $conn->prepare("SELECT * FROM `order` WHERE OrderID = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();

    if ($order_result->num_rows === 0) throw new Exception("Order not found");

    $order = $order_result->fetch_assoc();

    // 获取订单项
    $stmt_items = $conn->prepare("SELECT oi.*, p.ProductName 
                                  FROM orderitem oi
                                  JOIN product p ON oi.ProductID = p.ProductID
                                  WHERE oi.OrderID = ?");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();

    $items = [];
    while ($row = $items_result->fetch_assoc()) {
        $items[] = $row;
    }

    echo json_encode([
        "success" => true,
        "order" => [
            "orderId" => $order['OrderID'],
            "status" => $order['status'],
            "total" => floatval($order['TotalAmount']),
            "items" => $items
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
