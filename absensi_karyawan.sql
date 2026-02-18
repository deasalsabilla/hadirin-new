-- =========================
-- TABEL USERS
-- =========================
CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100),
    role ENUM('admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin default (aman)
INSERT IGNORE INTO users (username, password, nama) VALUES (
    'admin',
    '$2b$12$KHw0d/fPeMcCWaieXlesG.m7UCllm37Uv4UdZYjBKYzzaNhv6EiZm',
    'Administrator'
);

-- =========================
-- TABEL KARYAWAN
-- =========================
CREATE TABLE IF NOT EXISTS karyawan (
    id_karyawan INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(8) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =========================
-- TABEL ABSENSI
-- =========================
CREATE TABLE IF NOT EXISTS absensi (
    id_absensi INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('Hadir', 'Izin', 'Sakit', 'Alpha') NOT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_absensi_karyawan
        FOREIGN KEY (id_karyawan)
        REFERENCES karyawan(id_karyawan)
        ON DELETE CASCADE,

    CONSTRAINT unique_absensi_harian
        UNIQUE (id_karyawan, tanggal)
);

CREATE INDEX IF NOT EXISTS idx_absensi_tanggal ON absensi(tanggal);
CREATE INDEX IF NOT EXISTS idx_absensi_status ON absensi(status);
