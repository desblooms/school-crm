<?php
// Get current page info for active states
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Get the base URL for the application
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_dir = dirname($_SERVER['SCRIPT_NAME']);

// Clean up the script directory path
if ($script_dir === '\\' || $script_dir === '/') {
    $base_url = $protocol . '://' . $host . '/';
} else {
    $base_url = $protocol . '://' . $host . $script_dir . '/';
}

// For development, we can also use relative paths more simply
$is_root = ($current_dir === 'SCHOOL CRM' || $current_page === 'index.php');

function isActive($page, $dir = '') {
    global $current_page, $current_dir;
    if ($dir && $current_dir === $dir) {
        return true;
    }
    return $current_page === $page;
}
?>

<aside id="sidebar" class="bg-gray-800 text-white w-64 min-h-screen p-4 fixed md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40">
    <nav class="mt-8">
        <div class="space-y-2">
            <!-- Dashboard -->
            <a href="<?php echo $is_root ? 'index.php' : '../index.php'; ?>" 
               class="flex items-center space-x-3 p-3 rounded-lg transition-colors <?php echo isActive('index.php') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'teacher'): ?>
            <!-- Students -->
            <div class="space-y-2">
                <button class="flex items-center justify-between w-full p-3 rounded-lg transition-colors <?php echo $current_dir === 'students' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" onclick="toggleSubmenu('students-menu')">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </div>
                    <i class="fas fa-chevron-down transition-transform duration-200" id="students-menu-icon"></i>
                </button>
                <div class="<?php echo $current_dir === 'students' ? 'block' : 'hidden'; ?> pl-6 space-y-1" id="students-menu">
                    <a href="<?php echo $is_root ? 'students/list.php' : ($current_dir === 'students' ? 'list.php' : '../students/list.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('list.php', 'students') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-list mr-2"></i>All Students
                    </a>
                    <a href="<?php echo $is_root ? 'students/admission.php' : ($current_dir === 'students' ? 'admission.php' : '../students/admission.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('admission.php', 'students') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user-plus mr-2"></i>Admission
                    </a>
                    <a href="<?php echo $is_root ? 'students/attendance.php' : ($current_dir === 'students' ? 'attendance.php' : '../students/attendance.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('attendance.php', 'students') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-calendar-check mr-2"></i>Attendance
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <!-- Teachers -->
            <div class="space-y-2">
                <button class="flex items-center justify-between w-full p-3 rounded-lg transition-colors <?php echo $current_dir === 'teachers' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" onclick="toggleSubmenu('teachers-menu')">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Teachers</span>
                    </div>
                    <i class="fas fa-chevron-down transition-transform duration-200" id="teachers-menu-icon"></i>
                </button>
                <div class="<?php echo $current_dir === 'teachers' ? 'block' : 'hidden'; ?> pl-6 space-y-1" id="teachers-menu">
                    <a href="<?php echo $is_root ? 'teachers/list.php' : ($current_dir === 'teachers' ? 'list.php' : '../teachers/list.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('list.php', 'teachers') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-list mr-2"></i>All Teachers
                    </a>
                    <a href="<?php echo $is_root ? 'teachers/add.php' : ($current_dir === 'teachers' ? 'add.php' : '../teachers/add.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('add.php', 'teachers') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user-plus mr-2"></i>Add Teacher
                    </a>
                    <a href="<?php echo $is_root ? 'teachers/attendance.php' : ($current_dir === 'teachers' ? 'attendance.php' : '../teachers/attendance.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('attendance.php', 'teachers') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-calendar-check mr-2"></i>Attendance
                    </a>
                    <a href="<?php echo $is_root ? 'teachers/payroll.php' : ($current_dir === 'teachers' ? 'payroll.php' : '../teachers/payroll.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('payroll.php', 'teachers') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-money-bill mr-2"></i>Payroll
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'accountant'): ?>
            <!-- Fees & Accounts -->
            <div class="space-y-2">
                <button class="flex items-center justify-between w-full p-3 rounded-lg transition-colors <?php echo in_array($current_dir, ['fees', 'invoices', 'expenses']) ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" onclick="toggleSubmenu('fees-menu')">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Fees & Accounts</span>
                    </div>
                    <i class="fas fa-chevron-down transition-transform duration-200" id="fees-menu-icon"></i>
                </button>
                <div class="<?php echo in_array($current_dir, ['fees', 'invoices', 'expenses']) ? 'block' : 'hidden'; ?> pl-6 space-y-1" id="fees-menu">
                    <a href="<?php echo $is_root ? 'fees/collection.php' : ($current_dir === 'fees' ? 'collection.php' : '../fees/collection.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('collection.php', 'fees') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-cash-register mr-2"></i>Fee Collection
                    </a>
                    <a href="<?php echo $is_root ? 'fees/structure.php' : ($current_dir === 'fees' ? 'structure.php' : '../fees/structure.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('structure.php', 'fees') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-table mr-2"></i>Fee Structure
                    </a>
                    <a href="<?php echo $is_root ? 'invoices/list.php' : ($current_dir === 'invoices' ? 'list.php' : '../invoices/list.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo $current_dir === 'invoices' ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice mr-2"></i>Invoices
                    </a>
                    <a href="<?php echo $is_root ? 'expenses/list.php' : ($current_dir === 'expenses' ? 'list.php' : '../expenses/list.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo $current_dir === 'expenses' ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-receipt mr-2"></i>Expenses
                    </a>
                </div>
            </div>
            
            <!-- Accounting -->
            <div class="space-y-2">
                <button class="flex items-center justify-between w-full p-3 rounded-lg transition-colors <?php echo $current_dir === 'accounting' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" onclick="toggleSubmenu('accounting-menu')">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-calculator"></i>
                        <span>Accounting</span>
                    </div>
                    <i class="fas fa-chevron-down transition-transform duration-200" id="accounting-menu-icon"></i>
                </button>
                <div class="<?php echo $current_dir === 'accounting' ? 'block' : 'hidden'; ?> pl-6 space-y-1" id="accounting-menu">
                    <a href="<?php echo $is_root ? 'accounting/index.php' : ($current_dir === 'accounting' ? 'index.php' : '../accounting/index.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('index.php', 'accounting') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="<?php echo $is_root ? 'accounting/cash-book.php' : ($current_dir === 'accounting' ? 'cash-book.php' : '../accounting/cash-book.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('cash-book.php', 'accounting') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-money-bill mr-2"></i>Cash Book
                    </a>
                    <a href="<?php echo $is_root ? 'accounting/bank-reconciliation.php' : ($current_dir === 'accounting' ? 'bank-reconciliation.php' : '../accounting/bank-reconciliation.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('bank-reconciliation.php', 'accounting') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-university mr-2"></i>Bank Reconciliation
                    </a>
                    <a href="<?php echo $is_root ? 'accounting/cheque-management.php' : ($current_dir === 'accounting' ? 'cheque-management.php' : '../accounting/cheque-management.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('cheque-management.php', 'accounting') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-file-invoice mr-2"></i>Cheque Management
                    </a>
                    <a href="<?php echo $is_root ? 'accounting/financial-reports.php' : ($current_dir === 'accounting' ? 'financial-reports.php' : '../accounting/financial-reports.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('financial-reports.php', 'accounting') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-chart-bar mr-2"></i>Financial Reports
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Events & Notices -->
            <div class="space-y-2">
                <button class="flex items-center justify-between w-full p-3 rounded-lg transition-colors <?php echo in_array($current_dir, ['events', 'notices']) ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" onclick="toggleSubmenu('events-menu')">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Events & Notices</span>
                    </div>
                    <i class="fas fa-chevron-down transition-transform duration-200" id="events-menu-icon"></i>
                </button>
                <div class="<?php echo in_array($current_dir, ['events', 'notices']) ? 'block' : 'hidden'; ?> pl-6 space-y-1" id="events-menu">
                    <a href="<?php echo $is_root ? 'events/calendar.php' : ($current_dir === 'events' ? 'calendar.php' : '../events/calendar.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('calendar.php', 'events') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-calendar mr-2"></i>Calendar
                    </a>
                    <a href="<?php echo $is_root ? 'events/list.php' : ($current_dir === 'events' ? 'list.php' : '../events/list.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('list.php', 'events') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-list mr-2"></i>All Events
                    </a>
                    <a href="<?php echo $is_root ? 'notices/list.php' : ($current_dir === 'notices' ? 'list.php' : '../notices/list.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo $current_dir === 'notices' ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-bullhorn mr-2"></i>Notices
                    </a>
                </div>
            </div>
            
            <!-- Reports -->
            <div class="space-y-2">
                <button class="flex items-center justify-between w-full p-3 rounded-lg transition-colors <?php echo $current_dir === 'reports' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>" onclick="toggleSubmenu('reports-menu')">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </div>
                    <i class="fas fa-chevron-down transition-transform duration-200" id="reports-menu-icon"></i>
                </button>
                <div class="<?php echo $current_dir === 'reports' ? 'block' : 'hidden'; ?> pl-6 space-y-1" id="reports-menu">
                    <a href="<?php echo $is_root ? 'reports/index.php' : ($current_dir === 'reports' ? 'index.php' : '../reports/index.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('index.php', 'reports') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="<?php echo $is_root ? 'reports/students.php' : ($current_dir === 'reports' ? 'students.php' : '../reports/students.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('students.php', 'reports') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-user-graduate mr-2"></i>Student Reports
                    </a>
                    <a href="<?php echo $is_root ? 'reports/financial.php' : ($current_dir === 'reports' ? 'financial.php' : '../reports/financial.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('financial.php', 'reports') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-chart-line mr-2"></i>Financial Reports
                    </a>
                    <a href="<?php echo $is_root ? 'reports/attendance.php' : ($current_dir === 'reports' ? 'attendance.php' : '../reports/attendance.php'); ?>" 
                       class="block p-2 rounded text-sm transition-colors <?php echo isActive('attendance.php', 'reports') ? 'bg-blue-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-calendar-check mr-2"></i>Attendance Reports
                    </a>
                </div>
            </div>
        </div>
    </nav>
</aside>

<!-- Overlay for mobile -->
<div class="fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden hidden" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
function toggleSubmenu(menuId) {
    const menu = document.getElementById(menuId);
    const icon = document.getElementById(menuId + '-icon');
    
    if (menu && icon) {
        const isHidden = menu.classList.contains('hidden');
        
        // Close all other submenus first
        document.querySelectorAll('[id$="-menu"]').forEach(submenu => {
            if (submenu.id !== menuId) {
                submenu.classList.add('hidden');
                const submenuIcon = document.getElementById(submenu.id + '-icon');
                if (submenuIcon) {
                    submenuIcon.classList.remove('rotate-180');
                }
            }
        });
        
        // Toggle current submenu
        if (isHidden) {
            menu.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            menu.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }
}

// Show overlay on mobile when sidebar is open
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
}

// Initialize - set correct icon rotation for active menus
document.addEventListener('DOMContentLoaded', function() {
    // Check which submenu should be open based on current page
    const openMenus = document.querySelectorAll('[id$="-menu"]:not(.hidden)');
    openMenus.forEach(menu => {
        const icon = document.getElementById(menu.id + '-icon');
        if (icon) {
            icon.classList.add('rotate-180');
        }
    });
    
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.querySelector('[onclick="toggleSidebar()"]');
        const isMobile = window.innerWidth < 768;
        
        if (isMobile && sidebar && !sidebar.contains(event.target) && event.target !== hamburger) {
            sidebar.classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }
    });
});

// Handle window resize
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (window.innerWidth >= 768) {
        // Desktop view - ensure sidebar is visible and overlay is hidden
        if (sidebar) sidebar.classList.remove('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
    } else {
        // Mobile view - ensure sidebar is hidden initially
        if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        }
    }
});
</script>