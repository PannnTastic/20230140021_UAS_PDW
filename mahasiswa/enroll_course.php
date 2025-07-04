<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in as mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit;
}

// Check if course exists
$checkCourseSql = "SELECT id FROM mata_praktikum WHERE id = ?";
$checkCourseStmt = $conn->prepare($checkCourseSql);
$checkCourseStmt->bind_param("i", $course_id);
$checkCourseStmt->execute();

if ($checkCourseStmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Course not found']);
    exit;
}

// Check if already enrolled
$checkEnrollSql = "SELECT id FROM pendaftaran WHERE mahasiswa_id = ? AND mata_praktikum_id = ?";
$checkEnrollStmt = $conn->prepare($checkEnrollSql);
$checkEnrollStmt->bind_param("ii", $_SESSION['user_id'], $course_id);
$checkEnrollStmt->execute();

if ($checkEnrollStmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already enrolled in this course']);
    exit;
}

// Enroll student
$enrollSql = "INSERT INTO pendaftaran (mahasiswa_id, mata_praktikum_id) VALUES (?, ?)";
$enrollStmt = $conn->prepare($enrollSql);
$enrollStmt->bind_param("ii", $_SESSION['user_id'], $course_id);

if ($enrollStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Successfully enrolled']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to enroll']);
}

$conn->close();
?>
