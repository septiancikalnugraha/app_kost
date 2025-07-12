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
// Tambahkan barang 'Ranjang' dan 'Kasur' jika belum ada
$barang_baru = ['Ranjang', 'Kasur'];
foreach ($barang_baru as $nama_barang) {
    $cek = $conn->prepare('SELECT COUNT(*) FROM tb_barang WHERE nama = ?');
    $cek->execute([$nama_barang]);
    if ($cek->fetchColumn() == 0) {
        $conn->prepare('INSERT INTO tb_barang (nama, created_at, updated_at) VALUES (?, NOW(), NOW())')->execute([$nama_barang]);
    }
}
// Proses assign barang ke kamar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_barang_id'], $_POST['assign_kamar'])) {
    $barang_id = $_POST['assign_barang_id'];
    $kamar_ids = $_POST['assign_kamar'];
    // Hapus relasi lama
    $conn->prepare('DELETE FROM tb_barang_kamar WHERE id_barang=?')->execute([$barang_id]);
    // Insert relasi baru
    $stmt = $conn->prepare('INSERT INTO tb_barang_kamar (id_barang, id_kamar) VALUES (?, ?)');
    foreach ($kamar_ids as $kid) {
        $stmt->execute([$barang_id, $kid]);
    }
    header('Location: manajemen_barang.php');
    exit();
}
// Proses tambah/edit barang
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['form_mode']) && in_array($_POST['form_mode'], ['tambah','edit'])
) {
    $nama = $_POST['nama_barang'];
    if ($_POST['form_mode'] === 'edit' && isset($_POST['edit_id'])) {
        $id = $_POST['edit_id'];
        $stmt = $conn->prepare('UPDATE tb_barang SET nama=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$nama, $id]);
    } else {
        $stmt = $conn->prepare('INSERT INTO tb_barang (nama, created_at, updated_at) VALUES (?, NOW(), NOW())');
        $stmt->execute([$nama]);
    }
    header('Location: manajemen_barang.php');
    exit();
}
// Proses hapus barang
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare('DELETE FROM tb_barang WHERE id=?');
    $stmt->execute([$id]);
    header('Location: manajemen_barang.php');
    exit();
}
// SEKALI JALAN: Update semua tanggal barang ke waktu sekarang
if (isset($_GET['update_all_tanggal']) && $_GET['update_all_tanggal'] == '1') {
    $conn->query('UPDATE tb_barang SET created_at = NOW(), updated_at = NOW()');
    echo '<div style="background:#d1fae5;color:#065f46;padding:16px;text-align:center;">Semua tanggal barang sudah diupdate ke waktu sekarang. <a href="manajemen_barang.php" style="color:#2563eb;">Refresh</a></div>';
    exit;
}
// Ambil data barang dan kamar terkait
$barang = $conn->query('SELECT * FROM tb_barang ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
// Ambil mapping barang -> kamar
$barang_kamar_map = [];
$stmt = $conn->query('SELECT bk.id_barang, k.nomor FROM tb_barang_kamar bk JOIN tb_kamar k ON bk.id_kamar = k.id');
foreach ($stmt as $row) {
    $barang_kamar_map[$row['id_barang']][] = $row['nomor'];
}
// Ambil semua kamar untuk assign
$kamar_list = $conn->query('SELECT id, nomor FROM tb_kamar ORDER BY nomor')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Barang Kost</title>
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
        .print-btn { background: #8b5cf6; color: #fff; border: none; border-radius: 8px; padding: 10px 16px; font-size: 1.2rem; cursor: pointer; margin-left: 18px; transition: background 0.2s, box-shadow 0.2s; box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08); display: flex; align-items: center; }
        .print-btn:hover { background: #7c3aed; box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13); }
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
        .form-group input { width: 100%; padding: 10px 12px; border-radius: 7px; border: 1px solid #e2e8f0; font-size: 1rem; outline: none; transition: border 0.2s; background: #f8fafc; }
        .form-group input:focus { border: 1.5px solid #8b5cf6; }
        .modal-actions { display: flex; flex-direction: row; justify-content: space-between; align-items: center; margin-top: 1.5rem; gap: 10px; }
        .cancel-btn { background: #ede9fe; color: #7c3aed; border: none; border-radius: 8px; padding: 10px 22px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, color 0.2s; min-width: 90px; }
        .cancel-btn:hover { background: #d1c4e9; color: #5b21b6; }
        .add-btn[type="submit"] { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; border: none; border-radius: 8px; padding: 12px 22px; font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08); transition: background 0.2s, box-shadow 0.2s; width: 100%; justify-content: center; }
        .add-btn[type="submit"]:hover { background: linear-gradient(135deg, #7c3aed, #8b5cf6); box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13); }
        @media (max-width: 700px) { .main-content { padding: 1rem 0.5rem; } th, td { padding: 8px 6px; } .header-row { flex-direction: column; gap: 1rem; align-items: flex-start; } }
        @media (max-width: 768px) { .sidebar { width: 70px; } .main-content { margin-left: 70px; border-radius: 0; } }
        @media (max-width: 480px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; border-radius: 0; } }
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
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-box"></i>
            <span>Manajemen Barang Kost</span>
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
            <li><a href="manajemen_barang.php" class="active"><i class="fas fa-box"></i> <span>Manajemen Barang Kost</span></a></li>
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
            <h1><i class="fas fa-box"></i> Manajemen Barang Kost</h1>
            <button class="print-btn" onclick="window.print()" title="Cetak Halaman"><i class="fas fa-print"></i></button>
            <button id="btnTambahBarang" class="add-btn"><i class="fas fa-plus"></i> Tambah Barang</button>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Nomor Kamar</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php $no=1; foreach ($barang as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= isset($barang_kamar_map[$row['id']]) ? htmlspecialchars(implode(', ', $barang_kamar_map[$row['id']])) : '-' ?></td>
                    <td><?= $row['created_at'] ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                    <td><?= $row['updated_at'] ? date('d-m-Y H:i', strtotime($row['updated_at'])) : '-' ?></td>
                    <td class="aksi">
                        <button class="aksi-btn edit-btn" title="Edit"
                            data-id="<?= $row['id'] ?>"
                            data-nama="<?= htmlspecialchars($row['nama']) ?>"
                            data-harga="<?= $row['harga'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="aksi-btn hapus-btn" title="Hapus" data-id="<?= $row['id'] ?>"><i class="fas fa-trash"></i></button>
                        <button class="aksi-btn assign-btn" title="Assign Kamar" data-id="<?= $row['id'] ?>" data-nama="<?= htmlspecialchars($row['nama']) ?>"><i class="fas fa-link"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <!-- Modal Tambah/Edit Barang -->
    <div id="modalTambahBarang" class="modal-overlay" style="display:none;">
      <div class="modal-content">
        <h2 id="modalBarangTitle">Tambah Barang</h2>
        <form id="formTambahBarang" method="post" action="">
          <input type="hidden" name="form_mode" id="form_mode" value="tambah">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="form-group">
            <label for="nama_barang">Nama Barang</label>
            <input type="text" id="nama_barang" name="nama_barang" required>
          </div>
          <div class="modal-actions">
            <button type="button" class="cancel-btn">Batal</button>
            <button type="submit" class="add-btn"><i class="fas fa-plus"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
<!-- Modal Assign Kamar -->
<div id="modalAssignKamar" class="modal-overlay" style="display:none;">
  <div class="modal-content">
    <h2 id="assignTitle">Assign Barang ke Kamar</h2>
    <form id="formAssignKamar" method="post" action="">
      <input type="hidden" name="assign_barang_id" id="assign_barang_id">
      <div class="form-group">
        <label for="assign_kamar">Pilih Kamar</label>
        <select id="assign_kamar" name="assign_kamar[]" multiple required style="min-height:90px;">
          <?php foreach($kamar_list as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nomor']) ?></option>
          <?php endforeach; ?>
        </select>
        <small>Pilih satu atau lebih kamar (Ctrl+klik untuk multi)</small>
      </div>
      <div class="modal-actions">
        <button type="button" class="cancel-btn">Batal</button>
        <button type="submit" class="add-btn"><i class="fas fa-link"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const btnTambah = document.getElementById('btnTambahBarang');
  const modal = document.getElementById('modalTambahBarang');
  const cancelBtn = modal ? modal.querySelector('.cancel-btn') : null;
  const form = document.getElementById('formTambahBarang');
  const formMode = document.getElementById('form_mode');
  const editId = document.getElementById('edit_id');
  const namaInput = document.getElementById('nama_barang');
  btnTambah && btnTambah.addEventListener('click', function(e) {
    e.preventDefault();
    if(formMode) formMode.value = 'tambah';
    if(editId) editId.value = '';
    if(form) form.reset();
    if(modal) {
      modal.style.display = 'flex';
      modal.classList.add('active');
      modal.querySelector('h2').textContent = 'Tambah Barang';
    }
  });
  document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if(formMode) formMode.value = 'edit';
      if(editId) editId.value = btn.dataset.id;
      if(namaInput) namaInput.value = btn.dataset.nama;
      if(modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
        modal.querySelector('h2').textContent = 'Edit Barang';
      }
    });
  });
  document.querySelectorAll('.hapus-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if(confirm('Yakin ingin menghapus barang ini?')) {
        window.location = '?hapus=' + btn.getAttribute('data-id');
      }
    });
  });
  cancelBtn && cancelBtn.addEventListener('click', function() {
    if(modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
  });
  window.onclick = function(event) {
    if (modal && event.target === modal) {
      modal.style.display = 'none';
      modal.classList.remove('active');
    }
    if (modalAssign && event.target === modalAssign) {
      modalAssign.style.display = 'none';
      modalAssign.classList.remove('active');
    }
  }
  // Assign Kamar logic
  const assignBtns = document.querySelectorAll('.assign-btn');
  const modalAssign = document.getElementById('modalAssignKamar');
  const assignForm = document.getElementById('formAssignKamar');
  const assignBarangId = document.getElementById('assign_barang_id');
  const assignKamar = document.getElementById('assign_kamar');
  assignBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      if(assignBarangId) assignBarangId.value = btn.dataset.id;
      if(modalAssign) {
        modalAssign.style.display = 'flex';
        modalAssign.classList.add('active');
        document.getElementById('assignTitle').textContent = 'Assign "' + btn.dataset.nama + '" ke Kamar';
      }
    });
  });
  if(modalAssign) {
    const assignCancelBtn = modalAssign.querySelector('.cancel-btn');
    assignCancelBtn && assignCancelBtn.addEventListener('click', function() {
      modalAssign.style.display = 'none';
      modalAssign.classList.remove('active');
    });
  }
});
</script>
<?php
?>
</body>
</html> 