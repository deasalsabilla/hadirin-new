<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

$id_role = $_SESSION['id_role'];

// Ambil menu sesuai role
$stmtMenu = $conn->prepare("
    SELECT dm.nama_menu, dm.icon_menu, dm.link_menu
    FROM data_menu dm
    JOIN hak_akses ha ON dm.id_menu = ha.id_menu
    WHERE ha.id_role = ?
    AND ha.can_view = 1
    AND dm.status = 'aktif'
    ORDER BY dm.urutan ASC
");

$stmtMenu->bind_param("i", $id_role);
$stmtMenu->execute();
$resultMenu = $stmtMenu->get_result();


// ================================
// QUERY LOKASI KANTOR (BUKAN ABSENSI)
// ================================

$stmtLokasi = $conn->prepare("SELECT * FROM lokasi_kantor LIMIT 1");
$stmtLokasi->execute();
$resultLokasi = $stmtLokasi->get_result();
$lokasi = $resultLokasi->fetch_assoc();

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error   = isset($_GET['error']) ? $_GET['error'] : '';

$stmtLokasi->close();
$stmtMenu->close();
closeConnection($conn);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokasi Kantor - Sistem Absensi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <!-- Mobile Toggle Button -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

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
                        echo '<a href="../' . htmlspecialchars($menu['link_menu']) . '" class="menu-item ' . $activeClass . ' ' . $logoutClass . '">';
                        echo '<i class="' . htmlspecialchars($menu['icon_menu']) . '"></i> ' . htmlspecialchars($menu['nama_menu']);
                        echo '</a>';
                    }
                } else {
                    // Fallback jika tidak ada data menu
                    echo '<a href="../dashboard.php" class="menu-item active">';
                    echo '<i class="bi bi-speedometer2"></i> Dashboard';
                    echo '</a>';
                }
                ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <h1>Lokasi Kantor</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $_SESSION['nama']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>
                            <?php
                            if ($success === 'tambah') echo 'Lokasi berhasil ditambahkan!';
                            elseif ($success === 'edit') echo 'Lokasi berhasil diperbarui!';
                            elseif ($success === 'hapus') echo 'Lokasi berhasil dihapus!';
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-x-circle-fill"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>


                <!-- Tabel Data -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-geo-alt-fill"></i> Data Lokasi Kantor</h2>
                        <a href="edit.php?id=<?php echo $lokasi['id_lokasi']; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i> Edit Lokasi
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($lokasi): ?>
                            <table class="table">
                                <tr>
                                    <th>Nama Lokasi</th>
                                    <td><?php echo htmlspecialchars($lokasi['nama_lokasi']); ?></td>
                                </tr>
                                <tr>
                                    <th>Latitude</th>
                                    <td><?php echo $lokasi['latitude']; ?></td>
                                </tr>
                                <tr>
                                    <th>Longitude</th>
                                    <td><?php echo $lokasi['longitude']; ?></td>
                                </tr>
                                <tr>
                                    <th>Radius (Meter)</th>
                                    <td><?php echo $lokasi['radius_meter']; ?> meter</td>
                                </tr>
                            </table>

                            <div class="mt-3">
                                <a href="https://www.google.com/maps?q=<?php echo $lokasi['latitude']; ?>,<?php echo $lokasi['longitude']; ?>"
                                    target="_blank" class="btn btn-secondary">
                                    <i class="bi bi-map"></i> Lihat di Google Maps
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Lokasi kantor belum diatur.
                            </div>
                            <a href="tambah.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Tambah Lokasi
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>

</html>