<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();
$id_role = $_SESSION['id_role'];

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_lokasi  = sanitize($_POST['nama_lokasi']);
    $latitude     = $_POST['latitude'] ?? null;
    $longitude    = $_POST['longitude'] ?? null;
    $radius_meter = (int)$_POST['radius_meter'];

    if (empty($nama_lokasi) || empty($latitude) || empty($longitude) || $radius_meter <= 0) {
        $error = "Semua field wajib diisi dan lokasi harus dipilih di map!";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO lokasi_kantor (nama_lokasi, latitude, longitude, radius_meter)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param("sddi", $nama_lokasi, $latitude, $longitude, $radius_meter);

        if ($stmt->execute()) {
            header("Location: index.php?success=tambah");
            exit;
        } else {
            $error = "Gagal menyimpan lokasi: " . $conn->error;
        }

        $stmt->close();
    }
}
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Lokasi Kantor - Sistem Absensi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

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
                <h1>Tambah Lokasi Kantor</h1>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?php echo $_SESSION['nama']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="bi bi-x-circle-fill"></i>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-clipboard-plus"></i> Form Tambah Lokasi Kantor</h2>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label>Pilih Lokasi di Map *</label>
                                <div id="map" style="height: 450px; width: 100%; border-radius:8px;"></div>
                            </div>

                            <input type="hidden" name="latitude">
                            <input type="hidden" name="longitude">

                            <div class="form-group mb-3">
                                <label>Nama Lokasi *</label>
                                <input type="text" name="nama_lokasi" class="form-control" required>
                            </div>

                            <div class="form-group mb-3">
                                <label>Radius Absensi (Meter) *</label>
                                <input type="number" name="radius_meter" class="form-control" min="1" required>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        var map = L.map('map');
        var marker; // marker hasil klik
        var currentMarker; // marker lokasi saat ini

        L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Ambil lokasi device
        if (navigator.geolocation) {

            navigator.geolocation.getCurrentPosition(function(position) {

                var lat = position.coords.latitude;
                var lng = position.coords.longitude;

                map.setView([lat, lng], 16);

                // Marker lokasi saat ini
                currentMarker = L.marker([lat, lng]).addTo(map);
                document.querySelector("input[name='latitude']").value = lat;
                document.querySelector("input[name='longitude']").value = lng;

                setTimeout(function() {
                    map.invalidateSize();
                }, 300);

            }, function() {
                map.setView([-6.200000, 106.816666], 13);
            });

        } else {
            map.setView([-6.200000, 106.816666], 13);
        }

        // Klik untuk pilih lokasi baru
        map.on('click', function(e) {

            var latitude = e.latlng.lat;
            var longitude = e.latlng.lng;

            document.querySelector("input[name='latitude']").value = latitude;
            document.querySelector("input[name='longitude']").value = longitude;

            // Hapus marker lokasi saat ini jika ada
            if (currentMarker) {
                map.removeLayer(currentMarker);
                currentMarker = null;
            }

            // Hapus marker klik sebelumnya
            if (marker) {
                map.removeLayer(marker);
            }

            // Buat marker baru sesuai klik
            marker = L.marker([latitude, longitude]).addTo(map);
        });
    </script>
</body>

</html>