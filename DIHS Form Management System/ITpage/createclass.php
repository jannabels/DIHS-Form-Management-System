<?php 
session_start();
include '../db_connect.php';

// Check for errors and form data in session
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']); // Clear session data
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Create Class Section</title>
        <link rel="stylesheet" href="gstyle.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Select2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <!-- SweetAlert2 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <style>
            /* Make form containers transparent to show watermark */
            .container, .section, .box2, .box3 {
                background: transparent !important;
            }
            
            /* Ensure main content area is transparent */
            #main {
                background: transparent !important;
                position: relative;
            }
            
            /* Make individual form elements have slight background for readability */
            .input-box input, .box8 {
                background: rgba(255, 255, 255, 0.8) !important;
            }
            
            /* White background for grade offerings and academic/TVL sections */
            .box6, .box7 {
                background: white;
            }
            
            /* Remove blur effects and white backgrounds */
            .container .section {
                background: white;
                backdrop-filter: none !important;
            }
            
            .container .section .upper {
                background: white;
                backdrop-filter: none !important;
            }
            
            .container .class-section {
                background: white;
                backdrop-filter: none !important;
            }
            
            .container .class-adviser {
                background: transparent !important;
                backdrop-filter: none !important;
            }
            
            .class-adviser .adviser {
                background: white;
                backdrop-filter: none !important;
            }
            
            .box3 h3 {
                background: white;
                backdrop-filter: none !important;
            }
            
            .btn {
                background: white;
            }
            
            /* Watermark logo positioning */
            .watermark-logo {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.2;
                pointer-events: none;
                z-index: 0;
                transition: all 0.4s ease;
            }
            
            .watermark-logo img {
                width: 600px;
                height: 600px;
                max-width: 80vw;
                max-height: 80vh;
                object-fit: contain;
                pointer-events: none;
            }
            
            /* Responsive adjustments for mobile */
            @media (max-width: 1024px) {
                .watermark-logo {
                    top: calc(50% + 28px); /* Adjust for mobile sidebar height */
                } 
            }
            
            /* Adviser search styles */
            .adviser-search {
                display: flex;
                gap: 10px;
                margin-top: 10px;
                position: relative;
            }
            
            /* Select2 styles */
            .select2-container--default .select2-selection--single {
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                background: rgba(255, 255, 255, 0.8);
                height: auto;
                font-size: 16px;
            }
            
            .select2-container--default .select2-selection--single:focus {
                outline: none;
                border-color: #4CAF50;
            }
            
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: #333;
                line-height: normal;
            }
            
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 100%;
            }
            
            .select2-container {
                width: 100% !important;
            }
            
            .select2-dropdown {
                background: white;
                border: 1px solid #ccc;
                border-radius: 4px;
            }
            
            .select2-results__option {
                padding: 8px;
                color: black;
            }
            
            .select2-results__option--highlighted {
                background: #4CAF50 !important;
                color: white !important;
            }
            
            .select2-results__option[aria-disabled="true"] {
                color: #999;
            }
            
            /* Selected adviser display */
            #selected-adviser-display {
                margin-top: 10px;
                font-size: 14px;
                color: #333;
            }
            
            /* Set Adviser button */
            .adviser-search button {
                padding: 8px 16px;
                background: #4CAF50;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
            
            .adviser-search button:hover {
                background: #45a049;
            }
            
            .adviser-search button:disabled {
                background: #cccccc;
                cursor: not-allowed;
            }
            
            /* Error message styles */
            .error-messages {
                margin-bottom: 10px;
                color: #d32f2f;
                font-size: 14px;
            }
            .error-messages li {
                margin-bottom: 5px;
            }
            
            /* Radio button styles */
            .radio-group {
                margin-bottom: 15px;
            }
            .radio-group label {
                margin-right: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <?php include '../sidebar_component_guidance.php'; ?>
        
        <div id="main">
            <!-- Watermark Logo -->
            <div class="watermark-logo">
                <img src="../images/dihslogo.png" alt="Logo">
            </div>
            
            <div class="container">
                <form action="save_class.php" method="post">
                    <div class="section">
                        <!-- Display errors if any -->
                        <?php if (!empty($errors)): ?>
                            <div class="error-messages">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <div class="upper">
                            <div class="new-class">
                                <p>New Class</p>
                            </div>
                            <div class="box">
                                <div class="add-class">
                                    <i class="fa fa-plus"></i>
                                </div>
                            </div>
                        </div>
                        <div class="class-class">
                            <div class="box2">
                                <div class="class-section">
                                    <div class="input-box">
                                        <span class="class">Class name</span>
                                        <input type="text" name="classname" placeholder="classname" required value="<?php echo isset($form_data['classname']) ? htmlspecialchars($form_data['classname']) : ''; ?>">
                                    </div>
                                    <div class="box6">
                                        <p class="text-success">Grade Level</p>
                                        <div class="radio-group">
                                            <input type="radio" id="grade11" name="grade_level" value="Grade 11" <?php echo (isset($form_data['grade_level']) && $form_data['grade_level'] === '11') ? 'checked' : ''; ?> required>
                                            <label for="grade11">Grade 11</label>
                                            <input type="radio" id="grade12" name="grade_level" value="Grade 12" <?php echo (isset($form_data['grade_level']) && $form_data['grade_level'] === '12') ? 'checked' : ''; ?>>
                                            <label for="grade12">Grade 12</label>
                                        </div>
                                        <p class="text-success">Semester</p>
                                        <div class="radio-group">
                                            <input type="radio" id="sem1" name="semester" value="1st Semester" <?php echo (isset($form_data['semester']) && $form_data['semester'] === '1st Semester') ? 'checked' : ''; ?> required>
                                            <label for="sem1">1st Semester</label>
                                            <input type="radio" id="sem2" name="semester" value="2nd Semester" <?php echo (isset($form_data['semester']) && $form_data['semester'] === '2nd Semester') ? 'checked' : ''; ?>>
                                            <label for="sem2">2nd Semester</label>
                                        </div>
                                    </div>
                                    <div class="box7">
                                        <p>Academic</p>
                                        <input type="radio" id="stem" name="track" value="STEM" <?php echo (isset($form_data['track']) && $form_data['track'] === 'STEM') ? 'checked' : ''; ?>/>
                                        <label for="stem">Science, Technology, Engineering, and Mathematics (STEM)</label><br>
                                        <input type="radio" id="humss" name="track" value="HUMSS" <?php echo (isset($form_data['track']) && $form_data['track'] === 'HUMSS') ? 'checked' : ''; ?>/>
                                        <label for="humss">Humanities and Social Sciences (HUMSS)</label><br>
                                        <input type="radio" id="abm" name="track" value="ABM" <?php echo (isset($form_data['track']) && $form_data['track'] === 'ABM') ? 'checked' : ''; ?>/>
                                        <label for="abm">Accountancy, Business, and Management (ABM)</label>
                                        <p>TVL</p>
                                        <input type="radio" id="as" name="track" value="AS" <?php echo (isset($form_data['track']) && $form_data['track'] === 'AS') ? 'checked' : ''; ?>/>
                                        <label for="as">Automotive Servicing (AS)</label><br>
                                        <input type="radio" id="eim" name="track" value="EIM" <?php echo (isset($form_data['track']) && $form_data['track'] === 'EIM') ? 'checked' : ''; ?>/>
                                        <label for="eim">Electrical Installation and Maintenance (EIM)</label><br>
                                        <input type="radio" id="cbm" name="track" value="CBM" <?php echo (isset($form_data['track']) && $form_data['track'] === 'CBM') ? 'checked' : ''; ?>/>
                                        <label for="cbm">Computer and Business Management (CBM)</label><br>
                                        <input type="radio" id="afa" name="track" value="AFA" <?php echo (isset($form_data['track']) && $form_data['track'] === 'AFA') ? 'checked' : ''; ?>/>
                                        <label for="afa">Agri-Fishery Arts (AFA)</label>
                                    </div>
                                </div>
                            </div>
                            <div class="box3">
                                <div class="class-adviser">
                                    <div class="adviser">
                                        <div class="adviser-search">
                                            <select id="adviser-dropdown" name="adviser">
                                                <option value="" disabled selected>Select an adviser</option>
                                            </select>
                                            <button type="button" id="set-adviser" disabled>Set Adviser</button>
                                            <input type="hidden" id="adviser-id" name="adviser_id" value="<?php echo isset($form_data['adviser_id']) ? htmlspecialchars($form_data['adviser_id']) : ''; ?>">
                                        </div>
                                    </div>
                                    <h3 id="adviser-status">NO ADVISER</h3>
                                    <div class="p-3 fs-5" id="selected-adviser-display" style="display: none;"></div>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="btns">
                            <div class="box4">
                                <div class="back">
                                    <input type="button" value="Reset" onclick="resetForm()">
                                </div>
                            </div>
                            <div class="box5">
                                <div class="save">
                                    <input type="submit" value="Add Section">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- jQuery, Select2, and SweetAlert2 JS -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
        <script>
            // Function to adjust logo position based on sidebar state
            function adjustLogoPosition() {
                const sidebar = $('.sidebar');
                const watermarkLogo = $('.watermark-logo');
                const main = $('#main');
                
                if (!sidebar.length || !watermarkLogo.length || !main.length) return;
                
                const sidebarRect = sidebar[0].getBoundingClientRect();
                const mainRect = main[0].getBoundingClientRect();
                
                watermarkLogo.css({
                    left: '50%',
                    top: '50%',
                    transform: 'translate(-50%, -50%)'
                });
            }
            
            window.addEventListener('sidebarToggle', function(e) {
                const body = document.body;
                if (e.detail.collapsed) {
                    body.classList.add('sidebar-collapsed');
                } else {
                    body.classList.remove('sidebar-collapsed');
                }
                
                setTimeout(adjustLogoPosition, 100);
            });
            
            window.addEventListener('mobileMenuToggle', function(e) {
                const main = $('#main');
                if (e.detail.active) {
                    main.css('marginTop', '56px');
                } else {
                    main.css('marginTop', '0');
                }
                
                setTimeout(adjustLogoPosition, 100);
            });

            // Reset form function
            function resetForm() {
                $('form')[0].reset();
                $('#adviser-dropdown').val(null).trigger('change');
                $('#adviser-id').val('');
                $('#adviser-status').text('NO ADVISER');
                $('#selected-adviser-display').hide();
                $('#set-adviser').prop('disabled', true);
            }

            // Adviser search functionality with Select2
            $(document).ready(function() {
                // Initialize Select2
                $('#adviser-dropdown').select2({
                    placeholder: 'Search advisers...',
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: 'search_advisers.php',
                        type: 'POST',
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return {
                                search: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            if (data.error) {
                                return {
                                    results: [{ id: '', text: data.error, disabled: true }]
                                };
                            }
                            if (data.length === 0) {
                                return {
                                    results: [{ id: '', text: 'No advisers found', disabled: true }]
                                };
                            }
                            return {
                                results: data.map(adviser => ({
                                    id: adviser.id,
                                    text: adviser.name,
                                    selected: '<?php echo isset($form_data['adviser_id']) ? $form_data['adviser_id'] : ''; ?>' === String(adviser.id)
                                }))
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 0
                });

                // Pre-select adviser if form data exists
                <?php if (!empty($form_data['adviser_id'])): ?>
                    $.ajax({
                        url: 'search_advisers.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { search: '' },
                        success: function(data) {
                            if (!data.error && data.length > 0) {
                                const adviser = data.find(a => a.id == '<?php echo $form_data['adviser_id']; ?>');
                                if (adviser) {
                                    const option = new Option(adviser.name, adviser.id, true, true);
                                    $('#adviser-dropdown').append(option).trigger('change');
                                    $('#adviser-status').text('ADVISER SELECTED');
                                    $('#selected-adviser-display').text(`Selected: ${adviser.name}`).show();
                                    $('#set-adviser').prop('disabled', false);
                                }
                            }
                        }
                    });
                <?php endif; ?>

                // Enable/disable Set Adviser button based on selection
                $('#adviser-dropdown').on('select2:select', function(e) {
                    const data = e.params.data;
                    $('#set-adviser').prop('disabled', !data.id);
                });

                $('#adviser-dropdown').on('select2:unselect', function() {
                    $('#set-adviser').prop('disabled', true);
                    $('#adviser-id').val('');
                    $('#adviser-status').text('NO ADVISER');
                    $('#selected-adviser-display').hide();
                });

                // Handle Set Adviser button click
                $('#set-adviser').on('click', function() {
                    const selectedOption = $('#adviser-dropdown').select2('data')[0];
                    if (selectedOption && selectedOption.id) {
                        $('#adviser-id').val(selectedOption.id);
                        $('#adviser-status').text('ADVISER SELECTED');
                        $('#selected-adviser-display').text(` ${selectedOption.text}`).show();
                    }
                });

                // Show SweetAlert2 on success
                <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Section successfully added!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                <?php endif; ?>

                // Initialize logo position
                adjustLogoPosition();
            });
        </script>
    </body>
</html>