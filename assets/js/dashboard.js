// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadRecentActivity();
});

function loadDashboardData() {
    fetch('api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-students').textContent = data.stats.total_students || 0;
                document.getElementById('total-teachers').textContent = data.stats.total_teachers || 0;
                document.getElementById('monthly-collection').textContent = '₹' + (data.stats.monthly_collection || 0);
                document.getElementById('pending-fees').textContent = '₹' + (data.stats.pending_fees || 0);
            }
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
        });
}

function loadRecentActivity() {
    fetch('api/recent-activity.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.activities.length > 0) {
                const tbody = document.getElementById('recent-activity');
                tbody.innerHTML = data.activities.map(activity => `
                    <tr>
                        <td class="p-3 text-sm text-gray-600">${activity.created_at}</td>
                        <td class="p-3 text-sm text-gray-800">${activity.description}</td>
                        <td class="p-3 text-sm text-gray-600">${activity.user_name}</td>
                        <td class="p-3">
                            <span class="px-2 py-1 text-xs rounded-full ${getStatusClass(activity.action)}">
                                ${activity.action}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error loading recent activity:', error);
        });
}

function getStatusClass(action) {
    switch(action.toLowerCase()) {
        case 'login':
            return 'bg-green-100 text-green-800';
        case 'logout':
            return 'bg-gray-100 text-gray-800';
        case 'create':
        case 'add':
            return 'bg-blue-100 text-blue-800';
        case 'update':
        case 'edit':
            return 'bg-yellow-100 text-yellow-800';
        case 'delete':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}