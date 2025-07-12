<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config/database.php';
$conn = getConnection();
// Ambil data tagihan dan total pembayaran per tagihan, serta nomor kamar
$sql = 'SELECT t.*, (
    SELECT SUM(b.jml_bayar) FROM tb_bayar b WHERE b.id_tagihan = t.id
) AS total_bayar, k.nomor AS nomor_kamar,
p.nama AS nama_penghuni, p.no_ktp AS no_ktp_penghuni
FROM tb_tagihan t
JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id
JOIN tb_kamar k ON kp.id_kamar = k.id
JOIN tb_penghuni p ON kp.id_penghuni = p.id
ORDER BY t.id DESC';
$tagihan = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Proses edit tagihan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_mode']) && $_POST['form_mode'] === 'edit') {
    $id = $_POST['edit_id'];
    $bulan = $_POST['bulan'];
    $jml_tagihan = $_POST['jml_tagihan'];
    $status = $_POST['status'];
    $stmt = $conn->prepare('UPDATE tb_tagihan SET bulan=?, jml_tagihan=?, status=? WHERE id=?');
    $stmt->execute([$bulan, $jml_tagihan, $status, $id]);
    header('Location: manajemen_tagihan.php');
    exit();
}
// Proses hapus tagihan
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare('DELETE FROM tb_tagihan WHERE id=?');
    $stmt->execute([$id]);
    header('Location: manajemen_tagihan.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Tagihan</title>
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
        }
        tr:last-child {
            border-bottom: none;
        }
        td {
            font-size: 0.98rem;
            color: #333;
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
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(60, 30, 120, 0.18);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .modal-overlay.active, .modal-overlay[style*='display: flex'] {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: #fff;
            border-radius: 32px;
            padding: 2.2rem 1.5rem 1.5rem 1.5rem;
            box-shadow: 0 8px 32px rgba(124, 51, 234, 0.13);
            max-width: 250px;
            width: 50vw;
            margin: 32px 0;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            animation: modalPop 0.25s cubic-bezier(.4,2,.6,1) 1;
        }
        @keyframes modalPop {
            0% { transform: scale(0.95) translateY(30px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal-content h2 {
            color: #8b5cf6;
            margin-bottom: 0.5rem;
            text-align: center;
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .form-group {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            align-items: flex-start;
        }
        .form-group label {
            font-size: 1rem;
            color: #8b5cf6;
            font-weight: 700;
            margin-bottom: 2px;
            margin-left: 2px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            max-width: 100%;
            padding: 13px 16px;
            border: 2px solid #ede9fe;
            border-radius: 14px;
            font-size: 1.08rem;
            color: #333;
            background: #fff;
            font-weight: 500;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.04);
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #a78bfa;
            box-shadow: 0 0 0 2px #ede9fe;
            outline: none;
        }
        .modal-actions {
            width: 100%;
            display: flex;
            justify-content: space-between;
            gap: 14px;
            margin-top: 0.5rem;
        }
        .modal-actions button {
            flex: 1;
            padding: 13px 0;
            border: none;
            border-radius: 12px;
            font-size: 1.08rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, color 0.2s;
        }
        .modal-actions .cancel-btn {
            background: #f3e8ff;
            color: #8b5cf6;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.04);
        }
        .modal-actions .cancel-btn:hover {
            background: #ede9fe;
            color: #5b21b6;
        }
        .modal-actions .add-btn {
            background: linear-gradient(90deg, #a78bfa, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .modal-actions .add-btn:hover {
            background: linear-gradient(90deg, #7c3aed, #a78bfa);
            color: #fff;
            box-shadow: 0 6px 24px rgba(124, 51, 234, 0.18);
        }
        @media (max-width: 600px) {
            .modal-content {
                padding: 1rem 0.2rem 1rem 0.2rem;
                max-width: 99vw;
                margin: 12px 0;
            }
            .modal-content h2 { font-size: 1.08rem; }
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
            <li><a href="manajemen_penghuni.php"><i class="fas fa-users"></i> <span>Manajemen Penghuni</span></a></li>
            <li><a href="manajemen_kamar.php"><i class="fas fa-door-open"></i> <span>Manajemen Kamar</span></a></li>
            <li><a href="manajemen_tagihan.php" class="active"><i class="fas fa-file-invoice-dollar"></i> <span>Manajemen Tagihan</span></a></li>
            <li><a href="manajemen_barang.php"><i class="fas fa-box"></i> <span>Manajemen Barang Kost</span></a></li>
            <li><a href="manajemen_barang_bawaan.php"><i class="fas fa-suitcase"></i> <span>Manajemen Barang Bawaan</span></a></li>
            <li><a href="laporan.php" <?php if(basename($_SERVER['PHP_SELF'])=='laporan.php') echo 'class="active"'; ?>><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
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
            <h1><i class="fas fa-file-invoice-dollar"></i> Manajemen Tagihan</h1>
            <button class="add-btn"><i class="fas fa-plus"></i> Tambah Tagihan</button>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Bulan</th>
                    <th>Nomor Kamar</th>
                    <th>Penghuni (No. KTP)</th>
                    <th>Jumlah Tagihan</th>
                    <th>Status</th>
                    <th>Total Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; foreach ($tagihan as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['bulan']) ?></td>
                    <td><?= htmlspecialchars($row['nomor_kamar']) ?></td>
                    <td><?= htmlspecialchars($row['nama_penghuni']) ?> (<?= htmlspecialchars($row['no_ktp_penghuni']) ?>)</td>
                    <td>Rp <?= number_format($row['jml_tagihan'],0,',','.') ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td>Rp <?= number_format(strtolower($row['status'])==='lunas' ? $row['jml_tagihan'] : ($row['total_bayar'] ?? 0),0,',','.') ?></td>
                    <td class="aksi">
                        <button class="aksi-btn edit-btn" title="Edit"
                            data-id="<?= $row['id'] ?>"
                            data-bulan="<?= htmlspecialchars($row['bulan']) ?>"
                            data-jml_tagihan="<?= $row['jml_tagihan'] ?>"
                            data-status="<?= htmlspecialchars($row['status']) ?>"
                            data-nomor_kamar="<?= htmlspecialchars($row['nomor_kamar']) ?>"
                            data-nama_penghuni="<?= htmlspecialchars($row['nama_penghuni']) ?>"
                            data-no_ktp_penghuni="<?= htmlspecialchars($row['no_ktp_penghuni']) ?>">
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

    <!-- Modal Edit Tagihan -->
    <div id="modalEditTagihan" class="modal-overlay" style="display:none;">
      <div class="modal-content" style="min-width:340px;max-width:95vw;">
        <h2>Edit Tagihan</h2>
        <form id="formEditTagihan" method="post" action="">
          <input type="hidden" name="form_mode" value="edit">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="form-group">
            <label for="edit_nomor_kamar">Nomor Kamar</label>
            <input type="text" id="edit_nomor_kamar" name="nomor_kamar" readonly style="background:#f3f4f6;">
          </div>
          <div class="form-group">
            <label for="edit_penghuni">Penghuni (No. KTP)</label>
            <input type="text" id="edit_penghuni" name="penghuni" readonly style="background:#f3f4f6;">
          </div>
          <div class="form-group">
            <label for="edit_bulan">Bulan</label>
            <input type="month" id="edit_bulan" name="bulan" required>
          </div>
          <div class="form-group">
            <label for="edit_jml_tagihan">Jumlah Tagihan</label>
            <input type="number" id="edit_jml_tagihan" name="jml_tagihan" required min="0">
          </div>
          <div class="form-group">
            <label for="edit_status">Status</label>
            <select id="edit_status" name="status" required>
              <option value="pending">Pending</option>
              <option value="lunas">Lunas</option>
            </select>
          </div>
          <div class="modal-actions">
            <button type="button" class="cancel-btn">Batal</button>
            <button type="submit" class="add-btn"><i class="fas fa-save"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('modalEditTagihan');
      const form = document.getElementById('formEditTagihan');
      const editId = document.getElementById('edit_id');
      const editBulan = document.getElementById('edit_bulan');
      const editJmlTagihan = document.getElementById('edit_jml_tagihan');
      const editStatus = document.getElementById('edit_status');
      const editNomorKamar = document.getElementById('edit_nomor_kamar');
      const editPenghuni = document.getElementById('edit_penghuni');
      // Edit button logic
      document.querySelectorAll('.edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          editId.value = btn.dataset.id;
          editBulan.value = btn.dataset.bulan;
          editJmlTagihan.value = btn.dataset.jml_tagihan;
          editStatus.value = btn.dataset.status;
          editNomorKamar.value = btn.dataset.nomor_kamar;
          editPenghuni.value = btn.dataset.nama_penghuni + ' (' + btn.dataset.no_ktp_penghuni + ')';
          modal.style.display = 'flex';
          modal.classList.add('active');
        });
      });
      // Cancel button
      modal.querySelector('.cancel-btn').addEventListener('click', function() {
        modal.style.display = 'none';
        modal.classList.remove('active');
      });
      window.onclick = function(event) {
        if (event.target === modal) {
          modal.style.display = 'none';
          modal.classList.remove('active');
        }
      }
      // Delete button logic
      document.querySelectorAll('.hapus-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          if(confirm('Yakin ingin menghapus tagihan ini?')) {
            window.location = '?hapus=' + btn.getAttribute('data-id');
          }
        });
      });
    });
    </script>
</body>
</html> 