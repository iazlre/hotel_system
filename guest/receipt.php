<?php 
require __DIR__.'/../auth.php'; 
need_login();
if ($_SESSION['role']!=='Guest') redirect('../login.php');
$u = user();
$gid = (int)$u['guest_id'];

$type = isset($_GET['type']) ? $_GET['type'] : 'booking';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$is_reservation = ($type === 'reservation');

if ($is_reservation) {
    // Reservation Slip Logic
    $stmt = $conn->prepare("
        SELECT rr.*, g.first_name, g.last_name, g.email, r.room_number, r.room_type, r.price_per_night 
        FROM room_reservations rr
        JOIN guests g ON rr.guest_id = g.guest_id
        JOIN rooms r ON rr.room_id = r.room_id
        WHERE rr.reservation_id = ? AND rr.guest_id = ?
    ");
    $stmt->bind_param("ii", $id, $gid);
    $stmt->execute();
    $res = $stmt->get_result();
    $b = $res->fetch_assoc();

    if (!$b) die("<h3 style='text-align:center; margin-top:50px;'>Reservation not found or unauthorized.</h3>");
    
    $checkin = strtotime($b['expected_check_in']);
    $checkout = strtotime($b['expected_check_out']);
    $nights = max(1, ceil(($checkout - $checkin) / 86400));
    $total_amount = $nights * $b['price_per_night'];
    $status_badge = $b['reservation_status'];
    $date_issued = date('M d, Y g:i A', strtotime($b['reservation_date']));
    $checkin_display = date('M d, Y g:i A', $checkin);
    $checkout_display = date('M d, Y g:i A', $checkout);
    $receipt_no = "RES-" . str_pad($b['reservation_id'], 5, '0', STR_PAD_LEFT);

} else {
    // Official Receipt Logic
    $stmt = $conn->prepare("
        SELECT b.*, g.first_name, g.last_name, g.email, r.room_number, r.room_type, r.price_per_night 
        FROM booking_list b
        JOIN guests g ON b.guest_id = g.guest_id
        JOIN rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = ? AND b.guest_id = ?
    ");
    $stmt->bind_param("ii", $id, $gid);
    $stmt->execute();
    $res = $stmt->get_result();
    $b = $res->fetch_assoc();

    if (!$b) die("<h3 style='text-align:center; margin-top:50px;'>Receipt not found or unauthorized.</h3>");
    
    $checkin = strtotime($b['check_in']);
    $checkout = $b['check_out'] ? strtotime($b['check_out']) : time();
    $nights = max(1, ceil(($checkout - $checkin) / 86400));
    $total_amount = $b['total_amount'];
    $status_badge = $b['payment_status'];
    $date_issued = date('M d, Y g:i A');
    $checkin_display = date('M d, Y g:i A', $checkin);
    $checkout_display = $b['check_out'] ? date('M d, Y g:i A', strtotime($b['check_out'])) : 'Active';
    $receipt_no = "#" . str_pad($b['booking_id'], 6, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $is_reservation ? 'Reservation Slip' : 'Official Receipt' ?> - <?= $receipt_no ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #f1f5f9; padding: 20px; color: #333; }
        .receipt-box { max-width: 400px; margin: 0 auto; background: #fff; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 15px; margin-bottom: 15px; }
        .header h2 { margin: 0 0 5px 0; font-size: 20px; color: #1e293b; }
        .header p { margin: 0; font-size: 12px; color: #64748b; }
        .details { font-size: 13px; line-height: 1.6; margin-bottom: 15px; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 4px 0; }
        .details .right { text-align: right; font-weight: bold; }
        .divider { border-top: 2px dashed #cbd5e1; margin: 15px 0; }
        .total { font-size: 18px; font-weight: bold; display: flex; justify-content: space-between; margin-bottom: 10px; }
        .footer { text-align: center; font-size: 11px; color: #64748b; margin-top: 20px; }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .bg-green { background: #dcfce7; color: #166534; }
        .bg-red { background: #fee2e2; color: #991b1b; }
        .bg-yellow { background: #fef9c3; color: #854d0e; }
        .bg-blue { background: #dbeafe; color: #1e40af; }
        
        /* BAGONG BUTTON DESIGN */
        .button-group { display: flex; gap: 10px; margin-top: 25px; }
        .btn-action { 
            flex: 1; 
            padding: 12px; 
            border-radius: 6px; 
            font-weight: bold; 
            font-size: 13px; 
            text-align: center; 
            text-decoration: none; 
            cursor: pointer; 
            border: none;
            transition: background 0.3s;
        }
        .btn-print { background: #38bdf8; color: white; }
        .btn-print:hover { background: #0284c7; }
        .btn-back { background: #94a3b8; color: white; }
        .btn-back:hover { background: #64748b; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-box { box-shadow: none; max-width: 100%; padding: 0; }
            .button-group { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-box">
    <div class="header">
        <h2>Hotel & Restaurant</h2>
        <p><?= $is_reservation ? 'Reservation Slip' : 'Official Receipt' ?></p>
    </div>

    <div class="details">
        <table>
            <tr><td>No:</td><td class="right"><?= $receipt_no ?></td></tr>
            <tr><td>Date Issued:</td><td class="right"><?= $date_issued ?></td></tr>
            <tr><td>Guest Name:</td><td class="right"><?= e($b['first_name'] . ' ' . $b['last_name']) ?></td></tr>
            <tr><td>Email:</td><td class="right"><?= e($b['email']) ?></td></tr>
        </table>
    </div>

    <div class="divider"></div>

    <div class="details">
        <table>
            <tr><td>Room:</td><td class="right"><?= e($b['room_number']) ?> (<?= e($b['room_type']) ?>)</td></tr>
            <tr><td>Rate / Night:</td><td class="right">₱<?= number_format($b['price_per_night'], 2) ?></td></tr>
            <tr><td>Check-In:</td><td class="right"><?= $checkin_display ?></td></tr>
            <tr><td>Check-Out:</td><td class="right"><?= $checkout_display ?></td></tr>
            <tr><td>Total Nights:</td><td class="right"><?= $nights ?></td></tr>
        </table>
    </div>

    <div class="divider"></div>

    <div class="total">
        <span><?= $is_reservation ? 'EST. TOTAL:' : 'TOTAL:' ?></span>
        <span>₱<?= number_format($total_amount, 2) ?></span>
    </div>

    <div style="text-align: right; margin-bottom: 20px;">
        <?= $is_reservation ? 'Reservation Status:' : 'Payment Status:' ?>
        <span class="badge <?= ($status_badge === 'Paid' || $status_badge === 'Confirmed') ? 'bg-green' : (($status_badge === 'Unpaid' || $status_badge === 'Cancelled') ? 'bg-red' : 'bg-blue') ?>">
            <?= e($status_badge) ?>
        </span>
    </div>

    <div class="footer">
        <?= $is_reservation ? 'Please present this slip upon check-in.' : 'Thank you for staying with us!' ?><br>
        This is a system-generated document.
    </div>

    <!-- BAGONG BUTTONS -->
    <div class="button-group">
        <button class="btn-action btn-print" onclick="window.print()">Print Document</button>
        <a href="my_bookings.php" class="btn-action btn-back">Back to Bookings</a>
    </div>
</div>

</body>
</html>