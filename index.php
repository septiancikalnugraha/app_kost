<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KostPro - Full Screen Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
            height: 100vh;
        }

        .dashboard-container {
            position: relative;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        .dashboard-slider {
            display: flex;
            width: 400vw;
            height: 100vh;
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dashboard-slide {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        /* Dashboard Backgrounds */
        .dashboard-slide.promo {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .dashboard-slide.about {
            background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
            color: white;
        }

        .dashboard-slide.services {
            background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%);
            color: white;
        }

        .dashboard-slide.values {
            background: linear-gradient(135deg, #d8b4fe 0%, #c084fc 100%);
            color: white;
        }

        /* Animated Background Elements */
        .dashboard-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="70" cy="70" r="2.5" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-100vh) rotate(360deg); }
        }

        .dashboard-content {
            max-width: 1200px;
            width: 100%;
            text-align: center;
            z-index: 10;
            position: relative;
        }

        .dashboard-header {
            margin-bottom: 40px;
        }

        .dashboard-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.9;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .dashboard-title {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: slideInFromTop 1s ease-out;
        }

        .dashboard-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            animation: slideInFromBottom 1s ease-out;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        @keyframes slideInFromTop {
            0% { transform: translateY(-50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideInFromBottom {
            0% { transform: translateY(50px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 30px 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.8s ease-out;
            height: auto;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            0% { transform: translateY(30px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .feature-description {
            font-size: 0.95rem;
            line-height: 1.5;
            opacity: 0.9;
            text-align: left;
        }

        .highlight-text {
            color: #ffd700;
            font-weight: 600;
            display: block;
            margin-top: 8px;
            font-size: 0.9rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .stat-item {
            text-align: center;
            animation: countUp 2s ease-out;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Navigation */
        .dashboard-nav {
            position: fixed;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 100;
        }

        .nav-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-dot.active {
            background: white;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.8);
        }

        .nav-dot::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 0;
            height: 0;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .nav-dot.active::after {
            width: 6px;
            height: 6px;
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.8);
            z-index: 100;
            transition: width 0.3s ease;
        }

        /* Auto-slide indicator */
        .auto-slide-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 100;
            font-size: 0.9rem;
        }

        .indicator-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-slide {
                padding: 30px;
            }
            
            .dashboard-title {
                font-size: 2.5rem;
            }
            
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-slide {
                padding: 25px 20px;
            }

            .dashboard-title {
                font-size: 2.2rem;
            }

            .dashboard-subtitle {
                font-size: 1.1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .feature-card {
                padding: 25px 20px;
                min-height: 180px;
            }

            .dashboard-icon {
                font-size: 2.5rem;
            }

            .feature-icon {
                font-size: 2.2rem;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .stat-number {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard-slide {
                padding: 20px 15px;
            }

            .dashboard-title {
                font-size: 2rem;
            }

            .dashboard-subtitle {
                font-size: 1rem;
            }

            .feature-card {
                padding: 20px 15px;
                min-height: 160px;
            }

            .feature-title {
                font-size: 1.2rem;
            }

            .feature-description {
                font-size: 0.9rem;
            }

            .highlight-text {
                font-size: 0.85rem;
            }

            .auto-slide-indicator {
                top: 15px;
                right: 15px;
                padding: 6px 12px;
                font-size: 0.8rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        /* Admin Access Button */
        .admin-access {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .admin-btn {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }

        .admin-btn:active {
            transform: translateY(0);
        }

        .admin-btn i {
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .admin-access {
                top: 15px;
                left: 15px;
            }

            .admin-btn {
                padding: 10px 16px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Access Button -->
    <div class="admin-access">
        <a href="dashboard.php" class="admin-btn">
            <i class="fas fa-user-shield"></i>
            Admin Panel
        </a>
    </div>

    <div class="dashboard-container">
        <!-- Progress Bar -->
        <div class="progress-bar" id="progressBar"></div>

        <!-- Auto-slide Indicator -->
        <div class="auto-slide-indicator">
            <div class="indicator-dot"></div>
            <span>Auto-slide</span>
        </div>

        <!-- Dashboard Slider -->
        <div class="dashboard-slider" id="dashboardSlider">
            <!-- Dashboard 1: Promosi Kost -->
            <div class="dashboard-slide promo">
                <div class="dashboard-content">
                    <div class="dashboard-header">
                        <div class="dashboard-icon">🔥</div>
                        <h1 class="dashboard-title">Promosi Kost</h1>
                        <p class="dashboard-subtitle">Penawaran Terbaik untuk Hunian Premium Anda</p>
                    </div>
                    
                    <div class="dashboard-grid">
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-percentage feature-icon"></i>
                                <h3 class="feature-title">Diskon 50% Bulan Pertama</h3>
                                <p class="feature-description">
                                    Dapatkan kesempatan emas untuk tinggal di kost premium dengan fasilitas lengkap.
                                    <span class="highlight-text">Hanya berlaku untuk 20 pendaftar pertama!</span>
                                </p>
                            </div>
                        </div>

                        <div class="feature-card">
                            <div>
                                <i class="fas fa-wifi feature-icon"></i>
                                <h3 class="feature-title">Gratis WiFi & Listrik</h3>
                                <p class="feature-description">
                                    Nikmati koneksi internet super cepat dan listrik unlimited tanpa biaya tambahan.
                                    <span class="highlight-text">Hemat hingga Rp 500.000/bulan</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-money-bill-wave feature-icon"></i>
                                <h3 class="feature-title">Cashback 10% Member</h3>
                                <p class="feature-description">
                                    Dapatkan cashback untuk setiap pembayaran tepat waktu selama 6 bulan berturut-turut.
                                    <span class="highlight-text">Maksimal cashback Rp 300.000</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-gift feature-icon"></i>
                                <h3 class="feature-title">Paket Lengkap</h3>
                                <p class="feature-description">
                                    Semua fasilitas sudah termasuk dalam harga sewa bulanan.
                                    <span class="highlight-text">No hidden cost, all-inclusive!</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard 2: Tentang Kost -->
            <div class="dashboard-slide about">
                <div class="dashboard-content">
                    <div class="dashboard-header">
                        <div class="dashboard-icon">🏢</div>
                        <h1 class="dashboard-title">Tentang Kost</h1>
                        <p class="dashboard-subtitle">Hunian Modern di Lokasi Strategis dengan Fasilitas Premium</p>
                    </div>
                    
                    <div class="dashboard-grid">
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-map-marker-alt feature-icon"></i>
                                <h3 class="feature-title">Lokasi Strategis</h3>
                                <p class="feature-description">
                                    Terletak di pusat kota dengan akses mudah ke berbagai fasilitas umum, kampus, dan pusat bisnis.
                                    <span class="highlight-text">Jl. Sudirman No. 123, Jakarta Pusat</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-building feature-icon"></i>
                                <h3 class="feature-title">Fasilitas Lengkap</h3>
                                <p class="feature-description">
                                    Gedung 5 lantai dengan 50 kamar dilengkapi AC, water heater, kasur premium, dan lemari built-in.
                                    <span class="highlight-text">Setiap kamar memiliki balkon private</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-shield-alt feature-icon"></i>
                                <h3 class="feature-title">Keamanan 24/7</h3>
                                <p class="feature-description">
                                    Sistem keamanan terintegrasi dengan CCTV full coverage, kartu akses digital, dan petugas keamanan profesional.
                                    <span class="highlight-text">Smart access technology</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-users feature-icon"></i>
                                <h3 class="feature-title">Komunitas Aktif</h3>
                                <p class="feature-description">
                                    Bergabung dengan komunitas penghuni yang aktif dan supportif dengan berbagai kegiatan bersama.
                                    <span class="highlight-text">200+ happy residents</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard 3: Layanan Kost -->
            <div class="dashboard-slide services">
                <div class="dashboard-content">
                    <div class="dashboard-header">
                        <div class="dashboard-icon">🛎️</div>
                        <h1 class="dashboard-title">Layanan Kost</h1>
                        <p class="dashboard-subtitle">Pelayanan Premium untuk Kenyamanan Maksimal</p>
                    </div>
                    
                    <div class="dashboard-grid">
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-broom feature-icon"></i>
                                <h3 class="feature-title">Cleaning Service</h3>
                                <p class="feature-description">
                                    Tim housekeeping profesional untuk pembersihan area umum harian dan layanan cleaning kamar.
                                    <span class="highlight-text">Termasuk cuci seprei mingguan</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-tshirt feature-icon"></i>
                                <h3 class="feature-title">Laundry 24/7</h3>
                                <p class="feature-description">
                                    Fasilitas laundry dengan mesin cuci dan pengering berkualitas tinggi, tersedia 24 jam sehari.
                                    <span class="highlight-text">Detergen dan softener gratis</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-tools feature-icon"></i>
                                <h3 class="feature-title">Maintenance 24/7</h3>
                                <p class="feature-description">
                                    Tim teknisi siap membantu perbaikan dan maintenance fasilitas dengan response time kurang dari 2 jam.
                                    <span class="highlight-text">Gratis service AC & elektronik</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-utensils feature-icon"></i>
                                <h3 class="feature-title">Dapur Bersama</h3>
                                <p class="feature-description">
                                    Dapur modern dengan peralatan lengkap, kulkas bersama, dan area makan yang nyaman.
                                    <span class="highlight-text">Perlengkapan masak tersedia</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard 4: Nilai Kost -->
            <div class="dashboard-slide values">
                <div class="dashboard-content">
                    <div class="dashboard-header">
                        <div class="dashboard-icon">💎</div>
                        <h1 class="dashboard-title">Nilai Kost</h1>
                        <p class="dashboard-subtitle">Investasi Terbaik untuk Kualitas Hidup Premium</p>
                    </div>
                    
                    <div class="dashboard-grid">
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-star feature-icon"></i>
                                <h3 class="feature-title">Kualitas Premium</h3>
                                <p class="feature-description">
                                    Standar internasional dengan material berkualitas tinggi dan desain interior mewah.
                                    <span class="highlight-text">Rating 4.9/5.0 dari penghuni</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-dollar-sign feature-icon"></i>
                                <h3 class="feature-title">Harga Terjangkau</h3>
                                <p class="feature-description">
                                    Investasi terbaik dengan harga yang kompetitif dan value for money terbaik di kelasnya.
                                    <span class="highlight-text">Mulai dari Rp 2.500.000/bulan</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-handshake feature-icon"></i>
                                <h3 class="feature-title">Fleksibilitas</h3>
                                <p class="feature-description">
                                    Sistem pembayaran fleksibel dengan berbagai pilihan paket dan tidak ada biaya tersembunyi.
                                    <span class="highlight-text">Money back guarantee</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="feature-card">
                            <div>
                                <i class="fas fa-chart-line feature-icon"></i>
                                <h3 class="feature-title">ROI Tinggi</h3>
                                <p class="feature-description">
                                    Return on investment tinggi dengan lokasi strategis dan fasilitas yang selalu upgrade.
                                    <span class="highlight-text">Nilai properti terus meningkat</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="stats-container">
                        <div class="stat-item">
                            <div class="stat-number">200+</div>
                            <div class="stat-label">Happy Residents</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">4.9</div>
                            <div class="stat-label">Rating Google</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">50</div>
                            <div class="stat-label">Kamar Available</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Dots -->
        <div class="dashboard-nav">
            <div class="nav-dot active" onclick="goToSlide(0)"></div>
            <div class="nav-dot" onclick="goToSlide(1)"></div>
            <div class="nav-dot" onclick="goToSlide(2)"></div>
            <div class="nav-dot" onclick="goToSlide(3)"></div>
        </div>
    </div>

    <script>
        // Lock index.php in browser history
        if (window.history && window.history.replaceState) {
          window.history.replaceState(null, '', window.location.href);
        }

        class FullScreenDashboard {
            constructor() {
                this.currentSlide = 0;
                this.totalSlides = 4;
                this.autoSlideInterval = 8000; // 8 seconds
                this.autoSlideTimer = null;
                this.slider = document.getElementById('dashboardSlider');
                this.progressBar = document.getElementById('progressBar');
                this.navDots = document.querySelectorAll('.nav-dot');
                
                this.init();
            }

            init() {
                this.startAutoSlide();
                this.updateProgressBar();
                this.addKeyboardNavigation();
                this.addTouchNavigation();
            }

            goToSlide(slideIndex) {
                this.currentSlide = slideIndex;
                this.updateSlider();
                this.updateNavigation();
                this.updateProgressBar();
                this.resetAutoSlide();
            }

            nextSlide() {
                this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                this.updateSlider();
                this.updateNavigation();
                this.updateProgressBar();
            }

            prevSlide() {
                this.currentSlide = this.currentSlide === 0 ? 
                    this.totalSlides - 1 : this.currentSlide - 1;
                this.updateSlider();
                this.updateNavigation();
                this.updateProgressBar();
            }

            updateSlider() {
                const translateX = -this.currentSlide * 100;
                this.slider.style.transform = `translateX(${translateX}vw)`;
            }

            updateNavigation() {
                this.navDots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === this.currentSlide);
                });
            }

            updateProgressBar() {
                const progress = ((this.currentSlide + 1) / this.totalSlides) * 100;
                this.progressBar.style.width = progress + '%';
            }

            startAutoSlide() {
                this.autoSlideTimer = setInterval(() => {
                    this.nextSlide();
                }, this.autoSlideInterval);
            }

            resetAutoSlide() {
                clearInterval(this.autoSlideTimer);
                this.startAutoSlide();
            }

            addKeyboardNavigation() {
                document.addEventListener('keydown', (e) => {
                    switch(e.key) {
                        case 'ArrowRight':
                        case ' ':
                            this.nextSlide();
                            break;
                        case 'ArrowLeft':
                            this.prevSlide();
                            break;
                        case '1':
                            this.goToSlide(0);
                            break;
                        case '2':
                            this.goToSlide(1);
                            break;
                        case '3':
                            this.goToSlide(2);
                            break;
                        case '4':
                            this.goToSlide(3);
                            break;
                    }
                });
            }

            addTouchNavigation() {
                let startX = 0;
                let startY = 0;
                let endX = 0;
                let endY = 0;
                const sliderElem = document.getElementById('dashboardSlider');
                if (!sliderElem) return;
                sliderElem.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                }, {passive: false});
                sliderElem.addEventListener('touchend', (e) => {
                    endX = e.changedTouches[0].clientX;
                    endY = e.changedTouches[0].clientY;
                    const deltaX = startX - endX;
                    const deltaY = startY - endY;
                    // Horizontal swipe detection
                    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
                        e.preventDefault();
                        if (deltaX > 0) {
                            this.nextSlide();
                        } else {
                            this.prevSlide();
                        }
                    }
                }, {passive: false});
            }
        }

        // Initialize dashboard
        const dashboard = new FullScreenDashboard();

        // Global function for navigation dots
        function goToSlide(slideIndex) {
            dashboard.goToSlide(slideIndex);
        }

        // Add mouse wheel navigation
        document.addEventListener('wheel', (e) => {
            if (e.deltaY > 0) {
                dashboard.nextSlide();
            } else {
                dashboard.prevSlide();
            }
        });

        // Pause auto-slide when mouse is over the screen
        document.addEventListener('mouseenter', () => {
            clearInterval(dashboard.autoSlideTimer);
        });

        document.addEventListener('mouseleave', () => {
            dashboard.startAutoSlide();
        });
    </script>
</body>
</html>