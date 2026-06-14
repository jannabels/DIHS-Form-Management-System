<?php
include '../db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>G_createclass</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-50 flex h-screen">
    <!-- Watermark Logo -->
    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0.1; pointer-events: none; z-index: 0;">
        <img src="../images/dihslogo.png" alt="Logo" style="width: 1000px; height: 1000px; max-width: 70vw; max-height: 70vh; object-fit: contain; opacity: 1.5; pointer-events: none;">
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="flex-1 flex flex-col overflow-hidden relative ml-[302px] transition-all duration-300" style="background: transparent;">
        <div class="flex flex-col items-center justify-start pt-12 relative z-10">
          <div class="w-[800px] max-w-full">
                <div class="border border-green-300 bg-gray-100 text-gray-700 px-4 py-2 rounded">
                    <span class="font-medium">Grade 11 - STEM 1</span>
                </div>
        <!-- Section Content -->
        <div class="flex flex-1 flex-col items-center justify-start pt-6 relative z-10">
            <div class="w-[900px] max-w-full bg-white border border-black rounded-none shadow-none p-0">
                <div class="flex justify-between items-start p-6">
                    <!-- Info -->
                    <div>
                        <div class="mb-2">
                            <span class="font-bold">No of learners</span>
                            <span class="ml-2 text-gray-600">0</span>
                        </div>
                        <div>
                            <span class="font-bold">Class adviser</span>
                            <span class="ml-2 text-gray-600">Non assigned</span>
                        </div>
                    </div>
                    <!-- Buttons -->
                    <div class="flex gap-2">
                        <button class="border border-gray-400 px-3 py-1 rounded bg-white hover:bg-gray-100 flex items-center text-sm">
                            <i class="fa fa-user mr-2"></i>Enroll Learner
                        </button>
                        <button class="border border-gray-400 px-3 py-1 rounded bg-white hover:bg-gray-100 flex items-center text-sm">
                            <i class="fa fa-gear mr-2"></i>Class settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Boys Table -->
            <div class="w-[900px] max-w-full mt-6">
                <div class="bg-gray-300 text-gray-800 font-bold px-4 py-2 rounded-t">Male</div>
                <table class="w-full border border-gray-400">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border border-gray-400 px-2 py-1">#</th>
                            <th class="border border-gray-400 px-2 py-1">LRN</th>
                            <th class="border border-gray-400 px-2 py-1" colspan="2">NAME</th>
                            <th class="border border-gray-400 px-2 py-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-400 px-2 py-1 text-center">1</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">107921120657</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">CAMAHALAN</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">JULIANNE HAZEL MAE</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">DE BELEN</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-2 py-1 text-center">2</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">107921120657</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">CAMAHALAN</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">JULIANNE HAZEL MAE</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">DE BELEN</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-2 py-1 text-center">3</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">107921120657</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">CAMAHALAN</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">JULIANNE HAZEL MAE</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">DE BELEN</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Girls Table -->
            <div class="w-[900px] max-w-full mt-6">
                <div class="bg-gray-200 text-gray-400 font-bold px-4 py-2 rounded-t">Female</div>
                <table class="w-full border border-gray-400">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-400 px-2 py-1">#</th>
                            <th class="border border-gray-400 px-2 py-1">LRN</th>
                            <th class="border border-gray-400 px-2 py-1" colspan="2">NAME</th>
                            <th class="border border-gray-400 px-2 py-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-400 px-2 py-1 text-center">1</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">107921120657</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">CAMAHALAN</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">JULIANNE HAZEL MAE</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">DE BELEN</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-2 py-1 text-center">2</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">107921120657</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">CAMAHALAN</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">JULIANNE HAZEL MAE</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">DE BELEN</td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 px-2 py-1 text-center">3</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">107921120657</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">CAMAHALAN</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">JULIANNE HAZEL MAE</td>
                            <td class="border border-gray-400 px-2 py-1 text-center">DE BELEN</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Back Button -->
            <div class="flex justify-end w-[900px] max-w-full mt-6">
                <button class="bg-green-600 text-white px-8 py-2 rounded text-lg font-bold hover:bg-green-700">Back</button>
            </div>
        </div>
    </div>

  <!-- Sidebar toggle logic -->
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
