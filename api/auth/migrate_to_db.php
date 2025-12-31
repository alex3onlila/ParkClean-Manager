<?php
// Run this script once from CLI to migrate users from api/config/auth.php
// php api/auth/migrate_to_db.php

require_once __DIR__ . '/../config/database.php';
$cfgPath = __DIR__ . '/../config/auth.php';
if(!file_exists($cfgPath)){
    echo "auth config not found\n"; exit(1);
}
$cfg = require $cfgPath;
if(!isset($cfg['users']) || !is_array($cfg['users'])){
    echo "no users in config\n"; exit(1);
}

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE,
      password TEXT NOT NULL,
      role TEXT DEFAULT 'user',
      created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $insert = $conn->prepare('INSERT OR REPLACE INTO users (username, password) VALUES (:u,:p)');
    foreach($cfg['users'] as $username => $password){
        // if password looks like bcrypt keep it, otherwise hash it
        if(is_string($password) && strpos($password, '$2y$') === 0){
            $hash = $password;
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
        }
        $insert->execute([':u'=>$username, ':p'=>$hash]);
        echo "migrated: $username\n";
    }
    echo "migration complete\n";
} catch(PDOException $e){
    echo "DB error: " . $e->getMessage() . "\n";
    exit(1);
}
