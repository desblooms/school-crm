<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Fee.php';

requireRole('admin');

$fee = new Fee();
$classes = $fee->getClasses();
$feeTypes = $fee->getFeeTypes();

$success_message = '';
$error_message = '';
$selectedAcademicYear = $_GET['academic_year'] ?? (date('Y') . '-' . (date('Y') + 1));

$feeStructure = $fee->getFeeStructure(null, $selectedAcademicYear);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_structure'])) {
    $updates = 0;
    $errors = [];
    
    foreach ($_POST['fees'] as $classId => $feeData) {
        foreach ($feeData as $feeTypeId => $data) {
            if (!empty($data['amount'])) {
                $result = $fee->setFeeStructure(
                    $classId,
                    $feeTypeId,
                    floatval($data['amount']),
                    intval($data['due_date_day'] ?? 10),
                    $selectedAcademicYear
                );
                
                if ($result['success']) {
                    $updates++;
                } else {
                    $errors[] = $result['message'];
                }
            }
        }
    }
    
    if ($updates > 0) {
        $success_message = "Fee structure updated successfully! ($updates records updated)";
        // Refresh data
        $feeStructure = $fee->getFeeStructure(null, $selectedAcademicYear);
    }
    
    if (!empty($errors)) {
        $error_message = 'Some updates failed: ' . implode(', ', $errors);
    }
}

// Group fee structure by class for easier display
$structureByClass = [];
foreach ($feeStructure as $fs) {
    $classKey = $fs['class_id'];
    if (!isset($structureByClass[$classKey])) {
        $structureByClass[$classKey] = [
            'class_name' => $fs['class_name'] . ' - ' . $fs['section'],
            'fees' => []
        ];
    }
    $structureByClass[$classKey]['fees'][$fs['fee_type_id']] = $fs;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Fee Structure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-4 md:p-6">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Fee Structure</h1>
                        <p class="text-gray-600">Configure fee amounts for each class and fee type</p>
                    </div>
                </div>
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

            <!-- Academic Year Selection -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="flex items-center space-x-4">
                    <label class="text-sm font-medium text-gray-700">Academic Year:</label>
                    <select name="academic_year" onchange="this.form.submit()" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php for ($year = date('Y') - 2; $year <= date('Y') + 3; $year++): ?>
                        <option value="<?php echo $year . '-' . ($year + 1); ?>" 
                                <?php echo $selectedAcademicYear === ($year . '-' . ($year + 1)) ? 'selected' : ''; ?>>
                            <?php echo $year . '-' . ($year + 1); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <!-- Fee Structure Form -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Fee Structure for Academic Year <?php echo $selectedAcademicYear; ?>
                    </h2>
                    
                    <form method="POST">
                        <input type="hidden" name="update_structure" value="1">
                        
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Class</th>
                                        <?php foreach ($feeTypes as $feeType): ?>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">
                                            <?php echo htmlspecialchars($feeType['name']); ?>
                                            <?php if ($feeType['is_mandatory']): ?>
                                            <span class="text-red-500">*</span>
                                            <?php endif; ?>
                                        </th>
                                        <?php endforeach; ?>
                                        <th class="text-left p-3 text-sm font-medium text-gray-600">Due Date (Day of Month)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                    <tr class="border-t">
                                        <td class="p-3 font-medium text-gray-800">
                                            <?php echo htmlspecialchars($class['name'] . ' - ' . $class['section']); ?>
                                        </td>
                                        
                                        <?php foreach ($feeTypes as $feeType): ?>
                                        <td class="p-3">
                                            <?php 
                                            $currentAmount = '';
                                            if (isset($structureByClass[$class['id']]['fees'][$feeType['id']])) {
                                                $currentAmount = $structureByClass[$class['id']]['fees'][$feeType['id']]['amount'];
                                            }
                                            ?>
                                            <div class="relative">
                                                <span class="absolute left-3 top-2 text-gray-500">₹</span>
                                                <input type="number" 
                                                       name="fees[<?php echo $class['id']; ?>][<?php echo $feeType['id']; ?>][amount]" 
                                                       value="<?php echo $currentAmount; ?>"
                                                       step="0.01" min="0"
                                                       placeholder="0.00"
                                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                            </div>
                                        </td>
                                        <?php endforeach; ?>
                                        
                                        <td class="p-3">
                                            <?php 
                                            $currentDueDate = 10; // Default
                                            if (isset($structureByClass[$class['id']]['fees'])) {
                                                $fees = $structureByClass[$class['id']]['fees'];
                                                if (!empty($fees)) {
                                                    $firstFee = reset($fees);
                                                    $currentDueDate = $firstFee['due_date_day'];
                                                }
                                            }
                                            ?>
                                            <select name="fees[<?php echo $class['id']; ?>][due_date]" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                                <?php for ($day = 1; $day <= 28; $day++): ?>
                                                <option value="<?php echo $day; ?>" <?php echo $currentDueDate == $day ? 'selected' : ''; ?>>
                                                    <?php echo $day; ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6 flex justify-between items-center">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                * Mandatory fees are required for all students
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>Update Fee Structure
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="copyFromPreviousYear()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-copy mr-2"></i>Copy from Previous Year
                    </button>
                    <button onclick="bulkUpdate()" class="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>Bulk Update
                    </button>
                    <a href="collection.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-center">
                        <i class="fas fa-money-bill mr-2"></i>Start Collection
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
    function copyFromPreviousYear() {
        if (confirm('This will copy fee structure from previous academic year. Continue?')) {
            // Implementation for copying from previous year
            alert('Feature coming soon!');
        }
    }

    function bulkUpdate() {
        const percentage = prompt('Enter percentage increase/decrease (e.g., 10 for 10% increase, -5 for 5% decrease):');
        if (percentage !== null && !isNaN(percentage)) {
            const multiplier = 1 + (parseFloat(percentage) / 100);
            const inputs = document.querySelectorAll('input[name*="[amount]"]');
            
            inputs.forEach(input => {
                if (input.value && input.value > 0) {
                    const newValue = parseFloat(input.value) * multiplier;
                    input.value = newValue.toFixed(2);
                }
            });
            
            alert(`All existing amounts have been ${percentage > 0 ? 'increased' : 'decreased'} by ${Math.abs(percentage)}%`);
        }
    }

    // Set due date for all fee types in a class
    document.querySelectorAll('select[name*="[due_date]"]').forEach(select => {
        select.addEventListener('change', function() {
            const classId = this.name.match(/fees\[(\d+)\]/)[1];
            const dueDateValue = this.value;
            
            // Update hidden inputs for each fee type
            const feeInputs = document.querySelectorAll(`input[name*="fees[${classId}]"]`);
            feeInputs.forEach(input => {
                const feeTypeMatch = input.name.match(/\[(\d+)\]\[amount\]/);
                if (feeTypeMatch) {
                    const feeTypeId = feeTypeMatch[1];
                    // Create or update due date input
                    let dueDateInput = document.querySelector(`input[name="fees[${classId}][${feeTypeId}][due_date_day]"]`);
                    if (!dueDateInput) {
                        dueDateInput = document.createElement('input');
                        dueDateInput.type = 'hidden';
                        dueDateInput.name = `fees[${classId}][${feeTypeId}][due_date_day]`;
                        input.parentNode.appendChild(dueDateInput);
                    }
                    dueDateInput.value = dueDateValue;
                }
            });
        });
    });
    </script>
</body>
</html>