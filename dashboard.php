<?php
require_once 'config/session.php';
require_once 'config/koneksi.php';

$conn = getConnection();

// Ambil data menu
$resultMenu = $conn->query("SELECT nama_menu, icon_menu, link_menu FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC");

// Ambil statistik
$today = date('Y-m-d');

// Total karyawan
$result = $conn->query("SELECT COUNT(*) as total FROM karyawan");
$totalKaryawan = $result->fetch_assoc()['total'];

// Total absensi hari ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM absensi WHERE tanggal = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$totalAbsensiHariIni = $stmt->get_result()->fetch_assoc()['total'];

// Hadir hari ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM absensi WHERE tanggal = ? AND status = 'Hadir'");
$stmt->bind_param("s", $today);
$stmt->execute();
$hadirHariIni = $stmt->get_result()->fetch_assoc()['total'];

// Izin hari ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM absensi WHERE tanggal = ? AND status = 'Izin'");
$stmt->bind_param("s", $today);
$stmt->execute();
$izinHariIni = $stmt->get_result()->fetch_assoc()['total'];

// Sakit hari ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM absensi WHERE tanggal = ? AND status = 'Sakit'");
$stmt->bind_param("s", $today);
$stmt->execute();
$sakitHariIni = $stmt->get_result()->fetch_assoc()['total'];

// Alpha hari ini
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM absensi WHERE tanggal = ? AND status = 'Alpha'");
$stmt->bind_param("s", $today);
$stmt->execute();
$alphaHariIni = $stmt->get_result()->fetch_assoc()['total'];

$stmt->close();
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Absensi Karyawan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>


    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="bi bi-building"></i> Dandang Cap Gajah</h2>
            </div>
            <nav class="sidebar-menu">
                <?php
                if ($resultMenu && $resultMenu->num_rows > 0) {
                    while ($menu = $resultMenu->fetch_assoc()) {
                        $activeClass = (basename($_SERVER['PHP_SELF']) == basename($menu['link_menu'])) ? 'active' : '';
                        $logoutClass = ($menu['link_menu'] == 'logout.php') ? 'logout' : '';
                        echo '<a href="' . htmlspecialchars($menu['link_menu']) . '" class="menu-item ' . $activeClass . ' ' . $logoutClass . '">';
                        echo '<i class="' . htmlspecialchars($menu['icon_menu']) . '"></i> ' . htmlspecialchars($menu['nama_menu']);
                        echo '</a>';
                    }
                } else {
                    // Fallback jika tidak ada data menu
                    echo '<a href="dashboard.php" class="menu-item active">';
                    echo '<i class="bi bi-speedometer2"></i> Dashboard';
                    echo '</a>';
                }
                ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <!-- Mobile Toggle Button -->
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>

                <h1>Dashboard</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span>Selamat datang, <strong><?php echo $_SESSION['nama']; ?></strong></span>
                </div>
            </div>

            <div class="content">
                <!-- Info tanggal hari ini -->
                <div class="date-info-box">
                    <strong><i class="bi bi-calendar-event"></i> Tanggal Hari Ini: <?php echo date('d F Y', strtotime($today)); ?></strong>
                    <small>Statistik di bawah menampilkan data absensi untuk tanggal hari ini.</small>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #ec4899;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Karyawan</h3>
                            <p class="stat-number"><?php echo $totalKaryawan; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3b82f6;">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Absensi Hari Ini</h3>
                            <p class="stat-number"><?php echo $totalAbsensiHariIni; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: #10b981;">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Hadir</h3>
                            <p class="stat-number"><?php echo $hadirHariIni; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f59e0b;">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Izin</h3>
                            <p class="stat-number"><?php echo $izinHariIni; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: #6366f1;">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Sakit</h3>
                            <p class="stat-number"><?php echo $sakitHariIni; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" style="background: #ef4444;">
                            <i class="bi bi-x-circle-fill"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Alpha</h3>
                            <p class="stat-number"><?php echo $alphaHariIni; ?></p>
                        </div>
                    </div>
                </div>

                <div class="quick-actions">
                    <h2>Menu Utama</h2>
                    <div class="action-grid">
                        <a href="karyawan/index.php" class="action-card">
                            <div class="action-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <h3>Data Karyawan</h3>
                            <p>Kelola data karyawan</p>
                        </a>
                        <a href="absensi/index.php" class="action-card">
                            <div class="action-icon">
                                <i class="bi bi-clipboard-check-fill"></i>
                            </div>
                            <h3>Data Absensi</h3>
                            <p>Kelola data absensi</p>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>