/* /* -- =========================================
-- ParkClean Manager - Base complète SQLite
-- =========================================

-- Table clients
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    email TEXT,
    telephone TEXT,
    nbr_vehicules INTEGER DEFAULT 0,
    matricules_historique TEXT, -- liste ou JSON des anciennes immatriculations
    image TEXT -- chemin vers image (facultatif)
);

-- Table types de véhicules
CREATE TABLE IF NOT EXISTS vehicle_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    prix_lavage REAL DEFAULT 0
);

-- Table véhicules (dépend d'un client)
CREATE TABLE IF NOT EXISTS vehicles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    marque TEXT NOT NULL,
    type_id INTEGER NOT NULL,
    immatriculation TEXT NOT NULL UNIQUE,
    image TEXT, -- chemin vers image (facultatif)
    FOREIGN KEY(client_id) REFhttps://dusty-ungalling-globally.ngrok-free.dev/public/index.php?page=clients#modalLabelERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY(type_id) REFERENCES vehicle_types(id)
);

CREATE TABLE IF NOT EXISTS entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    
    -- Date et Heure : modifiable, défaut à l'instant présent
    date_enregistrement DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- On stocke le montant au moment du passage pour l'historique financier
    montant_total REAL NOT NULL, 
    montant_recu REAL DEFAULT 0,
    montant_restant REAL DEFAULT 0, -- Calculé via l'application : montant_total - montant_recu
    
    -- Statuts de présence
    est_entree BOOLEAN DEFAULT 1, -- true (1) si c'est une nouvelle entrée
    est_sorti BOOLEAN DEFAULT 0,  -- true (1) si le véhicule a quitté le parking
    
    obs TEXT,
    
    FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS abonnements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    
    -- Dates de l'abonnement
    date_enregistrement DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    
    -- Volet Financier
    montant_total REAL NOT NULL,   -- Le prix du forfait choisi
    montant_recu REAL DEFAULT 0,    -- Ce que le client a payé
    montant_restant REAL DEFAULT 0, -- Calculé : montant_total - montant_recu
    
    -- Statut et Observation
    est_actif BOOLEAN DEFAULT 1,    -- Pour désactiver manuellement si besoin
    obs TEXT,
    
    FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Table paiements (dépend d'une entrée)
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id INTEGER NOT NULL,
    montant REAL NOT NULL,
    mode_paiement TEXT DEFAULT 'cash', -- cash / carte / autre
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(entry_id) REFERENCES entries(id) ON DELETE CASCADE
);

-- Table rapports journaliers (optionnel)
CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date_report DATE NOT NULL,
    total_vehicules INTEGER DEFAULT 0,
    total_payments REAL DEFAULT 0
);

-- Données initiales pour types de véhicules
INSERT  INTO vehicle_types (id,type,prix_lavage) VALUES
(null,'Voiture',2000),
(null,'Moto',1000),
(null,'Camion',5000);
 */

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- 1) ajouter la colonne email (nullable pour commencer)
ALTER TABLE users ADD COLUMN email TEXT;

-- 2) (optionnel) si tu veux forcer unicité et que la DB le supporte,
-- créer un index unique sur email (après avoir nettoyé les doublons)
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email);
 */

INSERT INTO vehicle_types (type, prix_lavage) VALUES
('Moto/Kavaki', 500),
('Kavaki pleine', 1000),
('Taxi', 700),
('V.Personnelle', 1000),
('4x4', 1500),
('Bus', 1000),
('Coaster', 2000),
('Camion Léger', 2000),
('Camion Lourd', 5000);

drop TABLE vehicle_types;