<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Teacher.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/classes/Fee.php';
require_once __DIR__ . '/classes/Subject.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Error Diagnosis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="bg-red-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bug text-white text-3xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Error Diagnosis</h1>
                <p class="text-gray-600 mt-2">Let's identify and fix the internal server error</p>
            </div>

            <div class="space-y-6">
                <!-- Test Subject Assignment -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4">
                        <i class="fas fa-book mr-2"></i>Test Subject Assignment
                    </h3>
                    <div id="subject-test-result" class="text-sm">
                        <button onclick="testSubjectAssignment()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Run Subject Assignment Test
                        </button>
                    </div>
                </div>

                <!-- Test Fee Collection -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-green-800 mb-4">
                        <i class="fas fa-money-bill mr-2"></i>Test Fee Collection
                    </h3>
                    <div id="fee-test-result" class="text-sm">
                        <button onclick="testFeeCollection()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Run Fee Collection Test
                        </button>
                    </div>
                </div>

                <!-- Test Attendance -->
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-purple-800 mb-4">
                        <i class="fas fa-calendar mr-2"></i>Test Attendance
                    </h3>
                    <div id="attendance-test-result" class="text-sm">
                        <button onclick="testAttendance()" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                            Run Attendance Test
                        </button>
                    </div>
                </div>

                <!-- Database Structure Check -->
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-orange-800 mb-4">
                        <i class="fas fa-database mr-2"></i>Database Structure Check
                    </h3>
                    <div id="db-test-result" class="text-sm">
                        <button onclick="checkDatabaseStructure()" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700">
                            Check Database Structure
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="comprehensive-fix.php" class="bg-blue-600 text-white px-4 py-3 rounded-md hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-tools mr-2"></i>Run Comprehensive Fix
                    </a>
                    <a href="test-fixes.php" class="bg-green-600 text-white px-4 py-3 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-flask mr-2"></i>Run All Tests
                    </a>
                    <a href="index.php" class="bg-gray-600 text-white px-4 py-3 rounded-md hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function testSubjectAssignment() {
        const resultDiv = document.getElementById('subject-test-result');
        resultDiv.innerHTML = '<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Testing...</div>';
        
        fetch('diagnose-error.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_subject_assignment'
        })
        .then(response => response.text())
        .then(data => {
            resultDiv.innerHTML = data;
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + error.message + '</div>';
        });
    }

    function testFeeCollection() {
        const resultDiv = document.getElementById('fee-test-result');
        resultDiv.innerHTML = '<div class="text-green-600"><i class="fas fa-spinner fa-spin mr-2"></i>Testing...</div>';
        
        fetch('diagnose-error.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_fee_collection'
        })
        .then(response => response.text())
        .then(data => {
            resultDiv.innerHTML = data;
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + error.message + '</div>';
        });
    }

    function testAttendance() {
        const resultDiv = document.getElementById('attendance-test-result');
        resultDiv.innerHTML = '<div class="text-purple-600"><i class="fas fa-spinner fa-spin mr-2"></i>Testing...</div>';
        
        fetch('diagnose-error.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=test_attendance'
        })
        .then(response => response.text())
        .then(data => {
            resultDiv.innerHTML = data;
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + error.message + '</div>';
        });
    }

    function checkDatabaseStructure() {
        const resultDiv = document.getElementById('db-test-result');
        resultDiv.innerHTML = '<div class="text-orange-600"><i class="fas fa-spinner fa-spin mr-2"></i>Checking...</div>';
        
        fetch('diagnose-error.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_database_structure'
        })
        .then(response => response.text())
        .then(data => {
            resultDiv.innerHTML = data;
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error: ' + error.message + '</div>';
        });
    }
    </script>
</body>
</html>

<?php
// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $db = Database::getInstance()->getConnection();
        
        switch ($_POST['action']) {
            case 'test_subject_assignment':
                $teacher = new Teacher();
                $subject = new Subject();
                
                $teacherStmt = $db->query("SELECT id FROM teachers LIMIT 1");
                $teacherId = $teacherStmt->fetchColumn();
                
                $subjectStmt = $db->query("SELECT id FROM subjects LIMIT 1");
                $subjectId = $subjectStmt->fetchColumn();
                
                $classStmt = $db->query("SELECT id FROM classes LIMIT 1");
                $classId = $classStmt->fetchColumn();
                
                if ($teacherId && $subjectId && $classId) {
                    $result = $teacher->assignSubject($teacherId, $subjectId, $classId);
                    if ($result['success']) {
                        echo '<div class="text-green-600"><i class="fas fa-check mr-2"></i>Subject assignment test passed!</div>';
                        // Clean up
                        $subjects = $teacher->getTeacherSubjects($teacherId);
                        if (!empty($subjects)) {
                            $teacher->removeSubject($subjects[0]['id']);
                        }
                    } else {
                        echo '<div class="text-red-600"><i class="fas fa-times mr-2"></i>Subject assignment failed: ' . htmlspecialchars($result['message']) . '</div>';
                    }
                } else {
                    echo '<div class="text-yellow-600"><i class="fas fa-exclamation-triangle mr-2"></i>No test data available (missing teachers, subjects, or classes)</div>';
                }
                break;
                
            case 'test_fee_collection':
                $fee = new Fee();
                
                $studentStmt = $db->query("SELECT id FROM students LIMIT 1");
                $studentId = $studentStmt->fetchColumn();
                
                $feeTypeStmt = $db->query("SELECT id FROM fee_types LIMIT 1");
                $feeTypeId = $feeTypeStmt->fetchColumn();
                
                if ($studentId && $feeTypeId) {
                    $result = $fee->collectFee($studentId, $feeTypeId, 100.00, 'cash', 1, date('Y-m'), null, 'Test payment');
                    if ($result['success']) {
                        echo '<div class="text-green-600"><i class="fas fa-check mr-2"></i>Fee collection test passed! Receipt: ' . htmlspecialchars($result['receipt_number']) . '</div>';
                        // Clean up
                        $cleanupStmt = $db->prepare("DELETE FROM fee_payments WHERE receipt_number = ?");
                        $cleanupStmt->execute([$result['receipt_number']]);
                    } else {
                        echo '<div class="text-red-600"><i class="fas fa-times mr-2"></i>Fee collection failed: ' . htmlspecialchars($result['message']) . '</div>';
                    }
                } else {
                    echo '<div class="text-yellow-600"><i class="fas fa-exclamation-triangle mr-2"></i>No test data available (missing students or fee types)</div>';
                }
                break;
                
            case 'test_attendance':
                $student = new Student();
                
                $studentStmt = $db->query("SELECT id FROM students LIMIT 1");
                $studentId = $studentStmt->fetchColumn();
                
                $classStmt = $db->query("SELECT id FROM classes LIMIT 1");
                $classId = $classStmt->fetchColumn();
                
                if ($studentId && $classId) {
                    $result = $student->markAttendance($studentId, $classId, 'present', 1, date('Y-m-d'), '08:30:00', '15:30:00', 'Test attendance');
                    if ($result['success']) {
                        echo '<div class="text-green-600"><i class="fas fa-check mr-2"></i>Attendance test passed!</div>';
                        // Clean up
                        $cleanupStmt = $db->prepare("DELETE FROM student_attendance WHERE student_id = ? AND date = ?");
                        $cleanupStmt->execute([$studentId, date('Y-m-d')]);
                    } else {
                        echo '<div class="text-red-600"><i class="fas fa-times mr-2"></i>Attendance failed: ' . htmlspecialchars($result['message']) . '</div>';
                    }
                } else {
                    echo '<div class="text-yellow-600"><i class="fas fa-exclamation-triangle mr-2"></i>No test data available (missing students or classes)</div>';
                }
                break;
                
            case 'check_database_structure':
                $issues = [];
                
                // Check teacher_subjects table
                $stmt = $db->query("DESCRIBE teacher_subjects");
                $columns = array_column($stmt->fetchAll(), 'Field');
                if (!in_array('assigned_date', $columns)) {
                    $issues[] = 'Missing assigned_date column in teacher_subjects table';
                }
                
                // Check student_attendance table
                $stmt = $db->query("DESCRIBE student_attendance");
                $columns = array_column($stmt->fetchAll(), 'Field');
                if (!in_array('check_in_time', $columns)) {
                    $issues[] = 'Missing check_in_time column in student_attendance table';
                }
                if (!in_array('check_out_time', $columns)) {
                    $issues[] = 'Missing check_out_time column in student_attendance table';
                }
                
                if (empty($issues)) {
                    echo '<div class="text-green-600"><i class="fas fa-check mr-2"></i>Database structure is correct!</div>';
                } else {
                    echo '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Database structure issues found:</div>';
                    echo '<ul class="mt-2 text-red-600 text-sm ml-4">';
                    foreach ($issues as $issue) {
                        echo '<li>• ' . htmlspecialchars($issue) . '</li>';
                    }
                    echo '</ul>';
                    echo '<div class="mt-2 text-blue-600"><a href="comprehensive-fix.php" class="underline">Run Comprehensive Fix to resolve these issues</a></div>';
                }
                break;
                
            default:
                echo '<div class="text-red-600"><i class="fas fa-times mr-2"></i>Unknown action</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i>Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<div class="text-gray-600 text-sm mt-1">File: ' . htmlspecialchars($e->getFile()) . ' Line: ' . $e->getLine() . '</div>';
    }
    exit;
}
?>