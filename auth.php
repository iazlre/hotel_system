<?php
require_once __DIR__.'/functions.php';
require_once __DIR__.'/db.php';

function need_login(){ if(!isset($_SESSION['user_id'])) redirect('../login.php'); }
function need_role($r){ need_login(); if($_SESSION['role']!==$r) redirect('../login.php'); }
function need_staff($roles){ need_login();
    if($_SESSION['role']!=='Staff' || !in_array($_SESSION['staff_role'],$roles,true))
        redirect('../login.php');
}