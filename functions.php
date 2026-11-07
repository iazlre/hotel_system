<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function redirect($u){ header("Location: $u"); exit; }
function flash($t,$m){ $_SESSION['flash'][]=['t'=>$t,'m'=>$m]; }
function flashes(){ $f=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $f; }

function csrf(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_field(){ return '<input type="hidden" name="csrf" value="'.csrf().'">'; }
function verify_csrf(){ return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? ''); }

function money($n){ return '₱'.number_format((float)$n,2); }
function d($v){ return $v ? date('M d, Y', strtotime($v)) : '—'; }
function dt($v){ return $v ? date('M d, Y g:i A', strtotime($v)) : '—'; }

function user(){ return $_SESSION['user_id'] ?? null ? [
    'id'=>$_SESSION['user_id'],'role'=>$_SESSION['role'],'name'=>$_SESSION['name'] ?? '',
    'staff_role'=>$_SESSION['staff_role'] ?? null,
    'guest_id'=>$_SESSION['guest_id'] ?? null,'staff_id'=>$_SESSION['staff_id'] ?? null
] : null; }