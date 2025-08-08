<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$vendor_uid = $_GET['vendor_uid'] ?? null;
if (!$vendor_uid) {
    echo json_encode(["success" => false, "message" => "Missing vendor_uid"]);
    exit;
}

$sql = "
SELECT o.OrderID, o.Status, o.OrderDate, oi.ProductID, oi.Quantity, oi.UnitPrice, p.ProductName
FROM `order` o
JOIN orderitem oi ON o.OrderID = oi.OrderID
JOIN product p ON oi.ProductID = p.ProductID
WHERE o.VendorID = ? AND o.Status != 'completed'
ORDER BY o.OrderDate DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $vendor_uid);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $order_id = $row['OrderID'];

    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            "orderId" => $order_id,
            "status" => strtolower($row['Status']),
            "orderDate" => $row['OrderDate'],
            "items" => []
        ];
    }

    $orders[$order_id]["items"][] = [
        "ProductID" => $row["ProductID"],
        "ProductName" => $row["ProductName"],
        "Quantity" => $row["Quantity"],
        "UnitPrice" => $row["UnitPrice"]
    ];
}

echo json_encode(array_values($orders)); // 转换成 indexed array 返回给前端
?>
