<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productID = $_POST['ProductID'];

    $stmt = $conn->prepare("DELETE FROM product WHERE ProductID = ?");
    $stmt->bind_param("i", $productID);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }

    $stmt->close();
    $conn->close();
}
?>
