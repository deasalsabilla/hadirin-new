<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$error = '';
$id_absensi = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_absensi === 0) {
    header("Location: index.php?error=ID tidak valid");
    exit;
}

$conn = getConnection();

// Ambil data karyawan untuk dropdown
$karyawanResult = $conn->query("SELECT id_karyawan, nip, nama FROM karyawan ORDER BY nama ASC");

// Ambil data menu
$resultMenu = $conn->query("SELECT nama_menu, icon_menu, link_menu FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC");

// Ambil data absensi
$stmt = $conn->prepare("SELECT * FROM absensi WHERE id_absensi = ?");
$stmt->bind_param("i", $id_absensi);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=Data tidak ditemukan");
    exit;
}

$absensi = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan = (int)$_POST['id_karyawan'];
    $tanggal = sanitize($_POST['tanggal']);
    $status = sanitize($_POST['status']);
    $keterangan = sanitize($_POST['keterangan']);

    if ($id_karyawan === 0 || empty($tanggal) || empty($status)) {
        $error = 'Nama karyawan, tanggal, dan status harus diisi!';
    } else {
        // Cek apakah ada absensi lain dengan karyawan dan tanggal yang sama
        $stmt = $conn->prepare("SELECT id_absensi FROM absensi WHERE id_karyawan = ? AND tanggal = ? AND id_absensi != ?");
        $stmt->bind_param("isi", $id_karyawan, $tanggal, $id_absensi);
        $stmt->execute();
        $checkResult = $stmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = 'Absensi untuk karyawan ini pada tanggal tersebut sudah ada!';
        } else {
            // Update data absensi
            $stmt = $conn->prepare("UPDATE absensi SET id_karyawan = ?, tanggal = ?, status = ?, keterangan = ? WHERE id_absensi = ?");
            $stmt->bind_param("isssi", $id_karyawan, $tanggal, $status, $keterangan, $id_absensi);

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
    <title>Edit Absensi - Sistem Absensi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Dandang Cap Gajah</h2>
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
                <h1>Edit Absensi</h1>
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
                        <h2>Form Edit Absensi</h2>
                        <a href="index.php" class="btn btn-secondary">← Kembali</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="id_karyawan">Nama Karyawan <span class="required">*</span></label>
                                <select id="id_karyawan" name="id_karyawan" required>
                                    <option value="">-- Pilih Karyawan --</option>
                                    <?php while ($karyawan = $karyawanResult->fetch_assoc()): ?>
                                        <option value="<?php echo $karyawan['id_karyawan']; ?>" <?php echo ($absensi['id_karyawan'] == $karyawan['id_karyawan']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($karyawan['nama']) . ' (' . htmlspecialchars($karyawan['nip']) . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="tanggal">Tanggal <span class="required">*</span></label>
                                <input type="date" id="tanggal" name="tanggal" required value="<?php echo htmlspecialchars($absensi['tanggal']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="status">Status Kehadiran <span class="required">*</span></label>
                                <select id="status" name="status" required>
                                    <option value="">-- Pilih Status --</option>
                                    <option value="Hadir" <?php echo ($absensi['status'] === 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                                    <option value="Izin" <?php echo ($absensi['status'] === 'Izin') ? 'selected' : ''; ?>>Izin</option>
                                    <option value="Sakit" <?php echo ($absensi['status'] === 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                                    <option value="Alpha" <?php echo ($absensi['status'] === 'Alpha') ? 'selected' : ''; ?>>Alpha</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="keterangan">Keterangan</label>
                                <textarea id="keterangan" name="keterangan" rows="3" placeholder="Keterangan tambahan (opsional)"><?php echo htmlspecialchars($absensi['keterangan']); ?></textarea>
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
</body>

</html>