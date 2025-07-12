<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config/database.php';
$conn = getConnection();
// Proses tambah penghuni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_mode'])) {
    if ($_POST['form_mode'] === 'tambah' && isset($_POST['nama'], $_POST['no_ktp'], $_POST['no_hp'], $_POST['tgl_masuk'])) {
        $nama = $_POST['nama'];
        $no_ktp = $_POST['no_ktp'];
        $no_hp = $_POST['no_hp'];
        $tgl_masuk = $_POST['tgl_masuk'];
        $kamar_id = isset($_POST['kamar']) ? $_POST['kamar'] : null;
        $stmt = $conn->prepare('INSERT INTO tb_penghuni (nama, no_ktp, no_hp, tgl_masuk, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$nama, $no_ktp, $no_hp, $tgl_masuk]);
        $penghuni_id = $conn->lastInsertId();
        if ($kamar_id) {
            $stmt = $conn->prepare('INSERT INTO tb_kmr_penghuni (id_penghuni, id_kamar, tgl_masuk) VALUES (?, ?, ?)');
            $stmt->execute([$penghuni_id, $kamar_id, $tgl_masuk]);
            // Ambil harga kamar
            $stmtHarga = $conn->prepare('SELECT harga FROM tb_kamar WHERE id=?');
            $stmtHarga->execute([$kamar_id]);
            $hargaKamar = $stmtHarga->fetchColumn();
            // Insert tagihan bulan ini
            $bulan = date('Y-m');
            $stmtKmrPenghuni = $conn->prepare('SELECT id FROM tb_kmr_penghuni WHERE id_penghuni=? AND id_kamar=? AND (tgl_keluar IS NULL OR tgl_keluar="") ORDER BY id DESC LIMIT 1');
            $stmtKmrPenghuni->execute([$penghuni_id, $kamar_id]);
            $idKmrPenghuni = $stmtKmrPenghuni->fetchColumn();
            if ($hargaKamar && $idKmrPenghuni) {
                $stmt = $conn->prepare('INSERT INTO tb_tagihan (id_kmr_penghuni, bulan, jml_tagihan, status) VALUES (?, ?, ?, "pending")');
                $stmt->execute([$idKmrPenghuni, $bulan, $hargaKamar]);
            }
        }
        header('Location: manajemen_penghuni.php');
        exit();
    } elseif ($_POST['form_mode'] === 'edit' && isset($_POST['id'], $_POST['nama'], $_POST['no_ktp'], $_POST['no_hp'], $_POST['tgl_masuk'])) {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $no_ktp = $_POST['no_ktp'];
        $no_hp = $_POST['no_hp'];
        $tgl_masuk = $_POST['tgl_masuk'];
        $kamar_id = isset($_POST['kamar']) ? $_POST['kamar'] : null;
        $stmt = $conn->prepare('UPDATE tb_penghuni SET nama=?, no_ktp=?, no_hp=?, tgl_masuk=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$nama, $no_ktp, $no_hp, $tgl_masuk, $id]);
        // Update kamar jika diganti
        if ($kamar_id) {
            // Tutup relasi kamar lama
            $stmt = $conn->prepare('UPDATE tb_kmr_penghuni SET tgl_keluar=NOW() WHERE id_penghuni=? AND (tgl_keluar IS NULL OR tgl_keluar="")');
            $stmt->execute([$id]);
            // Insert relasi kamar baru
            $stmt = $conn->prepare('INSERT INTO tb_kmr_penghuni (id_penghuni, id_kamar, tgl_masuk) VALUES (?, ?, ?)');
            $stmt->execute([$id, $kamar_id, $tgl_masuk]);
        }
        header('Location: manajemen_penghuni.php');
        exit();
    }
}
// Proses hapus penghuni
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare('DELETE FROM tb_penghuni WHERE id=?');
    $stmt->execute([$id]);
    header('Location: manajemen_penghuni.php');
    exit();
}
// Ambil data penghuni beserta nomor kamar dan tagihan terakhir
$sql = 'SELECT p.*, k.id AS id_kamar, k.nomor AS nomor_kamar,
  (
    SELECT t2.jml_tagihan FROM tb_tagihan t2
    WHERE t2.id_kmr_penghuni = (
      SELECT kp2.id FROM tb_kmr_penghuni kp2 WHERE kp2.id_penghuni = p.id AND (kp2.tgl_keluar IS NULL OR kp2.tgl_keluar = "") ORDER BY kp2.id DESC LIMIT 1
    )
    ORDER BY t2.id DESC LIMIT 1
  ) AS tagihan_terakhir
FROM tb_penghuni p
LEFT JOIN tb_kmr_penghuni kp ON kp.id_penghuni = p.id AND (kp.tgl_keluar IS NULL OR kp.tgl_keluar = "")
LEFT JOIN tb_kamar k ON kp.id_kamar = k.id
ORDER BY p.id DESC';
$penghuni = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
// Query kamar tersedia (kosong atau kamar yang sedang ditempati penghuni yang diedit)
$kamar_tersedia = $conn->query('SELECT id, nomor, harga FROM tb_kamar WHERE status = "kosong" OR id IN (SELECT id_kamar FROM tb_kmr_penghuni WHERE tgl_keluar IS NULL OR tgl_keluar = "") ORDER BY nomor')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Penghuni</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #f8fafc;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            width: 100%;
            height: 70px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            position: fixed;
            top: 0;
            left: 0;
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #8b5cf6;
        }
        .navbar-brand i { font-size: 1.8rem; }
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: rgba(139, 92, 246, 0.1);
            border-radius: 25px;
            font-weight: 500;
            color: #8b5cf6;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(90deg, #ff6a5b, #ff9472);
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 14px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
        }
        .logout-btn i {
            font-size: 1.2em;
        }
        .logout-btn:hover {
            background: linear-gradient(90deg, #ff9472, #ff6a5b);
            box-shadow: 0 6px 24px rgba(0,0,0,0.12);
            transform: translateY(-2px) scale(1.03);
        }
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 280px;
            height: calc(100vh - 70px);
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: #fff;
            padding: 2rem 0;
            overflow-y: auto;
            z-index: 999;
            box-shadow: 2px 0 15px rgba(139, 92, 246, 0.1);
            display: flex;
            flex-direction: column;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0 1rem;
            flex: 1 1 auto;
        }
        .sidebar-menu li { margin-bottom: 0.5rem; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            margin: 0 15px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }
        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateX(5px);
        }
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }
        .sidebar-menu i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        .sidebar-logout-form {
            margin: 0 15px 20px 15px;
        }
        .logout-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            background: rgba(255,255,255,0.13);
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: none;
            text-align: left;
        }
        .logout-btn i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.22);
            color: #fff;
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 280px;
            padding: 100px 2rem 2rem 2rem;
            min-height: calc(100vh - 70px);
            background: #fff;
            border-radius: 32px 0 0 0;
            box-shadow: 0 8px 32px rgba(80, 36, 180, 0.10);
            transition: margin-left 0.2s, padding 0.2s, background 0.2s;
        }
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header-row h1 {
            font-size: 2rem;
            color: #7c3aed;
            margin: 0;
        }
        .add-btn {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 22px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .add-btn:hover {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
        }
        th {
            background: #f3e8ff;
            color: #7c3aed;
            font-size: 1rem;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
        }
        tr {
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.15s;
        }
        tr:last-child {
            border-bottom: none;
        }
        td {
            font-size: 0.98rem;
            color: #333;
        }
        tr:hover {
            background: #f3e8ff33;
        }
        .aksi {
            display: flex;
            gap: 10px;
        }
        .aksi-btn {
            border: none;
            background: none;
            cursor: pointer;
            color: #7c3aed;
            font-size: 1.1rem;
            padding: 6px;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
        }
        .aksi-btn:hover {
            background: #ede9fe;
            color: #5b21b6;
        }
        @media (max-width: 700px) {
            .main-content { padding: 1rem 0.5rem; }
            th, td { padding: 8px 6px; }
            .header-row { flex-direction: column; gap: 1rem; align-items: flex-start; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .main-content { margin-left: 70px; border-radius: 0; }
        }
        @media (max-width: 480px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; border-radius: 0; }
        }
        .modal-overlay {
          position: fixed;
          top: 0; left: 0; right: 0; bottom: 0;
          background: rgba(60, 0, 120, 0.18);
          display: none;
          align-items: center;
          justify-content: center;
          z-index: 9999;
        }
        .modal-overlay.active {
          display: flex !important;
        }
        .modal-content {
          background: #fff;
          border-radius: 18px;
          box-shadow: 0 8px 32px rgba(80,36,180,0.18);
          padding: 2.5rem 2rem 2rem 2rem;
          min-width: 340px;
          max-width: 95vw;
          position: relative;
          animation: modalIn 0.18s cubic-bezier(.4,2,.6,1) both;
          display: flex;
          flex-direction: column;
          align-items: stretch;
        }
        @keyframes modalIn {
          from { transform: translateY(40px) scale(0.98); opacity: 0; }
          to { transform: none; opacity: 1; }
        }
        .modal-content h2 {
          margin-top: 0;
          color: #7c3aed;
          font-size: 1.3rem;
          margin-bottom: 1.2rem;
          text-align: center;
        }
        .form-group {
          margin-bottom: 1.1rem;
          display: flex;
          flex-direction: column;
          align-items: stretch;
        }
        .form-group label {
          display: block;
          margin-bottom: 0.4rem;
          color: #7c3aed;
          font-weight: 600;
          font-size: 1rem;
        }
        .form-group input {
          width: 100%;
          padding: 10px 12px;
          border-radius: 7px;
          border: 1px solid #e2e8f0;
          font-size: 1rem;
          outline: none;
          transition: border 0.2s;
          background: #f8fafc;
        }
        .form-group input:focus {
          border: 1.5px solid #8b5cf6;
        }
        .modal-actions {
          display: flex;
          flex-direction: row;
          justify-content: space-between;
          align-items: center;
          margin-top: 1.5rem;
          gap: 10px;
        }
        .cancel-btn {
          background: #ede9fe;
          color: #7c3aed;
          border: none;
          border-radius: 8px;
          padding: 10px 22px;
          font-size: 1rem;
          font-weight: 600;
          cursor: pointer;
          transition: background 0.2s, color 0.2s;
          min-width: 90px;
        }
        .cancel-btn:hover {
          background: #d1c4e9;
          color: #5b21b6;
        }
        .add-btn[type="submit"] {
          background: linear-gradient(135deg, #8b5cf6, #7c3aed);
          color: #fff;
          border: none;
          border-radius: 8px;
          padding: 12px 22px;
          font-size: 1rem;
          font-weight: 600;
          cursor: pointer;
          display: flex;
          align-items: center;
          gap: 8px;
          box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08);
          transition: background 0.2s, box-shadow 0.2s;
          width: 100%;
          justify-content: center;
        }
        .add-btn[type="submit"]:hover {
          background: linear-gradient(135deg, #7c3aed, #8b5cf6);
          box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13);
        }
        .form-group select {
          width: 100%;
          padding: 10px 12px;
          border-radius: 7px;
          border: 1.5px solid #e2e8f0;
          font-size: 1rem;
          background: #f8fafc;
          color: #5b21b6;
          outline: none;
          transition: border 0.2s, box-shadow 0.2s;
          box-shadow: 0 1px 4px rgba(124, 51, 234, 0.06);
          margin-bottom: 2px;
        }
        .form-group select:focus {
          border: 1.5px solid #8b5cf6;
          box-shadow: 0 2px 8px rgba(124, 51, 234, 0.13);
        }
        .form-group .tagihan-wrapper {
          display: flex;
          align-items: center;
          background: #f3e8ff;
          border: 1.5px solid #c4b5fd;
          border-radius: 7px;
          padding: 10px 12px;
          margin-top: 2px;
          box-shadow: 0 1px 4px rgba(124, 51, 234, 0.06);
        }
        .form-group .tagihan-icon {
          color: #a78bfa;
          font-size: 1.2em;
          margin-right: 10px;
        }
        .form-group input[readonly]#tagihan {
          border: none;
          background: transparent;
          color: #7c3aed;
          font-weight: bold;
          font-size: 1.08rem;
          box-shadow: none;
          padding: 0;
          outline: none;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-home"></i>
            <span>KostPro Admin</span>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <!-- Tombol logout dihapus dari sini -->
        </div>
    </div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="manajemen_penghuni.php" class="active"><i class="fas fa-users"></i> <span>Manajemen Penghuni</span></a></li>
            <li><a href="manajemen_kamar.php"><i class="fas fa-door-open"></i> <span>Manajemen Kamar</span></a></li>
            <li><a href="manajemen_tagihan.php"><i class="fas fa-file-invoice-dollar"></i> <span>Manajemen Tagihan</span></a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i> <span>Laporan</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i> <span>Pengaturan</span></a></li>
        </ul>
        <form action="logout.php" method="post" class="sidebar-logout-form">
            <button type="submit" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
            </button>
        </form>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <div class="header-row">
            <h1><i class="fas fa-users"></i> Manajemen Penghuni</h1>
            <!-- Ubah tombol di header-row -->
            <button id="btnTambahPenghuni" class="add-btn"><i class="fas fa-plus"></i> Tambah Penghuni</button>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>No. KTP</th>
                    <th>No. HP</th>
                    <th>Nomor Kamar</th>
                    <th>Tagihan</th>
                    <th>Tgl Masuk</th>
                    <th>Tgl Keluar</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; foreach ($penghuni as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['no_ktp']) ?></td>
                    <td><?= htmlspecialchars($row['no_hp']) ?></td>
                    <td><?= $row['nomor_kamar'] ? htmlspecialchars($row['nomor_kamar']) : '-' ?></td>
                    <td><?= $row['tagihan_terakhir'] ? 'Rp ' . number_format($row['tagihan_terakhir'],0,',','.') : '-' ?></td>
                    <td><?= $row['tgl_masuk'] ? date('d M Y', strtotime($row['tgl_masuk'])) : '-' ?></td>
                    <td><?= $row['tgl_keluar'] ? date('d M Y', strtotime($row['tgl_keluar'])) : '-' ?></td>
                    <td><?= $row['created_at'] ? date('d M Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                    <td><?= $row['updated_at'] ? date('d M Y H:i', strtotime($row['updated_at'])) : '-' ?></td>
                    <td class="aksi">
                        <button class="aksi-btn edit-btn" title="Edit"
  data-id="<?= $row['id'] ?>"
  data-nama="<?= htmlspecialchars($row['nama']) ?>"
  data-no_ktp="<?= htmlspecialchars($row['no_ktp']) ?>"
  data-no_hp="<?= htmlspecialchars($row['no_hp']) ?>"
  data-tgl_masuk="<?= htmlspecialchars($row['tgl_masuk']) ?>"
  data-kamar_id="<?= $row['id_kamar'] ? $row['id_kamar'] : '' ?>"
  data-tagihan="<?= $row['tagihan_terakhir'] !== null && $row['tagihan_terakhir'] !== '' ? 'Rp ' . number_format($row['tagihan_terakhir'],0,',','.') : '-' ?>">
  <i class="fas fa-edit"></i>
</button>
                        <button class="aksi-btn hapus-btn" title="Hapus" data-id="<?= $row['id'] ?>"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div id="modalTambahPenghuni" class="modal-overlay" style="display:none;">
  <div class="modal-content">
    <h2>Tambah Penghuni</h2>
    <form id="formTambahPenghuni" method="post" action="">
      <input type="hidden" name="form_mode" id="form_mode" value="tambah">
      <input type="hidden" name="id" id="edit_id">
      <div class="form-group">
        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" required>
      </div>
      <div class="form-group">
        <label for="no_ktp">No. KTP</label>
        <input type="text" id="no_ktp" name="no_ktp" required>
      </div>
      <div class="form-group">
        <label for="no_hp">No. HP</label>
        <input type="text" id="no_hp" name="no_hp" required>
      </div>
      <div class="form-group">
        <label for="kamar">Nomor Kamar</label>
        <select id="kamar" name="kamar" required>
          <option value="">Pilih Kamar</option>
          <?php foreach($kamar_tersedia as $k): ?>
            <option value="<?= $k['id'] ?>" data-harga="<?= $k['harga'] ?>"><?= htmlspecialchars($k['nomor']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="tagihan">Tagihan</label>
        <div class="tagihan-wrapper">
          <span class="tagihan-icon"><i class="fas fa-money-bill-wave"></i></span>
          <input type="text" id="tagihan" name="tagihan" readonly>
        </div>
      </div>
      <div class="form-group">
        <label for="tgl_masuk">Tgl Masuk</label>
        <input type="date" id="tgl_masuk" name="tgl_masuk" required>
      </div>
      <div class="modal-actions">
        <button type="button" class="cancel-btn">Batal</button>
        <button type="submit" class="add-btn"><i class="fas fa-plus"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btnTambah = document.getElementById('btnTambahPenghuni');
  const modal = document.getElementById('modalTambahPenghuni');
  const cancelBtn = document.querySelector('.cancel-btn');
  const form = document.getElementById('formTambahPenghuni');
  const formMode = document.getElementById('form_mode');
  const editId = document.getElementById('edit_id');
  const namaInput = document.getElementById('nama');
  const noKtpInput = document.getElementById('no_ktp');
  const noHpInput = document.getElementById('no_hp');
  const tglMasukInput = document.getElementById('tgl_masuk');
  const kamarSelect = document.getElementById('kamar');
  const tagihanInput = document.getElementById('tagihan');
  // Tambah
  btnTambah.addEventListener('click', function(e) {
    e.preventDefault();
    formMode.value = 'tambah';
    editId.value = '';
    form.reset();
    kamarSelect.value = '';
    tagihanInput.value = '-';
    modal.classList.add('active');
    modal.querySelector('h2').textContent = 'Tambah Penghuni';
  });
  // Edit
  document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      formMode.value = 'edit';
      editId.value = btn.getAttribute('data-id');
      namaInput.value = btn.getAttribute('data-nama');
      noKtpInput.value = btn.getAttribute('data-no_ktp');
      noHpInput.value = btn.getAttribute('data-no_hp');
      // Format tgl_masuk ke yyyy-mm-dd
      let tglMasukRaw = btn.getAttribute('data-tgl_masuk');
      if (tglMasukRaw && tglMasukRaw.length >= 8) {
        if (/^\d{4}-\d{2}-\d{2}$/.test(tglMasukRaw)) {
          tglMasukInput.value = tglMasukRaw;
        } else {
          let parts = tglMasukRaw.split(/[-\/]/);
          if (parts.length === 3) {
            if (parts[2].length === 4) {
              tglMasukInput.value = parts[2] + '-' + parts[1].padStart(2,'0') + '-' + parts[0].padStart(2,'0');
            } else if (parts[0].length === 4) {
              tglMasukInput.value = parts[0] + '-' + parts[1].padStart(2,'0') + '-' + parts[2].padStart(2,'0');
            } else {
              tglMasukInput.value = '';
            }
          } else {
            tglMasukInput.value = '';
          }
        }
      } else {
        tglMasukInput.value = '';
      }
      // Pilih kamar aktif
      if(btn.dataset.kamar_id && btn.dataset.kamar_id !== '-') {
        kamarSelect.value = btn.dataset.kamar_id;
      } else {
        kamarSelect.value = '';
      }
      // Tampilkan tagihan
      if (btn.dataset.tagihan && btn.dataset.tagihan !== '-' && btn.dataset.tagihan !== '') {
        tagihanInput.value = btn.dataset.tagihan;
      } else {
        // Jika tidak ada tagihan, cek harga kamar aktif
        let kamarId = btn.dataset.kamar_id;
        let harga = '';
        if (kamarId && kamarSelect) {
          let opt = kamarSelect.querySelector('option[value="'+kamarId+'"][data-harga]');
          if (opt && opt.dataset.harga) {
            harga = opt.dataset.harga;
          }
        }
        if (harga && !isNaN(harga)) {
          tagihanInput.value = 'Rp ' + Number(harga).toLocaleString('id-ID');
        } else {
          tagihanInput.value = '-';
        }
      }
      modal.classList.add('active');
      modal.querySelector('h2').textContent = 'Edit Penghuni';
    });
  });
  // Hapus
  document.querySelectorAll('.hapus-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if(confirm('Yakin ingin menghapus data penghuni ini?')) {
        window.location = '?hapus=' + btn.getAttribute('data-id');
      }
    });
  });
  // Batal
  cancelBtn.addEventListener('click', function() {
    modal.classList.remove('active');
  });
  window.onclick = function(event) {
    if (event.target === modal) {
      modal.classList.remove('active');
    }
  }
  // Auto update tagihan saat pilih kamar
  kamarSelect.addEventListener('change', function() {
    var selected = kamarSelect.options[kamarSelect.selectedIndex];
    var harga = selected.getAttribute('data-harga');
    if (harga && !isNaN(harga)) {
      tagihanInput.value = 'Rp ' + Number(harga).toLocaleString('id-ID');
    } else {
      tagihanInput.value = '-';
    }
  });
});
</script>
</body>
</html> 