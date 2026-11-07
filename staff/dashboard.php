<?php require __DIR__.'/_layout.php';
$u=user();
$pendingArrivals = $conn->query("SELECT COUNT(*) c FROM room_reservations WHERE reservation_status='Confirmed'")->fetch_assoc()['c'];
$active    = $conn->query("SELECT COUNT(*) c FROM booking_list WHERE booking_status='Checked-In'")->fetch_assoc()['c'];
$myTasks   = $conn->query("SELECT COUNT(*) c FROM housekeeping_logs WHERE assigned_housekeeper_id=".(int)$u['staff_id']." AND cleaning_status<>'Completed'")->fetch_assoc()['c'];

// --- CALENDAR DATA PREPARATION ---
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);
$dayOfWeek = date('w', strtotime("$currentYear-$currentMonth-01"));

// Fetch Rooms for Calendar
$rooms = [];
$roomQuery = $conn->query("SELECT * FROM rooms ORDER BY room_number");
while($r = $roomQuery->fetch_assoc()) $rooms[] = $r;

// Fetch Cafe Reservations for Calendar
$cafeData = [];
$cafeQuery = $conn->query("SELECT reservation_date, COUNT(*) as c FROM cafe_reservations WHERE status IN ('Confirmed', 'Seated') AND MONTH(reservation_date) = $currentMonth AND YEAR(reservation_date) = $currentYear GROUP BY reservation_date");
while($c = $cafeQuery->fetch_assoc()) {
    $cafeData[$c['reservation_date']] = $c['c'];
}

function drawStaffCalendar($title, $type, $conn, $rooms, $cafeData, $month, $year, $daysInMonth, $dayOfWeek) {
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $monthName = $months[$month - 1];
    
    echo "<div style='flex: 1; min-width: 350px; background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 20px; border: 1px solid #e2e8f0;'>";
    echo "<h3 style='text-align: center; font-size: 16px; color: #1e293b; margin-bottom: 20px;'>$title <br><span style='font-size:12px; color:#64748b; font-weight:normal;'>$monthName $year</span></h3>";
    
    echo "<div style='display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center;'>";
    
    foreach(['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $d) {
        echo "<div style='font-weight: bold; font-size: 11px; color: #64748b; padding-bottom:5px;'>$d</div>";
    }
    
    for($i = 0; $i < $dayOfWeek; $i++) echo "<div></div>";

    $today = date('Y-m-d');

    for($day = 1; $day <= $daysInMonth; $day++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $isPast = (strtotime($dateStr) < strtotime($today));
        $isToday = ($dateStr === $today);
        $dayName = date('D', strtotime($dateStr));
        
        $style = "padding: 8px 2px; border-radius: 6px; font-size: 12px; font-weight: bold; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60px; ";
        $border = $isToday ? "border: 2px solid #38bdf8;" : "border: 1px solid #e2e8f0;";
        $badge = "";

        if ($isPast) {
            $style .= "background: #f8fafc; color: #cbd5e1; " . $border;
        } else if ($type === 'room') {
            // BINAGO: Bilangin kung ilan ang Standard at Deluxe na bakante
            $stdVacant = 0;
            $dlxVacant = 0;

            foreach($rooms as $r) {
                $rid = $r['room_id'];
                $rtype = $r['room_type'];
                
                $chk = $conn->query("
                    SELECT COUNT(*) c FROM (
                        SELECT room_id FROM room_reservations WHERE room_id=$rid AND reservation_status='Confirmed' AND expected_check_in <= '$dateStr 23:59:59' AND expected_check_out >= '$dateStr 00:00:00'
                        UNION
                        SELECT room_id FROM booking_list WHERE room_id=$rid AND booking_status='Checked-In' AND check_in <= '$dateStr 23:59:59' AND (check_out IS NULL OR check_out >= '$dateStr 00:00:00')
                    ) as b
                ")->fetch_assoc()['c'];
                
                if($chk == 0) {
                    if($rtype == 'Standard Room') $stdVacant++;
                    if($rtype == 'Deluxe Suite') $dlxVacant++;
                }
            }
            
            $totalVacant = $stdVacant + $dlxVacant;

            if ($totalVacant == 0) {
                $style .= "background: #fee2e2; color: #991b1b; " . $border;
                $badge = "<span style='font-size: 9px; font-weight: normal; margin-top: 3px;'>Fully Booked</span>";
            } else {
                $style .= "background: #dcfce7; color: #166534; " . $border;
                
                // Buuin ang text (e.g. "2 Std, 1 Dlx")
                $parts = [];
                if($stdVacant > 0) $parts[] = "$stdVacant Std";
                if($dlxVacant > 0) $parts[] = "$dlxVacant Dlx";
                
                $badge = "<span style='font-size: 9px; font-weight: normal; margin-top: 3px; line-height:1.2;'>" . implode('<br>', $parts) . "</span>";
            }

        } else if ($type === 'cafe') {
            if ($dayName === 'Tue') {
                $style .= "background: #f1f5f9; color: #94a3b8; " . $border;
                $badge = "<span style='font-size: 9px; font-weight: normal; margin-top: 3px;'>Closed</span>";
            } else {
                $style .= "background: #fef9c3; color: #854d0e; " . $border;
                $count = $cafeData[$dateStr] ?? 0;
                $badge = "<span style='font-size: 9px; font-weight: normal; margin-top: 3px;'>$count Booked</span>";
            }
        }

        echo "<div style='$style'> $day $badge </div>";
    }
    echo "</div></div>";
}

header_html('dashboard','Welcome, '.$u['name']); ?>

<p style="color:#64748b;margin-bottom:25px;">You're signed in as <b><?=e($u['staff_role'])?></b>. Use Operations to act on behalf of guests.</p>

<div class="card-grid">
 <div class="stat-card"><h3>Pending Arrivals</h3><div class="value" style="color:#38bdf8"><?=$pendingArrivals?></div></div>
 <div class="stat-card"><h3>Active Bookings</h3><div class="value" style="color:#22c55e"><?=$active?></div></div>
 <div class="stat-card"><h3>My Open Tasks</h3><div class="value" style="color:#f59e0b"><?=$myTasks?></div></div>
</div>

<!-- DUAL CALENDAR UI -->
<div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 30px;">
    <?php drawStaffCalendar('🏨 Room Availability', 'room', $conn, $rooms, $cafeData, $currentMonth, $currentYear, $daysInMonth, $dayOfWeek); ?>
    <?php drawStaffCalendar('☕ Café 1947 Bookings', 'cafe', $conn, $rooms, $cafeData, $currentMonth, $currentYear, $daysInMonth, $dayOfWeek); ?>
</div>

<div style="margin-top: 20px; text-align: center; font-size: 12px; color: #64748b; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
    <b>Legends:</b> 
    <span style="color:#166534; font-weight:bold;">Green</span> = Rooms Available | 
    <span style="color:#991b1b; font-weight:bold;">Red</span> = Fully Booked | 
    <span style="color:#854d0e; font-weight:bold;">Yellow</span> = Cafe Bookings | 
    <span style="color:#94a3b8; font-weight:bold;">Gray</span> = Closed / Past
    <br><br>
    <b>Room Types:</b> <b>Std</b> = Standard Room (2 Pax) | <b>Dlx</b> = Deluxe Suite (4 Pax)
</div>

<?php footer_html();