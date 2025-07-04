<?php
require_once '../config.php';

$pageTitle = 'Detail Praktikum';
$activePage = 'my_courses';
require_once 'templates/header_mahasiswa.php';

// Get course ID from URL
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($course_id <= 0) {
    echo "<div class='alert alert-error'>ID mata praktikum tidak valid</div>";
    exit;
}

// Check if student is enrolled in this course
$checkEnrollSql = "SELECT p.*, mp.nama_praktikum, mp.deskripsi, u.nama as asisten_nama
                   FROM pendaftaran p
                   JOIN mata_praktikum mp ON p.mata_praktikum_id = mp.id
                   JOIN users u ON mp.asisten_id = u.id
                   WHERE p.mahasiswa_id = ? AND p.mata_praktikum_id = ?";

$checkEnrollStmt = $conn->prepare($checkEnrollSql);
$checkEnrollStmt->bind_param("ii", $_SESSION['user_id'], $course_id);
$checkEnrollStmt->execute();
$enrollResult = $checkEnrollStmt->get_result();

if ($enrollResult->num_rows === 0) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>Anda tidak terdaftar pada mata praktikum ini</div>";
    exit;
}

$courseData = $enrollResult->fetch_assoc();

// Get modules for this course
$modulesSql = "SELECT * FROM modul WHERE mata_praktikum_id = ? ORDER BY urutan ASC";
$modulesStmt = $conn->prepare($modulesSql);
$modulesStmt->bind_param("i", $course_id);
$modulesStmt->execute();
$modulesResult = $modulesStmt->get_result();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $modul_id = intval($_POST['modul_id']);
    
    if (isset($_FILES['laporan_file']) && $_FILES['laporan_file']['error'] === 0) {
        $uploadResult = uploadFile($_FILES['laporan_file'], '../uploads/laporan/');
        
        if ($uploadResult['success']) {
            // Check if report already exists
            $checkReportSql = "SELECT id FROM laporan WHERE mahasiswa_id = ? AND modul_id = ?";
            $checkReportStmt = $conn->prepare($checkReportSql);
            $checkReportStmt->bind_param("ii", $_SESSION['user_id'], $modul_id);
            $checkReportStmt->execute();
            
            if ($checkReportStmt->get_result()->num_rows > 0) {
                // Update existing report
                $updateReportSql = "UPDATE laporan SET file_laporan = ?, tanggal_upload = CURRENT_TIMESTAMP, status = 'pending' WHERE mahasiswa_id = ? AND modul_id = ?";
                $updateReportStmt = $conn->prepare($updateReportSql);
                $updateReportStmt->bind_param("sii", $uploadResult['filename'], $_SESSION['user_id'], $modul_id);
                $updateReportStmt->execute();
            } else {
                // Insert new report
                $insertReportSql = "INSERT INTO laporan (mahasiswa_id, modul_id, file_laporan) VALUES (?, ?, ?)";
                $insertReportStmt = $conn->prepare($insertReportSql);
                $insertReportStmt->bind_param("iis", $_SESSION['user_id'], $modul_id, $uploadResult['filename']);
                $insertReportStmt->execute();
            }
            
            $success_message = "Laporan berhasil diunggah!";
        } else {
            $error_message = "Gagal mengunggah file: " . $uploadResult['message'];
        }
    } else {
        $error_message = "Mohon pilih file laporan";
    }
}
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($courseData['nama_praktikum']); ?></h2>
    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($courseData['deskripsi']); ?></p>
    
    <div class="flex items-center">
        <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        <span class="text-gray-700">Asisten: <?php echo htmlspecialchars($courseData['asisten_nama']); ?></span>
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

<div class="space-y-6">
    <?php if ($modulesResult->num_rows > 0): ?>
        <?php while ($module = $modulesResult->fetch_assoc()): ?>
            <?php
            // Get report status for this module
            $reportSql = "SELECT * FROM laporan WHERE mahasiswa_id = ? AND modul_id = ?";
            $reportStmt = $conn->prepare($reportSql);
            $reportStmt->bind_param("ii", $_SESSION['user_id'], $module['id']);
            $reportStmt->execute();
            $reportResult = $reportStmt->get_result();
            $report = $reportResult->fetch_assoc();
            ?>
            
            <div class="bg-white p-6 rounded-xl shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Modul <?php echo $module['urutan']; ?>: <?php echo htmlspecialchars($module['judul']); ?></h3>
                    <?php if ($report): ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            <?php echo $report['status'] === 'dinilai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo $report['status'] === 'dinilai' ? 'Sudah Dinilai' : 'Menunggu Penilaian'; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($module['deskripsi']); ?></p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Download Material Section -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Materi
                        </h4>
                        <?php if ($module['file_materi']): ?>
                            <a href="../uploads/materi/<?php echo htmlspecialchars($module['file_materi']); ?>" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-block"
                               download>
                                Download Materi
                            </a>
                        <?php else: ?>
                            <span class="text-gray-500">Belum ada materi</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Submit Report Section -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Upload Laporan
                        </h4>
                        
                        <form method="POST" enctype="multipart/form-data" class="space-y-3">
                            <input type="hidden" name="modul_id" value="<?php echo $module['id']; ?>">
                            <input type="file" name="laporan_file" accept=".pdf,.doc,.docx" required 
                                   class="w-full border border-gray-300 rounded-lg p-2">
                            <button type="submit" name="submit_report" 
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                <?php echo $report ? 'Update Laporan' : 'Upload Laporan'; ?>
                            </button>
                        </form>
                        
                        <?php if ($report): ?>
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">File saat ini: <?php echo htmlspecialchars($report['file_laporan']); ?></p>
                                <p class="text-sm text-gray-600">Upload: <?php echo date('d M Y H:i', strtotime($report['tanggal_upload'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Show grade if available -->
                <?php if ($report && $report['status'] === 'dinilai'): ?>
                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <h5 class="font-semibold text-green-800 mb-2">Nilai & Feedback</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm text-gray-600">Nilai:</span>
                                <span class="ml-2 text-lg font-bold text-green-800"><?php echo $report['nilai']; ?></span>
                            </div>
                            <?php if ($report['feedback']): ?>
                                <div>
                                    <span class="text-sm text-gray-600">Feedback:</span>
                                    <p class="mt-1 text-sm text-gray-800"><?php echo htmlspecialchars($report['feedback']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="bg-white p-12 rounded-xl shadow-md text-center">
            <div class="text-gray-500 text-lg mb-4">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253z"></path>
                </svg>
                Belum ada modul tersedia
            </div>
            <p class="text-gray-600">Modul akan ditambahkan oleh asisten</p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
?>
