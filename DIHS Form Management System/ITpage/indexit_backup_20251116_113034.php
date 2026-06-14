<?php
include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Personnel User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #ffffff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .table-container { 
            width: 100%;
            padding: 1rem;
            margin: 0 auto;
            max-width: 100%;
        }
        /* Logo removed as per request */
        .search-container:focus-within i { color: #16a34a; }
        .modal { transition: all 0.3s ease; }
        .user-row:hover { background-color: #f9fafb; }
        .checkbox-container input:checked + .checkmark { background-color: #16a34a; }
        .checkbox-container input:checked + .checkmark:after { display: block; }
        .checkmark:after { content: ""; position: absolute; display: none; left: 6px; top: 2px; width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);}
        .pagination-btn { transition: all 0.2s ease; }
        .pagination-btn:hover:not(.active) { background-color: #f3f4f6; }
        .tab-button { position: relative; }
        .tab-button.active:after { content: ''; position: absolute; bottom: -10px; left: 0; width: 100%; height: 3px; background-color: #16a34a;}
        .profile-section { transition: all 0.3s ease; }
        .burger-btn { transition: all 0.2s; }
        .burger-btn.hidden { display: none; }
        
        /* Mobile responsive styles */
        @media screen and (max-width: 768px) {
            .burger-btn {
                display: block !important;
            }
            
            .burger-btn.hidden {
                display: none !important;
            }
        }
        
        /* Loading spinner */
        .fa-spin {
            animation: fa-spin 2s infinite linear;
        }
        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Modal improvements */
        .modal-overlay {
            backdrop-filter: blur(4px);
            background: rgba(0, 0, 0, 0.5);
        }
        
        /* Button loading state */
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Main content layout */
        .main-content {
            padding: 80px 16px 16px; /* Added top padding to account for fixed navbar */
            min-height: 100vh;
            position: relative;
            z-index: 1;
            max-width: 100%;
            margin: 0 auto;
        }
        


    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/it_navbar.php'; ?>
    <style>
      background: transparent;
      border-radius: 50%;
    }
    
    .sidebar-header .header-logo img {
      width: 100%;
      height: 100%;
      display: block;
      object-fit: contain;
      border-radius: 50%;
    }
    
    .sidebar-header .toggler {
      height: 35px;
      width: 35px;
      color: #16a34a;
      border: none;
      cursor: pointer;
      display: flex;
      background: #fff;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: 0.4s ease;
    }
    
    .sidebar-header .sidebar-toggler {
      position: absolute;
      right: 20px;
    }
    
    .sidebar-header .menu-toggler {
      display: none;
    }
    
    .sidebar.collapsed .sidebar-header .toggler {
      transform: translate(-4px, 65px);
    }
    
    .sidebar.collapsed .sidebar-header .header-logo {
      width: 40px;
      height: 40px;
      padding: 4px;
    }
    
    .sidebar-header .toggler:hover {
      background: #dcfce7;
    }
    
    .sidebar-header .toggler span {
      font-size: 1.75rem;
      transition: 0.4s ease;
    }
    
    .sidebar.collapsed .sidebar-header .toggler span {
      transform: rotate(180deg);
    }
    
    .sidebar-nav .nav-list {
      list-style: none;
      display: flex;
      gap: 4px;
      padding: 0 15px;
      flex-direction: column;
      transform: translateY(15px);
      transition: 0.4s ease;
    }
    
    .sidebar.collapsed .sidebar-nav .primary-nav {
      transform: translateY(65px);
    }
    
    .sidebar-nav .nav-link {
      color: #fff;
      display: flex;
      gap: 12px;
      white-space: nowrap;
      border-radius: 8px;
      padding: 12px 15px;
      align-items: center;
      text-decoration: none;
      transition: 0.4s ease;
    }
    
    .sidebar.collapsed .sidebar-nav .nav-link {
      border-radius: 12px;
    }
    
    .sidebar .sidebar-nav .nav-link .nav-label {
      transition: opacity 0.3s ease;
    }
    
    .sidebar.collapsed .sidebar-nav .nav-link .nav-label {
      opacity: 0;
      pointer-events: none;
    }
    
    .sidebar-nav .nav-link:hover {
      color: #16a34a;
      background: #fff;
    }
    
    .sidebar-nav .nav-item {
      position: relative;
    }
    
    .sidebar-nav .nav-tooltip {
      position: absolute;
      top: -10px;
      opacity: 0;
      color: #16a34a;
      display: none;
      pointer-events: none;
      padding: 6px 12px;
      border-radius: 8px;
      white-space: nowrap;
      background: #fff;
      left: calc(100% + 25px);
      box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
      transition: 0s;
    }
    
    .sidebar.collapsed .sidebar-nav .nav-tooltip {
      display: block;
    }
    
    .sidebar-nav .nav-item:hover .nav-tooltip {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(50%);
      transition: all 0.4s ease;
    }
    
    .sidebar-nav .secondary-nav {
      position: absolute;
      bottom: 30px;
      width: 100%;
    }
    
    /* Responsive media query code for small screens */
    @media (max-width: 1024px) {
      .sidebar {
        height: 56px;
        margin: 13px;
        overflow-y: hidden;
        scrollbar-width: none;
        width: calc(100% - 26px);
        max-height: calc(100vh - 26px);
      }
      
      .sidebar.menu-active {
        overflow-y: auto;
      }
      
      .sidebar .sidebar-header {
        position: sticky;
        top: 0;
        z-index: 20;
        border-radius: 16px;
        background: #16a34a;
        padding: 8px 10px;
      }
      
      .sidebar-header .header-logo {
        width: 40px;
        height: 40px;
        padding: 4px;
      }
      
      .sidebar-header .header-logo img {
        width: 100%;
        height: 100%;
      }
      
      .sidebar-header .sidebar-toggler,
      .sidebar-nav .nav-item:hover .nav-tooltip {
        display: none;
      }
      
      .sidebar-header .menu-toggler {
        display: flex;
        height: 30px;
        width: 30px;
      }
      
      .sidebar-header .menu-toggler span {
        font-size: 1.3rem;
      }
      
      .sidebar .sidebar-nav .nav-list {
        padding: 0 10px;
      }
      
      .sidebar-nav .nav-link {
        gap: 10px;
        padding: 10px;
        font-size: 0.94rem;
      }
      
      .sidebar-nav .nav-link .nav-icon {
        font-size: 1.37rem;
      }
      
      .sidebar-nav .secondary-nav {
        position: relative;
        bottom: 0;
        margin: 40px 0 30px;
      }
    }
  </style>

  <!-- Logout confirmation modal -->
  <script>
    function showLogoutModal() {
      // Create modal overlay
      const modalOverlay = document.createElement('div');
      modalOverlay.className = 'logout-modal-overlay';
      modalOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(4px);
      `;
      
      // Create modal content
      const modalContent = document.createElement('div');
      modalContent.className = 'logout-modal-content';
      modalContent.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 400px;
        width: 90%;
        text-align: center;
        animation: modalSlideIn 0.3s ease-out;
      `;
      
      // Add CSS animation
      const style = document.createElement('style');
      style.textContent = `
        @keyframes modalSlideIn {
          from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
          }
          to {
            opacity: 1;
            transform: translateY(0) scale(1);
          }
        }
      `;
      document.head.appendChild(style);
      
      // Modal content
      modalContent.innerHTML = `
        <div style="margin-bottom: 1.5rem;">
          <div style="width: 60px; height: 60px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
            <span style="font-size: 2rem; color: #dc2626;">⚠️</span>
          </div>
          <h3 style="font-size: 1.25rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Confirm Logout</h3>
          <p style="color: #6b7280; font-size: 0.95rem;">Are you sure you want to logout from your account?</p>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: center;">
          <button id="cancelLogout" style="
            padding: 0.75rem 1.5rem;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
          " onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
            Cancel
          </button>
          <button id="confirmLogout" style="
            padding: 0.75rem 1.5rem;
            border: none;
            background: #dc2626;
            color: white;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
          " onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
            Yes, Logout
          </button>
        </div>
      `;
      
      // Append modal to body
      modalOverlay.appendChild(modalContent);
      document.body.appendChild(modalOverlay);
      
      // Event listeners
      const cancelBtn = document.getElementById('cancelLogout');
      const confirmBtn = document.getElementById('confirmLogout');
      
      function closeModal() {
        document.body.removeChild(modalOverlay);
      }
      
      cancelBtn.addEventListener('click', closeModal);
      confirmBtn.addEventListener('click', function() {
        window.location.href = '../logout.php';
      });
      
      // Close modal when clicking outside
      modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
          closeModal();
        }
      });
      
      // Close modal with Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeModal();
        }
      });
    }
  </script>
    
    <div class="main-content">
        <!-- Logo removed as per request -->
        <!-- Top Toolbar -->
        <div class="flex items-center justify-between z-10 mb-4">
            <div class="flex-1"></div>
            <div class="flex items-center space-x-4">
                <div class="search-container relative">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search" class="pl-10 pr-4 py-2 border border-gray-300 rounded-md w-full focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div class="cursor-pointer relative" id="filterButton">
                    <i class="fas fa-filter text-gray-600 text-xl hover:text-green-600"></i>
                </div>
                <button id="addUserBtn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center transition-colors">
                    <i class="fas fa-plus mr-2"></i> Add User
                </button>

            </div>
        </div>
        <!-- Filter Dropdown -->
        <div id="filterDropdown" class="absolute right-32 top-16 bg-white shadow-lg rounded-md p-4 z-20 hidden">
            <div class="text-gray-700 font-semibold mb-2">Filter by:</div>
            <div class="mb-2">
                <label class="block text-sm text-gray-600 mb-1">Role</label>
                <select class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-green-500" id="roleFilter">
                    <option value="">All Roles</option>
                    <option value="Adviser">Adviser</option>
                    <option value="Principal">Principal</option>
                    <option value="Guidance">Guidance</option>
                    <option value="Registrar">Registrar</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-sm text-gray-600 mb-1">Status</label>
                <select class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-green-500" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end">
                <button class="bg-gray-200 text-gray-700 px-3 py-1 rounded-md mr-2 hover:bg-gray-300" id="resetFilterBtn">Reset</button>
                <button class="bg-green-600 text-white px-3 py-1 rounded-md hover:bg-green-700" id="applyFilterBtn">Apply</button>
            </div>
        </div>
        <!-- Bulk Actions Bar (Initially Hidden) -->
        <div id="bulkActionsBar" class="bg-gray-100 p-3 border-b border-gray-200 hidden items-center justify-between">
            <div class="flex items-center">
                <span class="text-gray-700 mr-2"><span id="selectedCount">0</span> users selected</span>
            </div>
            <div class="flex space-x-2">
                <button id="bulkActivateBtn" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 flex items-center">
                    <i class="fas fa-check-circle mr-1"></i> Activate
                </button>
                <button id="bulkDeactivateBtn" class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 flex items-center">
                    <i class="fas fa-ban mr-1"></i> Deactivate
                </button>
                <button id="cancelBulkBtn" class="px-3 py-1 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Cancel
                </button>
            </div>
        </div>
        <!-- Main Content Area -->
        <div id="mainContentArea" class="flex-1 overflow-auto">
            <!-- Data Table -->
            <div class="p-6 table-container">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <thead>
                        <tr class="bg-green-600 text-white uppercase text-sm">
                            <th class="py-3 px-2 w-10">
                                <label class="checkbox-container flex justify-center">
                                    <input type="checkbox" id="selectAllCheckbox" class="hidden">
                                    <span class="checkmark h-5 w-5 border-2 border-white rounded relative"></span>
                                </label>
                            </th>
                            <th class="py-3 px-4 text-left">User Name</th>
                            <th class="py-3 px-4 text-left">First Name</th>
                            <th class="py-3 px-4 text-left">Last Name</th>
                            <th class="py-3 px-4 text-left">Role</th>
                            <th class="py-3 px-4 text-left">Status</th>
                            <th class="py-3 px-4 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
<?php
$sql = "SELECT `Username`, `First Name`, `Last Name`, `Role`, `Status` FROM `accounts`";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<tr class="user-row border-b border-gray-200">';
        echo '<td class="py-3 px-2">
                <label class="checkbox-container flex justify-center">
                    <input type="checkbox" class="user-checkbox hidden" value="'.htmlspecialchars($row['Username']).'">
                    <span class="checkmark h-5 w-5 border-2 border-gray-300 rounded relative"></span>
                </label>
              </td>';
        echo '<td class="py-3 px-4">'.htmlspecialchars($row['Username']).'</td>';
        echo '<td class="py-3 px-4">'.htmlspecialchars($row['First Name']).'</td>';
        echo '<td class="py-3 px-4">'.htmlspecialchars($row['Last Name']).'</td>';
        echo '<td class="py-3 px-4">'.htmlspecialchars($row['Role']).'</td>';
        echo '<td class="py-3 px-4">
                <div class="flex items-center">
                    <div class="h-2.5 w-2.5 rounded-full '.(strtolower($row['Status']) === 'active' ? 'bg-green-500' : 'bg-red-500').' mr-2"></div>
                    <span class="uppercase">'.htmlspecialchars($row['Status']).'</span>
                </div>
              </td>';
        echo '<td class="py-3 px-4">
                <button class="view-profile-btn text-blue-600 hover:text-blue-800 transition-colors" data-username="'.htmlspecialchars($row['Username']).'" title="View Profile">
                    <i class="fas fa-eye"></i>
                </button>
              </td>';

        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center py-4 text-gray-500">No users found.</td></tr>';
}
?>
                    </tbody>
                </table>
<?php
// Output users as JS array for editing
$sql = "SELECT * FROM `accounts`";
$result = $conn->query($sql);
$jsUsers = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $jsUsers[] = [
            'id' => $row['Username'],
            'username' => $row['Username'],
            'firstName' => $row['First Name'],
            'lastName' => $row['Last Name'],
            'role' => $row['Role'],
            'status' => strtolower($row['Status']),
            'email' => isset($row['Email']) ? $row['Email'] : '',
            'phone' => isset($row['Phone']) ? $row['Phone'] : '',
            'department' => isset($row['Department']) ? $row['Department'] : '',
            'password' => isset($row['Password']) ? $row['Password'] : '',
        ];
    }
}
?>
<script>
    // Make users array available to JS
    const users = <?php echo json_encode($jsUsers); ?>;
</script>
                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4">
                    <div class="text-sm text-gray-600">
                        Showing <span id="startRange">1</span>-<span id="endRange">5</span> of <span id="totalItems">0</span> users
                    </div>
                    <div class="flex space-x-1" id="paginationContainer"></div>
                </div>
            </div>
        </div>


        <!-- Edit User Modal -->
        <div id="editUserModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Edit User</h3>
                    <button id="closeEditModalBtn" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="editUserForm">
                    <input type="hidden" name="id" id="editUserId">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="fname" id="editFirstName" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="lname" id="editLastName" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" id="editUsername" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="editEmail" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" id="editPhone" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="editPassword" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" id="editRole" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            <option value="">Select Role</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Adviser">Adviser</option>
                            <option value="Guidance">Guidance</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department" id="editDepartment" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            <option value="">Select Department</option>
                            <option value="IT">IT</option>
                            <option value="Science">Science</option>
                            <option value="Math">Math</option>
                            <option value="English">English</option>
                            <option value="Administration">Administration</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="active" id="editStatusActive" class="form-radio text-green-600">
                                <span class="ml-2">Active</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="inactive" id="editStatusInactive" class="form-radio text-red-600">
                                <span class="ml-2">Inactive</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" id="cancelEditUser" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- User Profile Modal -->
        <div id="userProfileModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-2xl w-full max-w-4xl p-8 shadow-2xl max-h-[95vh] overflow-y-auto border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <button id="closeProfileModalBtn" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="profileContent" class="space-y-6">
                    <!-- Profile content will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div id="addUserModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-2xl w-full max-w-4xl p-8 shadow-2xl max-h-[95vh] overflow-y-auto border border-gray-100">
                <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-plus text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-800">Add New User</h3>
                            <p class="text-gray-500 text-sm">Create a new user account</p>
                        </div>
                    </div>
                    <button id="closeAddUserModalBtn" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center text-gray-500 hover:text-gray-700 transition-all duration-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="addUserForm">
                    <!-- Personal Information Section -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-circle text-green-600 mr-2"></i>
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-id-card text-blue-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">First Name *</label>
                                    <input type="text" name="fname" id="addFirstName" class="w-full text-gray-900 bg-white p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-id-badge text-purple-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Last Name *</label>
                                    <input type="text" name="lname" id="addLastName" class="w-full text-gray-900 bg-white p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-user text-green-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Username *</label>
                                    <input type="text" name="username" id="addUsername" class="w-full text-gray-900 bg-white p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-envelope text-orange-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email *</label>
                                    <input type="email" name="email" id="addEmail" class="w-full text-gray-900 bg-white p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-phone text-red-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone</label>
                                    <input type="text" name="phone" id="addPhone" class="w-full text-gray-900 bg-white p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information Section -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                            Account Information
                        </h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-lock text-indigo-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Password *</label>
                                    <div class="relative">
                                        <input type="password" name="password" id="addPassword" class="w-full text-gray-900 bg-white p-3 rounded-lg pr-12 border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                                        <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 transition-colors" onclick="toggleAddPassword()" id="addPasswordToggle">
                                            <i class="fas fa-eye" id="addPasswordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-briefcase text-teal-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Role *</label>
                                    <select name="role" id="addRole" class="w-full text-gray-900 bg-white p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                                        <option value="">Select Role</option>
                                        <option value="Teacher">Teacher</option>
                                        <option value="Adviser">Adviser</option>
                                        <option value="Guidance">Guidance</option>
                                        <option value="Principal">Principal</option>
                                        <option value="Registrar">Registrar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-building text-cyan-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Department *</label>
                                    <select name="department" id="addDepartment" class="w-full text-gray-900 bg-white p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                                        <option value="">Select Department</option>
                                        <option value="IT">IT</option>
                                        <option value="Science">Science</option>
                                        <option value="Math">Math</option>
                                        <option value="English">English</option>
                                        <option value="Administration">Administration</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-toggle-on text-pink-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status *</label>
                                    <div class="flex items-center space-x-4 bg-white p-3 rounded-lg border border-gray-200">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="status" value="active" id="addStatusActive" class="form-radio text-green-600" checked>
                                            <span class="ml-2 text-sm font-medium">Active</span>
                                        </label>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="status" value="inactive" id="addStatusInactive" class="form-radio text-red-600">
                                            <span class="ml-2 text-sm font-medium">Inactive</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <button type="button" id="cancelAddUser" class="px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg hover:from-gray-600 hover:to-gray-700 flex items-center font-medium shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg hover:from-green-700 hover:to-emerald-700 flex items-center font-medium shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                            <i class="fas fa-user-plus mr-2"></i> Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div id="confirmationModal" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-xl">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 id="confirmationTitle" class="text-lg font-medium text-gray-900 mb-2">Confirm Action</h3>
                    <p id="confirmationMessage" class="text-sm text-gray-500">Are you sure you want to proceed with this action?</p>
                </div>
                <div class="flex justify-center space-x-3">
                    <button id="cancelConfirmationBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button id="confirmActionBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    <script>
        // DOM elements
        const userTableBody = document.getElementById('userTableBody');
        const searchInput = document.getElementById('searchInput');
        const filterButton = document.getElementById('filterButton');
        const filterDropdown = document.getElementById('filterDropdown');

        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        const cancelBulkBtn = document.getElementById('cancelBulkBtn');
        const bulkActivateBtn = document.getElementById('bulkActivateBtn');
        const bulkDeactivateBtn = document.getElementById('bulkDeactivateBtn');
        const paginationContainer = document.getElementById('paginationContainer');
        const startRange = document.getElementById('startRange');
        const endRange = document.getElementById('endRange');
        const totalItems = document.getElementById('totalItems');
        const mainContentArea = document.getElementById('mainContentArea');
        const confirmationModal = document.getElementById('confirmationModal');
        const confirmationTitle = document.getElementById('confirmationTitle');
        const confirmationMessage = document.getElementById('confirmationMessage');
        const cancelConfirmationBtn = document.getElementById('cancelConfirmationBtn');
        const confirmActionBtn = document.getElementById('confirmActionBtn');
        const userProfileModal = document.getElementById('userProfileModal');
        const closeProfileModalBtn = document.getElementById('closeProfileModalBtn');
        const profileContent = document.getElementById('profileContent');
        const addUserModal = document.getElementById('addUserModal');
        const closeAddUserModalBtn = document.getElementById('closeAddUserModalBtn');
        const cancelAddUser = document.getElementById('cancelAddUser');
        const addUserForm = document.getElementById('addUserForm');
        const addUserBtn = document.getElementById('addUserBtn');


        
        // Pagination variables
        let currentPage = 1;
        const itemsPerPage = 5;
        let filteredUsers = [...users];
        // Selected users for bulk actions
        let selectedUsers = [];
        // Current action for confirmation modal
        let currentAction = null;
        // For edit modal
        const editUserModal = document.getElementById('editUserModal');
        const closeEditModalBtn = document.getElementById('closeEditModalBtn');
        const cancelEditUser = document.getElementById('cancelEditUser');
        const editUserForm = document.getElementById('editUserForm');

        // Initialize the page
        function init() {
            renderPagination();
            renderUsers();
            updateTotalItems();
        }
        // Render users in the table
        function renderUsers() {
            userTableBody.innerHTML = '';
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, filteredUsers.length);
            const paginatedUsers = filteredUsers.slice(startIndex, endIndex);
            updateRangeDisplay(startIndex, endIndex);
            paginatedUsers.forEach(user => {
                const row = document.createElement('tr');
                row.className = 'user-row border-b border-gray-200';
                const isSelected = selectedUsers.includes(user.id);
                row.innerHTML = `
                    <td class="py-3 px-2">
                        <label class="checkbox-container flex justify-center">
                            <input type="checkbox" class="user-checkbox hidden" value="${user.id}" ${isSelected ? 'checked' : ''}>
                            <span class="checkmark h-5 w-5 border-2 border-gray-300 rounded relative"></span>
                        </label>
                    </td>
                    <td class="py-3 px-4">${user.username}</td>
                    <td class="py-3 px-4">${user.firstName}</td>
                    <td class="py-3 px-4">${user.lastName}</td>
                    <td class="py-3 px-4">${user.role}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center">
                            <div class="h-2.5 w-2.5 rounded-full ${user.status === 'active' ? 'bg-green-500' : 'bg-red-500'} mr-2"></div>
                            <span class="uppercase">${user.status}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <button class="view-profile-btn text-green-600 hover:text-green-800 transition-colors" data-username="${user.username}" title="View Profile">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                userTableBody.appendChild(row);
            });
            // Add event listeners to checkboxes
            document.querySelectorAll('.user-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', handleCheckboxChange);
            });
            // Add event listeners to view profile buttons
            document.querySelectorAll('.view-profile-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const username = this.getAttribute('data-username');
                    showUserProfile(username);
                });
            });
        }
        // Update the range display (showing X-Y of Z users)
        function updateRangeDisplay(startIndex, endIndex) {
            startRange.textContent = startIndex + 1;
            endRange.textContent = endIndex;
        }
        // Update total items count
        function updateTotalItems() {
            totalItems.textContent = filteredUsers.length;
        }
        // Render pagination controls
        function renderPagination() {
            paginationContainer.innerHTML = '';
            const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
            // Previous button
            const prevBtn = document.createElement('button');
            prevBtn.className = 'pagination-btn h-8 w-8 flex items-center justify-center rounded-md border border-gray-300';
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            prevBtn.disabled = currentPage === 1;
            prevBtn.style.opacity = currentPage === 1 ? '0.5' : '1';
            prevBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderUsers();
                    renderPagination();
                }
            });
            paginationContainer.appendChild(prevBtn);
            // Page buttons
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
            for (let i = startPage; i <= endPage; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `pagination-btn h-8 w-8 flex items-center justify-center rounded-md ${i === currentPage ? 'bg-green-600 text-white' : 'border border-gray-300'}`;
                pageBtn.textContent = i;
                pageBtn.addEventListener('click', () => {
                    currentPage = i;
                    renderUsers();
                    renderPagination();
                });
                paginationContainer.appendChild(pageBtn);
            }
            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.className = 'pagination-btn h-8 w-8 flex items-center justify-center rounded-md border border-gray-300';
            nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.style.opacity = currentPage === totalPages ? '0.5' : '1';
            nextBtn.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    renderUsers();
                    renderPagination();
                }
            });
            paginationContainer.appendChild(nextBtn);
        }
        // Handle checkbox change
        function handleCheckboxChange() {
            const userId = this.value;
            if (this.checked) {
                if (!selectedUsers.includes(userId)) {
                    selectedUsers.push(userId);
                }
            } else {
                selectedUsers = selectedUsers.filter(id => id !== userId);
            }
            updateBulkActionsBar();
        }
        // Update bulk actions bar
        function updateBulkActionsBar() {
            if (selectedUsers.length > 0) {
                bulkActionsBar.classList.remove('hidden');
                bulkActionsBar.classList.add('flex');
                selectedCount.textContent = selectedUsers.length;
                // Update select all checkbox
                const allChecked = document.querySelectorAll('.user-checkbox').length === selectedUsers.length;
                selectAllCheckbox.checked = allChecked;
            } else {
                bulkActionsBar.classList.add('hidden');
                bulkActionsBar.classList.remove('flex');
                selectAllCheckbox.checked = false;
            }
        }

        // Show user profile modal
        function showUserProfile(username) {
            // Find user data
            const user = users.find(u => u.username === username);
            if (!user) {
                alert('User not found');
                return;
            }

            // Populate profile content
            profileContent.innerHTML = `
                <!-- Profile Header -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6 mb-6 border border-green-100">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            ${user.firstName.charAt(0)}${user.lastName.charAt(0)}
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-gray-800 mb-1">${user.firstName} ${user.lastName}</h2>
                            <p class="text-green-600 font-medium">${user.role}</p>
                            <p class="text-gray-500 text-sm">${user.department} Department</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                <span class="w-2 h-2 rounded-full ${user.status === 'active' ? 'bg-green-400' : 'bg-red-400'} mr-2"></span>
                                ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Profile Information -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Personal Information -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-user-circle text-green-600 mr-2"></i>
                                Personal Information
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-user text-green-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Username</label>
                                        <input type="text" value="${user.username}" class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profileUsername">
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-id-card text-blue-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">First Name</label>
                                        <input type="text" value="${user.firstName}" class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profileFirstName">
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-id-badge text-purple-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Last Name</label>
                                        <input type="text" value="${user.lastName}" class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profileLastName">
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-envelope text-orange-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email</label>
                                        <input type="email" value="${user.email || ''}" class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profileEmail">
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-phone text-red-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone</label>
                                        <input type="text" value="${user.phone || ''}" class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profilePhone">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                                Account Information
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-lock text-indigo-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Password</label>
                                        <div class="relative">
                                            <input type="password" value="${user.password || ''}" class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg pr-12 border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profilePassword">
                                            <button type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 transition-colors" onclick="toggleProfilePassword()" id="profilePasswordToggle">
                                                <i class="fas fa-eye" id="profilePasswordIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-briefcase text-teal-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Role</label>
                                        <select class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profileRole">
                                            <option value="Teacher" ${user.role === 'Teacher' ? 'selected' : ''}>Teacher</option>
                                            <option value="Adviser" ${user.role === 'Adviser' ? 'selected' : ''}>Adviser</option>
                                            <option value="Guidance" ${user.role === 'Guidance' ? 'selected' : ''}>Guidance</option>
                                            <option value="Principal" ${user.role === 'Principal' ? 'selected' : ''}>Principal</option>
                                            <option value="Registrar" ${user.role === 'Registrar' ? 'selected' : ''}>Registrar</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-building text-cyan-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Department</label>
                                        <select class="w-full text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" readonly id="profileDepartment">
                                            <option value="IT" ${user.department === 'IT' ? 'selected' : ''}>IT</option>
                                            <option value="Science" ${user.department === 'Science' ? 'selected' : ''}>Science</option>
                                            <option value="Math" ${user.department === 'Math' ? 'selected' : ''}>Math</option>
                                            <option value="English" ${user.department === 'English' ? 'selected' : ''}>English</option>
                                            <option value="Administration" ${user.department === 'Administration' ? 'selected' : ''}>Administration</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-toggle-on text-pink-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</label>
                                        <div class="flex items-center space-x-4 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="radio" name="profileStatus" value="active" ${user.status === 'active' ? 'checked' : ''} class="form-radio text-green-600" disabled id="profileStatusActive">
                                                <span class="ml-2 text-sm font-medium">Active</span>
                                            </label>
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="radio" name="profileStatus" value="inactive" ${user.status === 'inactive' ? 'checked' : ''} class="form-radio text-red-600" disabled id="profileStatusInactive">
                                                <span class="ml-2 text-sm font-medium">Inactive</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-2 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" id="cancelEditBtn" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-md text-sm font-medium hidden">
                        Cancel
                    </button>
                    <button type="button" id="saveProfileBtn" class="px-4 py-2 bg-blue-500 text-white rounded-md text-sm font-medium hidden hover:bg-blue-600">
                        Save
                    </button>
                    <button type="button" id="editProfileBtn" class="px-4 py-2 text-blue-600 hover:bg-blue-50 rounded-md text-sm font-medium">
                        Edit Profile
                    </button>
                </div>
            `;

                    // Show modal
        userProfileModal.classList.remove('hidden');
        
        // Add event listeners for edit buttons
        document.getElementById('editProfileBtn').addEventListener('click', enableProfileEdit);
        document.getElementById('saveProfileBtn').addEventListener('click', saveProfileChanges);
        document.getElementById('cancelEditBtn').addEventListener('click', function() {
            // Reload the profile to reset any changes
            showUserProfile(username);
        });
        }

        // Show confirmation modal
        function showConfirmationModal(title, message, action) {
            confirmationTitle.textContent = title;
            confirmationMessage.textContent = message;
            currentAction = action;
            confirmationModal.classList.remove('hidden');
        }
        // Search functionality
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filteredUsers = users.filter(user =>
                user.username.toLowerCase().includes(searchTerm) ||
                user.firstName.toLowerCase().includes(searchTerm) ||
                user.lastName.toLowerCase().includes(searchTerm) ||
                user.role.toLowerCase().includes(searchTerm)
            );
            currentPage = 1;
            renderPagination();
            renderUsers();
            updateTotalItems();
        });
        // Filter dropdown toggle
        filterButton.addEventListener('click', function() {
            filterDropdown.classList.toggle('hidden');
        });
        // Close filter dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!filterButton.contains(event.target) && !filterDropdown.contains(event.target)) {
                filterDropdown.classList.add('hidden');
            }
        });
        // Select all checkbox
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            if (this.checked) {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                    const userId = checkbox.value;
                    if (!selectedUsers.includes(userId)) {
                        selectedUsers.push(userId);
                    }
                });
            } else {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                selectedUsers = [];
            }
            updateBulkActionsBar();
        });
        // Cancel bulk actions
        cancelBulkBtn.addEventListener('click', function() {
            selectedUsers = [];
            document.querySelectorAll('.user-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            bulkActionsBar.classList.add('hidden');
            bulkActionsBar.classList.remove('flex');
        });
        // Bulk activate
        bulkActivateBtn.addEventListener('click', function() {
            showConfirmationModal(
                'Activate Users',
                `Are you sure you want to activate ${selectedUsers.length} user(s)?`,
                'activate'
            );
        });
        // Bulk deactivate
        bulkDeactivateBtn.addEventListener('click', function() {
            showConfirmationModal(
                'Deactivate Users',
                `Are you sure you want to deactivate ${selectedUsers.length} user(s)?`,
                'deactivate'
            );
        });

        

        // Close profile modal
        function closeProfileModal() {
            userProfileModal.classList.add('hidden');
        }
        closeProfileModalBtn.addEventListener('click', closeProfileModal);
        userProfileModal.addEventListener('click', function(event) {
            if (event.target === userProfileModal) {
                closeProfileModal();
            }
        });

        // Close confirmation modal
        function closeConfirmationModal() {
            confirmationModal.classList.add('hidden');
            currentAction = null;
        }
        cancelConfirmationBtn.addEventListener('click', closeConfirmationModal);
        confirmationModal.addEventListener('click', function(event) {
            if (event.target === confirmationModal) {
                closeConfirmationModal();
            }
        });
        // Confirm action
        confirmActionBtn.addEventListener('click', function() {
            if (currentAction === 'activate' || currentAction === 'deactivate') {
                const newStatus = currentAction === 'activate' ? 'active' : 'inactive';
                fetch('update_user_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `username=${encodeURIComponent(selectedUsers.join(','))}&status=${encodeURIComponent(newStatus)}&bulk=1`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Some updates failed.\n' + (data.error || ''));
                        location.reload();
                    }
                })
                .catch(() => {
                    alert('Some updates failed (network error).');
                    location.reload();
                });
                selectedUsers = [];
                bulkActionsBar.classList.add('hidden');
                bulkActionsBar.classList.remove('flex');
            }
        });


        // Close Edit Modal
        function closeEditModal() {
            editUserModal.classList.add('hidden');
            editUserForm.reset();
            // Reset any error states
            editUserForm.querySelectorAll('input, select').forEach(input => {
                input.classList.remove('border-red-500');
            });
        }
        closeEditModalBtn.addEventListener('click', closeEditModal);
        cancelEditUser.addEventListener('click', closeEditModal);
        editUserModal.addEventListener('click', function(event) {
            if (event.target === editUserModal) {
                closeEditModal();
            }
        });
        // Handle Edit Form Submission
        editUserForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(editUserForm);
            
            // Show loading state
            const submitBtn = editUserForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            submitBtn.disabled = true;
            
            fetch('edit_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    // Show success message before reload
                    const successMsg = document.createElement('div');
                    successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50';
                    successMsg.innerHTML = '<i class="fas fa-check mr-2"></i>User updated successfully!';
                    document.body.appendChild(successMsg);
                    setTimeout(() => {
                        document.body.removeChild(successMsg);
                    location.reload();
                    }, 1500);
                } else {
                    alert('Failed to update user: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update user. Please check your connection and try again.');
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        // Role filter functionality
        const roleFilter = document.getElementById('roleFilter');
        roleFilter.addEventListener('change', function() {
            const selectedRole = this.value.toLowerCase();
            filteredUsers = users.filter(user => {
                const matchesRole = selectedRole === '' || user.role.toLowerCase() === selectedRole;
                const searchTerm = searchInput.value.toLowerCase();
                const matchesSearch = user.username.toLowerCase().includes(searchTerm) ||
                    user.firstName.toLowerCase().includes(searchTerm) ||
                    user.lastName.toLowerCase().includes(searchTerm) ||
                    user.role.toLowerCase().includes(searchTerm);
                return matchesRole && matchesSearch;
            });
            currentPage = 1;
            renderPagination();
            renderUsers();
            updateTotalItems();
        });
        // Status filter functionality
        const statusFilter = document.getElementById('statusFilter');
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value.toLowerCase();
            filteredUsers = users.filter(user => {
                const matchesStatus = selectedStatus === '' || user.status === selectedStatus;
                const searchTerm = searchInput.value.toLowerCase();
                const matchesSearch = user.username.toLowerCase().includes(searchTerm) ||
                    user.firstName.toLowerCase().includes(searchTerm) ||
                    user.lastName.toLowerCase().includes(searchTerm) ||
                    user.role.toLowerCase().includes(searchTerm);
                return matchesStatus && matchesSearch;
            });
            currentPage = 1;
            renderPagination();
            renderUsers();
            updateTotalItems();
        });
        // Filter dropdown logic
        const applyFilterBtn = document.getElementById('applyFilterBtn');
        const resetFilterBtn = document.getElementById('resetFilterBtn');
        let filterRoleValue = '';
        let filterStatusValue = '';
        // Store filter values on change
        roleFilter.addEventListener('change', function() {
            filterRoleValue = this.value.toLowerCase();
        });
        statusFilter.addEventListener('change', function() {
            filterStatusValue = this.value.toLowerCase();
        });
        // Apply filter button
        applyFilterBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.toLowerCase();
            filteredUsers = users.filter(user => {
                const matchesRole = filterRoleValue === '' || user.role.toLowerCase() === filterRoleValue;
                const matchesStatus = filterStatusValue === '' || user.status === filterStatusValue;
                const matchesSearch = user.username.toLowerCase().includes(searchTerm) ||
                    user.firstName.toLowerCase().includes(searchTerm) ||
                    user.lastName.toLowerCase().includes(searchTerm) ||
                    user.role.toLowerCase().includes(searchTerm);
                return matchesRole && matchesStatus && matchesSearch;
            });
            currentPage = 1;
            renderPagination();
            renderUsers();
            updateTotalItems();
            filterDropdown.classList.add('hidden');
        });
        // Reset filter button
        resetFilterBtn.addEventListener('click', function() {
            filterRoleValue = '';
            filterStatusValue = '';
            roleFilter.value = '';
            statusFilter.value = '';
            searchInput.value = '';
            filteredUsers = [...users];
            currentPage = 1;
            renderPagination();
            renderUsers();
            updateTotalItems();
            filterDropdown.classList.add('hidden');
        });

        
        // Add keyboard support for closing modals
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (!editUserModal.classList.contains('hidden')) {
                    editUserModal.classList.add('hidden');
                }
                if (!confirmationModal.classList.contains('hidden')) {
                    confirmationModal.classList.add('hidden');
                }
                if (!userProfileModal.classList.contains('hidden')) {
                    userProfileModal.classList.add('hidden');
                }
                if (!addUserModal.classList.contains('hidden')) {
                    addUserModal.classList.add('hidden');
                }
            }
        });
        
        // Toggle password visibility for add user modal
        function toggleAddPassword() {
            const passwordField = document.getElementById('addPassword');
            const passwordIcon = document.getElementById('addPasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Toggle password visibility for profile modal
        function toggleProfilePassword() {
            const passwordField = document.getElementById('profilePassword');
            const passwordIcon = document.getElementById('profilePasswordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Toggle password visibility (for edit modal)
        function togglePassword() {
            const passwordField = document.getElementById('passwordField');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Enable edit mode in profile modal
        function enableProfileEdit() {
            const editBtn = document.getElementById('editProfileBtn');
            const saveBtn = document.getElementById('saveProfileBtn');
            const cancelBtn = document.getElementById('cancelEditBtn');
            
            // Show/hide buttons
            editBtn.classList.add('hidden');
            saveBtn.classList.remove('hidden');
            cancelBtn.classList.remove('hidden');
            
            // Enable all input fields
            const inputs = ['profileFirstName', 'profileLastName', 'profileEmail', 'profilePassword', 'profilePhone'];
            inputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.readOnly = false;
                    input.classList.remove('bg-gray-50');
                    input.classList.add('bg-white', 'border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                }
            });
            
            // Enable selects
            const selects = ['profileRole', 'profileDepartment'];
            selects.forEach(id => {
                const select = document.getElementById(id);
                if (select) {
                    select.disabled = false;
                    select.classList.remove('bg-gray-50');
                    select.classList.add('bg-white', 'border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                }
            });
            
            // Enable radio buttons
            const radios = ['profileStatusActive', 'profileStatusInactive'];
            radios.forEach(id => {
                const radio = document.getElementById(id);
                if (radio) {
                    radio.disabled = false;
                }
            });
            
            // Update status container styling
            const statusContainer = document.querySelector('input[name="profileStatus"]').closest('div');
            if (statusContainer) {
                statusContainer.classList.remove('bg-gray-50');
                statusContainer.classList.add('bg-white', 'border-green-300');
            }
        }

        // Disable edit mode in profile modal
        function disableProfileEdit() {
            const editBtn = document.getElementById('editProfileBtn');
            const saveBtn = document.getElementById('saveProfileBtn');
            const cancelBtn = document.getElementById('cancelEditBtn');
            
            // Show/hide buttons
            editBtn.classList.remove('hidden');
            saveBtn.classList.add('hidden');
            cancelBtn.classList.add('hidden');
            
            // Disable all input fields
            const inputs = ['profileFirstName', 'profileLastName', 'profileEmail', 'profilePassword', 'profilePhone'];
            inputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.readOnly = true;
                    input.classList.remove('bg-white', 'border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                    input.classList.add('bg-gray-50', 'border-gray-200');
                }
            });
            
            // Disable selects
            const selects = ['profileRole', 'profileDepartment'];
            selects.forEach(id => {
                const select = document.getElementById(id);
                if (select) {
                    select.disabled = true;
                    select.classList.remove('bg-white', 'border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                    select.classList.add('bg-gray-50', 'border-gray-200');
                }
            });
            
            // Disable radio buttons
            const radios = ['profileStatusActive', 'profileStatusInactive'];
            radios.forEach(id => {
                const radio = document.getElementById(id);
                if (radio) {
                    radio.disabled = true;
                }
            });
            
            // Update status container styling
            const statusContainer = document.querySelector('input[name="profileStatus"]').closest('div');
            if (statusContainer) {
                statusContainer.classList.remove('bg-white', 'border-green-300');
                statusContainer.classList.add('bg-gray-50', 'border-gray-200');
            }
        }

        // Save profile changes
        function saveProfileChanges() {
            const username = document.getElementById('profileUsername').value;
            const firstName = document.getElementById('profileFirstName').value;
            const lastName = document.getElementById('profileLastName').value;
            const email = document.getElementById('profileEmail').value;
            const password = document.getElementById('profilePassword').value;
            const phone = document.getElementById('profilePhone').value;
            const role = document.getElementById('profileRole').value;
            const department = document.getElementById('profileDepartment').value;
            const status = document.querySelector('input[name="profileStatus"]:checked').value;

            // Create form data
            const formData = new FormData();
            formData.append('id', username); // Use username as id for the WHERE clause
            formData.append('username', username);
            formData.append('fname', firstName);
            formData.append('lname', lastName);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('phone', phone);
            formData.append('role', role);
            formData.append('department', department);
            formData.append('status', status);

            // Show loading state
            const saveBtn = document.getElementById('saveProfileBtn');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            saveBtn.disabled = true;

            fetch('edit_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50';
                    successMsg.innerHTML = '<i class="fas fa-check mr-2"></i>Profile updated successfully!';
                    document.body.appendChild(successMsg);
                    setTimeout(() => {
                        document.body.removeChild(successMsg);
                    }, 2000);

                    // Disable edit mode
                    disableProfileEdit();
                    
                    // Update the users array
                    const userIndex = users.findIndex(u => u.username === username);
                    if (userIndex !== -1) {
                        users[userIndex] = {
                            ...users[userIndex],
                            firstName,
                            lastName,
                            email,
                            password,
                            phone,
                            role,
                            department,
                            status
                        };
                    }
                } else {
                    alert('Failed to update profile: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update profile. Please check your connection and try again.');
            })
            .finally(() => {
                // Reset button state
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }

        // Show Add User Modal
        function showAddUserModal() {
            addUserModal.classList.remove('hidden');
            addUserForm.reset();
        }

        // Close Add User Modal
        function closeAddUserModal() {
            addUserModal.classList.add('hidden');
            addUserForm.reset();
        }

        // Handle Add User Form Submission
        addUserForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(addUserForm);
            
            // Show loading state
            const submitBtn = addUserForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
            submitBtn.disabled = true;
            
            fetch('add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeAddUserModal();
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50';
                    successMsg.innerHTML = '<i class="fas fa-check mr-2"></i>User added successfully!';
                    document.body.appendChild(successMsg);
                    setTimeout(() => {
                        document.body.removeChild(successMsg);
                        location.reload(); // Reload to show new user
                    }, 1500);
                } else {
                    alert('Failed to add user: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add user. Please check your connection and try again.');
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Add User Modal Event Listeners
        addUserBtn.addEventListener('click', showAddUserModal);
        closeAddUserModalBtn.addEventListener('click', closeAddUserModal);
        cancelAddUser.addEventListener('click', closeAddUserModal);
        addUserModal.addEventListener('click', function(event) {
            if (event.target === addUserModal) {
                closeAddUserModal();
            }
        });

        // Initialize the page
        init();
        
        // Listen for sidebar toggle events
        window.addEventListener('sidebarToggle', function(event) {
            const mainContent = document.querySelector('.main-content');
            if (event.detail.collapsed) {
                mainContent.classList.add('sidebar-collapsed');
            } else {
                mainContent.classList.remove('sidebar-collapsed');
            }
        });
        
        // Listen for mobile menu toggle events
        window.addEventListener('mobileMenuToggle', function(event) {
            const mainContent = document.querySelector('.main-content');
            if (window.innerWidth <= 1024) {
                // On mobile, adjust content when menu is active
                if (event.detail.active) {
                    mainContent.style.marginTop = '70px'; // Account for mobile header
                } else {
                    mainContent.style.marginTop = '0';
                }
            }
        });
        
        // Listen for window resize events
        window.addEventListener('resize', function() {
            const mainContent = document.querySelector('.main-content');
            if (window.innerWidth <= 1024) {
                // On mobile, always remove sidebar-collapsed class and reset margin-top
                mainContent.classList.remove('sidebar-collapsed');
                mainContent.style.marginTop = '0';
            } else {
                // On desktop, remove margin-top style
                mainContent.style.marginTop = '';
            }
        });
    </script>
</body>
</html>