<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$sender_uid = $_POST['sender_uid'] ?? '';
$receiver_uid = $_POST['receiver_uid'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);
$six_digit_password = $_POST['SixDigitPassword'] ?? '';
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : null; // ✅ 新增

if (!$sender_uid || !$receiver_uid || $amount <= 0 || !$six_digit_password) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid input']);
    exit;
}

// Step 1: 检查 Sender 是 user 或 vendor
$sender_type = null;
$sender_data = null;

$stmt = $conn->prepare("SELECT * FROM users WHERE FirebaseUID = ?");
$stmt->bind_param("s", $sender_uid);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $sender_type = 'User';
    $sender_data = $row;
} else {
    $stmt = $conn->prepare("SELECT * FROM vendor WHERE FirebaseUID = ?");
    $stmt->bind_param("s", $sender_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $sender_type = 'Vendor';
        $sender_data = $row;
    }
}

if (!$sender_type || !$sender_data) {
    echo json_encode(['success' => false, 'message' => 'Sender not found']);
    exit;
}

// Step 2: 验证 6 位密码
if ($sender_data['SixDigitPassword'] !== $six_digit_password) {
    echo json_encode(['success' => false, 'message' => 'Incorrect 6-digit PIN']);
    exit;
}

// Step 3: 获取余额
$stmt = $conn->prepare("SELECT Balance FROM vault WHERE OwnerID = ? AND OwnerType = ?");
$stmt->bind_param("ss", $sender_uid, $sender_type);
$stmt->execute();
$result = $stmt->get_result();
$sender_vault = $result->fetch_assoc();

if (!$sender_vault || floatval($sender_vault['Balance']) < $amount) {
    echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
    exit;
}

// Step 4: 确定 Receiver 是 user 还是 vendor
$receiver_type = null;

$stmt = $conn->prepare("SELECT * FROM users WHERE FirebaseUID = ?");
$stmt->bind_param("s", $receiver_uid);
$stmt->execute();
$result = $stmt->get_result();
if ($result->fetch_assoc()) {
    $receiver_type = 'User';
} else {
    $stmt = $conn->prepare("SELECT * FROM vendor WHERE FirebaseUID = ?");
    $stmt->bind_param("s", $receiver_uid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $receiver_type = 'Vendor';
    }
}

if (!$receiver_type) {
    echo json_encode(['success' => false, 'message' => 'Receiver not found']);
    exit;
}

// ✅ 开始事务处理
$conn->begin_transaction();

try {
    // Step 5: 扣除 sender 金额
    $stmt1 = $conn->prepare("UPDATE vault SET Balance = Balance - ? WHERE OwnerID = ? AND OwnerType = ?");
    $stmt1->bind_param("dss", $amount, $sender_uid, $sender_type);
    $stmt1->execute();
    if ($stmt1->affected_rows === 0) {
        throw new Exception("Failed to deduct sender balance");
    }

    // Step 6: 增加 receiver 金额
    $stmt2 = $conn->prepare("UPDATE vault SET Balance = Balance + ? WHERE OwnerID = ? AND OwnerType = ?");
    $stmt2->bind_param("dss", $amount, $receiver_uid, $receiver_type);
    $stmt2->execute();
    if ($stmt2->affected_rows === 0) {
        throw new Exception("Failed to credit receiver balance");
    }

    // ✅ Step 7: 写入 transaction，包括 order_id（可为 NULL）
    $stmt3 = $conn->prepare("
        INSERT INTO transaction (SenderID, SenderType, ReceiverID, ReceiverType, Amount, OrderID, CreatedAt)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt3->bind_param("ssssid", $sender_uid, $sender_type, $receiver_uid, $receiver_type, $amount, $order_id);
    $stmt3->execute();
    
$transaction_id = $stmt3->insert_id;

$conn->commit();
echo json_encode(['success' => true, 'transaction_id' => $transaction_id]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
?>
