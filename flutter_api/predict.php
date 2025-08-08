<?php
header("Content-Type: application/json");
include __DIR__ . '/config.php'; // 连接数据库

$input = json_decode(file_get_contents("php://input"), true);
$uid = $input['FirebaseUID'] ?? null;

if (!$uid) {
    echo json_encode(["success" => false, "message" => "FirebaseUID missing"]);
    exit;
}

// FirebaseUID 就是 SenderID，所以无需查 users 表
$userId = $uid;


// 获取最近 5 条交易记录
$sql = "SELECT SenderID, SenderType, ReceiverType, CreatedAt 
        FROM transaction
        WHERE SenderID = ? 
        ORDER BY CreatedAt DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();
$transaction = [];

while ($row = $result->fetch_assoc()) {
    $transaction[] = $row;
}

if (empty($transaction)) {
    echo json_encode(["success" => false, "message" => "No recent transaction found"]);
    exit;
}

// 准备传入 Python 的 JSON 输入
$inputJSON = json_encode($transaction);

// 执行 Python 脚本
$command = "python3 predict_transaction.py";
$descriptorspec = [
    0 => ["pipe", "r"],  // stdin
    1 => ["pipe", "w"],  // stdout
    2 => ["pipe", "w"]   // stderr
];

$process = proc_open($command, $descriptorspec, $pipes);

if (is_resource($process)) {
    fwrite($pipes[0], $inputJSON);
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $return_value = proc_close($process);

    if ($return_value === 0) {
        echo $output; // 返回预测结果
    } else {
        echo json_encode(["success" => false, "error" => $error]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Failed to start Python script."]);
}
?>
