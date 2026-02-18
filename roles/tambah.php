<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$error = '';

$conn = getConnection();

// Ambil data menu
$resultMenu = $conn->query("SELECT nama_menu, icon_menu, link_menu FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_role  = sanitize($_POST['nama_role']);
    $keterangan = sanitize($_POST['keterangan']);
    $status     = sanitize($_POST['status']);

    if (empty($nama_role)) {
        $error = 'Nama role wajib diisi!';
    } else {
        $conn = getConnection();

        // Cek apakah nama role sudah ada
        $stmt = $conn->prepare("SELECT id_role FROM data_roles WHERE nama_role = ?");
        $stmt->bind_param("s", $nama_role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Nama role sudah terdaftar!';
        } else {
            // Insert data role
            $stmt = $conn->prepare("
                INSERT INTO data_roles (nama_role, keterangan, status)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("sss", $nama_role, $keterangan, $status);

            if ($stmt->execute()) {
                header("Location: index.php?success=tambah");
                exit;
            } else {
                $error = 'Gagal menambahkan data role!';
            }
        }

        $stmt->close();
        closeConnection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Roles - Sistem Absensi</title>
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
                <h1>Tambah Roles</h1>
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
                        <h2><i class="bi bi-person-plus"></i> Form Tambah Roles</h2>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="nama_role">
                                    <i class="bi bi-shield-lock-fill"></i> Nama Role <span class="required">*</span>
                                </label>
                                <input type="text" id="nama_role" name="nama_role" class="form-control"
                                    required placeholder="Contoh: Admin"
                                    value="<?= isset($_POST['nama_role']) ? htmlspecialchars($_POST['nama_role']) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="keterangan">
                                    <i class="bi bi-card-text"></i> Keterangan
                                </label>
                                <textarea id="keterangan" name="keterangan" class="form-control" rows="3"
                                    placeholder="Contoh: Akses penuh ke sistem"><?= isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : ''; ?></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="status">
                                    <i class="bi bi-toggle-on"></i> Status
                                </label>
                                <select name="status" id="status" class="form-control">
                                    <option value="aktif" <?= (isset($_POST['status']) && $_POST['status'] == 'aktif') ? 'selected' : ''; ?>>
                                        Aktif
                                    </option>
                                    <option value="nonaktif" <?= (isset($_POST['status']) && $_POST['status'] == 'nonaktif') ? 'selected' : ''; ?>>
                                        Nonaktif
                                    </option>
                                </select>
                            </div>

                            <div class="form-actions mt-4">
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