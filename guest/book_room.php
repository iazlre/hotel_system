<?php require __DIR__.'/_layout.php'; $u=user(); $gid=(int)$u['guest_id'];

$default_in = date('Y-m-d\TH:i'); 
$default_out = date('Y-m-d\TH:i', strtotime('+1 day'));

if ($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('book_room.php'); }
    $rid=(int)$_POST['room_id'];
    
    $default_in = $_POST['in'];
    $default_out = $_POST['out'];

    $in = str_replace('T', ' ', $default_in);
    $out = str_replace('T', ' ', $default_out);

    $stmt=$conn->prepare("SELECT COUNT(*) c FROM room_reservations WHERE room_id=? AND reservation_status='Confirmed' AND expected_check_in < ? AND expected_check_out > ?");
    $stmt->bind_param("iss",$rid,$out,$in); $stmt->execute();
    $overlap=$stmt->get_result()->fetch_assoc()['c'];

    $stmt2=$conn->prepare("SELECT COUNT(*) c FROM booking_list WHERE room_id=? AND booking_status='Checked-In' AND check_in < ? AND (check_out IS NULL OR check_out > ?)");
    $stmt2->bind_param("iss",$rid,$out,$in); $stmt2->execute();
    $active=$stmt2->get_result()->fetch_assoc()['c'];
    
    if ($overlap + $active > 0){
        flash('error','Sorry, this room is already booked for those specific dates. Please check the slots below.');
    } else {
        $stmt3=$conn->prepare("INSERT INTO room_reservations (guest_id,room_id,expected_check_in,expected_check_out,reservation_status) VALUES (?,?,?,?,'Confirmed')");
        $stmt3->bind_param("iiss",$gid,$rid,$in,$out);
        if ($stmt3->execute()){
            $conn->query("UPDATE rooms SET status='Reserved' WHERE room_id=$rid");
            flash('success','Room reserved! Please check-in at the front desk on your arrival date.');
        } else flash('error','Failed: '.$conn->error);
    }
    redirect('book_room.php');
}

// --- PREPARE CALENDAR DATA FOR JAVASCRIPT ---
$calendarData = [];
$generalAvailability = [];
$allRooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");
$roomList = [];

while($r = $allRooms->fetch_assoc()) {
    $roomList[] = $r;
    $rid = $r['room_id'];
    $rstatus = $r['status'];
    
    $reservations = $conn->query("SELECT expected_check_in, expected_check_out FROM room_reservations WHERE room_id=$rid AND reservation_status='Confirmed'");
    $bookings = $conn->query("SELECT check_in, check_out FROM booking_list WHERE room_id=$rid AND booking_status='Checked-In'");
    
    $blocked = [];
    while($res = $reservations->fetch_assoc()) {
        $start = strtotime($res['expected_check_in']);
        $end = strtotime($res['expected_check_out']);
        for($i = $start; $i <= $end; $i += 86400) {
            $blocked[date('Y-m-d', $i)] = 'Reserved';
        }
    }
    while($bk = $bookings->fetch_assoc()) {
        $start = strtotime($bk['check_in']);
        $end = strtotime($bk['check_out'] ?? date('Y-m-d'));
        for($i = $start; $i <= $end; $i += 86400) {
            $blocked[date('Y-m-d', $i)] = 'Occupied';
        }
    }
    
    for($i = 0; $i < 60; $i++) {
        $d = date('Y-m-d', strtotime("+$i days"));
        if ($rstatus == 'Cleaning' && $d == date('Y-m-d')) {
            $calendarData[$rid][$d] = 'Cleaning';
        } elseif (isset($blocked[$d])) {
            $calendarData[$rid][$d] = $blocked[$d];
        } else {
            $calendarData[$rid][$d] = 'Vacant';
        }
    }
}

for($i = 0; $i < 60; $i++) {
    $d = date('Y-m-d', strtotime("+$i days"));
    $vacantCount = 0;
    foreach($calendarData as $rid => $dates) {
        if (isset($dates[$d]) && $dates[$d] === 'Vacant') $vacantCount++;
    }
    $generalAvailability[$d] = ($vacantCount > 0) ? 'Vacant' : 'Occupied';
}

// Function para mag-drawing ng Calendar Month
function drawMonth($month, $year) {
    $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $monthName = $months[$month - 1];
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $dayOfWeek = date('w', strtotime("$year-$month-01"));
    
    echo "<div style='flex: 1; min-width: 280px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>";
    echo "<h3 style='text-align: center; margin-bottom: 15px; color: #1e293b; font-size: 16px;'>$monthName $year</h3>";
    echo "<div style='display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; text-align: center;'>";
    
    $days = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    foreach($days as $d) echo "<div style='font-weight: bold; font-size: 10px; color: #64748b;'>$d</div>";
    
    for($i = 0; $i < $dayOfWeek; $i++) echo "<div></div>";

    for($day = 1; $day <= $daysInMonth; $day++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $isPast = (strtotime($dateStr) < strtotime(date('Y-m-d')));
        
        $style = $isPast ? "background: #f1f5f9; color: #cbd5e1; cursor: not-allowed;" : "background: #dcfce7; color: #166534; cursor: pointer; border: 1px solid #bbf7d0;";
        $onclick = $isPast ? "" : "onclick=\"selectCalendarDate('$dateStr')\"";
        
        echo "<div class='cal-cell' data-date='$dateStr' data-past='".($isPast?'true':'false')."' $onclick style='padding: 8px 0; border-radius: 4px; font-size: 12px; font-weight: bold; $style'>$day</div>";
    }
    echo "</div></div>";
}

$currentMonth = (int)date('n');
$currentYear = (int)date('Y');
$nextMonth = (int)date('n', strtotime('+1 month'));
$nextYear = (int)date('Y', strtotime('+1 month'));

header_html('book','Book a Room'); ?>

<style>
    /* Modal Design */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; justify-content: center; align-items: center; }
    .modal-content { background: #f8fafc; padding: 25px; border-radius: 12px; width: 95%; max-width: 900px; position: relative; }
    .close-btn { position: absolute; top: 15px; right: 20px; font-size: 28px; font-weight: bold; color: #ef4444; cursor: pointer; background: none; border: none; line-height: 1; }
    .close-btn:hover { color: #991b1b; }
    
    /* INALIS KO ANG MGA EXACT SPACING SA CSS PARA MAS MALINIS */
    .slot-table { width: 100%; border-collapse: collapse; font-size: 11px; table-layout: fixed; background: white; }
    .slot-table th { padding: 12px 8px; font-size: 10px; text-align: center; background: #38bdf8; color: white; font-weight: 600; }
    .slot-table th:first-child, .slot-table td:first-child { text-align: left; padding-left: 15px; width: 100px; }
    .slot-table td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; text-align: center; vertical-align: middle; }
    .slot-table tr:last-child td { border-bottom: none; }
    
    .slot-box { padding: 8px 4px; border-radius: 6px; font-weight: bold; font-size: 10px; }
</style>

<div class="form-container"><h3>New Room Reservation</h3>
<form method="POST" id="bookingForm"><?=csrf_field()?>
 <div style="width:100%; margin-bottom:15px;">
   <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:5px;">Select Room</label>
   <select name="room_id" id="roomSelect" required style="width:100%; padding:10px;" onchange="calcTotal()">
    <option value="">— Pick a Room —</option>
    <?php foreach($roomList as $x):
        if($x['status'] == 'Vacant' || $x['status'] == 'Reserved'): ?>
            <option value="<?= $x['room_id'] ?>" data-price="<?= $x['price_per_night'] ?>">
                <?= e($x['room_number']) ?> — <?= e($x['room_type']) ?> (<?= $x['status'] ?>)
            </option>
        <?php endif; 
    endforeach; ?>
   </select>
 </div>
 
 <div style="width:100%; display:flex; flex-wrap:wrap; gap:15px; margin-bottom:15px;">
   <div style="flex:1 1 200px;">
       <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:5px;">Check-In Date & Time</label>
       <input type="datetime-local" name="in" id="checkin" required style="width:100%; padding:10px; box-sizing:border-box;" value="<?= $default_in ?>" onchange="calcTotal()">
   </div>
   <div style="flex:1 1 100px;">
       <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:5px;">Number of Nights</label>
       <input type="number" id="nights" min="1" value="1" required style="width:100%; padding:10px; box-sizing:border-box;" onchange="calcTotal()">
   </div>
   <div style="flex:1 1 200px;">
       <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:5px;">Check-Out Date & Time</label>
       <input type="datetime-local" name="out" id="checkout" required style="width:100%; padding:10px; box-sizing:border-box;" readonly value="<?= $default_out ?>">
   </div>
 </div>

 <div style="width:100%; margin-bottom:15px; font-size:18px; font-weight:bold; color:#1e293b;">
     Total Price: <span id="totalDisplay" style="color:#22c55e;">₱0.00</span>
 </div>
 <button class="btn btn-primary">Reserve</button>
</form></div>

<div style="display:flex; justify-content:space-between; align-items:flex-end; margin:30px 0 10px;">
    <div>
        <h2 style="font-size:18px;color:#1e293b;margin:0;"> Room Availability (Next 7 Days)</h2>
        <p style="font-size:12px; color:#64748b; margin-top:5px;">Click a <b style="color:#22c55e;">Vacant</b> slot to automatically fill the form above.</p>
    </div>
    <button type="button" class="btn btn-secondary" onclick="openCalendar()" style="padding: 10px 15px; font-size: 13px;"> Open Full Calendar</button>
</div>

<!-- BINAGO: Nilagyan ng background, border-radius, shadow, at margin -->
<div style="overflow-x:auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #e2e8f0;">
    <table class="slot-table">
        <tr>
            <th>ROOM</th>
            <?php 
            $dates = [];
            for($i = 0; $i < 7; $i++){
                $d = date('Y-m-d', strtotime("+$i days"));
                $dates[] = $d;
                echo "<th>" . date('M d', strtotime($d)) . "<br><span style='font-weight:normal; font-size:9px;'>(" . date('D', strtotime($d)) . ")</span></th>";
            }
            ?>
        </tr>
        <?php
        foreach($roomList as $room):
            $rid = $room['room_id'];
            $rnumber = $room['room_number'];
            $rprice = $room['price_per_night'];
            $current_room_status = $room['status'];
        ?>
        <tr>
            <td style="font-weight:bold;">
                <?=e($rnumber)?> <br>
                <span style="font-size:9px; color:#64748b; font-weight:normal;"><?=e($room['room_type'])?></span>
            </td>
            <?php foreach($dates as $date): 
                $dateStart = $date . ' 00:00:00';
                $dateEnd   = $date . ' 23:59:59';
                
                $chk1 = $conn->query("SELECT COUNT(*) c FROM room_reservations WHERE room_id=$rid AND reservation_status='Confirmed' AND expected_check_in <= '$dateEnd' AND expected_check_out >= '$dateStart'")->fetch_assoc()['c'];
                $chk2 = $conn->query("SELECT COUNT(*) c FROM booking_list WHERE room_id=$rid AND booking_status='Checked-In' AND check_in <= '$dateEnd' AND (check_out IS NULL OR check_out >= '$dateStart')")->fetch_assoc()['c'];
                
                $isOccupied = ($chk2 > 0);
                $isReserved = ($chk1 > 0);
                $isCleaning = ($current_room_status === 'Cleaning' && $date === date('Y-m-d'));
            ?>
                <td>
                    <?php if ($isCleaning): ?>
                        <div class="slot-box" style="background:#dbeafe; color:#1e40af;">Cleaning</div>
                    <?php elseif ($isOccupied): ?>
                        <div class="slot-box" style="background:#fee2e2; color:#991b1b;">Occupied</div>
                    <?php elseif ($isReserved): ?>
                        <div class="slot-box" style="background:#fef9c3; color:#854d0e;">Reserved</div>
                    <?php else: ?>
                        <div class="slot-box" onclick="selectSlot(<?=$rid?>, '<?=$date?>', <?=$rprice?>)" style="background:#dcfce7; color:#166534; cursor:pointer;" onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">Vacant</div>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- MODAL POP UP (CALENDAR VIEW) -->
<div id="calendarModal" class="modal-overlay">
    <div class="modal-content">
        <button class="close-btn" onclick="closeCalendar()">&times;</button>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <h2 style="font-size:20px;color:#1e293b;margin:0;">📅 Availability Calendar</h2>
            <p style="font-size:13px; color:#64748b; margin-top:5px;">Click any date to set your Check-In.</p>
        </div>

        <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 25px; background: #e0f2fe; padding: 10px; border-radius: 8px; border: 1px solid #bae6fd;">
            <label style="font-weight: bold; font-size: 13px; color: #0369a1;">Filter by Room:</label>
            <select id="modalRoomSelect" onchange="updateModalCalendar()" style="padding: 8px 15px; border-radius: 6px; border: 1px solid #7dd3fc; font-weight: bold; color: #1e293b; outline: none;">
                <option value="">-- General Availability --</option>
                <?php foreach($roomList as $r): ?>
                    <option value="<?= $r['room_id'] ?>">Room <?= $r['room_number'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;">
            <?php drawMonth($currentMonth, $currentYear); ?>
            <?php drawMonth($nextMonth, $nextYear); ?>
        </div>
    </div>
</div>

<h2 style="font-size:18px;color:#1e293b;margin:30px 0 15px;">All Rooms Details</h2>
<!-- BINAGO: Inayos din ang details table para maganda -->
<div style="overflow-x:auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
    <table style="width:100%; border-collapse:collapse; font-size:12px;">
        <tr><th style="padding:15px; text-align:left; background:#38bdf8; color:white;">Room</th><th style="padding:15px; text-align:left; background:#38bdf8; color:white;">Type</th><th style="padding:15px; text-align:left; background:#38bdf8; color:white;">Capacity</th><th style="padding:15px; text-align:left; background:#38bdf8; color:white;">Price/Night</th></tr>
    <?php foreach($roomList as $x): ?>
        <tr>
            <td style="padding:12px 15px; border-bottom:1px solid #e2e8f0;"><?=e($x['room_number'])?></td>
            <td style="padding:12px 15px; border-bottom:1px solid #e2e8f0;"><?=e($x['room_type'])?></td>
            <td style="padding:12px 15px; border-bottom:1px solid #e2e8f0;"><?=$x['capacity']?></td>
            <td style="padding:12px 15px; border-bottom:1px solid #e2e8f0;"><?=money($x['price_per_night'])?></td>
        </tr>
    <?php endforeach; ?>
    </table>
</div>

<script>
const calendarData = <?= json_encode($calendarData) ?>;
const generalAvailability = <?= json_encode($generalAvailability) ?>;

window.onload = function() { calcTotal(); };

function openCalendar() { 
    document.getElementById('calendarModal').style.display = 'flex'; 
    updateModalCalendar();
}
function closeCalendar() { document.getElementById('calendarModal').style.display = 'none'; }

window.onclick = function(event) {
    let modal = document.getElementById('calendarModal');
    if (event.target == modal) { closeCalendar(); }
}

function updateModalCalendar() {
    let selectedRoom = document.getElementById('modalRoomSelect').value;
    let cells = document.querySelectorAll('.cal-cell');
    
    cells.forEach(cell => {
        if (cell.getAttribute('data-past') === 'true') return;
        
        let dateStr = cell.getAttribute('data-date');
        
        if (!selectedRoom) {
            let status = generalAvailability[dateStr] || 'Vacant';
            if (status === 'Vacant') {
                cell.style.background = '#dcfce7'; cell.style.color = '#166534'; cell.style.border = '1px solid #bbf7d0'; cell.title = 'At least 1 room available';
            } else {
                cell.style.background = '#fee2e2'; cell.style.color = '#991b1b'; cell.style.border = '1px solid #fca5a5'; cell.title = 'All rooms fully booked';
            }
        } else {
            let status = calendarData[selectedRoom][dateStr];
            if (status === 'Vacant') {
                cell.style.background = '#dcfce7'; cell.style.color = '#166534'; cell.style.border = '1px solid #bbf7d0'; cell.title = 'Vacant';
            } else if (status === 'Reserved') {
                cell.style.background = '#fef9c3'; cell.style.color = '#854d0e'; cell.style.border = '1px solid #fde047'; cell.title = 'Reserved';
            } else if (status === 'Occupied') {
                cell.style.background = '#fee2e2'; cell.style.color = '#991b1b'; cell.style.border = '1px solid #fca5a5'; cell.title = 'Occupied';
            } else if (status === 'Cleaning') {
                cell.style.background = '#dbeafe'; cell.style.color = '#1e40af'; cell.style.border = '1px solid #93c5fd'; cell.title = 'Cleaning';
            }
        }
    });
}

function selectSlot(roomId, dateStr, price) {
    let select = document.getElementById('roomSelect');
    select.value = roomId;
    document.getElementById('checkin').value = dateStr + 'T14:00';
    document.getElementById('nights').value = 1;
    calcTotal();
    document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
}

function selectCalendarDate(dateStr) {
    document.getElementById('checkin').value = dateStr + 'T14:00';
    document.getElementById('nights').value = 1;
    calcTotal();
    closeCalendar();
    document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
}

function calcTotal() {
    let roomSelect = document.getElementById('roomSelect');
    let checkin = document.getElementById('checkin');
    let nights = document.getElementById('nights');
    let checkout = document.getElementById('checkout');
    let totalDisplay = document.getElementById('totalDisplay');
    
    let roomId = roomSelect.value;
    if(!roomId || !checkin.value || nights.value < 1) { 
        totalDisplay.innerText = '₱0.00'; 
        return; 
    }

    let price = parseFloat(roomSelect.options[roomSelect.selectedIndex].getAttribute('data-price'));
    let days = parseInt(nights.value);
    
    let inDate = new Date(checkin.value);
    inDate.setDate(inDate.getDate() + days);
    
    let y = inDate.getFullYear();
    let m = String(inDate.getMonth()+1).padStart(2,'0');
    let d = String(inDate.getDate()).padStart(2,'0');
    let hh = String(inDate.getHours()).padStart(2,'0');
    let mm = String(inDate.getMinutes()).padStart(2,'0');
    checkout.value = `${y}-${m}-${d}T${hh}:${mm}`;
    
    totalDisplay.innerText = '₱' + (price * days).toFixed(2);
}
</script>

<?php footer_html();