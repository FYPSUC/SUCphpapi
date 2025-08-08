<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';


$uid = $_GET['uid'] ?? '';

if (!$uid) {
    echo json_encode(["success" => false, "message" => "Missing uid"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM appointment WHERE UserID = ? ORDER BY DateTime DESC");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    echo json_encode(["success" => true, "appointments" => $appointments]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
