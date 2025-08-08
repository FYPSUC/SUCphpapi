<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firebase_uid = $_POST['firebase_uid'] ?? '';

    if (empty($firebase_uid)) {
        echo json_encode(['success' => false, 'message' => 'Missing Firebase UID']);
        exit;
    }

    try {
        // 获取所有跟该用户有关的交易记录（Sender or Receiver）
        $query = "
            SELECT 
                t.TransactionID,
                t.SenderID,
                t.ReceiverID,
                t.Amount,
                t.CreatedAt,
                t.OrderID,
                o.TotalAmount AS OrderTotal,
                o.UserID AS OrderUserID,
                o.VendorID AS OrderVendorID,
                o.status AS OrderStatus
            FROM transaction t
            LEFT JOIN `order` o ON t.OrderID = o.OrderID
            WHERE 
                (t.SenderID = ? AND t.SenderType = 'User')
                OR 
                (t.ReceiverID = ? AND t.ReceiverType = 'User')
            ORDER BY t.CreatedAt DESC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $firebase_uid, $firebase_uid);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];

        while ($row = $result->fetch_assoc()) {
            $type = '';
            $title = '';
            $amount = (float)$row['Amount'];
            $date = $row['CreatedAt'];
            $orderId = $row['OrderID'] ?? null;

            $isSender = $row['SenderID'] === $firebase_uid;
            $isReceiver = $row['ReceiverID'] === $firebase_uid;

            // ✅ 优先判断 Top-up（Sender = Receiver）
            if ($row['SenderID'] === $firebase_uid && $row['ReceiverID'] === $firebase_uid) {
                $type = 'topup';
                $title = 'Top-up';
                $amount = abs($amount); // 正数
            }
            elseif ($isSender) {
                if ($orderId) {
                    $type = 'order';
                    $title = 'Order Payment';
                } else {
                    $type = 'transfer_out';
                    $title = 'Transfer Out';
                }
                $amount = -abs($amount); // 出钱
            } 
            elseif ($isReceiver) {
                if ($orderId) {
                    $type = 'order_refund';
                    $title = 'Order Refund';
                } else {
                    $type = 'transfer_in';
                    $title = 'Received Transfer';
                }
                $amount = abs($amount); // 收钱
            }

            $transactions[] = [
                'type' => $type,
                'title' => $title,
                'amount' => number_format($amount, 2, '.', ''),
                'date' => $date,
                'order_id' => $orderId,
            ];
        }

        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
