<?php
include 'config.php';
header('Content-Type: application/json');

$uid = $_POST['uid'] ?? '';

if (empty($uid)) {
    echo json_encode(["success" => false, "message" => "UID is required"]);
    exit;
}

// 查询 users 表，确保角色是 User
$stmt = $conn->prepare("SELECT UserID, username, email, role FROM users WHERE FirebaseUID = ? AND role = 'User'");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "message" => "User login successful",
        "user" => $row
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "User account not found. Please register first."
    ]);
}

$stmt->close();
$conn->close();