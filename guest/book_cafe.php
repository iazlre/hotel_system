<?php require __DIR__.'/_layout.php'; $u=user(); $gid=(int)$u['guest_id'];

$view_date = isset($_GET['view_date']) ? $_GET['view_date'] : date('Y-m-d');
$view_time = isset($_GET['view_time']) ? $_GET['view_time'] : '12:00';

// Kunin ang napiling table galing sa URL (kung meron)
$selected_table_id = isset($_GET['selected_table']) ? (int)$_GET['selected_table'] : 0;

$day_of_week = date('D', strtotime($view_date));
$is_closed = ($day_of_week === 'Tue');

// Operating Hours for Cafe 1947
if (in_array($day_of_week, ['Fri', 'Sat', 'Sun'])) {
    $time_slots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
} else {
    $time_slots = ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];
}

if ($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf()){ flash('error','CSRF failed'); redirect('book_cafe.php'); }
    $tid=(int)$_POST['table_id']; $date=$_POST['date']; $time=$_POST['time']; $pax=(int)$_POST['pax'];

    if (date('D', strtotime($date)) === 'Tue') {
        flash('error',"Cafe 1947 is closed on Tuesdays.");
    } else {
        $cap = $conn->query("SELECT capacity FROM cafe_tables WHERE table_id=$tid")->fetch_assoc()['capacity'];
        if ($pax > $cap) {
            flash('error',"Party of $pax exceeds the maximum capacity ($cap).");
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) c FROM cafe_reservations WHERE table_id=? AND reservation_date=? AND reservation_time=? AND status IN ('Confirmed', 'Seated')");
            $stmt->bind_param("iss", $tid, $date, $time);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()['c'] > 0) {
                flash('error','Sorry, this table was just booked by someone else.');
            } else {
                $stmt2 = $conn->prepare("INSERT INTO cafe_reservations (guest_id,table_id,reservation_date,reservation_time,number_of_guests,status) VALUES (?,?,?,?,?,'Confirmed')");
                $stmt2->bind_param("iissi",$gid,$tid,$date,$time,$pax);
                if ($stmt2->execute()){
                    $conn->query("UPDATE cafe_tables SET status='Reserved' WHERE table_id=$tid");
                    flash('success','Café table reserved successfully!');
                } else flash('error','Failed: '.$conn->error);
            }
        }
    }
    redirect('book_cafe.php');
}

header_html('cafe','Reserve a Café Table'); ?>

<style>
    .cafe-wrapper { max-width: 850px; margin: 0 auto; }
    .table-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 30px; }
    
    .table-card { 
        padding: 12px 5px; border-radius: 8px; text-align: center; font-size: 13px; font-weight: bold; 
        cursor: pointer; border: 2px solid transparent; transition: 0.2s;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    
    .table-card:hover { opacity: 0.8; transform: translateY(-2px); }
    .tc-green { background: #dcfce7; color: #166534; }
    .tc-yellow { background: #fef9c3; color: #854d0e; }
    .tc-red { background: #fee2e2; color: #991b1b; }
    .tc-selected { border: 2px solid #3b82f6; background: #dbeafe; color: #1e40af; transform: scale(1.05); }
    
    .center-row { display: flex; justify-content: center; align-items: center; width: 100%; gap: 15px; flex-wrap: wrap; }
    
    /* Design para sa Operating Hours Banner */
    .hours-banner {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        font-size: 12px;
        color: #0369a1;
        margin-bottom: 25px;
        line-height: 1.6;
    }

    /* Design para sa Capacity Cards */
    .capacity-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.04);
    }
    .capacity-card .t-num { font-weight: bold; color: #1e293b; font-size: 14px; }
    .capacity-card .t-divider { width: 1px; height: 16px; background: #cbd5e1; }
    .capacity-card .t-pax { color: #64748b; font-size: 12px; font-weight: 600; }

    @media (max-width: 768px) { .table-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 480px) { .table-grid { grid-template-columns: repeat(2, 1fr); } }
</style>

<div class="cafe-wrapper">
    <div class="form-container">
        <h3 style="text-align: center; margin-bottom: 15px; color: #1e293b;">New Café Reservation</h3>

        <!-- BAGONG OPERATING HOURS BANNER -->
        <div class="hours-banner">
            <strong>Café 1947 Operating Hours:</strong><br>
            <b>Mon, Wed, Thu:</b> 9:00 AM – 8:00 PM &nbsp;|&nbsp; 
            <b>Fri – Sun:</b> 9:00 AM – 9:00 PM &nbsp;|&nbsp; 
            <span style="color: #dc2626; font-weight: bold;">Tuesday: CLOSED</span>
        </div>

        <div class="center-row" style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px dashed #cbd5e1;">
            <span style="font-weight: bold; font-size: 13px; color: #1e293b;">Choose Date & Time:</span>
            
            <form method="GET" action="book_cafe.php" id="filterForm" style="display: flex; gap: 10px;">
                <input type="date" name="view_date" required value="<?= $view_date ?>" style="width: 160px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;" onchange="this.form.submit()">
                
                <select name="view_time" required style="width: 140px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;" onchange="this.form.submit()" <?= $is_closed ? 'disabled' : '' ?>>
                    <?php foreach($time_slots as $t): ?>
                        <option value="<?= $t ?>" <?= $view_time == $t ? 'selected' : '' ?>><?= date('g:i A', strtotime($t)) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input type="hidden" name="selected_table" id="selectedTableHidden" value="<?= $selected_table_id ?>">
            </form>
        </div>

        <?php if($is_closed): ?>
            <p style="color:#ef4444; font-size:14px; font-weight:bold; text-align:center; padding: 20px;">⚠️ Cafe 1947 is CLOSED every Tuesday. Please select another date.</p>
        <?php else: ?>
        
        <div style="text-align: center; margin-bottom: 15px;">
            <span style="font-weight: bold; font-size: 13px; color: #1e293b;">Pick a Table (Click a Box)</span>
        </div>

        <div class="table-grid">
            <?php 
            $tables_res = $conn->query("SELECT * FROM cafe_tables ORDER BY table_number");
            while($t = $tables_res->fetch_assoc()):
                $tid = $t['table_id'];
                
                $chk = $conn->query("SELECT status FROM cafe_reservations WHERE table_id=$tid AND reservation_date='$view_date' AND reservation_time='$view_time' AND status IN ('Confirmed', 'Seated')");
                
                $status_text = "Available";
                $color_class = "tc-green";
                
                if ($chk->num_rows > 0) {
                    $res = $chk->fetch_assoc();
                    if ($res['status'] == 'Seated') {
                        $status_text = "Occupied";
                        $color_class = "tc-red";
                    } else {
                        $status_text = "Reserved";
                        $color_class = "tc-yellow";
                    }
                }

                $is_currently_selected = ($tid == $selected_table_id && $status_text == 'Available') ? 'tc-selected' : '';
            ?>
                <div class="table-card <?= $color_class ?> <?= $is_currently_selected ?>" id="card-<?= $tid ?>" onclick="selectTable(<?=$tid?>, '<?=$status_text?>', this)">
                    <?=e($t['table_number'])?>
                    <span style="font-size:10px; font-weight:normal; margin-top:2px;"><?=$status_text?></span>
                    <span style="font-size:9px; font-weight:normal; color:#64748b;">(<?=$t['capacity']?>pax)</span>
                </div>
            <?php endwhile; ?>
        </div>

        <form method="POST" id="bookingForm" class="center-row" style="align-items: flex-end;">
            <?=csrf_field()?>
            <input type="hidden" name="date" value="<?= $view_date ?>">
            <input type="hidden" name="time" value="<?= $view_time ?>">
            
            <div style="text-align: left;">
                <label style="font-size:11px; color:#64748b; margin-bottom:5px; display: block;">Selected Table</label>
                <select name="table_id" id="tableDropdown" required style="width: 250px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="">— Click a box above —</option>
                    <?php 
                    $dd = $conn->query("SELECT * FROM cafe_tables ORDER BY table_number");
                    while($d = $dd->fetch_assoc()) {
                        $sel = ($d['table_id'] == $selected_table_id) ? 'selected' : '';
                        echo "<option value='{$d['table_id']}' $sel>{$d['table_number']} ({$d['capacity']} Seater)</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div style="text-align: left;">
                <label style="font-size:11px; color:#64748b; margin-bottom:5px; display: block;">Number of Guests</label>
                <input type="number" name="pax" id="paxInput" min="1" required placeholder="e.g. 4" style="width: 120px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary" style="padding: 11px 20px;">Reserve Table</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <!-- MINIMIZED TABLE DETAILS -->
    <div style="margin-top: 30px; text-align: center; padding: 25px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
        <h4 style="color:#64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;">
            Café 1947 — Table Capacities
        </h4>
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px;">
            <?php 
            $r = $conn->query("SELECT * FROM cafe_tables ORDER BY table_number");
            while($x = $r->fetch_assoc()): 
            ?>
                <div class="capacity-card">
                    <span class="t-num"><?=e($x['table_number'])?></span>
                    <span class="t-divider"></span>
                    <span class="t-pax"><?=$x['capacity']?> Pax</span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function selectTable(tableId, status, element) {
    if (status === 'Reserved' || status === 'Occupied') {
        alert("This table is currently " + status + ". Please pick a green (Available) table.");
        return;
    }

    let allCards = document.querySelectorAll('.table-card');
    allCards.forEach(c => c.classList.remove('tc-selected'));

    element.classList.add('tc-selected');

    let dropdown = document.getElementById('tableDropdown');
    dropdown.value = tableId;

    document.getElementById('selectedTableHidden').value = tableId;

    document.getElementById('paxInput').focus();
}
</script>

<?php footer_html();