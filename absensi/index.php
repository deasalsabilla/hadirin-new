<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

// Ambil data menu
$resultMenu = $conn->query("SELECT nama_menu, icon_menu, link_menu FROM data_menu WHERE status = 'aktif' ORDER BY urutan ASC");

// Filter
$filterTanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Query data absensi
$sql = "SELECT a.*, k.nip, k.nama 
        FROM absensi a 
        JOIN karyawan k ON a.id_karyawan = k.id_karyawan 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($filterTanggal)) {
    $sql .= " AND a.tanggal = ?";
    $params[] = $filterTanggal;
    $types .= "s";
}

if (!empty($filterStatus)) {
    $sql .= " AND a.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

$sql .= " ORDER BY a.tanggal DESC, k.nama ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

$stmt->close();
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Absensi - Sistem Absensi</title>
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
                <h1>Data Absensi</h1>
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
                            if ($success === 'tambah') echo 'Data absensi berhasil ditambahkan!';
                            elseif ($success === 'edit') echo 'Data absensi berhasil diperbarui!';
                            elseif ($success === 'hapus') echo 'Data absensi berhasil dihapus!';
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

                <!-- Filter -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-funnel"></i> Filter Data</h2>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="filter-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tanggal"><i class="bi bi-calendar"></i> Tanggal</label>
                                    <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo htmlspecialchars($filterTanggal); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="status"><i class="bi bi-tag"></i> Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="Hadir" <?php echo $filterStatus === 'Hadir' ? 'selected' : ''; ?>>Hadir</option>
                                        <option value="Izin" <?php echo $filterStatus === 'Izin' ? 'selected' : ''; ?>>Izin</option>
                                        <option value="Sakit" <?php echo $filterStatus === 'Sakit' ? 'selected' : ''; ?>>Sakit</option>
                                        <option value="Alpha" <?php echo $filterStatus === 'Alpha' ? 'selected' : ''; ?>>Alpha</option>
                                    </select>
                                </div>
                                <div class="form-group filter-buttons">
                                    <label>&nbsp;</label>
                                    <div class="button-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search"></i> Filter
                                        </button>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-table"></i> Daftar Absensi</h2>
                        <a href="tambah.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Tambah Absensi
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Karyawan</th>
                                        <th>NIP</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result->num_rows > 0):
                                        $no = 1;
                                        while ($row = $result->fetch_assoc()):
                                            $statusClass = 'status-' . strtolower($row['status']);
                                            $statusIcon = '';
                                            switch ($row['status']) {
                                                case 'Hadir':
                                                    $statusIcon = 'bi-check-circle-fill';
                                                    break;
                                                case 'Izin':
                                                    $statusIcon = 'bi-file-earmark-text';
                                                    break;
                                                case 'Sakit':
                                                    $statusIcon = 'bi-heart-pulse';
                                                    break;
                                                case 'Alpha':
                                                    $statusIcon = 'bi-x-circle-fill';
                                                    break;
                                            }
                                    ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nip']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <i class="bi <?php echo $statusIcon; ?>"></i>
                                                        <?php echo htmlspecialchars($row['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['keterangan'] ?: '-'); ?></td>
                                                <td class="text-center">
                                                    <div class="action-buttons">
                                                        <a href="edit.php?id=<?php echo $row['id_absensi']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="bi bi-pencil-square"></i><span class="btn-text"> Edit</span>
                                                        </a>
                                                        <a href="hapus.php?id=<?php echo $row['id_absensi']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                            <i class="bi bi-trash"></i><span class="btn-text"> Hapus</span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="7" class="text-center">
                                                <i class="bi bi-inbox"></i> Belum ada data absensi
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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