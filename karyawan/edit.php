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
$id_karyawan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_karyawan === 0) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$conn = getConnection();

// Ambil data karyawan
$stmt = $conn->prepare("SELECT * FROM karyawan WHERE id_karyawan = ?");
$stmt->bind_param("i", $id_karyawan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Data tidak ditemukan");
    exit;
}

$karyawan = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = sanitize($_POST['nip']);
    $nama = sanitize($_POST['nama']);

    if (empty($nip) || empty($nama)) {
        $error = 'NIP dan Nama harus diisi!';
    } else {
        // Cek apakah NIP sudah digunakan oleh karyawan lain
        $stmt = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE nip = ? AND id_karyawan != ?");
        $stmt->bind_param("si", $nip, $id_karyawan);
        $stmt->execute();
        $checkResult = $stmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = 'NIP sudah digunakan oleh karyawan lain!';
        } else {
            // Update data karyawan
            $stmt = $conn->prepare("UPDATE karyawan SET nip = ?, nama = ? WHERE id_karyawan = ?");
            $stmt->bind_param("ssi", $nip, $nama, $id_karyawan);

            if ($stmt->execute()) {
                header("Location: index.php?success=edit");
                exit;
            } else {
                $error = 'Gagal memperbarui data: ' . $conn->error;
            }
        }
    }
}

$stmt->close();
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan - Sistem Absensi</title>
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
                <h1>Edit Karyawan</h1>
                <div class="user-info">
                    <span><?php echo $_SESSION['nama']; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h2>Form Edit Karyawan</h2>
                        <a href="index.php" class="btn btn-secondary">← Kembali</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="nip">NIP <span class="required">*</span></label>
                                <input type="text" id="nip" name="nip" required placeholder="Contoh: EMP001" value="<?php echo htmlspecialchars($karyawan['nip']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="nama">Nama Lengkap <span class="required">*</span></label>
                                <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap" value="<?php echo htmlspecialchars($karyawan['nama']); ?>">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
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
</body>

</html>
