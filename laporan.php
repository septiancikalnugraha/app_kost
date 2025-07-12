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
$kamar = $conn->query('SELECT * FROM tb_kamar ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$penghuni = $conn->query('SELECT * FROM tb_penghuni ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$tagihan = $conn->query('SELECT t.*, k.nomor AS nomor_kamar, p.nama AS nama_penghuni FROM tb_tagihan t JOIN tb_kmr_penghuni kp ON t.id_kmr_penghuni = kp.id JOIN tb_kamar k ON kp.id_kamar = k.id JOIN tb_penghuni p ON kp.id_penghuni = p.id ORDER BY t.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$barang = $conn->query('SELECT * FROM tb_barang ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$barang_bawaan = $conn->query('SELECT bb.*, p.nama AS nama_penghuni, p.no_ktp, b.nama AS nama_barang, bb.created_at FROM tb_brng_bawaan bb JOIN tb_penghuni p ON bb.id_penghuni = p.id JOIN tb_barang b ON bb.id_barang = b.id ORDER BY p.nama, p.no_ktp, bb.id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kost</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; min-height: 100vh; color: #333; }
        .navbar { position: fixed; top: 0; left: 0; width: 100%; height: 70px; background: white; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; z-index: 1000; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08); }
        .navbar-brand { display: flex; align-items: center; gap: 12px; font-size: 1.5rem; font-weight: 700; color: #8b5cf6; }
        .navbar-brand i { font-size: 1.8rem; }
        .navbar-user { display: flex; align-items: center; gap: 15px; }
        .user-info { display: flex; align-items: center; gap: 10px; padding: 8px 16px; background: rgba(139, 92, 246, 0.1); border-radius: 25px; font-weight: 500; color: #8b5cf6; }
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
        .main-content {
            max-width: 900px;
            margin: 48px auto 48px 310px;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(80,36,180,0.10);
            padding: 2.5rem 2.5rem 2.5rem 2.5rem;
            min-height: calc(100vh - 110px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h1 {
            color: #7c3aed;
            text-align: center;
            margin-bottom: 2.5rem;
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .export-btn {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 32px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
            margin-bottom: 2.5rem;
            margin-left: auto;
            margin-right: auto;
        }
        .export-btn:hover {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13);
        }
        h2 { color: #7c3aed; margin-top: 2.5rem; margin-bottom: 1rem; font-size: 1.35rem; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); margin-bottom: 2rem; }
        th, td { padding: 14px 12px; text-align: left; }
        th { background: #f3e8ff; color: #7c3aed; font-size: 1.08rem; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
        tr { border-bottom: 1px solid #f1f1f1; }
        tr:last-child { border-bottom: none; }
        td { font-size: 1.01rem; color: #333; }
        tr:hover { background: #f3e8ff33; }
        @media (max-width: 1200px) { .main-content { margin-left: 0; } }
        @media (max-width: 900px) { .main-content { max-width: 98vw; padding: 1.2rem 0.5rem; } }
        @media (max-width: 768px) { .sidebar { width: 70px; padding: 1rem 0; } .sidebar-menu a { padding: 15px 10px; justify-content: center; } .sidebar-menu a span { display: none; } .main-content { margin-left: 0; padding: 100px 1rem 1rem 1rem; } .navbar { padding: 0 1rem; } .navbar-brand span { display: none; } .user-info span { display: none; } h1 { font-size: 2rem; } }
        @media (max-width: 480px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 100px 0.5rem 1rem 0.5rem; } }
        .print-btn { background: #8b5cf6; color: #fff; border: none; border-radius: 8px; padding: 10px 16px; font-size: 1.2rem; cursor: pointer; margin-left: 18px; transition: background 0.2s, box-shadow 0.2s; box-shadow: 0 2px 8px rgba(124, 51, 234, 0.08); display: flex; align-items: center; }
        .print-btn:hover { background: #7c3aed; box-shadow: 0 4px 16px rgba(124, 51, 234, 0.13); }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-brand">
            <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <i class="fas fa-home"></i>
            <span>KostPro Admin</span>
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
            <li><a href="laporan.php" class="active"><i class="fas fa-file-alt"></i> <span>Laporan</span></a></li>
            <li><a href="#"><i class="fas fa-cog"></i> <span>Pengaturan</span></a></li>
        </ul>
        <form action="logout.php" method="post" class="sidebar-logout-form">
            <button type="submit" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
            </button>
        </form>
    </div>
    <div class="main-content">
    <div style="display:flex;align-items:center;justify-content:center;margin-bottom:2.5rem;gap:18px;">
  <h1 style="margin-bottom:0;">Laporan Kost</h1>
  <button class="print-btn" onclick="window.print()" title="Cetak Halaman"><i class="fas fa-print"></i></button>
</div>
    <form method="post" action="laporan_pdf.php" target="_blank" style="text-align:right;">
        <button type="submit" class="export-btn"><i class="fas fa-file-pdf"></i> Export PDF</button>
    </form>
    <h2>Laporan Kamar</h2>
    <table><thead><tr><th>No</th><th>Nomor Kamar</th><th>Harga</th><th>Status</th></tr></thead><tbody>
    <?php $no=1; foreach($kamar as $k): ?>
        <tr><td><?= $no++ ?></td><td><?= htmlspecialchars($k['nomor']) ?></td><td>Rp <?= number_format($k['harga'],0,',','.') ?></td><td><?= htmlspecialchars($k['status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <h2>Laporan Penghuni</h2>
    <table><thead><tr><th>No</th><th>Nama</th><th>No. KTP</th><th>No. HP</th><th>Tgl Masuk</th><th>Tgl Keluar</th></tr></thead><tbody>
    <?php $no=1; foreach($penghuni as $p): ?>
        <tr><td><?= $no++ ?></td><td><?= htmlspecialchars($p['nama']) ?></td><td><?= htmlspecialchars($p['no_ktp']) ?></td><td><?= htmlspecialchars($p['no_hp']) ?></td><td><?= $p['tgl_masuk'] ?></td><td><?= $p['tgl_keluar'] ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <h2>Laporan Tagihan</h2>
    <table><thead><tr><th>No</th><th>Bulan</th><th>Nomor Kamar</th><th>Nama Penghuni</th><th>Jumlah Tagihan</th><th>Status</th></tr></thead><tbody>
    <?php $no=1; foreach($tagihan as $t): ?>
        <tr><td><?= $no++ ?></td><td><?= htmlspecialchars($t['bulan']) ?></td><td><?= htmlspecialchars($t['nomor_kamar']) ?></td><td><?= htmlspecialchars($t['nama_penghuni']) ?></td><td>Rp <?= number_format($t['jml_tagihan'],0,',','.') ?></td><td><?= htmlspecialchars($t['status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <h2>Laporan Barang Kost</h2>
    <table><thead><tr><th>No</th><th>Nama Barang</th><th>Created</th><th>Updated</th></tr></thead><tbody>
    <?php $no=1; foreach($barang as $b): ?>
        <tr><td><?= $no++ ?></td><td><?= htmlspecialchars($b['nama']) ?></td><td><?= $b['created_at'] ? date('d M Y H:i', strtotime($b['created_at'])) : '-' ?></td><td><?= $b['updated_at'] ? date('d M Y H:i', strtotime($b['updated_at'])) : '-' ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
    <h2>Laporan Barang Bawaan</h2>
    <table><thead><tr><th>No</th><th>Nama Penghuni</th><th>No. KTP</th><th>Nama Barang</th><th>Created</th></tr></thead><tbody>
    <?php $no=1; foreach($barang_bawaan as $row): ?>
        <tr><td><?= $no++ ?></td><td><?= htmlspecialchars($row['nama_penghuni']) ?></td><td><?= htmlspecialchars($row['no_ktp']) ?></td><td><?= htmlspecialchars($row['nama_barang']) ?></td><td><?= $row['created_at'] ? date('d M Y H:i', strtotime($row['created_at'])) : '-' ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
</div>
</body>
</html> 