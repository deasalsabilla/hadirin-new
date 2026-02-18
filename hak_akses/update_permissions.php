<?php
require_once '../config/session.php';
require_once '../config/koneksi.php';

$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role_id = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;
    $permissions = $_POST['permissions'] ?? [];

    if ($role_id <= 0) {
        header('Location: index.php?error=Role tidak valid');
        exit;
    }

    // Hapus semua hak akses untuk role ini
    $stmt_delete = $conn->prepare("DELETE FROM hak_akses WHERE id_role = ?");
    $stmt_delete->bind_param("i", $role_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Query insert sesuai nama kolom di tabel
    $sql_insert = "INSERT INTO hak_akses 
        (id_role, id_menu, can_view, can_add, can_edit, can_delete) 
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt_insert = $conn->prepare($sql_insert);

    if (!$stmt_insert) {
        die("Prepare failed: " . $conn->error);
    }

    foreach ($permissions as $menu_id => $perms) {

        $menu_id = (int) $menu_id;

        $can_view   = isset($perms['view']) ? 1 : 0;
        $can_add    = isset($perms['add']) ? 1 : 0;
        $can_edit   = isset($perms['edit']) ? 1 : 0;
        $can_delete = isset($perms['delete']) ? 1 : 0;

        $stmt_insert->bind_param(
            "iiiiii",
            $role_id,
            $menu_id,
            $can_view,
            $can_add,
            $can_edit,
            $can_delete
        );

        $stmt_insert->execute();
    }

    $stmt_insert->close();

    header('Location: index.php?role=' . $role_id . '&success=edit');
    exit;
}

closeConnection($conn);
?>
