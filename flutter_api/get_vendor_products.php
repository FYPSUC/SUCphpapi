<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
include __DIR__ . '/config.php';

//在vendor setproduct地方用的

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_POST['uid'];

    $stmt = $conn->prepare("SELECT ProductID, ProductName, ProductPrice, Image, isSoldOut FROM product WHERE FirebaseUID = ?");
    
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
file_put_contents('debug_issoldout.txt', json_encode($_POST));
// 👈 打印数据库返回的每一行
    $products[] = [
    'ProductID' => $row['ProductID'],
    'ProductName' => $row['ProductName'],
    'ProductPrice' => $row['ProductPrice'],
    'Image' => $row['Image'],
    'isSoldOut' => (int)$row['isSoldOut']

];

}


    echo json_encode(['success' => true, 'menu' => $products]);

    $stmt->close();
    $conn->close();
}
?>
