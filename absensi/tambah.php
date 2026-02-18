<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

// Ambil data karyawan untuk dropdown
$karyawanResult = $conn->query("SELECT id_karyawan, nip, nama FROM karyawan ORDER BY nama ASC");

// Ambil data menu
$resultMenu = $conn->query("SELECT nama_menu, icon_menu, link_menu FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan = (int)$_POST['id_karyawan'];
    $tanggal = sanitize($_POST['tanggal']);
    $status = sanitize($_POST['status']);
    $keterangan = sanitize($_POST['keterangan']);

    if ($id_karyawan === 0 || empty($tanggal) || empty($status)) {
        $error = 'Nama karyawan, tanggal, dan status harus diisi!';
    } else {
        // Cek apakah sudah ada absensi untuk karyawan ini di tanggal yang sama
        $stmt = $conn->prepare("SELECT id_absensi FROM absensi WHERE id_karyawan = ? AND tanggal = ?");
        $stmt->bind_param("is", $id_karyawan, $tanggal);
        $stmt->execute();
        $checkResult = $stmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = 'Absensi untuk karyawan ini pada tanggal tersebut sudah ada!';
        } else {
            // Insert data absensi
            $stmt = $conn->prepare("INSERT INTO absensi (id_karyawan, tanggal, status, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $id_karyawan, $tanggal, $status, $keterangan);

            if ($stmt->execute()) {
                header("Location: index.php?success=tambah");
                exit;
            } else {
                $error = 'Gagal menambahkan data: ' . $conn->error;
            }
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
    <title>Tambah Absensi - Sistem Absensi</title>
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
                <h1>Tambah Absensi</h1>
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
                        <h2><i class="bi bi-clipboard-plus"></i> Form Tambah Absensi</h2>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="id_karyawan"><i class="bi bi-person"></i> Nama Karyawan <span class="required">*</span></label>
                                <select id="id_karyawan" name="id_karyawan" class="form-control" required>
                                    <option value="">-- Pilih Karyawan --</option>
                                    <?php while ($karyawan = $karyawanResult->fetch_assoc()): ?>
                                        <option value="<?php echo $karyawan['id_karyawan']; ?>" <?php echo (isset($_POST['id_karyawan']) && $_POST['id_karyawan'] == $karyawan['id_karyawan']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($karyawan['nama']) . ' (' . htmlspecialchars($karyawan['nip']) . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="tanggal"><i class="bi bi-calendar"></i> Tanggal <span class="required">*</span></label>
                                <input type="date" id="tanggal" name="tanggal" class="form-control" required value="<?php echo isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="status"><i class="bi bi-tag"></i> Status Kehadiran <span class="required">*</span></label>
                                <select id="status" name="status" class="form-control" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Hadir" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                                    <option value="Izin" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Izin') ? 'selected' : ''; ?>>Izin</option>
                                    <option value="Sakit" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                                    <option value="Alpha" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Alpha') ? 'selected' : ''; ?>>Alpha</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="keterangan"><i class="bi bi-chat-left-text"></i> Keterangan</label>
                                <textarea id="keterangan" name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)"><?php echo isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
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
</body>

</html>