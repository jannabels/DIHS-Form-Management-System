<?php  
session_start();
require_once __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Guidance') {
    header("Location: /systemdihs/login/index.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Class</title>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #3b82f6;
    --primary-hover: #2563eb;
    --success: #10b981;
    --success-hover: #059669;
    --border: #e5e7eb;
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --bg-light: #f9fafb;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --radius: 0.5rem;
    --transition: all 0.2s ease-in-out;
}

* {
    box-sizing: border-box;
}

body {
    background-color: #f5f7fa;
    min-height: 100vh;
    display: flex;
    flex-direction: row;
    padding: 0;
    margin: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--text-primary);
    line-height: 1.5;
}

.main-container {
    flex: 1;
    margin-left: 16rem;
    transition: var(--transition);
    width: calc(100% - 16rem);
    padding: 1.5rem;
}

.form-card {
    max-width: 64rem;
    margin: 0 auto;
    background: #fff;
    border-radius: var(--radius);
    padding: 2.5rem 3rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    width: 100%;
}

h1 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 1rem;
    text-align: center;
    position: relative;
    padding-bottom: 0.5rem;
}

h1:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 4rem;
    height: 0.25rem;
    background: var(--primary);
    border-radius: 2px;
}

.form-group {
    margin-bottom: 1.25rem;
}

label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.help-text {
    font-size: 0.65rem;
    color: var(--text-secondary);
    margin-top: 0.15rem;
    display: block;
}

input[type="text"],
input[type="email"],
input[type="password"],
select,
textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: var(--text-primary);
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

input[type="text"]:focus,
input[type="email"]:focus,
input[type="password"]:focus,
select:focus,
textarea:focus {
    border-color: var(--primary);
    outline: 0;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.25rem;
}

.radio-option {
    position: relative;
    flex: 1 0 calc(50% - 0.5rem);
    min-width: 120px;
}

.radio-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.radio-option label {
    display: flex;
    align-items: center;
    font-size: 0.75rem;
    line-height: 1.15rem;
    padding: 0.375rem 0.5rem;
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    background-color: #fff;
    transition: var(--transition);
    margin: 0;
    text-align: center;
    justify-content: center;
}

.radio-option input[type="radio"]:checked + label {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.radio-option input[type="radio"]:focus + label {
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
}

.radio-option:hover label {
    border-color: var(--primary);
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.5;
    text-align: center;
    text-decoration: none;
    white-space: nowrap;
    border-radius: var(--radius);
    border: 1px solid transparent;
    cursor: pointer;
    transition: var(--transition);
    width: 100%;
}

.btn-primary {
    background: var(--success);
    color: white;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.8125rem;
}

.btn-primary:hover {
    background: var(--success-hover);
    transform: translateY(-1px);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Track specific styles */
.track-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0.75rem 0.5rem;
    border-radius: 0.5rem;
    background: var(--bg-light);
    border: 1px solid var(--border);
    transition: var(--transition);
    cursor: pointer;
}

.track-option:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.track-option input[type="radio"]:checked + .track-label {
    border-color: var(--primary);
    background: rgba(59, 130, 246, 0.05);
}

.track-label {
    width: 100%;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: var(--transition);
}

.track-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.track-desc {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

/* Tabs */
.tabs-container {
    margin-bottom: 1.5rem;
}

.tabs {
    border-bottom: 1px solid var(--border);
    margin-bottom: 1.5rem;
}

.tab-btn {
    padding: 0.5rem 1rem;
    font-size: 0.65rem;
    line-height: 1.1rem;
    color: var(--text-secondary);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    margin-bottom: -1px;
}

.tab-btn:hover {
    color: var(--primary);
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    font-weight: 600;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Grid layout for tracks */
.track-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.25rem;
    margin-top: 0.75rem;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .main-container {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
    }
    
    .form-card {
        padding: 1.5rem;
    }
    
    .track-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}

@media (max-width: 640px) {
    .radio-option {
        flex: 1 0 100%;
    }
    
    .track-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Custom select styling */
.select-wrapper {
    position: relative;
}

.select-wrapper:after {
    content: '▼';
    font-size: 0.625rem;
    color: var(--text-secondary);
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}

select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    padding-right: 2.5rem;
    background-image: none;
}

/* Focus styles */
*:focus {
    outline: none;
}

.focus-visible:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}
</style>
</head>

<body>
<!-- Include the unified sidebar -->
<?php include '../includes/unified_sidebar.php'; ?>

<!-- Main Content -->
<div class="main-container">

<div class="form-card">
    <h1>Create New Class</h1>

    <form id="classForm" onsubmit="event.preventDefault();">
        <!-- Section Code -->
        <div class="form-group">
            <label for="sectionCode">Section Code</label>
            <input type="text" id="sectionCode" name="classname" class="focus-visible" readonly>
            <span class="help-text">Automatically generated as: [TRACK] [GRADE]-[SECTION]</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Section Letter -->
                <div class="form-group">
                    <label for="section_letter">Section Letter</label>
                    <input type="text" id="section_letter" maxlength="1" required 
                           oninput="this.value=this.value.toUpperCase(); updateSectionCode();"
                           class="text-center focus-visible" placeholder="A">
                </div>

                <!-- Grade Level -->
                <div class="form-group">
                    <label>Grade Level</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="grade11" name="grade_level" value="Grade 11" onchange="updateSectionCode()">
                            <label for="grade11">Grade 11</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="grade12" name="grade_level" value="Grade 12" onchange="updateSectionCode()">
                            <label for="grade12">Grade 12</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Adviser -->
                <div class="form-group">
                    <label for="adviser">Adviser</label>
                    <div class="select-wrapper">
                        <select id="adviser" required class="focus-visible">
                            <option value="">Select an adviser</option>
                            <?php
                            // Get all active advisers who don't have a section assigned yet
                            $q = $conn->query("
                                SELECT a.* 
                                FROM accounts a
                                LEFT JOIN section s ON a.id = s.adviser
                                WHERE LOWER(a.Role) = 'adviser' 
                                AND a.Status = 'active'
                                AND s.section_id IS NULL
                                ORDER BY a.`Last Name`, a.`First Name`
                            ");
                            
                            $advisers = [];
                            while ($a = $q->fetch_assoc()) {
                                $name = htmlspecialchars($a['Last Name'] . ', ' . $a['First Name']);
                                echo "<option value='{$a['id']}'>$name</option>";
                                $advisers[] = $a;
                            }
                            
                            // If no advisers available, show a message
                            if (empty($advisers)) {
                                echo "<option value='' disabled>No available advisers (all are already assigned to sections)</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Semester -->
                <div class="form-group">
                    <label>Semester</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="sem1" name="semester" value="1st Semester" required>
                            <label for="sem1">1st Semester</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="sem2" name="semester" value="2nd Semester">
                            <label for="sem2">2nd Semester</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Track Selection -->
        <div class="form-group mt-6">
            <label>Select Track Type</label>
            
            <!-- Tab Navigation -->
            <div class="tabs-container mb-4">
                <div class="tabs flex border-b border-gray-200">
                    <button type="button" class="tab-btn active" data-tab="academic">Academic Track</button>
                    <button type="button" class="tab-btn" data-tab="tvl">TVL Track</button>
                </div>
                <div class="tab-content">
                    <!-- Academic Tracks -->
                    <div class="tab-pane active" id="academic-tracks">
                        <div class="track-grid mt-4">
                            <div class="track-option">
                                <input type="radio" id="track-humss" name="track" value="HUMSS" onchange="updateSectionCode()">
                                <div class="track-label">
                                    <div class="track-name">HUMSS</div>
                                    <div class="track-desc">Humanities & Social Sciences</div>
                                </div>
                            </div>
                            
                            <div class="track-option">
                                <input type="radio" id="track-stem" name="track" value="STEM" onchange="updateSectionCode()">
                                <div class="track-label">
                                    <div class="track-name">STEM</div>
                                    <div class="track-desc">Science & Technology</div>
                                </div>
                            </div>
                            
                            <div class="track-option">
                                <input type="radio" id="track-abm" name="track" value="ABM" onchange="updateSectionCode()">
                                <div class="track-label">
                                    <div class="track-name">ABM</div>
                                    <div class="track-desc">Business & Management</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TVL Tracks -->
                    <div class="tab-pane hidden" id="tvl-tracks">
                        <div class="track-grid mt-4">
                            <div class="track-option">
                                <input type="radio" id="track-as" name="track" value="AS" onchange="updateSectionCode()">
                                <div class="track-label">
                                    <div class="track-name">AS</div>
                                    <div class="track-desc">Automotive Servicing</div>
                                </div>
                            </div>
                            
                            <div class="track-option">
                                <input type="radio" id="track-eim" name="track" value="EIM" onchange="updateSectionCode()">
                                <div class="track-label">
                                    <div class="track-name">EIM</div>
                                    <div class="track-desc">Electrical Installation</div>
                                </div>
                            </div>
                            
                            <div class="track-option">
                                <input type="radio" id="track-cbm" name="track" value="CBM" onchange="updateSectionCode()">
                                <div class="track-label">
                                    <div class="track-name">CBM</div>
                                    <div class="track-desc">Computer & Business</div>
                                </div>
                            </div>
                            
                            <div class="track-option">
                                <input type="radio" id="track-afa" name="track" value="AFA" onchange="updateSectionCode()">
                                <div class="track-label">
                                    <div class="track-name">AFA</div>
                                    <div class="track-desc">Agri-Fishery Arts</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-8">
            <button type="button" id="submitBtn" class="btn btn-primary focus-visible">
                <i class="fa fa-plus-circle mr-2"></i> Create Class
            </button>
        </div>

    </form>
</div>

<!-- Required JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Function to update section code
function updateSectionCode() {
    const track = document.querySelector("input[name='track']:checked")?.value || "";
    const grade = document.querySelector("input[name='grade_level']:checked")?.value.replace("Grade ", "") || "";
    const section = document.getElementById("section_letter")?.value.trim().toUpperCase() || "";

    if (track && grade && section) {
        document.getElementById("sectionCode").value = `${track} ${grade}-${section}`;
    }
    return !!track && !!grade && !!section;
}

// Helper function to show error messages
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#dc3545'
    });
}

// Form submission handler
function handleFormSubmit() {
    console.log('Form submission started');
    
    const form = document.getElementById('classForm');
    const formData = new FormData(form);
    
    // Get form values
    const selectedTrack = document.querySelector('input[name="track"]:checked');
    const gradeLevel = document.querySelector('input[name="grade_level"]:checked');
    const semester = document.querySelector('input[name="semester"]:checked');
    const adviserId = document.getElementById('adviser').value;
    
    // Ensure section code is generated and get its value
    const sectionCodeGenerated = updateSectionCode();
    const className = document.getElementById('sectionCode').value;

    // Validate form
    if (!selectedTrack) {
        showError('Please select a track');
        return;
    }
    if (!gradeLevel) {
        showError('Please select a grade level');
        return;
    }
    if (!semester) {
        showError('Please select a semester');
        return;
    }
    if (!adviserId) {
        showError('Please select an adviser');
        return;
    }
    if (!sectionCodeGenerated) {
        showError('Please ensure all section details are filled out (track, grade level, and section letter)');
        return;
    }

    // Prepare form data
    formData.append('track', selectedTrack.value);
    formData.append('grade_level', gradeLevel.value);
    formData.append('semester', semester.value);
    formData.append('adviser_id', adviserId);
    formData.append('classname', className);

    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span>Creating...</span>';

    // Show loading message
    Swal.fire({
        title: 'Creating Section',
        text: 'Please wait while we create the new section...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Submit form via AJAX
    fetch('save_class.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Success:', data);
        Swal.close();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: `
                    <div class="text-left">
                        <p class="mb-2"><strong>Section ID:</strong> ${data.data.section_id}</p>
                        <p class="mb-2"><strong>Class Name:</strong> ${data.data.class_name}</p>
                        <p class="mb-2"><strong>Track:</strong> ${data.data.track}</p>
                        <p class="mb-2"><strong>Grade Level:</strong> ${data.data.grade_level}</p>
                        <p class="mb-2"><strong>Semester:</strong> ${data.data.semester}</p>
                        <p class="mb-2"><strong>Adviser:</strong> ${data.data.adviser}</p>
                    </div>
                `,
                confirmButtonText: 'Create Another Class',
                confirmButtonColor: '#16A34A'
            }).then(() => {
                // Reset form
                form.reset();
                if ($.fn.select2) {
                    $('#adviser').val(null).trigger('change');
                }
                $('input[type="radio"]').prop('checked', false);
                $('.radio-option').removeClass('selected');
            });
        } else {
            showError(data.message || 'An error occurred while creating the class.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while creating the class. Please try again.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Initialize when document is ready
$(document).ready(function() {
    console.log('Document ready');
    
    // Initialize Select2 for adviser dropdown
    if ($.fn.select2) {
        $('#adviser').select2({
            placeholder: 'Search for an adviser...',
            width: '100%',
            allowClear: true,
            dropdownParent: $('.form-card')
        });
    }
    
    // Tab functionality
    $('.tab-btn').on('click', function() {
        const tabId = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('active').addClass('hidden');
        $(`#${tabId}-tracks`).addClass('active').removeClass('hidden');
    });
    
    // Add selected class to radio button containers when clicked and update section code
    $('.radio-option input[type="radio"][name="track"], .radio-option input[type="radio"][name="grade_level"]').on('change', function() {
        if (this.checked) {
            $(this).closest('.radio-option').addClass('selected')
                .siblings('.radio-option').removeClass('selected');
            updateSectionCode();
        }
    });
    
    // Handle section letter input
    $('#section_letter').on('input', function() {
        this.value = this.value.toUpperCase();
        updateSectionCode();
    });
    
    // Add click event listener to the submit button
    $('#submitBtn').on('click', handleFormSubmit);
    
    // Initial update of section code
    updateSectionCode();
});
</script>
</div>

</body>
</html>