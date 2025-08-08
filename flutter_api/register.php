<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php-error.log');
header('Content-Type: application/json');

include __DIR__ . '/config.php';

// 获取字段
$uid = $_POST['uid'] ?? null;
$username = $_POST['username'] ?? null;
$email = $_POST['email'] ?? null;
$role = $_POST['role'] ?? null;

// ✅ 只允许 Firebase 注册（必须有 UID，不能有 password）
if ($uid && $username && $email && $role) {
    // 检查 UID 是否已存在
    $checkUser = $conn->prepare("SELECT FirebaseUID FROM users WHERE FirebaseUID = ?");
    $checkUser->bind_param("s", $uid);
    $checkUser->execute();
    $resultUser = $checkUser->get_result();

    $checkVendor = $conn->prepare("SELECT FirebaseUID FROM vendor WHERE FirebaseUID = ?");
    $checkVendor->bind_param("s", $uid);
    $checkVendor->execute();
    $resultVendor = $checkVendor->get_result();
file_put_contents('register-debug.log', json_encode($_POST) . "\n", FILE_APPEND);

    if ($resultUser->num_rows > 0 || $resultVendor->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Account already exists']);
    } else {
        if ($role === 'User') {
            $stmt = $conn->prepare("INSERT INTO users (FirebaseUID, username, email, role) VALUES (?, ?, ?, 'User')");
            $stmt->bind_param("sss", $uid, $username, $email);
        } elseif ($role === 'Vendor') {
            $stmt = $conn->prepare("INSERT INTO vendor (FirebaseUID, Name, email, role) VALUES (?, ?, ?, 'Vendor')");
            $stmt->bind_param("sss", $uid, $username, $email);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            exit;
        }

         if ($stmt->execute()) {
           
            // ✅ 注册成功后插入 QR 数据
            $qr_data = "firebase_uid:$uid";
            $insertQR = $conn->prepare("INSERT INTO qr (OwnerID, OwnerType, QR_Data, CreatedAt) VALUES (?, ?, ?, NOW())");
            $insertQR->bind_param("sss", $uid, $role, $qr_data);
            $insertQR->execute();
            $insertQR->close();

            // 注册成功后插入 vault 初始记录（根据角色）
            $insertVault = $conn->prepare("INSERT INTO vault (OwnerID, Balance, OwnerType) VALUES (?, 0.00, ?)");
            $insertVault->bind_param("ss", $uid, $role);
            $insertVault->execute();
            $insertVault->close();

            echo json_encode(['success' => true, 'message' => 'Registered successfully']);
} else {
    $errorMsg = $stmt->error;
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $errorMsg]);
}
       


        $stmt->close();
    }
    

    $checkUser->close();
    $checkVendor->close();
} else {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
}

$conn->close();
?>
