<?php
require_once '../config.php';

$pageTitle = 'Praktikum Saya';
$activePage = 'my_courses';
require_once 'templates/header_mahasiswa.php';

// Get enrolled courses for the student
$sql = "SELECT mp.*, u.nama as asisten_nama, p.tanggal_daftar
        FROM pendaftaran p
        JOIN mata_praktikum mp ON p.mata_praktikum_id = mp.id
        JOIN users u ON mp.asisten_id = u.id
        WHERE p.mahasiswa_id = ?
        ORDER BY p.tanggal_daftar DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Mata Praktikum Yang Saya Ikuti</h2>
    <p class="text-gray-600">Berikut adalah daftar mata praktikum yang telah Anda daftarkan.</p>
</div>

<?php if ($result->num_rows > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($course = $result->fetch_assoc()): ?>
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($course['nama_praktikum']); ?></h3>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($course['deskripsi']); ?></p>
                
                <div class="flex items-center mb-4">
                    <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-gray-700">Asisten: <?php echo htmlspecialchars($course['asisten_nama']); ?></span>
                </div>

                <div class="flex items-center mb-4">
                    <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-gray-700">Terdaftar: <?php echo date('d M Y', strtotime($course['tanggal_daftar'])); ?></span>
                </div>

                <?php
                // Count modules and submitted reports
                $moduleCountSql = "SELECT COUNT(*) as total_modules FROM modul WHERE mata_praktikum_id = ?";
                $moduleCountStmt = $conn->prepare($moduleCountSql);
                $moduleCountStmt->bind_param("i", $course['id']);
                $moduleCountStmt->execute();
                $moduleCount = $moduleCountStmt->get_result()->fetch_assoc()['total_modules'];

                $reportCountSql = "SELECT COUNT(DISTINCT l.modul_id) as submitted_reports 
                                 FROM laporan l 
                                 JOIN modul m ON l.modul_id = m.id 
                                 WHERE m.mata_praktikum_id = ? AND l.mahasiswa_id = ?";
                $reportCountStmt = $conn->prepare($reportCountSql);
                $reportCountStmt->bind_param("ii", $course['id'], $_SESSION['user_id']);
                $reportCountStmt->execute();
                $reportCount = $reportCountStmt->get_result()->fetch_assoc()['submitted_reports'];
                ?>

                <div class="bg-gray-50 p-3 rounded-lg mb-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Progress Laporan</span>
                        <span class="text-sm font-medium text-gray-800"><?php echo $reportCount; ?>/<?php echo $moduleCount; ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $moduleCount > 0 ? ($reportCount / $moduleCount) * 100 : 0; ?>%"></div>
                    </div>
                </div>

                <a href="course_detail.php?id=<?php echo $course['id']; ?>" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-block w-full text-center">
                    Lihat Detail & Tugas
                </a>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="bg-white p-12 rounded-xl shadow-md text-center">
        <div class="text-gray-500 text-lg mb-4">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253z"></path>
            </svg>
            Anda belum terdaftar pada mata praktikum apapun
        </div>
        <p class="text-gray-600 mb-6">Mulai dengan mendaftar ke mata praktikum yang tersedia</p>
        <a href="courses.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
            Cari Mata Praktikum
        </a>
    </div>
<?php endif; ?>

<?php
require_once 'templates/footer_mahasiswa.php';
?>
