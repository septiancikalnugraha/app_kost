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
// Proses tambah/edit kamar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor = $_POST['nomor_kamar'];
    $harga = $_POST['harga'];
    $status = $_POST['status'];
    $created_at = $_POST['created_at'] ?? date('Y-m-d H:i:s');
    if (isset($_POST['form_mode']) && $_POST['form_mode'] === 'edit' && isset($_POST['edit_id'])) {
        // Edit kamar
        $id = $_POST['edit_id'];
        $stmt = $conn->prepare('UPDATE tb_kamar SET nomor=?, harga=?, status=? WHERE id=?');
        $stmt->execute([$nomor, $harga, $status, $id]);
    } else {
        // Tambah kamar
        $stmt = $conn->prepare('INSERT INTO tb_kamar (nomor, harga, status, created_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nomor, $harga, $status, $created_at]);
    }
    header('Location: manajemen_kamar.php');
    exit();
}
// Proses hapus kamar
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare('DELETE FROM tb_kamar WHERE id=?');
    $stmt->execute([$id]);
    header('Location: manajemen_kamar.php');
    exit();
}
// Ambil data kamar dan jumlah penghuni per kamar
$sql = 'SELECT k.*, (
    SELECT COUNT(*) FROM tb_kmr_penghuni kp WHERE kp.id_kamar = k.id AND (kp.tgl_keluar IS NULL OR kp.tgl_keluar = "")
) AS jumlah_penghuni,
(
    SELECT GROUP_CONCAT(CONCAT(p.nama, " (", p.no_ktp, ")") SEPARATOR ", ")
    FROM tb_kmr_penghuni kp 
    JOIN tb_penghuni p ON kp.id_penghuni = p.id 
    WHERE kp.id_kamar = k.id AND (kp.tgl_keluar IS NULL OR kp.tgl_keluar = "")
) AS daftar_penghuni
FROM tb_kamar k ORDER BY k.id DESC';
$kamar = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kamar</title>
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
        .print-btn { background: #8b5cf6; color: #fff; border: none; border-radius: 8px; padding: 10px 16px; font-size: 1.2rem; cursor: pointer; margin-left: 18px; transition: background 0.2s, box-shadow 0.2s; box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08); display: flex; align-items: center; }
        .print-btn:hover { background: #7c3aed; box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13); }
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
        .form-group input, .form-group select {
          width: 100%;
          padding: 10px 12px;
          border-radius: 7px;
          border: 1px solid #e2e8f0;
          font-size: 1rem;
          outline: none;
          transition: border 0.2s;
          background: #f8fafc;
          margin-bottom: 2px;
        }
        .form-group input:focus, .form-group select:focus {
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
        @media print {
          body, html {
            background: #fff !important;
            margin: 0 !important;
            padding: 0 !important;
          }
          .navbar, .sidebar, .print-btn, .add-btn, form, .sidebar-menu, .sidebar-logout-form, .mobile-menu-toggle {
            display: none !important;
          }
          .main-content, .container {
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            max-width: 100% !important;
            width: 100% !important;
            border-radius: 0 !important;
            background: #fff !important;
          }
          h1, h2 {
            text-align: center !important;
            color: #333 !important;
            margin-top: 0 !important;
            margin-bottom: 18px !important;
            font-size: 1.5em !important;
          }
          table {
            width: 100% !important;
            font-size: 1em !important;
            border-collapse: collapse !important;
            margin: 0 0 18px 0 !important;
            box-shadow: none !important;
          }
          th, td {
            border: 1px solid #888 !important;
            padding: 8px 6px !important;
            color: #222 !important;
            background: #fff !important;
          }
          th {
            background: #e9d5ff !important;
            color: #5b21b6 !important;
          }
          tr:nth-child(even) {
            background: #f3e8ff !important;
          }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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
            <li><a href="manajemen_kamar.php" class="active"><i class="fas fa-door-open"></i> <span>Manajemen Kamar</span></a></li>
            <li><a href="manajemen_tagihan.php"><i class="fas fa-file-invoice-dollar"></i> <span>Manajemen Tagihan</span></a></li>
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
            <h1><i class="fas fa-door-open"></i> Manajemen Kamar</h1>
            <button class="print-btn" onclick="window.print()" title="Cetak Halaman"><i class="fas fa-print"></i></button>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nomor Kamar</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th>Penghuni (No. KTP)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; foreach ($kamar as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nomor']) ?></td>
                    <td>Rp <?= number_format($row['harga'],0,',','.') ?></td>
                    <td><?= ($row['jumlah_penghuni'] >= 1 ? 'terisi' : 'kosong') ?></td>
                    <td><?= $row['daftar_penghuni'] ? htmlspecialchars($row['daftar_penghuni']) : 'Kosong' ?></td>
                    <td class="aksi">
                        <button class="aksi-btn edit-btn" title="Edit"
                            data-id="<?= $row['id'] ?>"
                            data-nomor="<?= htmlspecialchars($row['nomor']) ?>"
                            data-harga="<?= $row['harga'] ?>"
                            data-status="<?= htmlspecialchars($row['status']) ?>"
                            data-penghuni="<?= htmlspecialchars($row['daftar_penghuni'] ?? '') ?>">
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
    <!-- Modal Tambah Kamar -->
    <div id="modalTambahKamar" class="modal-overlay" style="display:none;">
      <div class="modal-content" style="min-width:340px;max-width:95vw;">
        <h2 id="modalKamarTitle">Tambah Kamar</h2>
        <form id="formTambahKamar" method="post" action="">
          <input type="hidden" name="created_at" id="created_at">
          <input type="hidden" name="form_mode" id="form_mode" value="tambah">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="form-group">
            <label for="nomor_kamar">Nomor Kamar</label>
            <input type="text" id="nomor_kamar" name="nomor_kamar" required>
          </div>
          <div class="form-group">
            <label for="harga">Harga</label>
            <input type="number" id="harga" name="harga" required min="0">
          </div>
          <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
              <option value="kosong">Kosong</option>
              <option value="terisi">Terisi</option>
            </select>
          </div>
          <div class="form-group" id="penghuni_info" style="display:none;">
            <label for="penghuni_list">Penghuni Saat Ini</label>
            <textarea id="penghuni_list" name="penghuni_list" readonly style="background:#f3f4f6; min-height:60px; resize:none;"></textarea>
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
  const btnTambah = document.getElementById('btnTambahKamar');
  const modal = document.getElementById('modalTambahKamar');
  if (!btnTambah || !modal) return;
  const cancelBtn = modal.querySelector('.cancel-btn');
  const form = document.getElementById('formTambahKamar');
  const createdAtInput = document.getElementById('created_at');
  const formMode = document.getElementById('form_mode');
  const editId = document.getElementById('edit_id');
  const nomorInput = document.getElementById('nomor_kamar');
  const hargaInput = document.getElementById('harga');
  const statusSelect = document.getElementById('status');
  const modalTitle = document.getElementById('modalKamarTitle');
  const penghuniInfo = document.getElementById('penghuni_info');
  const penghuniList = document.getElementById('penghuni_list');

      btnTambah.addEventListener('click', function(e) {
      e.preventDefault();
      if(form) form.reset();
      formMode.value = 'tambah';
      editId.value = '';
      penghuniInfo.style.display = 'none';
      penghuniList.value = '';
      modalTitle.textContent = 'Tambah Kamar';
      modal.style.display = 'flex';
      modal.classList.add('active');
    if (createdAtInput) {
      fetch('https://worldtimeapi.org/api/ip')
        .then(res => res.json())
        .then(data => {
          if(data && data.datetime) {
            createdAtInput.value = data.datetime.replace('T',' ').substring(0,19);
          } else {
            createdAtInput.value = '';
          }
        })
        .catch(() => { createdAtInput.value = ''; });
    }
  });
  if(cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      modal.style.display = 'none';
      modal.classList.remove('active');
    });
  }
  window.onclick = function(event) {
    if (event.target === modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
  }
  // Edit button logic
  document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      formMode.value = 'edit';
      editId.value = btn.dataset.id;
      nomorInput.value = btn.dataset.nomor;
      hargaInput.value = btn.dataset.harga;
      statusSelect.value = btn.dataset.status;
      
      // Tampilkan informasi penghuni jika ada
      if (btn.dataset.penghuni && btn.dataset.penghuni !== '' && btn.dataset.penghuni !== 'Kosong') {
        penghuniInfo.style.display = 'block';
        penghuniList.value = btn.dataset.penghuni;
      } else {
        penghuniInfo.style.display = 'none';
        penghuniList.value = '';
      }
      
      modalTitle.textContent = 'Edit Kamar';
      modal.style.display = 'flex';
      modal.classList.add('active');
    });
  });
  // Delete button logic
  document.querySelectorAll('.hapus-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if(confirm('Yakin ingin menghapus kamar ini?')) {
        window.location = '?hapus=' + btn.getAttribute('data-id');
      }
    });
  });
});
</script>
</body>
</html> 