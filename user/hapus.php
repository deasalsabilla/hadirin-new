<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

/* Ambil ID user */
$id_user = isset($_GET['id']) ? (int) $_GET['id'] : 0;

/* Validasi ID */
if ($id_user === 0) {
    header("Location: index.php?error=ID user tidak valid");
    exit;
}

$conn = getConnection();

/* (Opsional) Cegah hapus user yang sedang login */
if ($_SESSION['id_user'] == $id_user) {
    header("Location: index.php?error=Tidak bisa menghapus akun sendiri");
    exit;
}

/* Hapus data user */
$stmt = $conn->prepare("DELETE FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);

if ($stmt->execute()) {
    header("Location: index.php?success=hapus");
} else {
    header("Location: index.php?error=" . urlencode("Gagal menghapus data user"));
}

$stmt->close();
closeConnection($conn);
exit;
?>






