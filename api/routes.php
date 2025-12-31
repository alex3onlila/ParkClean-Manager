<?php
header('Content-Type: application/json; charset=utf-8');

// Simple machine-readable list of CRUD endpoints and example payloads
$routes = [
    'clients' => [
        'list' => ['method'=>'GET','path'=>'/api/clients/list.php','description'=>'Retourne la liste des clients'],
        'get'  => ['method'=>'GET','path'=>'/api/clients/get.php?id={id}','description'=>'Retourne un client par id'],
        'create'=>['method'=>'POST','path'=>'/api/clients/create.php','body_example'=>["nom"=>"Dupont","prenom"=>"Jean","email"=>"j@example.com","telephone"=>"0123456789","nbr_vehicules"=>0,"matricules_historique"=>"","image"=>""]],
        'update'=>['method'=>'POST','path'=>'/api/clients/update.php','body_example'=>["id"=>1,"nom"=>"Dupont","prenom"=>"Jean"]],
        'delete'=>['method'=>'POST','path'=>'/api/clients/delete.php','body_example'=>["id"=>1]]
    ],
    'vehicle_types' => [
        'list'=>['method'=>'GET','path'=>'/api/vehicle_types/list.php'],
        'get'=>['method'=>'GET','path'=>'/api/vehicle_types/get.php?id={id}'],
        'create'=>['method'=>'POST','path'=>'/api/vehicle_types/create.php','body_example'=>["type"=>"Voiture","prix_lavage"=>2000]],
        'update'=>['method'=>'POST','path'=>'/api/vehicle_types/update.php','body_example'=>["id"=>1,"type"=>"Voiture","prix_lavage"=>2500]],
        'delete'=>['method'=>'POST','path'=>'/api/vehicle_types/delete.php','body_example'=>["id"=>1]]
    ],
    'vehicles' => [
        'list'=>['method'=>'GET','path'=>'/api/vehicles/list.php','description'=>'Optionnel: ?client_id='],
        'get'=>['method'=>'GET','path'=>'/api/vehicles/get.php?id={id}'],
        'create'=>['method'=>'POST','path'=>'/api/vehicles/create.php','body_example'=>["client_id"=>1,"marque"=>"Toyota","type_id"=>1,"immatriculation"=>"AB-123-CD","image"=>""]],
        'update'=>['method'=>'POST','path'=>'/api/vehicles/update.php','body_example'=>["id"=>1,"marque"=>"Toyota"]],
        'delete'=>['method'=>'POST','path'=>'/api/vehicles/delete.php','body_example'=>["id"=>1]]
    ],
    'entries' => [
        'list'=>['method'=>'GET','path'=>'/api/entries/list.php','description'=>'Optionnel: filtres disponibles selon implémentation'],
        'get'=>['method'=>'GET','path'=>'/api/entries/get.php?id={id}'],
        'create'=>['method'=>'POST','path'=>'/api/entries/create.php','body_example'=>["vehicle_id"=>1,"marque"=>"Toyota","type"=>"Voiture","immatriculation"=>"AB-123-CD","categorie"=>"personnelle","montant_init"=>2000,"montant_recu"=>2000,"montant_restant"=>0,"obs"=>""]],
        'update'=>['method'=>'POST','path'=>'/api/entries/update.php','body_example'=>["id"=>1,"montant_recu"=>1500]],
        'delete'=>['method'=>'POST','path'=>'/api/entries/delete.php','body_example'=>["id"=>1]]
    ],
    'abonnements' => [
        'list'=>['method'=>'GET','path'=>'/api/abonnements/list.php','description'=>'Options: ?client_id=&vehicle_id='],
        'get'=>['method'=>'GET','path'=>'/api/abonnements/get.php?id={id}'],
        'create'=>['method'=>'POST','path'=>'/api/abonnements/create.php','body_example'=>["vehicle_id"=>1,"client_id"=>1,"marque"=>"Toyota","type"=>"Voiture","immatriculation"=>"AB-123-CD","categorie"=>"personnelle","date_debut"=>"2025-12-15","date_fin"=>"2026-12-15","montant_init"=>24000,"montant_recu"=>24000,"montant_restant"=>0,"obs"=>""]],
        'update'=>['method'=>'POST','path'=>'/api/abonnements/update.php','body_example'=>["id"=>1,"date_fin"=>"2027-01-01"]],
        'delete'=>['method'=>'POST','path'=>'/api/abonnements/delete.php','body_example'=>["id"=>1]]
    ],
    'payments' => [
        'list'=>['method'=>'GET','path'=>'/api/payments/list.php','description'=>'Optionnel: ?entry_id='],
        'get'=>['method'=>'GET','path'=>'/api/payments/get.php?id={id}'],
        'create'=>['method'=>'POST','path'=>'/api/payments/create.php','body_example'=>["entry_id"=>1,"montant"=>2000,"mode_paiement"=>"cash"]],
        'delete'=>['method'=>'POST','path'=>'/api/payments/delete.php','body_example'=>["id"=>1]]
    ],
    'reports' => [
        'daily'=>['method'=>'GET','path'=>'/api/reports/daily.php','description'=>'Rapport journalier résumé ou détaillé selon implémentation']
    ],
    'auth' => [
    'login' => [
        'method'=>'POST',
        'path'=>'/api/auth/login.php',
        'body_example'=>["username"=>"admin","password"=>"secret"],
        'response_example'=>["success"=>true,"message"=>"Connexion réussie","user"=>["id"=>1,"role"=>"admin"]]
    ],
    'logout' => [
        'method'=>'POST',
        'path'=>'/api/auth/logout.php',
        'description'=>'Déconnecte l’utilisateur et détruit la session',
        'response_example'=>["success"=>true,"message"=>"Déconnexion réussie"]
    ],
    'reset' => [
        'method'=>'POST',
        'path'=>'/api/auth/reset.php',
        'body_example'=>["username"=>"admin","token"=>"abc123","new_password"=>"nouveauSecret"],
        'response_example'=>["success"=>true,"message"=>"Mot de passe mis à jour avec succès"]
    ]
]

];

echo json_encode(['success'=>true,'routes'=>$routes], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
