<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once 'config/koneksi.php';

$error = '';
$timeout = isset($_GET['timeout']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $conn = getConnection();

        $stmt = $conn->prepare("SELECT u.id_user, u.username, u.password, u.nama, u.id_role, r.nama_role 
    FROM users u
    JOIN data_roles r ON u.id_role = r.id_role
    WHERE u.username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['login'] = true;
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['id_role'] = $user['id_role'];
                $_SESSION['nama_role'] = $user['nama_role'];
                $_SESSION['last_activity'] = time();

                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username atau password salah!';
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
    <title>Login - Sistem Absensi Karyawan</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><i class="bi bi-building"></i> Dandang Cap Gajah</h1>
                <p>Sistem Absensi Karyawan</p>
            </div>

            <?php if ($timeout): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Session Anda telah berakhir. Silakan login kembali.</span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="bi bi-x-circle-fill"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username"><i class="bi bi-person-fill"></i> Username</label>
                    <input type="text" id="username" name="username" class="form-control" required placeholder="Masukkan username" autofocus>
                </div>

                <div class="form-group">
                    <label for="password"><i class="bi bi-lock-fill"></i> Password</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Masukkan password">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="bi bi-box-arrow-in-right"></i> Login
                </button>
            </form>

            <div class="login-footer">
                <p><i class="bi bi-info-circle-fill"></i> Username: <strong>admin</strong> | Password: <strong>admin123</strong></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>