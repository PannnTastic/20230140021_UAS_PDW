<?php
require_once '../config.php';

$pageTitle = 'Manajemen Mata Praktikum';
$activePage = 'mata_praktikum';
require_once 'templates/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_course'])) {
        $nama_praktikum = sanitize_input($_POST['nama_praktikum']);
        $deskripsi = sanitize_input($_POST['deskripsi']);
        
        $sql = "INSERT INTO mata_praktikum (nama_praktikum, deskripsi, asisten_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = "Mata praktikum berhasil ditambahkan";
        } else {
            $error_message = "Gagal menambahkan mata praktikum";
        }
    }
    
    if (isset($_POST['edit_course'])) {
        $id = intval($_POST['course_id']);
        $nama_praktikum = sanitize_input($_POST['nama_praktikum']);
        $deskripsi = sanitize_input($_POST['deskripsi']);
        
        $sql = "UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ? AND asisten_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nama_praktikum, $deskripsi, $id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = "Mata praktikum berhasil diupdate";
        } else {
            $error_message = "Gagal mengupdate mata praktikum";
        }
    }
    
    if (isset($_POST['delete_course'])) {
        $id = intval($_POST['course_id']);
        
        $sql = "DELETE FROM mata_praktikum WHERE id = ? AND asisten_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success_message = "Mata praktikum berhasil dihapus";
        } else {
            $error_message = "Gagal menghapus mata praktikum";
        }
    }
}

// Get courses managed by this asisten
$coursesSql = "SELECT mp.*, 
               (SELECT COUNT(*) FROM pendaftaran WHERE mata_praktikum_id = mp.id) as total_mahasiswa,
               (SELECT COUNT(*) FROM modul WHERE mata_praktikum_id = mp.id) as total_modul
               FROM mata_praktikum mp 
               WHERE mp.asisten_id = ? 
               ORDER BY mp.created_at DESC";
$coursesStmt = $conn->prepare($coursesSql);
$coursesStmt->bind_param("i", $_SESSION['user_id']);
$coursesStmt->execute();
$coursesResult = $coursesStmt->get_result();

// Get course data for editing
$editCourse = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editSql = "SELECT * FROM mata_praktikum WHERE id = ? AND asisten_id = ?";
    $editStmt = $conn->prepare($editSql);
    $editStmt->bind_param("ii", $editId, $_SESSION['user_id']);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    if ($editResult->num_rows > 0) {
        $editCourse = $editResult->fetch_assoc();
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
        <?php echo $editCourse ? 'Edit Mata Praktikum' : 'Tambah Mata Praktikum Baru'; ?>
    </h3>
    
    <form method="POST" class="space-y-4">
        <?php if ($editCourse): ?>
            <input type="hidden" name="course_id" value="<?php echo $editCourse['id']; ?>">
        <?php endif; ?>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Mata Praktikum</label>
            <input type="text" name="nama_praktikum" required 
                   value="<?php echo $editCourse ? htmlspecialchars($editCourse['nama_praktikum']) : ''; ?>"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
            <textarea name="deskripsi" rows="3" required 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $editCourse ? htmlspecialchars($editCourse['deskripsi']) : ''; ?></textarea>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" name="<?php echo $editCourse ? 'edit_course' : 'add_course'; ?>" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <?php echo $editCourse ? 'Update' : 'Tambah'; ?>
            </button>
            
            <?php if ($editCourse): ?>
                <a href="mata_praktikum.php" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition-colors">
                    Batal
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Mata Praktikum</h3>
    
    <?php if ($coursesResult->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Nama Praktikum</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Deskripsi</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Mahasiswa</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Modul</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($course = $coursesResult->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($course['nama_praktikum']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo htmlspecialchars(substr($course['deskripsi'], 0, 100)); ?>
                                <?php if (strlen($course['deskripsi']) > 100) echo '...'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                    <?php echo $course['total_mahasiswa']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                    <?php echo $course['total_modul']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="modul.php?course_id=<?php echo $course['id']; ?>" 
                                       class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">
                                        Modul
                                    </a>
                                    <a href="mata_praktikum.php?edit=<?php echo $course['id']; ?>" 
                                       class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                        Edit
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus mata praktikum ini?')">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" name="delete_course" 
                                                class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-8 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253z"></path>
            </svg>
            <p>Belum ada mata praktikum</p>
            <p class="text-sm mt-2">Tambah mata praktikum baru menggunakan form di atas</p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer.php';
?>
