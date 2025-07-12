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
// Proses tambah barang bawaan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_penghuni = $_POST['id_penghuni'];
    $id_barang = $_POST['id_barang'];
    $stmt = $conn->prepare('INSERT INTO tb_brng_bawaan (id_penghuni, id_barang) VALUES (?, ?)');
    $stmt->execute([$id_penghuni, $id_barang]);
    header('Location: manajemen_barang_bawaan.php');
    exit();
}
// Proses hapus barang bawaan
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare('DELETE FROM tb_brng_bawaan WHERE id=?');
    $stmt->execute([$id]);
    header('Location: manajemen_barang_bawaan.php');
    exit();
}
$barang_bawaan = $conn->query('SELECT bb.*, p.nama AS nama_penghuni, p.no_ktp, b.nama AS nama_barang, bb.created_at FROM tb_brng_bawaan bb JOIN tb_penghuni p ON bb.id_penghuni = p.id JOIN tb_barang b ON bb.id_barang = b.id ORDER BY p.nama, p.no_ktp, bb.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$penghuni = $conn->query('SELECT id, nama, no_ktp FROM tb_penghuni ORDER BY nama')->fetchAll(PDO::FETCH_ASSOC);
$barang = $conn->query('SELECT id, nama FROM tb_barang ORDER BY nama')->fetchAll(PDO::FETCH_ASSOC);
// Group barang bawaan per penghuni
$grouped = [];
foreach ($barang_bawaan as $row) {
    $key = $row['nama_penghuni'] . '|' . $row['no_ktp'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            'nama_penghuni' => $row['nama_penghuni'],
            'no_ktp' => $row['no_ktp'],
            'created_at' => $row['created_at'],
            'barang' => []
        ];
    }
    $grouped[$key]['barang'][] = $row['nama_barang'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang Bawaan</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #f8fafc;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            width: 100%; height: 70px; background: white; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between; padding: 0 2rem;
            z-index: 1000; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); position: fixed; top: 0; left: 0;
        }
        .navbar-brand { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; color: #8b5cf6; }
        .navbar-brand i { font-size: 1.8rem; }
        .navbar-user { display: flex; align-items: center; gap: 15px; }
        .user-info { display: flex; align-items: center; gap: 10px; padding: 8px 16px; background: rgba(139, 92, 246, 0.1); border-radius: 25px; font-weight: 500; color: #8b5cf6; }
        .logout-btn { display: flex; align-items: center; gap: 10px; background: linear-gradient(90deg, #ff6a5b, #ff9472); color: #fff; border: none; border-radius: 30px; padding: 14px 32px; font-size: 1.1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 16px rgba(0,0,0,0.08); transition: background 0.2s, box-shadow 0.2s, transform 0.1s; }
        .logout-btn i { font-size: 1.2em; }
        .sidebar { position: fixed; top: 70px; left: 0; width: 280px; height: calc(100vh - 70px); background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 2rem 0; overflow-y: auto; z-index: 999; box-shadow: 2px 0 15px rgba(139, 92, 246, 0.1); display: flex; flex-direction: column; }
        .sidebar-menu { list-style: none; padding: 0 1rem; flex: 1 1 auto; }
        .sidebar-menu li { margin-bottom: 0.5rem; }
        .sidebar-menu a { display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: rgba(255, 255, 255, 0.8); text-decoration: none; border-radius: 12px; margin: 0 15px; transition: all 0.3s ease; font-weight: 500; position: relative; }
        .sidebar-menu a:hover { background: rgba(255, 255, 255, 0.1); color: white; transform: translateX(5px); }
        .sidebar-menu a.active { background: rgba(255, 255, 255, 0.2); color: white; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2); }
        .sidebar-menu i { width: 20px; text-align: center; font-size: 1.1rem; }
        .sidebar-logout-form { margin: 0 15px 20px 15px; }
        .logout-btn { width: 100%; display: flex; align-items: center; gap: 15px; padding: 15px 20px; color: rgba(255,255,255,0.8); background: rgba(255,255,255,0.13); border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 500; cursor: pointer; transition: all 0.3s ease; box-shadow: none; text-align: left; }
        .logout-btn i { width: 20px; text-align: center; font-size: 1.1rem; }
        .logout-btn:hover { background: rgba(255,255,255,0.22); color: #fff; transform: translateX(5px); }
        .main-content { margin-left: 280px; padding: 100px 2rem 2rem 2rem; min-height: calc(100vh - 70px); background: #fff; border-radius: 32px 0 0 0; box-shadow: 0 8px 32px rgba(80, 36, 180, 0.10); transition: margin-left 0.2s, padding 0.2s, background 0.2s; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header-row h1 { font-size: 2rem; color: #7c3aed; margin: 0; }
        .add-btn { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; border: none; border-radius: 8px; padding: 12px 22px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08); transition: background 0.2s, box-shadow 0.2s; }
        .add-btn:hover { background: linear-gradient(135deg, #7c3aed, #8b5cf6); box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13); }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        th, td { padding: 14px 12px; text-align: left; }
        th { background: #f3e8ff; color: #7c3aed; font-size: 1rem; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
        tr { border-bottom: 1px solid #f1f1f1; transition: background 0.15s; }
        tr:last-child { border-bottom: none; }
        td { font-size: 0.98rem; color: #333; }
        tr:hover { background: #f3e8ff33; }
        .aksi { display: flex; gap: 10px; }
        .aksi-btn { border: none; background: none; cursor: pointer; color: #7c3aed; font-size: 1.1rem; padding: 6px; border-radius: 6px; transition: background 0.2s, color 0.2s; }
        .aksi-btn:hover { background: #ede9fe; color: #5b21b6; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(60, 0, 120, 0.18); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .modal-overlay.active { display: flex !important; }
        .modal-content { background: #fff; border-radius: 18px; box-shadow: 0 8px 32px rgba(80,36,180,0.18); padding: 2.5rem 2rem 2rem 2rem; min-width: 340px; max-width: 95vw; position: relative; animation: modalIn 0.18s cubic-bezier(.4,2,.6,1) both; display: flex; flex-direction: column; align-items: stretch; }
        @keyframes modalIn { from { transform: translateY(40px) scale(0.98); opacity: 0; } to { transform: none; opacity: 1; } }
        .modal-content h2 { margin-top: 0; color: #7c3aed; font-size: 1.3rem; margin-bottom: 1.2rem; text-align: center; }
        .form-group { margin-bottom: 1.1rem; display: flex; flex-direction: column; align-items: stretch; }
        .form-group label { display: block; margin-bottom: 0.4rem; color: #7c3aed; font-weight: 600; font-size: 1rem; }
        .form-group select { width: 100%; padding: 10px 12px; border-radius: 7px; border: 1px solid #e2e8f0; font-size: 1rem; outline: none; transition: border 0.2s; background: #f8fafc; }
        .form-group select:focus { border: 1.5px solid #8b5cf6; }
        .modal-actions { display: flex; flex-direction: row; justify-content: space-between; align-items: center; margin-top: 1.5rem; gap: 10px; }
        .cancel-btn { background: #ede9fe; color: #7c3aed; border: none; border-radius: 8px; padding: 10px 22px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, color 0.2s; min-width: 90px; }
        .cancel-btn:hover { background: #d1c4e9; color: #5b21b6; }
        .add-btn[type="submit"] { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; border: none; border-radius: 8px; padding: 12px 22px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08); transition: background 0.2s, box-shadow 0.2s; width: 100%; justify-content: center; }
        .add-btn[type="submit"]:hover { background: linear-gradient(135deg, #7c3aed, #8b5cf6); box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13); }
        @media (max-width: 700px) { .main-content { padding: 1rem 0.5rem; } th, td { padding: 8px 6px; } .header-row { flex-direction: column; gap: 1rem; align-items: flex-start; } }
        @media (max-width: 768px) { .sidebar { width: 70px; } .main-content { margin-left: 70px; border-radius: 0; } }
        @media (max-width: 480px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-suitcase"></i>
            <span>Manajemen Barang Bawaan</span>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        </div>
    </div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="manajemen_penghuni.php"><i class="fas fa-users"></i> <span>Manajemen Penghuni</span></a></li>
            <li><a href="manajemen_kamar.php"><i class="fas fa-door-open"></i> <span>Manajemen Kamar</span></a></li>
            <li><a href="manajemen_tagihan.php"><i class="fas fa-file-invoice-dollar"></i> <span>Manajemen Tagihan</span></a></li>
            <li><a href="manajemen_barang.php"><i class="fas fa-box"></i> <span>Manajemen Barang Kost</span></a></li>
            <li><a href="manajemen_barang_bawaan.php" class="active"><i class="fas fa-suitcase"></i> <span>Manajemen Barang Bawaan</span></a></li>
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
            <h1><i class="fas fa-suitcase"></i> Manajemen Barang Bawaan</h1>
            <button id="btnTambahBarangBawaan" class="add-btn"><i class="fas fa-plus"></i> Tambah Barang Bawaan</button>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Penghuni</th>
                    <th>No. KTP</th>
                    <th>Nama Barang</th>
                    <th>Created</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $no=1; 
            foreach ($grouped as $row): 
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_penghuni']) ?></td>
                    <td><?= htmlspecialchars($row['no_ktp']) ?></td>
                    <td><?= htmlspecialchars(implode(', ', $row['barang'])) ?></td>
                    <td><?= $row['created_at'] ? date('d M Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                    <td class="aksi">
                        <!-- Hapus hanya bisa jika 1 barang, jika lebih dari 1 bisa custom logic -->
                        <?php if (count($row['barang']) == 1): ?>
                            <button class="aksi-btn hapus-btn" title="Hapus" data-id="<?= $row['id'] ?? '' ?>"><i class="fas fa-trash"></i></button>
                        <?php else: ?>
                            <span style="color:#aaa;font-size:0.95em;">Multi</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <!-- Modal Tambah Barang Bawaan -->
    <div id="modalTambahBarangBawaan" class="modal-overlay" style="display:none;">
      <div class="modal-content">
        <h2>Tambah Barang Bawaan</h2>
        <form id="formTambahBarangBawaan" method="post" action="">
          <div class="form-group">
            <label for="id_penghuni">Nama Penghuni</label>
            <select id="id_penghuni" name="id_penghuni" required>
              <option value="">Pilih Penghuni</option>
              <?php foreach($penghuni as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama']) ?> (<?= htmlspecialchars($p['no_ktp']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="id_barang">Nama Barang</label>
            <select id="id_barang" name="id_barang" required>
              <option value="">Pilih Barang</option>
              <?php foreach($barang as $b): ?>
                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['nama']) ?></option>
              <?php endforeach; ?>
            </select>
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
  const btnTambah = document.getElementById('btnTambahBarangBawaan');
  const modal = document.getElementById('modalTambahBarangBawaan');
  const cancelBtn = modal.querySelector('.cancel-btn');
  btnTambah.addEventListener('click', function(e) {
    e.preventDefault();
    modal.style.display = 'flex';
    modal.classList.add('active');
  });
  document.querySelectorAll('.hapus-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if(confirm('Yakin ingin menghapus barang bawaan ini?')) {
        window.location = '?hapus=' + btn.getAttribute('data-id');
      }
    });
  });
  cancelBtn.addEventListener('click', function() {
    modal.style.display = 'none';
    modal.classList.remove('active');
  });
  window.onclick = function(event) {
    if (event.target === modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
  }
});
</script>
</body>
</html> 