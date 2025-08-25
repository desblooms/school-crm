<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Teacher.php';
require_once '../classes/Student.php';

requirePermission('manage_attendance');

$teacher = new Teacher();
$student = new Student();

$success_message = '';
$error_message = '';
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedClass = $_GET['class_id'] ?? '';

// Get classes (if teacher, get only assigned classes)
if ($_SESSION['user_role'] === 'teacher') {
    // Get teacher's assigned classes
    $teacherData = $teacher->getByUserId($_SESSION['user_id']);
    $classes = $teacherData ? $teacher->getTeacherClasses($teacherData['id']) : [];
} else {
    // Admin can see all classes
    $stmt = $teacher->getConnection()->query("SELECT * FROM classes ORDER BY name, section");
    $classes = $stmt->fetchAll();
}

$classStudents = [];
$attendanceData = [];
if ($selectedClass) {
    $classStudents = $student->getStudentsByClass($selectedClass);
    // Get existing attendance for selected date
    $attendanceData = $student->getClassAttendanceByDate($selectedClass, $selectedDate);
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $classId = intval($_POST['class_id']);
    $date = $_POST['date'];
    $attendanceRecords = $_POST['attendance'] ?? [];
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($attendanceRecords as $studentId => $attendanceInfo) {
        $status = $attendanceInfo['status'];
        $checkInTime = $attendanceInfo['check_in'] ?? null;
        $checkOutTime = $attendanceInfo['check_out'] ?? null;
        $remarks = $attendanceInfo['remarks'] ?? '';
        
        $result = $student->markAttendance(
            intval($studentId),
            $classId,
            $status,
            $_SESSION['user_id'],
            $date,
            $checkInTime,
            $checkOutTime,
            $remarks
        );
        
        if ($result['success']) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    if ($successCount > 0) {
        $success_message = "Attendance marked for $successCount students successfully.";
        if ($errorCount > 0) {
            $success_message .= " $errorCount records failed to update.";
        }
        // Refresh attendance data
        $attendanceData = $student->getClassAttendanceByDate($selectedClass, $selectedDate);
    } else {
        $error_message = "Failed to mark attendance. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Teacher Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Attendance Management</h1>
                <p class="text-gray-600">Mark and manage student attendance</p>
            </div>

            <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Class and Date</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                        <select name="class_id" onchange="this.form.submit()" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $selectedClass == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name'] . '-' . $class['section']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>"
                               onchange="this.form.submit()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Load Attendance
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($selectedClass && !empty($classStudents)): ?>
            <!-- Attendance Form -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Mark Attendance - <?php echo date('F j, Y', strtotime($selectedDate)); ?>
                    </h2>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                    <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($classStudents as $student): ?>
                                <?php 
                                $existingAttendance = null;
                                foreach ($attendanceData as $att) {
                                    if ($att['student_id'] == $student['id']) {
                                        $existingAttendance = $att;
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                                <span class="text-white text-sm font-medium">
                                                    <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($student['name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($student['admission_number']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($student['roll_number'] ?: 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select name="attendance[<?php echo $student['id']; ?>][status]" 
                                                class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="present" <?php echo ($existingAttendance && $existingAttendance['status'] === 'present') ? 'selected' : ''; ?>>Present</option>
                                            <option value="absent" <?php echo ($existingAttendance && $existingAttendance['status'] === 'absent') ? 'selected' : ''; ?>>Absent</option>
                                            <option value="late" <?php echo ($existingAttendance && $existingAttendance['status'] === 'late') ? 'selected' : ''; ?>>Late</option>
                                            <option value="half_day" <?php echo ($existingAttendance && $existingAttendance['status'] === 'half_day') ? 'selected' : ''; ?>>Half Day</option>
                                            <option value="excused" <?php echo ($existingAttendance && $existingAttendance['status'] === 'excused') ? 'selected' : ''; ?>>Excused</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="time" name="attendance[<?php echo $student['id']; ?>][check_in]" 
                                               value="<?php echo $existingAttendance ? htmlspecialchars($existingAttendance['check_in_time'] ?? '') : ''; ?>"
                                               class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="time" name="attendance[<?php echo $student['id']; ?>][check_out]" 
                                               value="<?php echo $existingAttendance ? htmlspecialchars($existingAttendance['check_out_time'] ?? '') : ''; ?>"
                                               class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="text" name="attendance[<?php echo $student['id']; ?>][remarks]" 
                                               value="<?php echo $existingAttendance ? htmlspecialchars($existingAttendance['remarks'] ?? '') : ''; ?>"
                                               placeholder="Optional remarks"
                                               class="w-full px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-end space-x-4">
                            <button type="button" onclick="markAllPresent()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                                <i class="fas fa-check-circle mr-2"></i>Mark All Present
                            </button>
                            <button type="submit" name="mark_attendance" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>Save Attendance
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php elseif ($selectedClass): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">No students found in the selected class.</p>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-calendar-check text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">Please select a class to mark attendance.</p>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function markAllPresent() {
        const statusSelects = document.querySelectorAll('select[name*="[status]"]');
        statusSelects.forEach(select => {
            select.value = 'present';
        });
        
        // Set default check-in time if empty
        const checkInInputs = document.querySelectorAll('input[name*="[check_in]"]');
        checkInInputs.forEach(input => {
            if (!input.value) {
                input.value = '08:30';
            }
        });
    }
    </script>
</body>
</html>