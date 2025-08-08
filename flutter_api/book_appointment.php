<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$uid = $_POST['uid'] ?? '';
$datetime = $_POST['datetime'] ?? '';
$note = $_POST['note'] ?? '';

if (!$uid || !$datetime) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO appointment (UserID, Status, DateTime) VALUES (?, 'PENDING', ?)");
    $stmt->bind_param("ss", $uid, $datetime);
    $stmt->execute();

    echo json_encode(["success" => true]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
