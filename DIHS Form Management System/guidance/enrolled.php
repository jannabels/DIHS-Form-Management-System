<?php
include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enrolled Students</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../guidance/gstyle.css" />
</head>
<body class="bg-gray-50 min-h-screen flex">
    <!-- Include Sidebar Component -->
    <?php include '../sidebar_component.php'; ?>
    
    <!-- Watermark Logo -->
    <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.1; pointer-events: none; z-index: 0;">
        <img src="../images/dihslogo.png" alt="Logo" style="width: 1000px; height: 1000px; max-width: 70vw; max-height: 70vh; object-fit: contain; opacity: 1.5; pointer-events: none;">
    </div>
    <!-- Main Content -->
    <div id="mainContent" class="flex-1 flex justify-center items-start min-h-screen ml-[302px] transition-all duration-300" style="background: transparent;">
        <div class="w-full max-w-6xl bg-white bg-opacity-90 rounded-lg shadow-lg px-6 pt-6 pb-6 m-8 max-h-[calc(100vh-4rem)] overflow-y-auto" style="background: rgba(255, 255, 255, 0.8);">
            <div class="flex justify-between items-center mb-4">
                <!-- Removed the ENROLLED STUDENTS label -->
                <div></div>
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="SEARCH" class="border border-green-600 rounded px-4 py-1 focus:outline-none focus:ring-2 focus:ring-green-400 w-64" />
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-green-600 rounded-lg">
                    <thead class="bg-green-600 text-white">
                        <tr>
                            <th class="p-2 text-center">#</th>
                            <th class="p-2 text-center">LRN</th>
                            <th class="p-2 text-center">NAME</th>
                            <th class="p-2 text-center">SEX</th>
                            <th class="p-2 text-center">BIRTHDAY</th>
                            <th class="p-2 text-center">AGE</th>
                            <th class="p-2 text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php
                        // Fetch students from sf1 table
                        $sql = "SELECT LRN, Name, Sex, Birthdate, Age FROM sf1 ORDER BY LRN";
                        $result = $conn->query($sql);
                        $count = 1;
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $lrn = htmlspecialchars($row['LRN']);
                                $name = htmlspecialchars($row['Name']);
                                $sex = htmlspecialchars($row['Sex']);
                                $birthdate = htmlspecialchars($row['Birthdate']);
                                $age = htmlspecialchars($row['Age']);
                        ?>
                        <tr class="border-b hover:bg-gray-100">
                            <td class="p-2 text-center font-semibold"><?php echo $count++; ?></td>
                            <td class="p-2 text-center"><?php echo $lrn; ?></td>
                            <td class="p-2 text-center"><?php echo $name; ?></td>
                            <td class="p-2 text-center"><?php echo $sex; ?></td>
                            <td class="p-2 text-center"><?php echo $birthdate; ?></td>
                            <td class="p-2 text-center"><?php echo $age; ?></td>
                            <td class="p-2 text-center">
                                <a href="../adviser/adviserform.php?lrn=<?php echo urlencode($lrn); ?>" class="text-green-700 font-semibold hover:underline focus:outline-none">EDIT</a>
                            </td>
                        </tr>
                        <?php
                            }
                        } else {
                        ?>
                        <tr><td colspan="7" class="text-center p-4">No students found.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Sidebar toggle logic -->
    <script>
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.getElementById('mainContent');

        // Function to update main content margin based on sidebar state 
        function updateMainContentMargin() {
            if (window.innerWidth >= 1024) {
                // Desktop view
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.style.marginLeft = '117px'; // 85px sidebar + 32px margin
                } else {
                    mainContent.style.marginLeft = '302px'; // 270px sidebar + 32px margin
                }
            } else {
                // Mobile view
                if (sidebar.classList.contains('menu-active')) {
                    mainContent.style.marginLeft = '32px'; // Just margin when menu is active
                } else {
                    mainContent.style.marginLeft = '32px'; // Just margin when menu is collapsed
                }
            }
        }

        // Update margin on window resize
        window.addEventListener('resize', updateMainContentMargin);
        
        // Listen for sidebar toggle events
        window.addEventListener('sidebarToggle', function(event) {
            setTimeout(updateMainContentMargin, 100); // Small delay to ensure sidebar state is updated
        });
        
        // Listen for mobile menu toggle events
        window.addEventListener('mobileMenuToggle', function(event) {
            setTimeout(updateMainContentMargin, 100); // Small delay to ensure sidebar state is updated
        });
        
        // Initial margin update
        updateMainContentMargin();

        // Search bar filtering logic
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function() {
            const filter = searchInput.value.toLowerCase();
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
