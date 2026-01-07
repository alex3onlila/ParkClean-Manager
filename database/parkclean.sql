-- =========================================
-- ParkClean Manager - Base complète SQLite
-- =========================================

-- Table utilisateurs pour l'authentification
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT UNIQUE,
    role TEXT DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table clients
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    email TEXT,
    telephone TEXT,
    nbr_vehicules INTEGER DEFAULT 0,
    matricules_historique TEXT, -- JSON des anciennes immatriculations
    image TEXT, -- chemin vers image (facultatif)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table types de véhicules
CREATE TABLE IF NOT EXISTS vehicle_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL UNIQUE,
    prix_lavage REAL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table véhicules (dépend d'un client)
CREATE TABLE IF NOT EXISTS vehicles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    marque TEXT NOT NULL,
    type_id INTEGER NOT NULL,
    immatriculation TEXT NOT NULL UNIQUE,
    image TEXT, -- chemin vers image (facultatif)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY(type_id) REFERENCES vehicle_types(id) ON DELETE RESTRICT
);

-- Table entrées journalières
CREATE TABLE IF NOT EXISTS entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    date_enregistrement DATETIME DEFAULT CURRENT_TIMESTAMP,
    montant_total REAL NOT NULL,
    montant_recu REAL DEFAULT 0,
    montant_restant REAL DEFAULT 0, -- Calculé : montant_total - montant_recu
    est_entree BOOLEAN DEFAULT 1, -- true si nouvelle entrée
    est_sorti BOOLEAN DEFAULT 0,  -- true si véhicule sorti
    obs TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Table abonnements
CREATE TABLE IF NOT EXISTS abonnements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vehicle_id INTEGER NOT NULL,
    date_enregistrement DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    montant_total REAL NOT NULL,
    montant_recu REAL DEFAULT 0,
    montant_restant REAL DEFAULT 0, -- Calculé : montant_total - montant_recu
    est_actif BOOLEAN DEFAULT 1,
    obs TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Table paiements (dépend d'une entrée)
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id INTEGER NOT NULL,
    montant REAL NOT NULL,
    mode_paiement TEXT DEFAULT 'cash', -- cash, carte, autre
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(entry_id) REFERENCES entries(id) ON DELETE CASCADE
);

-- Table rapports journaliers (optionnel)
CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date_report DATE NOT NULL UNIQUE,
    total_vehicules INTEGER DEFAULT 0,
    total_payments REAL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Indexes pour optimiser les performances
CREATE INDEX IF NOT EXISTS idx_clients_email ON clients(email);
CREATE INDEX IF NOT EXISTS idx_clients_nom_prenom ON clients(nom, prenom);
CREATE INDEX IF NOT EXISTS idx_vehicles_client_id ON vehicles(client_id);
CREATE INDEX IF NOT EXISTS idx_vehicles_immatriculation ON vehicles(immatriculation);
CREATE INDEX IF NOT EXISTS idx_entries_vehicle_id ON entries(vehicle_id);
CREATE INDEX IF NOT EXISTS idx_entries_date ON entries(date_enregistrement);
CREATE INDEX IF NOT EXISTS idx_abonnements_vehicle_id ON abonnements(vehicle_id);
CREATE INDEX IF NOT EXISTS idx_abonnements_dates ON abonnements(date_debut, date_fin);
CREATE INDEX IF NOT EXISTS idx_payments_entry_id ON payments(entry_id);
CREATE INDEX IF NOT EXISTS idx_reports_date ON reports(date_report);

-- Données initiales pour types de véhicules
INSERT OR IGNORE INTO vehicle_types (type, prix_lavage) VALUES
('Moto/Kavaki', 500),
('Kavaki pleine', 1000),
('Taxi', 700),
('V.Personnelle', 1000),
('4x4', 1500),
('Bus', 1000),
('Coaster', 2000),
('Camion Léger', 2000),
('Camion Lourd', 5000);

-- Utilisateur admin par défaut (mot de passe: admin123)
INSERT OR IGNORE INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@parkclean.local', 'admin');
