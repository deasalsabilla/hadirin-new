<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

// Ambil data menu
$resultMenu = $conn->query("SELECT nama_menu, icon_menu, link_menu FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Tidak sanitize password karena akan di-hash
    $nama     = sanitize($_POST['nama']);
    $id_role  = sanitize($_POST['id_role']);

    if (empty($username) || empty($password) || empty($id_role)) {
        $error = 'Username, password, dan role wajib diisi!';
    } else {
        $conn = getConnection();

        // Cek apakah username sudah ada
        $stmt = $conn->prepare("SELECT id_user FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert data user
            $stmt = $conn->prepare("
                INSERT INTO users (username, password, nama, id_role)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("sssi", $username, $hashed_password, $nama, $id_role);

            if ($stmt->execute()) {
                header("Location: index.php?success=tambah");
                exit;
            } else {
                $error = 'Gagal menambahkan data user!';
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
    <title>Tambah User - Sistem Absensi</title>
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
                <h1>Tambah User</h1>
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
                                <label for="username">
                                    <i class="bi bi-person-fill"></i> Username <span class="required">*</span>
                                </label>
                                <input type="text" id="username" name="username" class="form-control"
                                    required placeholder="Masukkan username"
                                    value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="password">
                                    <i class="bi bi-lock-fill"></i> Password <span class="required">*</span>
                                </label>
                                <input type="password" id="password" name="password" class="form-control"
                                    required placeholder="Masukkan password">
                            </div>

                            <div class="form-group mb-3">
                                <label for="nama">
                                    <i class="bi bi-person-vcard"></i> Nama Lengkap
                                </label>
                                <input type="text" id="nama" name="nama" class="form-control"
                                    placeholder="Masukkan nama lengkap"
                                    value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label for="id_role">
                                    <i class="bi bi-shield-lock-fill"></i> Role <span class="required">*</span>
                                </label>
                                <select name="id_role" id="id_role" class="form-control" required>
                                    <option value="">Pilih Role</option>
                                    <?php
                                    $conn = getConnection();
                                    $stmt = $conn->prepare("SELECT id_role, nama_role FROM data_roles WHERE status = 'aktif' ORDER BY nama_role ASC");
                                    $stmt->execute();
                                    $roles = $stmt->get_result();
                                    while ($role = $roles->fetch_assoc()) {
                                        $selected = (isset($_POST['id_role']) && $_POST['id_role'] == $role['id_role']) ? 'selected' : '';
                                        echo "<option value='{$role['id_role']}' {$selected}>{$role['nama_role']}</option>";
                                    }
                                    $stmt->close();
                                    closeConnection($conn);
                                    ?>
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