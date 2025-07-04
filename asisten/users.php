<?php
require_once '../config.php';

$pageTitle = 'Manajemen Pengguna';
$activePage = 'users';
require_once 'templates/header.php';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $nama = sanitize_input($_POST['nama']);
        $email = sanitize_input($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        
        $checkEmailSql = "SELECT id FROM users WHERE email = ?";
        $checkEmailStmt = $conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        
        if ($checkEmailStmt->get_result()->num_rows > 0) {
            $error_message = "Email sudah terdaftar";
        } else {
            $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nama, $email, $password, $role);
            
            if ($stmt->execute()) {
                $success_message = "Pengguna berhasil ditambahkan";
            } else {
                $error_message = "Gagal menambahkan pengguna";
            }
        }
    }
    
    if (isset($_POST['edit_user'])) {
        $id = intval($_POST['user_id']);
        $nama = sanitize_input($_POST['nama']);
        $email = sanitize_input($_POST['email']);
        $role = $_POST['role'];
        
        // Check if email is already used by another user
        $checkEmailSql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $checkEmailStmt = $conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param("si", $email, $id);
        $checkEmailStmt->execute();
        
        if ($checkEmailStmt->get_result()->num_rows > 0) {
            $error_message = "Email sudah digunakan oleh pengguna lain";
        } else {
            $sql = "UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nama, $email, $role, $id);
            
            if ($stmt->execute()) {
                $success_message = "Pengguna berhasil diupdate";
            } else {
                $error_message = "Gagal mengupdate pengguna";
            }
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $id = intval($_POST['user_id']);
        
        // Don't allow deletion of current user
        if ($id === $_SESSION['user_id']) {
            $error_message = "Tidak dapat menghapus akun sendiri";
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Pengguna berhasil dihapus";
            } else {
                $error_message = "Gagal menghapus pengguna";
            }
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $id = intval($_POST['user_id']);
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_password, $id);
        
        if ($stmt->execute()) {
            $success_message = "Password berhasil direset";
        } else {
            $error_message = "Gagal mereset password";
        }
    }
}

// Get all users
$usersSql = "SELECT u.*, 
             (SELECT COUNT(*) FROM mata_praktikum WHERE asisten_id = u.id) as managed_courses,
             (SELECT COUNT(*) FROM pendaftaran WHERE mahasiswa_id = u.id) as enrolled_courses
             FROM users u 
             ORDER BY u.role ASC, u.nama ASC";
$usersResult = $conn->query($usersSql);

// Get user data for editing
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editSql = "SELECT * FROM users WHERE id = ?";
    $editStmt = $conn->prepare($editSql);
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    if ($editResult->num_rows > 0) {
        $editUser = $editResult->fetch_assoc();
    }
}

// Get user data for password reset
$resetUser = null;
if (isset($_GET['reset'])) {
    $resetId = intval($_GET['reset']);
    $resetSql = "SELECT * FROM users WHERE id = ?";
    $resetStmt = $conn->prepare($resetSql);
    $resetStmt->bind_param("i", $resetId);
    $resetStmt->execute();
    $resetResult = $resetStmt->get_result();
    if ($resetResult->num_rows > 0) {
        $resetUser = $resetResult->fetch_assoc();
    }
}
?>

<?php if (isset($success_message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">
        <?php echo $editUser ? 'Edit Pengguna' : 'Tambah Pengguna Baru'; ?>
    </h3>
    
    <form method="POST" class="space-y-4">
        <?php if ($editUser): ?>
            <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                <input type="text" name="nama" required 
                       value="<?php echo $editUser ? htmlspecialchars($editUser['nama']) : ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required 
                       value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="mahasiswa" <?php echo ($editUser && $editUser['role'] === 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo ($editUser && $editUser['role'] === 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
            </div>
            
            <?php if (!$editUser): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" name="<?php echo $editUser ? 'edit_user' : 'add_user'; ?>" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <?php echo $editUser ? 'Update' : 'Tambah'; ?>
            </button>
            
            <?php if ($editUser): ?>
                <a href="users.php" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition-colors">
                    Batal
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Pengguna</h3>
    
    <div class="overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Nama</th>
                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Email</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Role</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Statistik</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Terdaftar</th>
                    <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php while ($user = $usersResult->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($user['nama']); ?>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">You</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                <?php echo $user['role'] === 'asisten' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <?php if ($user['role'] === 'asisten'): ?>
                                <span class="text-xs text-gray-600">
                                    <?php echo $user['managed_courses']; ?> Praktikum
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-gray-600">
                                    <?php echo $user['enrolled_courses']; ?> Terdaftar
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center text-gray-600">
                            <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <div class="flex justify-center space-x-2">
                                <a href="users.php?edit=<?php echo $user['id']; ?>" 
                                   class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                    Edit
                                </a>
                                <a href="users.php?reset=<?php echo $user['id']; ?>" 
                                   class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700">
                                    Reset Password
                                </a>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" 
                                                class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">
                                            Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Password Reset Modal -->
<?php if ($resetUser): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Reset Password</h3>
            
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Pengguna: <span class="font-medium"><?php echo htmlspecialchars($resetUser['nama']); ?></span></p>
                <p class="text-sm text-gray-600">Email: <span class="font-medium"><?php echo htmlspecialchars($resetUser['email']); ?></span></p>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="user_id" value="<?php echo $resetUser['id']; ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                    <input type="password" name="new_password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex gap-4 justify-end">
                    <a href="users.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">
                        Batal
                    </a>
                    <button type="submit" name="reset_password" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once 'templates/footer.php';
?>
