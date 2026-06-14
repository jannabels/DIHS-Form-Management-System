<?php

include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../guidance/gstyle.css">
    <?php include '../header_component.php'; ?>
</head>
<body class="bg-gray-50 flex h-screen">
  <!-- Main Content (Right Panel) -->
  <div class="flex-1 flex flex-col overflow-hidden relative ml-[302px] transition-all duration-300" id="mainContent">
    <!-- Watermark Logo -->
    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0.1; pointer-events: none; z-index: 0;">
        <img src="../images/dihslogo.png" alt="Logo" style="width: 1000px; height: 1000px; max-width: 70vw; max-height: 70vh; object-fit: contain; opacity: 1.5; pointer-events: none;">
    </div>
    <!-- Top Navigation -->
    <div class="bg-white shadow-sm z-10 relative" style="background: rgba(255, 255, 255, 0.9);">
      <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center">
            <div class="ml-4 md:ml-0">
              <div class="relative">
                <select id="school-year" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                  <option>SY 2024-2025</option>
                  <option>SY 2023-2024</option>
                  <option>SY 2022-2023</option>
                </select>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <!-- Create Class Button -->
            <button onclick="window.location.href='createclass.php'" class="flex items-center gap-2 border border-green-400 bg-white text-black font-semibold px-4 py-2 uppercase text-xs tracking-wide hover:bg-gray-100 transition focus:outline-none whitespace-nowrap" style="box-shadow:none; border-radius:4px;">
              <i class="fas fa-plus"></i>
              Create Class
            </button>
            <div class="max-w-lg w-full lg:max-w-xs">
              <label for="search" class="sr-only">Search</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                  </svg>
                </div>
                <input id="search" name="search" class="block w-full pl-10 pr-3 py-2 border border-green-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"  type="search">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto bg-transparent relative z-10">
      <div class="py-10 flex justify-center items-start">
        <div class="bg-white bg-opacity-90 rounded-lg shadow-lg p-6 w-full max-w-4xl flex flex-col md:flex-row gap-8" style="background: rgba(255, 255, 255, 0.8);">
          <!-- Grade 11 -->
          <div class="flex-1">
            <div class="font-bold text-gray-700 border-b pb-2 mb-4">Grade 11</div>
            <?php
            $grade11_query = "SELECT * FROM classes WHERE grade_level = 'Grade 11' ORDER BY class_name";
            $grade11_result = mysqli_query($conn, $grade11_query);
            if (mysqli_num_rows($grade11_result) > 0) {
                while ($class = mysqli_fetch_assoc($grade11_result)) {
                    echo '<div class="flex items-center justify-between mb-3 p-2 border rounded">';
                    echo '<span class="font-semibold text-green-700">' . htmlspecialchars($class['class_name']) . '</span>';
                    echo '<div class="flex items-center gap-2">';
                    echo '<button class="px-3 py-1 bg-transparent rounded text-green-700 text-sm hover:underline focus:outline-none border-0 shadow-none">View</button>';
                    echo '<span class="border px-2 py-1 rounded text-xs">' . $class['student_count'] . '</span>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="text-gray-500 text-center py-4">No Grade 11 classes found</div>';
            }
            ?>
          </div>
          <!-- Grade 12 -->
          <div class="flex-1">
            <div class="font-bold text-gray-700 border-b pb-2 mb-4">Grade 12</div>
            <?php
            $grade12_query = "SELECT * FROM classes WHERE grade_level = 'Grade 12' ORDER BY class_name";
            $grade12_result = mysqli_query($conn, $grade12_query);
            if (mysqli_num_rows($grade12_result) > 0) {
                while ($class = mysqli_fetch_assoc($grade12_result)) {
                    echo '<div class="flex items-center justify-between mb-3 p-2 border rounded">';
                    echo '<span class="font-semibold text-green-700">' . htmlspecialchars($class['class_name']) . '</span>';
                    echo '<div class="flex items-center gap-2">';
                    echo '<button class="px-3 py-1 bg-transparent rounded text-green-700 text-sm hover:underline focus:outline-none border-0 shadow-none">View</button>';
                    echo '<span class="border px-2 py-1 rounded text-xs">' . $class['student_count'] . '</span>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="text-gray-500 text-center py-4">No Grade 12 classes found</div>';
            }
            ?>
          </div>
        </div>
      </div>
    </main>
  </div>
  <script>
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.getElementById('mainContent');

    // Function to update main content margin based on sidebar state
    function updateMainContentMargin() {
      if (sidebar.classList.contains('collapsed')) {
        mainContent.style.marginLeft = '117px'; // 85px sidebar + 32px margin
      } else {
        mainContent.style.marginLeft = '302px'; // 270px sidebar + 32px margin
      }
    }

    // Update margin on window resize
    window.addEventListener('resize', updateMainContentMargin);
    
    // Listen for sidebar toggle events
    window.addEventListener('sidebarToggle', updateMainContentMargin);
    
    // Initial margin update
    updateMainContentMargin();
  </script>
</body>
</html>
