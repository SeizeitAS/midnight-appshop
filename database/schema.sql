CREATE DATABASE IF NOT EXISTS sisoft_midnight_appshop;
USE sisoft_midnight_appshop;

-- 1. Tabell for appene i butikken
CREATE TABLE apps (
    app_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    developer_id INT NOT NULL,
    monthly_cost_nok DECIMAL(10,2) DEFAULT 10.00,
    required_ada_stake INT NOT NULL,
    docker_container_id VARCHAR(128) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabell for anonym tilgang (ZK-bevis-hasher)
CREATE TABLE app_access (
    access_id INT AUTO_INCREMENT PRIMARY KEY,
    zk_proof_hash VARCHAR(64) NOT NULL,
    app_id INT NOT NULL,
    active_status BOOLEAN DEFAULT TRUE,
    expiration_date TIMESTAMP NOT NULL,
    last_verified_epoch INT NOT NULL,
    FOREIGN KEY (app_id) REFERENCES apps(app_id),
    UNIQUE KEY unique_user_app (zk_proof_hash, app_id)
);

-- 3. Tabell for regnskap og Business 2.0 API-integrasjon (Modul 5)
CREATE TABLE billing_ledger (
    ledger_id INT AUTO_INCREMENT PRIMARY KEY,
    zk_proof_hash VARCHAR(64) NOT NULL,
    developer_id INT NOT NULL,
    app_id INT NOT NULL,
    amount_developer_nok DECIMAL(10,2) NOT NULL,
    amount_saas_nok DECIMAL(10,2) NOT NULL,
    amount_iaas_dust DECIMAL(16,6) NOT NULL,
    sync_status ENUM('pending', 'synced') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sett inn en virtuell test-app så vi har data å teste med i kveld
INSERT INTO apps (title, developer_id, monthly_cost_nok, required_ada_stake, docker_container_id)
VALUES ('Sisoft Analytix', 99, 10.00, 680, 'docker_container_sisoft_analytix_prod');

