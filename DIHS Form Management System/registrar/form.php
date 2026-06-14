<?php
include '../db_connect.php';
?>
<!DOCTYPE html>

<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>FORM</title>
        <link rel="stylesheet" href="style.css" />
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
        
        <!-- Google Material Symbols -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
        
        <!-- Custom styles for responsive layout -->
        <style>
            #mainContent {
                transition: margin-left 0.4s ease;
            }
            
            .container {
                transition: padding 0.4s ease;
            }
            
            .form {
                transition: max-width 0.4s ease, width 0.4s ease;
            }
            
            .form img[src*="dihslogo.png"] {
                transition: width 0.4s ease, height 0.4s ease, max-width 0.4s ease, max-height 0.4s ease;
            }
            
            @media (max-width: 1024px) {
                #mainContent {
                    margin-left: 0 !important;
                }
                
                .container {
                    padding-left: 16px !important;
                    padding-right: 16px !important;
                }
                
                .form {
                    max-width: 100% !important;
                    width: 100% !important;
                }
            }
        </style>
    </head>
    <body class="bg-gray-50 flex h-screen">
        <!-- Include Sidebar Component -->
        <?php include '../sidebar_component.php'; ?>
        
        <!-- Main Content -->
        <div id="mainContent" class="flex-1 flex flex-col overflow-hidden relative ml-[302px] transition-all duration-300" style="background: transparent;">
            <div class="container relative flex justify-center items-center min-h-screen py-10">
                <div class="form relative z-10 bg-white bg-opacity-90 rounded-lg shadow-lg p-6 my-10" style="background: rgba(255, 255, 255, 0.8);">
                    <!-- Watermark Logo centered in form -->
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; opacity: 0.2; pointer-events: none; z-index: 0;">
                        <img src="../images/dihslogo.png" alt="Logo" style="width: 400px; height: 400px; max-width: 80%; max-height: 80%; object-fit: contain; opacity: 1.5; pointer-events: none;">
                    </div>
                    <form action="#">
                    <div class="student-details">
                        <div class="input-box">
                            <span class="details">Last Name</span>
                            <input type="text" placeholder="Lastname" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Given Name</span>
                            <input type="text" placeholder="Givenname" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Middle Name</span>
                            <input type="text" placeholder="Middle name">
                        </div>
                        <div class="input-boxe">
                        <div class="select-box">
                            <span class="details">Gender</span>
                            <select name="gender" id="gender">
                                <option value="Select" selected disabled>-select-</option>
                                <option value="Female">F</option>
                                <option value="Male">M</option>
                            </select>
                        </div>
                        </div>
                        <div class="input-boxe">
                            <span class="details">Birthday</span>
                            <input type="date" id="Birthday" name="Birthday" required>
                        </div>
                        <div class="input-boxe">
                            <span class="details">Age</span>
                            <input type="number" placeholder="Age" id="Age" min="2" max="2" required>
                        </div>
                        <div class="input-boxe">
                            <span class="details">Mother Tongue</span>
                            <input type="text" placeholder="Mother Tongue" required>
                        </div>
                        <div class="input-boxe">
                            <span class="details">IP</span>
                            <input type="text" placeholder="Ethnic Group" required>
                        </div>
                        <div class="input-boxe">
                            <span class="details">Religion</span>
                            <input type="text" placeholder="Religion" required>
                        </div>
                        <div class="input-boxe">
                            <div class="select-box">
                            <span class="details">Track</span>
                            <select name="track" id="track">
                                <option value="Academic">Academic</option>
                                <option value="TVL">TVL</option>
                              </select>
                            </div>
                        </div>
                        <div class="input-boxe">
                            <div class="select-box">
                            <span class="details">Strand</span>
                            <select name="strand" id="strand">
                                <option value="">Select Track first</option>
                            </select>
                        </div>
                        </div>
                    </div>
                    <div class="Address">
                        <div class="input-box">
                            <span class="details">House Number</span>
                            <input type="text" placeholder="House Number" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Baranggay</span>
                            <input type="text" placeholder="Baranggay" required>
                        </div>
                        <div class="input-box">
                            <span class="details">City</span>
                            <input type="text" placeholder="City" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Province</span>
                            <input type="text" placeholder="Province" required>
                        </div>
                    </div>
                    <div class="father-details">
                        <!-- <header>Father</header> -->
                        <div class="input-box">
                            <span class="details">Last Name</span>
                            <input type="text" placeholder="Lastname" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Given Name</span>
                            <input type="text" placeholder="Givenname" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Middle Name</span>
                            <input type="text" placeholder="Middle name">
                        </div>
                    </div>
                    <div class="mother-details">
                        <div class="input-box">
                            <span class="details">Last Name</span>
                            <input type="text" placeholder="Lastname" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Given Name</span>
                            <input type="text" placeholder="Givenname" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Middle Name</span>
                            <input type="text" placeholder="Middle name">
                        </div>
                    </div>
                    <div class="guardian">
                        <input type="checkbox" id="parent" name="guradian"/>
                        <label for="parent">Same as parent</label>
                        <div class="guardian-details">
                        <div class="input-box">
                            <span class="details">Name</span>
                            <input type="text" placeholder="Name" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Relationship</span>
                            <input type="text" placeholder="Relationship" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Contact Number</span>
                            <input type="number" placeholder="Contact number" id="contact_no" name="contact_no" min="11" max="11">
                        </div>
                    </div>
                    </div>
                        <div class="submitbtn">
                            <input type="button" value="Edit" onclick="showEditConfirmation()">
                        </div>
                        </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirm Edit</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to replace the current data? This action cannot be undone.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="confirmEdit" class="px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300">
                        Yes
                    </button>
                    <button id="cancelEdit" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-24 ml-2 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        No
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar toggle logic -->
    <script>
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.getElementById('mainContent');
        const container = document.querySelector('.container');
        const form = document.querySelector('.form');

        // Function to update main content margin and form layout based on sidebar state
        function updateLayout() {
            const isCollapsed = sidebar.classList.contains('collapsed');
            const isMobile = window.innerWidth < 1024;
            const logo = document.querySelector('.form img[src*="dihslogo.png"]');
            
            if (isMobile) {
                // Mobile layout - no sidebar margin needed
                mainContent.style.marginLeft = '0';
                container.style.paddingLeft = '16px';
                container.style.paddingRight = '16px';
                form.style.maxWidth = '100%';
                form.style.width = '100%';
                // Smaller logo for mobile
                if (logo) {
                    logo.style.width = '250px';
                    logo.style.height = '250px';
                    logo.style.maxWidth = '60%';
                    logo.style.maxHeight = '60%';
                }
            } else {
                // Desktop layout
                if (isCollapsed) {
                    mainContent.style.marginLeft = '117px'; // 85px sidebar + 32px margin
                    // Larger logo when sidebar is collapsed (more space)
                    if (logo) {
                        logo.style.width = '500px';
                        logo.style.height = '500px';
                        logo.style.maxWidth = '90%';
                        logo.style.maxHeight = '90%';
                    }
                } else {
                    mainContent.style.marginLeft = '302px'; // 270px sidebar + 32px margin
                    // Medium logo when sidebar is expanded
                    if (logo) {
                        logo.style.width = '400px';
                        logo.style.height = '400px';
                        logo.style.maxWidth = '80%';
                        logo.style.maxHeight = '80%';
                    }
                }
                container.style.paddingLeft = '0';
                container.style.paddingRight = '0';
                form.style.maxWidth = '800px';
                form.style.width = 'auto';
            }
        }

        // Update layout on window resize
        window.addEventListener('resize', updateLayout);
        
        // Listen for sidebar toggle events
        window.addEventListener('sidebarToggle', updateLayout);
        
        // Listen for mobile menu toggle events
        window.addEventListener('mobileMenuToggle', updateLayout);
        
        // Initial layout update
        updateLayout();

        // Modal functionality
        function showEditConfirmation() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function hideEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Event listeners for modal buttons
        document.getElementById('confirmEdit').addEventListener('click', function() {
            // Here you can add the actual form submission logic
            alert('Data has been updated successfully!');
            hideEditModal();
            // Uncomment the line below if you want to actually submit the form
            // document.querySelector('form').submit();
        });

        document.getElementById('cancelEdit').addEventListener('click', function() {
            hideEditModal();
        });

        // Close modal when clicking outside of it
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideEditModal();
            }
        });

        // Dynamic strand options based on track selection
        const trackSelect = document.getElementById('track');
        const strandSelect = document.getElementById('strand');

        function updateStrandOptions() {
            const selectedTrack = trackSelect.value;
            
            // Clear current options
            strandSelect.innerHTML = '';
            
            if (selectedTrack === 'Academic') {
                const academicStrands = ['STEM', 'HUMSS', 'ABM'];
                academicStrands.forEach(strand => {
                    const option = document.createElement('option');
                    option.value = strand;
                    option.textContent = strand;
                    strandSelect.appendChild(option);
                });
            } else if (selectedTrack === 'TVL') {
                const tvlStrands = ['AS', 'EIM', 'CBM', 'AFA'];
                tvlStrands.forEach(strand => {
                    const option = document.createElement('option');
                    option.value = strand;
                    option.textContent = strand;
                    strandSelect.appendChild(option);
                });
            } else {
                // Default option when no track is selected
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Select Track first';
                strandSelect.appendChild(defaultOption);
            }
        }

        // Add event listener to track select
        trackSelect.addEventListener('change', updateStrandOptions);

        // Initialize strand options on page load
        updateStrandOptions();
    </script>
    </body>
</html>