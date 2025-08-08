<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/config.php';

// ✅ 检查参数
if (!isset($_POST['uid']) || !isset($_POST['role'])) {
    echo json_encode(['success' => false, 'message' => 'Missing uid or role']);
    exit;
}

$uid = $_POST['uid'];
$role = ucfirst(strtolower($_POST['role'])); // 统一为 User / Vendor

if (!in_array($role, ['User', 'Vendor'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// ✅ 查询余额
$sql = "SELECT Balance FROM vault WHERE OwnerID = ? AND OwnerType = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ss", $uid, $role);
$stmt->execute();
$stmt->bind_result($balance);

if ($stmt->fetch()) {
    echo json_encode(['success' => true, 'balance' => floatval($balance)]);
} else {
    // ✅ 没有 Vault 记录时默认返回 0
    echo json_encode(['success' => true, 'balance' => 0.0]);
}

$stmt->close();
$conn->close();
?>
