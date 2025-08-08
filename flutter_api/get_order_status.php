<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents("php://input"), true);
$order_id = $data['order_id'];

$response = [];

$stmt = $conn->prepare("SELECT Status FROM `order` WHERE OrderID = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $response['success'] = true;
    $response['status'] = $row['Status'];

    // 获取订单项
    $items = [];
    $stmt_items = $conn->prepare("
        SELECT p.ProductName, oi.Quantity
        FROM orderitem oi
        JOIN product p ON oi.ProductID = p.ProductID
        WHERE oi.OrderID = ?
    ");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    while ($item = $result_items->fetch_assoc()) {
        $items[] = [
            "name" => $item['ProductName'],
            "quantity" => (int)$item['Quantity']
        ];
    }

    $response['items'] = $items;

    echo json_encode($response);
} else {
    echo json_encode(["success" => false, "message" => "Order not found"]);
}
?>
