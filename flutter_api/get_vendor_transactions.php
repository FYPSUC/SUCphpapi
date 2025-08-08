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
        $query = "
            SELECT 
                t.TransactionID,
                t.SenderID,
                t.ReceiverID,
                t.Amount,
                t.CreatedAt,
                t.OrderID,
                o.TotalAmount AS OrderTotal,
                o.Status AS OrderStatus
            FROM transaction t
            LEFT JOIN `order` o ON t.OrderID = o.OrderID
            WHERE 
                t.ReceiverID = ? AND t.ReceiverType = 'Vendor'
            ORDER BY t.CreatedAt DESC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $firebase_uid);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];

        while ($row = $result->fetch_assoc()) {
            $title = 'Received Payment';
            $type = 'transfer';

            if (!empty($row['OrderID'])) {
                $title = 'Order Payment';
                $type = 'order';
            } elseif ($row['SenderID'] === $row['ReceiverID']) {
                $title = 'Top-up';
                $type = 'topup';
            }

            $transactions[] = [
                'type' => $type,
                'title' => $title,
                'amount' => number_format($row['Amount'], 2, '.', ''),
                'date' => $row['CreatedAt'],
                'order_id' => $row['OrderID'] ?? null,
                'status' => $row['OrderStatus'] ?? null,
            ];
        }

        echo json_encode(['success' => true, 'transactions' => $transactions]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
