<?php
header('Content-Type: application/json; charset=utf-8');
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$user = $body['username'] ?? '';
if(!$user){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'username missing']); exit; }

// prefer checking users table if available
$found = false;
try {
	require_once __DIR__ . '/../config/database.php';
	$stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
	$stmt->execute([$user]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	$found = (bool)$row;
} catch(Throwable $e){
	// ignore and fallback to config
}

if(!$found){
	$cfgPath = __DIR__ . '/../config/auth.php';
	if(!file_exists($cfgPath)){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'user not found']); exit; }
	$cfg = require $cfgPath;
	if(!isset($cfg['users'][$user])){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'user not found']); exit; }
}

$token = bin2hex(random_bytes(12));
$resetsFile = __DIR__ . '/../../data/resets.json';
$resets = file_exists($resetsFile)? json_decode(file_get_contents($resetsFile), true) : [];
$resets[$token] = ['username'=>$user, 'expires'=>time()+3600];
file_put_contents($resetsFile, json_encode($resets, JSON_PRETTY_PRINT));

// In dev we return the token so user can use it. In prod send by email.
echo json_encode(['success'=>true,'message'=>'Code généré (mode dev) : '.$token,'token'=>$token]);
exit;
