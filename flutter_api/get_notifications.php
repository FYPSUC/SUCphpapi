<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$uid = $_GET['uid'] ?? '';

if (!$uid) {
    echo json_encode(['success' => false, 'message' => 'Missing uid']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE UserID = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
