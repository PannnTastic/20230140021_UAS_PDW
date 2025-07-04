<?php
require_once '../config.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header_mahasiswa.php';

// Get statistics
$enrolledCoursesQuery = "SELECT COUNT(*) as total FROM pendaftaran WHERE mahasiswa_id = ?";
$enrolledCoursesStmt = $conn->prepare($enrolledCoursesQuery);
$enrolledCoursesStmt->bind_param("i", $_SESSION['user_id']);
$enrolledCoursesStmt->execute();
$enrolledCourses = $enrolledCoursesStmt->get_result()->fetch_assoc()['total'];

$completedReportsQuery = "SELECT COUNT(*) as total FROM laporan WHERE mahasiswa_id = ? AND status = 'dinilai'";
$completedReportsStmt = $conn->prepare($completedReportsQuery);
$completedReportsStmt->bind_param("i", $_SESSION['user_id']);
$completedReportsStmt->execute();
$completedReports = $completedReportsStmt->get_result()->fetch_assoc()['total'];

$pendingReportsQuery = "SELECT COUNT(*) as total FROM laporan WHERE mahasiswa_id = ? AND status = 'pending'";
$pendingReportsStmt = $conn->prepare($pendingReportsQuery);
$pendingReportsStmt->bind_param("i", $_SESSION['user_id']);
$pendingReportsStmt->execute();
$pendingReports = $pendingReportsStmt->get_result()->fetch_assoc()['total'];

// Get recent activities
$recentActivitiesQuery = "SELECT l.*, m.judul as modul_judul, mp.nama_praktikum
                         FROM laporan l
                         JOIN modul m ON l.modul_id = m.id
                         JOIN mata_praktikum mp ON m.mata_praktikum_id = mp.id
                         WHERE l.mahasiswa_id = ?
                         ORDER BY l.tanggal_upload DESC
                         LIMIT 5";
$recentActivitiesStmt = $conn->prepare($recentActivitiesQuery);
$recentActivitiesStmt->bind_param("i", $_SESSION['user_id']);
$recentActivitiesStmt->execute();
$recentActivities = $recentActivitiesStmt->get_result();
?>

<div class="bg-gradient-to-r from-blue-500 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
    <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h1>
    <p class="mt-2 opacity-90">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-blue-600"><?php echo $enrolledCourses; ?></div>
        <div class="mt-2 text-lg text-gray-600">Praktikum Diikuti</div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-green-500"><?php echo $completedReports; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Dinilai</div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center">
        <div class="text-5xl font-extrabold text-yellow-500"><?php echo $pendingReports; ?></div>
        <div class="mt-2 text-lg text-gray-600">Tugas Menunggu</div>
    </div>
    
</div>

<div class="bg-white p-6 rounded-xl shadow-md">
    <h3 class="text-2xl font-bold text-gray-800 mb-4">Aktivitas Terbaru</h3>
    <?php if ($recentActivities->num_rows > 0): ?>
        <ul class="space-y-4">
            <?php while ($activity = $recentActivities->fetch_assoc()): ?>
                <li class="flex items-start p-3 border-b border-gray-100 last:border-b-0">
                    <span class="text-xl mr-4">
                        <?php echo $activity['status'] === 'dinilai' ? '✅' : '⏳'; ?>
                    </span>
                    <div>
                        <div class="font-semibold text-gray-800">
                            <?php echo htmlspecialchars($activity['nama_praktikum']); ?> - <?php echo htmlspecialchars($activity['modul_judul']); ?>
                        </div>
                        <div class="text-sm text-gray-600">
                            <?php if ($activity['status'] === 'dinilai'): ?>
                                Laporan telah dinilai dengan nilai: <span class="font-bold text-green-600"><?php echo $activity['nilai']; ?></span>
                            <?php else: ?>
                                Laporan sedang menunggu penilaian
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            <?php echo date('d M Y H:i', strtotime($activity['tanggal_upload'])); ?>
                        </div>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div class="text-center py-8 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>Belum ada aktivitas</p>
            <p class="text-sm mt-2">Mulai dengan mendaftar ke mata praktikum</p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'templates/footer_mahasiswa.php';
?>