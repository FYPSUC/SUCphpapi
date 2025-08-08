<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productID = $_POST['ProductID'];
    $productName = $_POST['ProductName'];
    $productPrice = $_POST['ProductPrice'];
    $image_url = $_POST['image_url'] ?? null;

    if (empty($productID) || empty($productName) || empty($productPrice)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    if ($image_url) {
        // 如果有 image_url，就连图片一起更新
        $stmt = $conn->prepare("UPDATE product SET ProductName = ?, ProductPrice = ?, Image = ? WHERE ProductID = ?");
        $stmt->bind_param("sdsi", $productName, $productPrice, $image_url, $productID);
    } else {
        // 如果没有 image_url，只更新名称和价格
        $stmt = $conn->prepare("UPDATE product SET ProductName = ?, ProductPrice = ? WHERE ProductID = ?");
        $stmt->bind_param("sdi", $productName, $productPrice, $productID);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
