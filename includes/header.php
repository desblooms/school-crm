<header class="bg-white shadow-md">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-3 md:py-4">
            <div class="flex items-center">
                <button class="md:hidden text-gray-600 hover:text-gray-800 focus:outline-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="ml-3 md:ml-0 flex items-center">
                    <div class="bg-blue-500 w-8 h-8 rounded-full flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white"></i>
                    </div>
                    <h1 class="ml-3 text-xl font-bold text-gray-800"><?php echo APP_NAME; ?></h1>
                </div>
            </div>
            
            <div class="flex items-center space-x-3">
                <!-- Notifications -->
                <div class="relative">
                    <button class="text-gray-600 hover:text-gray-800 focus:outline-none" onclick="toggleNotifications()">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" id="notification-count">0</span>
                    </button>
                    <div class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-50 hidden" id="notifications-dropdown">
                        <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b">Notifications</div>
                        <div class="max-h-60 overflow-y-auto" id="notifications-list">
                            <div class="px-4 py-3 text-sm text-gray-500">No new notifications</div>
                        </div>
                    </div>
                </div>
                
                <!-- User Profile -->
                <div class="relative">
                    <button class="flex items-center space-x-2 text-gray-600 hover:text-gray-800 focus:outline-none" onclick="toggleUserMenu()">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-medium"><?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?></span>
                        </div>
                        <span class="hidden md:block font-medium"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i class="fas fa-chevron-down text-sm"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden" id="user-menu">
                        <div class="px-4 py-2 text-sm text-gray-500 border-b">
                            <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                            <div class="text-xs text-blue-600 capitalize"><?php echo htmlspecialchars($_SESSION['user_role']); ?></div>
                        </div>
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i>Profile
                        </a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                        <div class="border-t my-1"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    if (sidebar) {
        sidebar.classList.toggle('-translate-x-full');
    }
    
    if (overlay) {
        overlay.classList.toggle('hidden');
    }
}

function toggleNotifications() {
    const dropdown = document.getElementById('notifications-dropdown');
    dropdown.classList.toggle('hidden');
    // Close user menu if open
    document.getElementById('user-menu').classList.add('hidden');
}

function toggleUserMenu() {
    const dropdown = document.getElementById('user-menu');
    dropdown.classList.toggle('hidden');
    // Close notifications if open
    document.getElementById('notifications-dropdown').classList.add('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const notificationsBtn = event.target.closest('[onclick="toggleNotifications()"]');
    const userMenuBtn = event.target.closest('[onclick="toggleUserMenu()"]');
    
    if (!notificationsBtn) {
        document.getElementById('notifications-dropdown').classList.add('hidden');
    }
    if (!userMenuBtn) {
        document.getElementById('user-menu').classList.add('hidden');
    }
});
</script>