<?php
require_once '../config.php';

$pageTitle = 'Laporan Masuk';
$activePage = 'laporan';
require_once 'templates/header.php';

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_report'])) {
    $laporan_id = intval($_POST['laporan_id']);
    $nilai = floatval($_POST['nilai']);
    $feedback = sanitize_input($_POST['feedback']);
    
    $sql = "UPDATE laporan SET nilai = ?, feedback = ?, status = 'dinilai' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsi", $nilai, $feedback, $laporan_id);
    
    if ($stmt->execute()) {
        $success_message = "Laporan berhasil dinilai";
    } else {
        $error_message = "Gagal memberikan nilai";
    }
}

// Get filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_course = isset($_GET['course']) ? intval($_GET['course']) : 0;
$filter_mahasiswa = isset($_GET['mahasiswa']) ? sanitize_input($_GET['mahasiswa']) : '';

// Get courses managed by this asisten for filter
$coursesSql = "SELECT id, nama_praktikum FROM mata_praktikum WHERE asisten_id = ? ORDER BY nama_praktikum";
$coursesStmt = $conn->prepare($coursesSql);
$coursesStmt->bind_param("i", $_SESSION['user_id']);
$coursesStmt->execute();
$coursesResult = $coursesStmt->get_result();

// Build query with filters
$whereConditions = ["mp.asisten_id = ?"];
$params = [$_SESSION['user_id']];
$paramTypes = "i";

if ($filter_status) {
    $whereConditions[] = "l.status = ?";
    $params[] = $filter_status;
    $paramTypes .= "s";
}

if ($filter_course > 0) {
    $whereConditions[] = "mp.id = ?";
    $params[] = $filter_course;
    $paramTypes .= "i";
}

if ($filter_mahasiswa) {
    $whereConditions[] = "u.nama LIKE ?";
    $params[] = "%$filter_mahasiswa%";
    $paramTypes .= "s";
}

$whereClause = implode(" AND ", $whereConditions);

$reportsSql = "SELECT l.*, u.nama as mahasiswa_nama, u.email as mahasiswa_email,
                      m.judul as modul_judul, m.urutan as modul_urutan,
                      mp.nama_praktikum
               FROM laporan l
               JOIN users u ON l.mahasiswa_id = u.id
               JOIN modul m ON l.modul_id = m.id
               JOIN mata_praktikum mp ON m.mata_praktikum_id = mp.id
               WHERE $whereClause
               ORDER BY l.tanggal_upload DESC";

$reportsStmt = $conn->prepare($reportsSql);
$reportsStmt->bind_param($paramTypes, ...$params);
$reportsStmt->execute();
$reportsResult = $reportsStmt->get_result();

// Get report for grading modal
$gradingReport = null;
if (isset($_GET['grade'])) {
    $gradeId = intval($_GET['grade']);
    $gradingSql = "SELECT l.*, u.nama as mahasiswa_nama, u.email as mahasiswa_email,
                          m.judul as modul_judul, m.urutan as modul_urutan,
                          mp.nama_praktikum
                   FROM laporan l
                   JOIN users u ON l.mahasiswa_id = u.id
                   JOIN modul m ON l.modul_id = m.id
                   JOIN mata_praktikum mp ON m.mata_praktikum_id = mp.id
                   WHERE l.id = ? AND mp.asisten_id = ?";
    $gradingStmt = $conn->prepare($gradingSql);
    $gradingStmt->bind_param("ii", $gradeId, $_SESSION['user_id']);
    $gradingStmt->execute();
    $gradingResult = $gradingStmt->get_result();
    if ($gradingResult->num_rows > 0) {
        $gradingReport = $gradingResult->fetch_assoc();
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

<!-- Filters -->
<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Filter Laporan</h3>
    
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="dinilai" <?php echo $filter_status === 'dinilai' ? 'selected' : ''; ?>>Dinilai</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mata Praktikum</label>
            <select name="course" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="0">Semua Praktikum</option>
                <?php while ($course = $coursesResult->fetch_assoc()): ?>
                    <option value="<?php echo $course['id']; ?>" <?php echo $filter_course === $course['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['nama_praktikum']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Mahasiswa</label>
            <input type="text" name="mahasiswa" value="<?php echo htmlspecialchars($filter_mahasiswa); ?>" 
                   placeholder="Cari nama mahasiswa..." 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                    Filter
                </button>
                <a href="laporan.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Reports Table -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Daftar Laporan</h3>
    
    <?php if ($reportsResult->num_rows > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Mahasiswa</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Praktikum</th>
                        <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Modul</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Tanggal Upload</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Status</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Nilai</th>
                        <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($report = $reportsResult->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($report['mahasiswa_nama']); ?></div>
                                <div class="text-gray-500"><?php echo htmlspecialchars($report['mahasiswa_email']); ?></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php echo htmlspecialchars($report['nama_praktikum']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                Modul <?php echo $report['modul_urutan']; ?>: <?php echo htmlspecialchars($report['modul_judul']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-gray-900">
                                <?php echo date('d M Y H:i', strtotime($report['tanggal_upload'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    <?php echo $report['status'] === 'dinilai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo $report['status'] === 'dinilai' ? 'Dinilai' : 'Pending'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <?php if ($report['nilai']): ?>
                                    <span class="font-bold text-green-600"><?php echo $report['nilai']; ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <div class="flex justify-center space-x-2">
                                    <a href="../uploads/laporan/<?php echo htmlspecialchars($report['file_laporan']); ?>" 
                                       class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700" 
                                       target="_blank">
                                        Download
                                    </a>
                                    <a href="laporan.php?grade=<?php echo $report['id']; ?>" 
                                       class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">
                                        <?php echo $report['status'] === 'dinilai' ? 'Edit Nilai' : 'Beri Nilai'; ?>
                                    </a>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>Belum ada laporan masuk</p>
            <p class="text-sm mt-2">Laporan akan muncul setelah mahasiswa mengupload tugas</p>
        </div>
    <?php endif; ?>
</div>

<!-- Grading Modal -->
<?php if ($gradingReport): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Beri Nilai Laporan</h3>
            
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Mahasiswa: <span class="font-medium"><?php echo htmlspecialchars($gradingReport['mahasiswa_nama']); ?></span></p>
                <p class="text-sm text-gray-600 mb-1">Praktikum: <span class="font-medium"><?php echo htmlspecialchars($gradingReport['nama_praktikum']); ?></span></p>
                <p class="text-sm text-gray-600">Modul: <span class="font-medium">Modul <?php echo $gradingReport['modul_urutan']; ?>: <?php echo htmlspecialchars($gradingReport['modul_judul']); ?></span></p>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="laporan_id" value="<?php echo $gradingReport['id']; ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nilai (0-100)</label>
                    <input type="number" name="nilai" min="0" max="100" step="0.1" required 
                           value="<?php echo $gradingReport['nilai'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Feedback (Optional)</label>
                    <textarea name="feedback" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($gradingReport['feedback'] ?? ''); ?></textarea>
                </div>
                
                <div class="flex gap-4 justify-end">
                    <a href="laporan.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors">
                        Batal
                    </a>
                    <button type="submit" name="grade_report" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Simpan Nilai
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once 'templates/footer.php';
?>
