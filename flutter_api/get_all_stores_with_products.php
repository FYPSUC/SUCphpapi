<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

//在fetchstore用的，也就是user order食物，可以加数量的那个地方

$result = [];

$query = "SELECT v.FirebaseUID, v.ShopName, v.PickupAddress, v.AdShopImage,
                 p.ProductID, p.ProductName, p.ProductPrice, p.Image, p.isSoldOut,
                 COALESCE(SUM(oi.Quantity), 0) AS total_quantity,
                 COALESCE(SUM(oi.Subtotal), 0) AS total_sales
          FROM vendor v
          LEFT JOIN product p ON v.FirebaseUID = p.FirebaseUID
          LEFT JOIN orderitem oi ON p.ProductID = oi.ProductID
          GROUP BY v.FirebaseUID, v.ShopName, v.PickupAddress, v.AdShopImage,
                   p.ProductID, p.ProductName, p.ProductPrice, p.Image, p.isSoldOut
          ORDER BY total_quantity DESC";


$res = mysqli_query($conn, $query);

if (!$res) {
    echo json_encode(["success" => false, "message" => "Query failed"]);
    exit;
}

$storeMap = [];

while ($row = mysqli_fetch_assoc($res)) {
    $uid = $row['FirebaseUID'];
    if (!isset($storeMap[$uid])) {
        $storeMap[$uid] = [
            "FirebaseUID" => $uid, // 👈 让 Flutter 能接收到 vendorUID
            "store_name" => $row['ShopName'] ?? "Unnamed Store",
            "location" => $row['PickupAddress'] ?? "Unknown Location",
            "ad_image" => $row['AdShopImage'] ?? "",
            "menu" => [],
        ];
    }

    if (!empty($row['ProductName'])) {
        $storeMap[$uid]['menu'][] = [
    "id" => $row['ProductID'],
    "name" => $row['ProductName'],
    "price" => floatval($row['ProductPrice']),
    "image" => $row['Image'],
    "isSoldOut" => (int)$row['isSoldOut'],
    "total_quantity" => (int)$row['total_quantity'],
    "total_sales" => (float)$row['total_sales']
];

    }
}

$stores = array_values($storeMap);

echo json_encode([
    "success" => true,
    "stores" => $stores,
]);
?>
