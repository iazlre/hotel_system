<?php require __DIR__.'/_layout.php'; $u=user();
$gid=(int)$u['guest_id'];
$res=$conn->query("SELECT COUNT(*) c FROM room_reservations WHERE guest_id=$gid AND reservation_status='Confirmed'")->fetch_assoc()['c'];
$book=$conn->query("SELECT COUNT(*) c FROM booking_list WHERE guest_id=$gid AND booking_status='Checked-In'")->fetch_assoc()['c'];
$cafe=$conn->query("SELECT COUNT(*) c FROM cafe_reservations WHERE guest_id=$gid AND status IN ('Confirmed','Seated')")->fetch_assoc()['c'];

header_html('dash','Welcome, '.$u['name']); ?>
<p style="color:#64748b;margin-bottom:25px;">Here's a quick view of your account.</p>
<div class="card-grid">
 <div class="stat-card"><h3>Confirmed Reservations</h3><div class="value" style="color:#38bdf8"><?=$res?></div></div>
 <div class="stat-card"><h3>Active Stays</h3><div class="value" style="color:#22c55e"><?=$book?></div></div>
 <div class="stat-card"><h3>Café Reservations</h3><div class="value" style="color:#a855f7"><?=$cafe?></div></div>
</div>
<h2 style="font-size:18px;color:#1e293b;margin-bottom:15px;">Quick Actions</h2>
<div style="display:flex;gap:15px;flex-wrap:wrap;">
 <a href="book_room.php" class="btn btn-primary">+ Book a Room</a>
 <a href="book_cafe.php" class="btn btn-primary">+ Reserve a Café Table</a>
 <a href="my_bookings.php" class="btn btn-secondary">View My Bookings</a>
</div>
<?php footer_html();