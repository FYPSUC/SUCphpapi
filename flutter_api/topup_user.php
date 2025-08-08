<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/config.php';

if (!isset($_POST['uid']) || !isset($_POST['amount']) || !isset($_POST['role'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$uid = $_POST['uid'];
$amount = floatval($_POST['amount']);
$roleInput = ucfirst(strtolower($_POST['role'])); // "User" or "Vendor"

if (!in_array($roleInput, ['User', 'Vendor'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

$table = $roleInput === 'User' ? 'users' : 'vendor';
$checkStmt = $conn->prepare("SELECT FirebaseUID FROM $table WHERE FirebaseUID = ?");
$checkStmt->bind_param("s", $uid);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => "$roleInput UID not found"]);
    exit;
}

$conn->begin_transaction();

try {
    // 更新或插入 Vault
    $updateStmt = $conn->prepare("UPDATE vault SET Balance = Balance + ? WHERE OwnerID = ? AND OwnerType = ?");
    $updateStmt->bind_param("dss", $amount, $uid, $roleInput);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        $insertVaultStmt = $conn->prepare("INSERT INTO vault (OwnerID, OwnerType, Balance) VALUES (?, ?, ?)");
        $insertVaultStmt->bind_param("ssd", $uid, $roleInput, $amount);
        if (!$insertVaultStmt->execute()) {
            throw new Exception("Vault insert failed: " . $insertVaultStmt->error);
        }
    }

    // 插入交易记录
    $insertTxStmt = $conn->prepare(
        "INSERT INTO `transaction` (SenderID, SenderType, ReceiverID, ReceiverType, Amount, CreatedAt)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );


    if (!$insertTxStmt) {
        throw new Exception("Prepare transaction insert failed: " . $conn->error);
    }

    $insertTxStmt->bind_param("ssssd", $uid, $roleInput, $uid, $roleInput, $amount);

    if ($insertTxStmt->execute()) {
    
} else {

    throw new Exception("Transaction insert failed: " . $insertTxStmt->error);
}


    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Top up successful']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Top up failed: ' . $e->getMessage()]);
}

$conn->close();
?>
