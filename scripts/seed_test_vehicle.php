<?php
// Script d'aide pour insérer une voiture de test dans database/parkclean.db
try {
    $db_file = __DIR__ . '/../database/parkclean.db';
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // Assurer qu'il y a au moins un client
    $clientId = null;
    $stmt = $pdo->query('SELECT id FROM clients LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $clientId = (int)$row['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO clients (nom, prenom, email, telephone, nbr_vehicules, matricules_historique, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(['Test', 'Client', 'test@example.local', '0000000000', 1, '', '']);
        $clientId = (int)$pdo->lastInsertId();
    }

    // Assurer qu'il y a au moins un vehicle_type
    $typeId = null;
    $stmt = $pdo->query('SELECT id FROM vehicle_types LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $typeId = (int)$row['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO vehicle_types (type, prix_lavage) VALUES (?, ?)');
        $stmt->execute(['TestType', 5]);
        $typeId = (int)$pdo->lastInsertId();
    }

    // Insert test vehicle
    $imm = 'TEST-' . substr(sha1((string)microtime(true)), 0, 6);
    $stmt = $pdo->prepare('INSERT INTO vehicles (client_id, marque, type_id, immatriculation, image) VALUES (:client_id, :marque, :type_id, :immatriculation, :image)');
    $stmt->execute([
        ':client_id' => $clientId,
        ':marque' => 'TestAuto',
        ':type_id' => $typeId,
        ':immatriculation' => $imm,
        ':image' => ''
    ]);
    $vehicleId = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode(['success' => true, 'vehicle_id' => $vehicleId, 'immatriculation' => $imm, 'client_id' => $clientId, 'type_id' => $typeId]) . PHP_EOL;
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]) . PHP_EOL;
    exit(1);
}
