<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$data = json_decode(file_get_contents("php://input"));
$uid = $data->firebaseUID;

$sql = "SELECT v.VoucherID, v.VoucherName, v.DiscountAmount, v.ExpiredDate, 
               v.Status, v.CreatedAt, vd.ShopName AS VendorName
        FROM voucher v
        JOIN vendor vd ON v.FirebaseUID = vd.FirebaseUID
        WHERE v.FirebaseUID = ? AND v.ExpiredDate >= CURDATE()";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

$vouchers = [];
while ($row = $result->fetch_assoc()) {
    $voucherID = $row['VoucherID'];

    // 🔍 查询统计数据（Claimed / Used / Unused）
    $stat_sql = "
    SELECT 
        SUM(CASE WHEN Status = 'claimed' THEN 1 ELSE 0 END) AS claimed,
        SUM(CASE WHEN Status = 'used' THEN 1 ELSE 0 END) AS used,
        SUM(CASE WHEN Status = 'claimed' AND UsedAt IS NULL THEN 1 ELSE 0 END) AS unused
    FROM uservoucher
    WHERE VoucherID = ?
";

    $stat_stmt = $conn->prepare($stat_sql);
    $stat_stmt->bind_param("i", $voucherID);
    $stat_stmt->execute();
    $stat_result = $stat_stmt->get_result();
    $stat = $stat_result->fetch_assoc();

    $vouchers[] = [
        'id' => $row['VoucherID'],
        'name' => $row['VoucherName'],
        'discount' => $row['DiscountAmount'],
        'date' => date('Y-m-d', strtotime($row['ExpiredDate'])),
        'amount' => 'RM ' . $row['DiscountAmount'] . ' Off',
        'expiry' => 'Use before ' . date('d/m', strtotime($row['ExpiredDate'])),
        'claimed' => (int)($stat['claimed'] ?? 0),
        'used' => (int)($stat['used'] ?? 0),
        'unused' => (int)($stat['unused'] ?? 0),
        'VendorName' => $row['VendorName'],
    ];
}

echo json_encode(["success" => true, "vouchers" => $vouchers]);
?>
