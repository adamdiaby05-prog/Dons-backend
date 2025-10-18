-- =====================================================
-- SCRIPT COMPLET POUR BASE DE DONNÉES DONS
-- PostgreSQL - Création de toutes les tables
-- =====================================================

-- 1. Table users (Utilisateurs)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table groups (Groupes de contribution)
CREATE TABLE IF NOT EXISTS groups (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    target_amount DECIMAL(10,2) NOT NULL,
    current_amount DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Table group_members (Membres des groupes)
CREATE TABLE IF NOT EXISTS group_members (
    id SERIAL PRIMARY KEY,
    group_id INTEGER REFERENCES groups(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50) DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(group_id, user_id)
);

-- 4. Table contributions (Contributions)
CREATE TABLE IF NOT EXISTS contributions (
    id SERIAL PRIMARY KEY,
    group_id INTEGER REFERENCES groups(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_reference VARCHAR(255),
    contributed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Table payments (Paiements)
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    contribution_id INTEGER REFERENCES contributions(id) ON DELETE CASCADE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'XOF',
    payment_method VARCHAR(50) NOT NULL,
    payment_reference VARCHAR(255) UNIQUE,
    status VARCHAR(50) DEFAULT 'pending',
    transaction_id VARCHAR(255),
    payment_url TEXT,
    callback_url TEXT,
    success_url TEXT,
    cancel_url TEXT,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Table notifications (Notifications)
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSONB,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Table cache (Cache)
CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

-- 8. Table jobs (Tâches en arrière-plan)
CREATE TABLE IF NOT EXISTS jobs (
    id SERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts INTEGER DEFAULT 0,
    reserved_at INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

-- 9. Table migrations (Migrations Laravel)
CREATE TABLE IF NOT EXISTS migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- 10. Table personal_access_tokens (Tokens d'API)
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id SERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    abilities TEXT,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- CRÉATION DES INDEX POUR AMÉLIORER LES PERFORMANCES
-- =====================================================

-- Index pour la table users
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_is_admin ON users(is_admin);

-- Index pour la table groups
CREATE INDEX IF NOT EXISTS idx_groups_status ON groups(status);
CREATE INDEX IF NOT EXISTS idx_groups_created_by ON groups(created_by);

-- Index pour la table group_members
CREATE INDEX IF NOT EXISTS idx_group_members_group_id ON group_members(group_id);
CREATE INDEX IF NOT EXISTS idx_group_members_user_id ON group_members(user_id);

-- Index pour la table contributions
CREATE INDEX IF NOT EXISTS idx_contributions_group_id ON contributions(group_id);
CREATE INDEX IF NOT EXISTS idx_contributions_user_id ON contributions(user_id);
CREATE INDEX IF NOT EXISTS idx_contributions_status ON contributions(status);
CREATE INDEX IF NOT EXISTS idx_contributions_contributed_at ON contributions(contributed_at);

-- Index pour la table payments
CREATE INDEX IF NOT EXISTS idx_payments_contribution_id ON payments(contribution_id);
CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);
CREATE INDEX IF NOT EXISTS idx_payments_payment_reference ON payments(payment_reference);
CREATE INDEX IF NOT EXISTS idx_payments_created_at ON payments(created_at);

-- Index pour la table notifications
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_read_at ON notifications(read_at);
CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at);

-- Index pour la table jobs
CREATE INDEX IF NOT EXISTS idx_jobs_queue ON jobs(queue);
CREATE INDEX IF NOT EXISTS idx_jobs_available_at ON jobs(available_at);

-- =====================================================
-- INSERTION DES DONNÉES DE TEST
-- =====================================================

-- Insérer des utilisateurs de test
INSERT INTO users (name, email, password, is_admin, created_at, updated_at) VALUES
('Admin DONS', 'admin@dons.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', true, NOW(), NOW()),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', false, NOW(), NOW()),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', false, NOW(), NOW()),
('Alice Johnson', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', false, NOW(), NOW()),
('Bob Wilson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', false, NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- Insérer des groupes de test
INSERT INTO groups (name, description, target_amount, current_amount, status, created_by, created_at, updated_at) VALUES
('Groupe Test 1', 'Description du groupe test 1 - Projet communautaire', 100000.00, 25000.00, 'active', 1, NOW(), NOW()),
('Groupe Test 2', 'Description du groupe test 2 - Événement spécial', 50000.00, 15000.00, 'active', 1, NOW(), NOW()),
('Groupe Test 3', 'Description du groupe test 3 - Aide d''urgence', 75000.00, 0.00, 'active', 1, NOW(), NOW())
ON CONFLICT DO NOTHING;

-- Insérer des membres de groupe
INSERT INTO group_members (group_id, user_id, role, joined_at) VALUES
(1, 1, 'admin', NOW()),
(1, 2, 'member', NOW()),
(1, 3, 'member', NOW()),
(1, 4, 'member', NOW()),
(2, 1, 'admin', NOW()),
(2, 2, 'member', NOW()),
(2, 5, 'member', NOW()),
(3, 1, 'admin', NOW()),
(3, 3, 'member', NOW()),
(3, 4, 'member', NOW())
ON CONFLICT (group_id, user_id) DO NOTHING;

-- Insérer des contributions de test
INSERT INTO contributions (group_id, user_id, amount, status, payment_method, contributed_at, created_at, updated_at) VALUES
(1, 2, 10000.00, 'completed', 'barapay', NOW(), NOW(), NOW()),
(1, 3, 15000.00, 'completed', 'barapay', NOW(), NOW(), NOW()),
(1, 4, 5000.00, 'completed', 'barapay', NOW(), NOW(), NOW()),
(2, 2, 8000.00, 'completed', 'barapay', NOW(), NOW(), NOW()),
(2, 5, 7000.00, 'completed', 'barapay', NOW(), NOW(), NOW()),
(3, 3, 5000.00, 'pending', 'barapay', NOW(), NOW(), NOW()),
(3, 4, 3000.00, 'pending', 'barapay', NOW(), NOW(), NOW())
ON CONFLICT DO NOTHING;

-- Insérer des paiements de test
INSERT INTO payments (contribution_id, amount, currency, payment_method, payment_reference, status, created_at, updated_at) VALUES
(1, 10000.00, 'XOF', 'barapay', 'PAY_001', 'completed', NOW(), NOW()),
(2, 15000.00, 'XOF', 'barapay', 'PAY_002', 'completed', NOW(), NOW()),
(3, 5000.00, 'XOF', 'barapay', 'PAY_003', 'completed', NOW(), NOW()),
(4, 8000.00, 'XOF', 'barapay', 'PAY_004', 'completed', NOW(), NOW()),
(5, 7000.00, 'XOF', 'barapay', 'PAY_005', 'completed', NOW(), NOW()),
(6, 5000.00, 'XOF', 'barapay', 'PAY_006', 'pending', NOW(), NOW()),
(7, 3000.00, 'XOF', 'barapay', 'PAY_007', 'pending', NOW(), NOW())
ON CONFLICT (payment_reference) DO NOTHING;

-- Insérer des notifications de test
INSERT INTO notifications (user_id, type, title, message, created_at) VALUES
(1, 'info', 'Bienvenue dans DONS', 'Bienvenue dans l''application DONS ! Votre compte administrateur est prêt.', NOW()),
(2, 'success', 'Contribution réussie', 'Votre contribution de 10,000 XOF a été enregistrée avec succès.', NOW()),
(3, 'success', 'Contribution réussie', 'Votre contribution de 15,000 XOF a été enregistrée avec succès.', NOW()),
(4, 'info', 'Nouveau groupe', 'Vous avez été ajouté au groupe "Groupe Test 1".', NOW()),
(5, 'info', 'Nouveau groupe', 'Vous avez été ajouté au groupe "Groupe Test 2".', NOW()),
(1, 'info', 'Nouveau membre', 'Un nouveau membre a rejoint le groupe "Groupe Test 1".', NOW()),
(2, 'warning', 'Paiement en attente', 'Votre paiement de 5,000 XOF est en attente de traitement.', NOW())
ON CONFLICT DO NOTHING;

-- =====================================================
-- VÉRIFICATION DES DONNÉES
-- =====================================================

-- Afficher le nombre de tables créées
SELECT 'Tables créées' as info, COUNT(*) as count 
FROM information_schema.tables 
WHERE table_schema = 'public';

-- Afficher le nombre d'enregistrements par table
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'groups', COUNT(*) FROM groups
UNION ALL
SELECT 'group_members', COUNT(*) FROM group_members
UNION ALL
SELECT 'contributions', COUNT(*) FROM contributions
UNION ALL
SELECT 'payments', COUNT(*) FROM payments
UNION ALL
SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL
SELECT 'cache', COUNT(*) FROM cache
UNION ALL
SELECT 'jobs', COUNT(*) FROM jobs
UNION ALL
SELECT 'migrations', COUNT(*) FROM migrations
UNION ALL
SELECT 'personal_access_tokens', COUNT(*) FROM personal_access_tokens;

-- Afficher les tables créées
SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;

-- =====================================================
-- MESSAGE DE CONFIRMATION
-- =====================================================

SELECT 'Base de données DONS initialisée avec succès !' as message,
       '10 tables créées' as tables,
       '5 utilisateurs' as users,
       '3 groupes' as groups,
       '7 contributions' as contributions,
       '7 paiements' as payments,
       '7 notifications' as notifications;
