-- Database: app_kost
-- Created for UAS Pemrograman Internet

CREATE DATABASE IF NOT EXISTS app_kost;
USE app_kost;

-- Tabel penghuni kost
CREATE TABLE tb_penghuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_ktp VARCHAR(20) UNIQUE NOT NULL,
    no_hp VARCHAR(15) NOT NULL,
    tgl_masuk DATE NOT NULL,
    tgl_keluar DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel kamar
CREATE TABLE tb_kamar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor VARCHAR(10) UNIQUE NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    status ENUM('kosong', 'terisi') DEFAULT 'kosong',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel barang
CREATE TABLE tb_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel kamar penghuni (relasi many-to-many)
CREATE TABLE tb_kmr_penghuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kamar INT NOT NULL,
    id_penghuni INT NOT NULL,
    tgl_masuk DATE NOT NULL,
    tgl_keluar DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kamar) REFERENCES tb_kamar(id) ON DELETE CASCADE,
    FOREIGN KEY (id_penghuni) REFERENCES tb_penghuni(id) ON DELETE CASCADE
);

-- Tabel barang bawaan
CREATE TABLE tb_brng_bawaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penghuni INT NOT NULL,
    id_barang INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penghuni) REFERENCES tb_penghuni(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES tb_barang(id) ON DELETE CASCADE
);

-- Tabel tagihan
CREATE TABLE tb_tagihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bulan VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    id_kmr_penghuni INT NOT NULL,
    jml_tagihan DECIMAL(10,2) NOT NULL,
    status ENUM('belum_bayar', 'cicil', 'lunas') DEFAULT 'belum_bayar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kmr_penghuni) REFERENCES tb_kmr_penghuni(id) ON DELETE CASCADE
);

-- Tabel pembayaran
CREATE TABLE tb_bayar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_tagihan INT NOT NULL,
    jml_bayar DECIMAL(10,2) NOT NULL,
    status ENUM('cicil', 'lunas') NOT NULL,
    tgl_bayar DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tagihan) REFERENCES tb_tagihan(id) ON DELETE CASCADE
);

-- Tabel user admin untuk login
CREATE TABLE IF NOT EXISTS tb_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL -- simpan hash password
);

-- Contoh user admin default
-- password: admin123
INSERT INTO tb_user (username, password) VALUES ('admin', '$2y$10$/SUQt2JJJpYxCZJPQGSF5el1BOJh162YnM/fLmuQy7oJ2ztCj5RpK');

-- Insert sample data
INSERT INTO tb_kamar (nomor, harga) VALUES
('101', 500000.00),
('102', 550000.00),
('103', 500000.00),
('201', 600000.00),
('202', 650000.00),
('203', 600000.00);

INSERT INTO tb_barang (nama, harga) VALUES
('AC', 100000.00),
('Kulkas', 50000.00),
('TV', 75000.00),
('Mesin Cuci', 80000.00),
('Komputer', 60000.00);

INSERT INTO tb_penghuni (nama, no_ktp, no_hp, tgl_masuk) VALUES
('Ahmad Fauzi', '3201234567890001', '081234567890', '2025-01-15'),
('Siti Rahayu', '3201234567890002', '081234567891', '2025-02-01'),
('Budi Santoso', '3201234567890003', '081234567892', '2025-03-10');

INSERT INTO tb_kmr_penghuni (id_kamar, id_penghuni, tgl_masuk) VALUES
(1, 1, '2025-01-15'),
(2, 2, '2025-02-01'),
(3, 3, '2025-03-10');

INSERT INTO tb_brng_bawaan (id_penghuni, id_barang) VALUES
(1, 1), -- Ahmad bawa AC
(1, 2), -- Ahmad bawa Kulkas
(2, 3), -- Siti bawa TV
(3, 1), -- Budi bawa AC
(3, 4); -- Budi bawa Mesin Cuci