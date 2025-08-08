<?php
header('Content-Type: application/json');
include __DIR__ . '/config.php';

$query = "SELECT v.*, vd.ShopName AS VendorName 
          FROM voucher v 
          JOIN vendor vd ON v.FirebaseUID = vd.FirebaseUID 
          WHERE v.Status = 'active' AND v.ExpiredDate > NOW() 
          ORDER BY v.ExpiredDate ASC";

$result = $conn->query($query);

$vouchers = [];

while ($row = $result->fetch_assoc()) {
    $vouchers[] = $row;
}

echo json_encode($vouchers);
?>
