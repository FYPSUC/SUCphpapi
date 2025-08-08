<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
file_put_contents('debug_soldout.txt', json_encode($_POST));


include __DIR__ . '/config.php';

$productID = $_POST['ProductID'] ?? '';
$isSoldOut = $_POST['isSoldOut'] ?? '';

if (empty($productID) || $isSoldOut === '') {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE Product SET isSoldOut = ? WHERE ProductID = ?");
    $stmt->execute([$isSoldOut, $productID]);

    echo json_encode(["success" => true, "message" => "Sold out status updated"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
