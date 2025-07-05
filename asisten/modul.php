<?php
require_once '../config.php';

$pageTitle = 'Manajemen Modul';
$activePage = 'modul';
require_once 'templates/header.php';

// Get course_id from URL parameter
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
if ($course_id === 0) {
    // Get all courses for this asisten
    $userCoursesSql = "SELECT * FROM mata_praktikum WHERE asisten_id = ? ORDER BY nama_praktikum ASC";
    $userCoursesStmt = $conn->prepare($userCoursesSql);
    $userCoursesStmt->bind_param("i", $_SESSION['user_id']);
    $userCoursesStmt->execute();
    $userCoursesResult = $userCoursesStmt->get_result();
    ?>
    
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Pilih Mata Praktikum</h2>
        <p class="text-gray-600 mb-6">Pilih mata praktikum untuk mengelola modul</p>
    </div>
    
    <?php if ($userCoursesResult->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($course = $userCoursesResult->fetch_assoc()): ?>
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">
                        <?php echo htmlspecialchars($course['nama_praktikum']); ?>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo htmlspecialchars(substr($course['deskripsi'], 0, 100)); ?>
                        <?php if (strlen($course['deskripsi']) > 100) echo '...'; ?>
                    </p>
                    <div class="flex justify-between items-center">
                        
                        <a href="modul.php?course_id=<?php echo $course['id']; ?>" 
                           class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Kelola Modul
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-16 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Mata Praktikum</h3>
            <p class="mb-4">Anda belum memiliki mata praktikum untuk dikelola</p>
            <a href="mata_praktikum.php" 
               class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                Buat Mata Praktikum
            </a>
        </div>
    <?php endif; ?>
    
    <?php
    require_once 'templates/footer.php';
    exit;
}
// Debug: Check values
echo "Course ID: " . $course_id . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";

// Verify course ownership
$courseSql = "SELECT * FROM mata_praktikum WHERE id = ? AND asisten_id = ?";
$courseStmt = $conn->prepare($courseSql);
$courseStmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$courseStmt->execute();
$courseResult = $courseStmt->get_result();

if ($courseResult->num_rows === 0) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>Mata praktikum tidak ditemukan atau Anda tidak memiliki akses</div>";
    exit;
}

$courseData = $courseResult->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_module'])) {
        $judul = sanitize_input($_POST['judul']);
        $deskripsi = sanitize_input($_POST['deskripsi']);
        $urutan = intval($_POST['urutan']);
        
        $file_materi = null;
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === 0) {
            $uploadResult = uploadFile($_FILES['file_materi'], '../uploads/materi/', ['pdf', 'docx', 'doc', 'pptx', 'ppt']);
            if ($uploadResult['success']) {
                $file_materi = $uploadResult['filename'];
            } else {
                $error_message = "Gagal mengupload file: " . $uploadResult['message'];
            }
        }
        
        if (!isset($error_message)) {
            $sql = "INSERT INTO modul (mata_praktikum_id, judul, deskripsi, file_materi, urutan) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssi", $course_id, $judul, $deskripsi, $file_materi, $urutan);
            
            if ($stmt->execute()) {
                $success_message = "Modul berhasil ditambahkan";
            } else {
                $error_message = "Gagal menambahkan modul";
            }
        }
    }
    
    if (isset($_POST['edit_module'])) {
        $id = intval($_POST['module_id']);
        $judul = sanitize_input($_POST['judul']);
        $deskripsi = sanitize_input($_POST['deskripsi']);
        $urutan = intval($_POST['urutan']);
        
        $file_materi = $_POST['existing_file'];
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === 0) {
            $uploadResult = uploadFile($_FILES['file_materi'], '../uploads/materi/', ['pdf', 'docx', 'doc', 'pptx', 'ppt']);
            if ($uploadResult['success']) {
                // Delete old file if exists
                if ($file_materi && file_exists('../uploads/materi/' . $file_materi)) {
                    unlink('../uploads/materi/' . $file_materi);
                }
                $file_materi = $uploadResult['filename'];
            } else {
                $error_message = "Gagal mengupload file: " . $uploadResult['message'];
            }
        }
        
        if (!isset($error_message)) {
            $sql = "UPDATE modul SET judul = ?, deskripsi = ?, file_materi = ?, urutan = ? WHERE id = ? AND mata_praktikum_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiii", $judul, $deskripsi, $file_materi, $urutan, $id, $course_id);
            
            if ($stmt->execute()) {
                $success_message = "Modul berhasil diupdate";
            } else {
                $error_message = "Gagal mengupdate modul";
            }
        }
    }
    
    if (isset($_POST['delete_module'])) {
        $id = intval($_POST['module_id']);
        
        // Get file name to delete
        $fileSql = "SELECT file_materi FROM modul WHERE id = ? AND mata_praktikum_id = ?";
        $fileStmt = $conn->prepare($fileSql);
        $fileStmt->bind_param("ii", $id, $course_id);
        $fileStmt->execute();
        $fileResult = $fileStmt->get_result();
        
        if ($fileResult->num_rows > 0) {
            $fileData = $fileResult->fetch_assoc();
            
            $sql = "DELETE FROM modul WHERE id = ? AND mata_praktikum_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $course_id);
            
            if ($stmt->execute()) {
                // Delete file if exists
                if ($fileData['file_materi'] && file_exists('../uploads/materi/' . $fileData['file_materi'])) {
                    unlink('../uploads/materi/' . $fileData['file_materi']);
                }
                $success_message = "Modul berhasil dihapus";
            } else {
                $error_message = "Gagal menghapus modul";
            }
        }
    }
}

// Get modules for this course
$modulesSql = "SELECT * FROM modul WHERE mata_praktikum_id = ? ORDER BY urutan ASC";
$modulesStmt = $conn->prepare($modulesSql);
$modulesStmt->bind_param("i", $course_id);
$modulesStmt->execute();
$modulesResult = $modulesStmt->get_result();

// Get module data for editing
$editModule = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editSql = "SELECT * FROM modul WHERE id = ? AND mata_praktikum_id = ?";
    $editStmt = $conn->prepare($editSql);
    $editStmt->bind_param("ii", $editId, $course_id);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    if ($editResult->num_rows > 0) {
        $editModule = $editResult->fetch_assoc();
    }
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Manajemen Modul</h2>
            <p class="text-gray-600">Mata Praktikum: <?php echo htmlspecialchars($courseData['nama_praktikum']); ?></p>
        </div>
        <a href="mata_praktikum.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">
            Kembali
        </a>
    </div>
</div>

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
    <h3 class="text-lg font-bold text-gray-800 mb-4">
        <?php echo $editModule ? 'Edit Modul' : 'Tambah Modul Baru'; ?>
    </h3>
    
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <?php if ($editModule): ?>
            <input type="hidden" name="module_id" value="<?php echo $editModule['id']; ?>">
            <input type="hidden" name="existing_file" value="<?php echo htmlspecialchars($editModule['file_materi']); ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Judul Modul</label>
                <input type="text" name="judul" required 
                       value="<?php echo $editModule ? htmlspecialchars($editModule['judul']) : ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Urutan</label>
                <input type="number" name="urutan" required min="1" 
                       value="<?php echo $editModule ? $editModule['urutan'] : ''; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
            <textarea name="deskripsi" rows="3" required 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $editModule ? htmlspecialchars($editModule['deskripsi']) : ''; ?></textarea>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">File Materi (Optional)</label>
            <input type="file" name="file_materi" accept=".pdf,.doc,.docx,.ppt,.pptx" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-sm text-gray-500 mt-1">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX</p>
            
            <?php if ($editModule && $editModule['file_materi']): ?>
                <p class="text-sm text-gray-600 mt-2">
                    File saat ini: <?php echo htmlspecialchars($editModule['file_materi']); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" name="<?php echo $editModule ? 'edit_module' : 'add_module'; ?>" 
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                <?php echo $editModule ? 'Update' : 'Tambah'; ?>
            </button>
            
            <?php if ($editModule): ?>
                <a href="modul.php?course_id=<?php echo $course_id; ?>" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition-colors">
                    Batal
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Daftar Modul</h3>
    
    <?php if ($modulesResult->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Urutan</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Judul</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Deskripsi</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Materi</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($module = $modulesResult->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full font-medium">
                                    <?php echo $module['urutan']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($module['judul']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo htmlspecialchars(substr($module['deskripsi'], 0, 100)); ?>
                                <?php if (strlen($module['deskripsi']) > 100) echo '...'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <?php if ($module['file_materi']): ?>
                                    <a href="../uploads/materi/<?php echo htmlspecialchars($module['file_materi']); ?>" 
                                       class="text-blue-600 hover:text-blue-800" download>
                                        Download
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="modul.php?course_id=<?php echo $course_id; ?>&edit=<?php echo $module['id']; ?>" 
                                       class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                                        Edit
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus modul ini?')">
                                        <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                        <button type="submit" name="delete_module" 
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
            <p>Belum ada modul</p>
            <p class="text-sm mt-2">Tambah modul baru menggunakan form di atas</p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer.php';
?>
