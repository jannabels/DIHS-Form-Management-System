<?php
session_start();

// Check if user is logged in and has Guidance role
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Guidance') {
    // Redirect to login page if not authenticated or wrong role
    header("Location: ../login/index.php");
    exit();
}

include '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Adviser - Prospective Enrollees</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../guidance/gstyle.css" />
    <?php include '../header_component.php'; ?>
</head>
<body class="bg-gray-50 min-h-screen flex">
            <?php include '../sidebar_component_guidance.php'; ?>
        
    <!-- Watermark Logo -->
    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0.1; pointer-events: none; z-index: 0;">
        <img src="../images/dihslogo.png" alt="Logo" style="width: 1000px; height: 1000px; max-width: 70vw; max-height: 70vh; object-fit: contain; opacity: 1.5; pointer-events: none;">
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="flex-1 flex justify-center items-start min-h-screen ml-[302px] transition-all duration-300" style="background: transparent;">
        <div class="w-full max-w-6xl bg-white bg-opacity-90 rounded-lg shadow-lg px-6 pt-6 pb-6 m-8 max-h-[calc(100vh-4rem)] overflow-y-auto" style="background: rgba(255, 255, 255, 0.8);">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <p class="text-gray-700 text-sm">Prospective list of Enrollees from previous school year</p>
                </div>
                <div class="text-gray-600 font-semibold">Grade 11 - STEM 1</div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2 gap-2">
                <div class="flex gap-4 items-center">
                    <label class="flex items-center gap-1 text-sm font-medium">
                        <input type="checkbox" id="selectRetained" class="accent-green-600"> Select Retained
                    </label>
                    <label class="flex items-center gap-1 text-sm font-medium">
                        <input type="checkbox" id="selectPromoted" class="accent-green-600"> Select Promoted
                    </label>
                </div>
                <button id="batchEnrollBtn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">Batch Enroll Selected</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-green-600 rounded-lg">
                    <thead class="bg-green-600 text-white">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" class="accent-green-600" /></th>
                            <th class="p-2 text-center">#</th>
                            <th class="p-2 text-center">LRN</th>
                            <th class="p-2 text-center">LAST NAME</th>
                            <th class="p-2 text-center">FIRST NAME</th>
                            <th class="p-2 text-center">MIDDLE NAME</th>
                            <th class="p-2 text-center">EXT</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                        <tr class="border-b">
                            <td class="p-2 text-center">
                                <input type="checkbox" class="row-checkbox accent-green-600" data-status="<?php echo ($i % 3 == 0) ? 'Retained' : 'Promoted'; ?>" />
                            </td>
                            <td class="p-2 text-center"><?php echo $i; ?></td>
                            <td class="p-2 text-center">107921120657</td>
                            <td class="p-2 text-center">CAMAHALAN</td>
                            <td class="p-2 text-center">JULIANNE HAZEL MAE</td>
                            <td class="p-2 text-center">DE BELEN</td>
                            <td class="p-2 text-center"></td>
                            <td class="p-2 text-center">
                                <?php echo ($i % 3 == 0) ? '<span class="text-yellow-600 font-semibold">Retained</span>' : '<span class="text-green-600 font-semibold">Promoted</span>'; ?>
                            </td>
                            <td class="p-2 text-center">
                                <button class="enrollBtn bg-green-500 text-white px-3 py-1 rounded hover:bg-green-700 transition">Enroll</button>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Modal for confirmation and success -->
    <div id="enrollModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm text-center">
            <div id="modalContent">
                <h2 class="text-lg font-semibold mb-4" id="modalTitle">Are you sure you want to enroll?</h2>
                <div class="flex justify-center gap-4">
                    <button id="confirmEnrollBtn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">Yes, Enroll</button>
                    <button id="cancelEnrollBtn" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Sidebar toggle logic and select logic -->
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

        // Select Retained/Promoted logic
        const selectRetained = document.getElementById('selectRetained');
        const selectPromoted = document.getElementById('selectPromoted');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');

        function setCheckboxesByStatus(status, checked) {
            rowCheckboxes.forEach(cb => {
                if (cb.dataset.status === status) {
                    cb.checked = checked;
                }
            });
        }

        selectRetained.addEventListener('change', function() {
            setCheckboxesByStatus('Retained', this.checked);
        });
        selectPromoted.addEventListener('change', function() {
            setCheckboxesByStatus('Promoted', this.checked);
        });

        // If user manually unchecks a row, uncheck the selectRetained/selectPromoted if not all are checked
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const retainedBoxes = Array.from(rowCheckboxes).filter(c => c.dataset.status === 'Retained');
                const promotedBoxes = Array.from(rowCheckboxes).filter(c => c.dataset.status === 'Promoted');
                selectRetained.checked = retainedBoxes.length > 0 && retainedBoxes.every(c => c.checked);
                selectPromoted.checked = promotedBoxes.length > 0 && promotedBoxes.every(c => c.checked);
            });
        });

        // Modal logic and enroll actions
        const enrollModal = document.getElementById('enrollModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalContent = document.getElementById('modalContent');
        const confirmEnrollBtn = document.getElementById('confirmEnrollBtn');
        const cancelEnrollBtn = document.getElementById('cancelEnrollBtn');
        let rowsToEnroll = [];
        let isBatch = false;

        // Helper to show modal
        function showModal(batch, rows) {
            isBatch = batch;
            rowsToEnroll = rows;
            modalTitle.textContent = batch ? 'Are you sure you want to enroll the selected students?' : 'Are you sure you want to enroll this student?';
            confirmEnrollBtn.style.display = '';
            cancelEnrollBtn.style.display = '';
            enrollModal.classList.remove('hidden');
        }
        // Helper to hide modal
        function hideModal() {
            enrollModal.classList.add('hidden');
        }
        // Helper to show success
        function showSuccess() {
            modalTitle.textContent = 'Successfully Enrolled!';
            confirmEnrollBtn.style.display = 'none';
            cancelEnrollBtn.textContent = 'Close';
        }
        // Batch Enroll button
        document.getElementById('batchEnrollBtn').addEventListener('click', function() {
            const checkedRows = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.closest('tr'));
            if (checkedRows.length === 0) {
                modalTitle.textContent = 'No students selected.';
                confirmEnrollBtn.style.display = 'none';
                cancelEnrollBtn.textContent = 'Close';
                enrollModal.classList.remove('hidden');
                return;
            }
            cancelEnrollBtn.textContent = 'Cancel';
            showModal(true, checkedRows);
        });
        // Single Enroll buttons
        document.querySelectorAll('.enrollBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                cancelEnrollBtn.textContent = 'Cancel';
                showModal(false, [btn.closest('tr')]);
            });
        });
        // Confirm Enroll
        confirmEnrollBtn.addEventListener('click', function() {
            // Remove rows from DOM
            rowsToEnroll.forEach(row => row.remove());
            showSuccess();
        });
        // Cancel/Close button
        cancelEnrollBtn.addEventListener('click', function() {
            hideModal();
            // Reset modal for next use
            confirmEnrollBtn.style.display = '';
            cancelEnrollBtn.textContent = 'Cancel';
        });
        // Hide modal when clicking outside
        enrollModal.addEventListener('click', function(e) {
            if (e.target === enrollModal) hideModal();
        });
    </script>
</body>
</html>
