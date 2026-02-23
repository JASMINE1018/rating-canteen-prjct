<?php
require 'database.php';

$db = new Database();
$conn = $db->koneksi;

if (isset($_POST['simpan'])) {

    $id        = $_POST['id'];
    $username  = $_POST['username'];
    $email     = $_POST['email'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $deskripsi = $_POST['deskripsi'];

    $stmt = mysqli_prepare($conn, 
        "INSERT INTO akun_data (id, username, email, password, deskripsi) 
         VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, "issss", $id, $username, $email, $password, $deskripsi);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Data berhasil ditambahkan!');
                window.location='form.html';
              </script>";
    } else {
        echo "Gagal menambahkan data (ID mungkin sudah ada).";
    }
}
?>
