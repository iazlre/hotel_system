<?php
$conn = new mysqli("localhost","root","","hotel_restaurant_dbs");
if ($conn->connect_error) die("DB error: ".$conn->connect_error);
$conn->set_charset("utf8mb4");