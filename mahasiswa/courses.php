<?php
require_once '../config.php';

$pageTitle = 'Cari Praktikum';
$activePage = 'courses';
require_once 'templates/header_mahasiswa.php';

// Get search parameter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Get all available courses
$sql = "SELECT mp.*, u.nama as asisten_nama 
        FROM mata_praktikum mp 
        JOIN users u ON mp.asisten_id = u.id 
        WHERE mp.nama_praktikum LIKE ? OR mp.deskripsi LIKE ?
        ORDER BY mp.nama_praktikum ASC";

$stmt = $conn->prepare($sql);
$searchParam = "%$search%";
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="bg-white p-6 rounded-xl shadow-md mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Cari Mata Praktikum</h2>
    
    <!-- Search Form -->
    <form method="GET" action="courses.php" class="mb-6">
        <div class="flex gap-4">
            <input type="text" 
                   name="search" 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Cari nama praktikum..." 
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Cari
            </button>
            <?php if ($search): ?>
                <a href="courses.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    Reset
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($search): ?>
        <p class="text-gray-600 mb-4">Hasil pencarian untuk: <strong><?php echo htmlspecialchars($search); ?></strong></p>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if ($result->num_rows > 0): ?>
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

                <?php
                // Check if student is already enrolled
                $checkSql = "SELECT id FROM pendaftaran WHERE mahasiswa_id = ? AND mata_praktikum_id = ?";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->bind_param("ii", $_SESSION['user_id'], $course['id']);
                $checkStmt->execute();
                $enrolled = $checkStmt->get_result()->num_rows > 0;
                ?>

                <?php if ($enrolled): ?>
                    <span class="bg-green-100 text-green-800 px-4 py-2 rounded-lg font-medium">
                        ✓ Sudah Terdaftar
                    </span>
                <?php else: ?>
                    <button onclick="enrollCourse(<?php echo $course['id']; ?>)" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Daftar Sekarang
                    </button>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-full text-center py-12">
            <div class="text-gray-500 text-lg mb-4">
                <?php if ($search): ?>
                    Tidak ada praktikum yang ditemukan untuk pencarian "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    Belum ada mata praktikum yang tersedia
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function enrollCourse(courseId) {
    if (confirm('Apakah Anda yakin ingin mendaftar ke mata praktikum ini?')) {
        fetch('enroll_course.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'course_id=' + courseId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Berhasil mendaftar ke mata praktikum!');
                location.reload();
            } else {
                alert('Gagal mendaftar: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mendaftar');
        });
    }
}
</script>

<?php
require_once 'templates/footer_mahasiswa.php';
?>
