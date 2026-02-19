<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();
$error = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_menu = sanitize($_POST['nama_menu']);
    $icon_menu = sanitize($_POST['icon_menu']);
    $link_menu = sanitize($_POST['link_menu']);
    $urutan    = (int) $_POST['urutan'];
    $status    = sanitize($_POST['status']);

    if (empty($nama_menu) || empty($icon_menu) || empty($link_menu) || empty($urutan)) {
        $error = 'Semua field wajib diisi!';
    } else {
        $conn = getConnection();

        // Cek apakah nama menu sudah ada
        $stmt = $conn->prepare("SELECT id_menu FROM data_menu WHERE nama_menu = ?");
        $stmt->bind_param("s", $nama_menu);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Nama menu sudah terdaftar!';
        } else {
            // Insert data menu
            $stmt = $conn->prepare("
                INSERT INTO data_menu (nama_menu, icon_menu, link_menu, urutan, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssis", $nama_menu, $icon_menu, $link_menu, $urutan, $status);

            if ($stmt->execute()) {
                header("Location: index.php?success=tambah");
                exit;
            } else {
                $error = 'Gagal menambahkan data menu!';
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
    <title>Tambah Menu - Sistem Absensi</title>
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
                <h1>Tambah Menu</h1>
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
                        <h2><i class="bi bi-person-plus"></i> Form Tambah Menu</h2>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="nama_menu">
                                    <i class="bi bi-list-ul"></i> Nama Menu <span class="required">*</span>
                                </label>
                                <input type="text" id="nama_menu" name="nama_menu" class="form-control"
                                    required placeholder="Contoh: Data Menu"
                                    value="<?= isset($_POST['nama_menu']) ? htmlspecialchars($_POST['nama_menu']) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="icon_menu">
                                    <i class="bi bi-bootstrap"></i> Icon Menu <span class="required">*</span>
                                </label>
                                <input type="text" id="icon_menu" name="icon_menu" class="form-control"
                                    required placeholder="Contoh: bi bi-list-ul"
                                    value="<?= isset($_POST['icon_menu']) ? htmlspecialchars($_POST['icon_menu']) : ''; ?>">
                                <small class="text-muted">Gunakan Bootstrap Icons (bi bi-...)</small>
                            </div>

                            <div class="form-group mb-3">
                                <label for="link_menu">
                                    <i class="bi bi-link-45deg"></i> Link Menu <span class="required">*</span>
                                </label>
                                <input type="text" id="link_menu" name="link_menu" class="form-control"
                                    required placeholder="Contoh: menu/index.php"
                                    value="<?= isset($_POST['link_menu']) ? htmlspecialchars($_POST['link_menu']) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="urutan">
                                    <i class="bi bi-sort-numeric-down"></i> Urutan <span class="required">*</span>
                                </label>
                                <input type="number" id="urutan" name="urutan" class="form-control"
                                    required placeholder="Contoh: 1"
                                    value="<?= isset($_POST['urutan']) ? htmlspecialchars($_POST['urutan']) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="status">
                                    <i class="bi bi-toggle-on"></i> Status
                                </label>
                                <select name="status" id="status" class="form-control">
                                    <option value="aktif" <?= (isset($_POST['status']) && $_POST['status'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="nonaktif" <?= (isset($_POST['status']) && $_POST['status'] == 'nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
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