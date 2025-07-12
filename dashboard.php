<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config/database.php';
$conn = getConnection();
$total_kamar = $conn->query('SELECT COUNT(*) FROM tb_kamar')->fetchColumn();
$total_penghuni_aktif = $conn->query('SELECT COUNT(DISTINCT id_penghuni) FROM tb_kmr_penghuni WHERE tgl_keluar IS NULL OR tgl_keluar = ""')->fetchColumn();
$bulan_ini = date('Y-m');
$pendapatan_bulan_ini = $conn->query("SELECT SUM(jml_tagihan) FROM tb_tagihan WHERE status = 'lunas' AND bulan = '$bulan_ini'")->fetchColumn();
$pendapatan_bulan_ini = $pendapatan_bulan_ini ? $pendapatan_bulan_ini : 0;
$tagihan_pending = $conn->query("SELECT COUNT(*) FROM tb_tagihan WHERE status != 'lunas'")->fetchColumn();

// Query pendapatan per bulan (6 bulan terakhir)
$pendapatan_per_bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-{$i} months"));
    $label = date('M Y', strtotime("-{$i} months"));
    $total = $conn->query("SELECT SUM(jml_tagihan) FROM tb_tagihan WHERE status = 'lunas' AND bulan = '$bulan'")->fetchColumn();
    $pendapatan_per_bulan[] = [
        'label' => $label,
        'total' => $total ? (float)$total : 0
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Kost</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #333;
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
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
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #8b5cf6;
        }

        .navbar-brand i {
            font-size: 1.8rem;
        }

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

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 280px;
            height: calc(100vh - 70px);
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

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
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 100px 2rem 2rem 2rem;
            min-height: calc(100vh - 70px);
            background: white;
        }

        .dashboard-header {
            margin-bottom: 3rem;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            color: #6b7280;
            margin-bottom: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.rooms {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-icon.tenants {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-icon.revenue {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-icon.bills {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .stat-number {
            font-size: 1.7rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }

        /* Quick Actions */
        .quick-actions {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .quick-actions h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 1rem 0;
            }

            .sidebar-menu a {
                padding: 15px 10px;
                justify-content: center;
            }

            .sidebar-menu a span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
                padding: 100px 1rem 1rem 1rem;
            }

            .navbar {
                padding: 0 1rem;
            }

            .navbar-brand span {
                display: none;
            }

            .user-info span {
                display: none;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 100px 0.5rem 1rem 0.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #8b5cf6;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            .mobile-menu-toggle {
                display: block;
            }
        }
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
            <!-- Tombol logout dihapus dari sini -->
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="manajemen_penghuni.php"><i class="fas fa-users"></i> <span>Manajemen Penghuni</span></a></li>
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
        <div class="dashboard-header">
            <h1 class="welcome-title">Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p class="welcome-subtitle">Kelola kost Anda dengan mudah dan efisien</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon rooms">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-number"><?php echo $total_kamar; ?></div>
                <div class="stat-label">Total Kamar</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon tenants">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_penghuni_aktif; ?></div>
                <div class="stat-label">Penghuni Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number">Rp <?php echo number_format($pendapatan_bulan_ini,0,',','.'); ?></div>
                <div class="stat-label">Pendapatan Bulan Ini</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bills">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-number"><?php echo $tagihan_pending; ?></div>
                <div class="stat-label">Tagihan Pending</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <!-- Ganti dengan grafik batang -->
        <div class="quick-actions" style="margin-top:2rem;">
            <h3 style="margin-bottom:1.5rem;">Grafik Pendapatan 6 Bulan Terakhir</h3>
            <canvas id="barChartPendapatan" height="90"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        const ctx = document.getElementById('barChartPendapatan').getContext('2d');
        const barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($pendapatan_per_bulan, 'label')); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode(array_column($pendapatan_per_bulan, 'total')); ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.7)',
                    borderColor: 'rgba(124, 51, 234, 1)',
                    borderWidth: 1,
                    borderRadius: 8,
                    maxBarThickness: 48
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let val = context.parsed.y || 0;
                                return 'Rp ' + val.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        </script>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 480 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>