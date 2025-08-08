<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_POST['uid'];
    $productName = $_POST['ProductName'];
    $productPrice = $_POST['ProductPrice'];
    $image_url = $_POST['image_url'];

    if (empty($uid) || empty($productName) || empty($productPrice) || empty($image_url)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO product (FirebaseUID, ProductName, ProductPrice, Image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $uid, $productName, $productPrice, $image_url);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
