<?php
include '../db_connect.php';
?>
<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Guidance - School Form 1</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; }
        /* Remove sidebar and sidebar-item custom styles, use sidebar_component.php styles */
        .excel-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .excel-frame {
            width: 100%;
            height: calc(100vh - 200px);
            border: none;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Watermark Logo -->
    <div class="fixed inset-0 flex items-center justify-center pointer-events-none z-0 select-none" style="opacity:0.07;">
        <img src="../images/dihslogo.png" alt="Logo" class="w-[900px] h-[900px] max-w-[85vw] max-h-[85vh] object-contain" draggable="false">
    </div>
    <!-- Main Content -->
    <main id="mainContent" class="container mx-auto px-4 py-8">
        <script>
        // Render zoom slider at the top of main content after DOMContentLoaded
        document.addEventListener('DOMContentLoaded', function() {
            const mainContent = document.getElementById('mainContent');
            if (mainContent && !document.getElementById('mainZoomSliderWrapper')) {
                const zoomWrapper = document.createElement('div');
                zoomWrapper.id = 'mainZoomSliderWrapper';
                zoomWrapper.className = 'flex justify-end mb-4';
                const zoomContainer = document.createElement('div');
                zoomContainer.className = 'flex items-center gap-2';
                const zoomLabel = document.createElement('span');
                zoomLabel.textContent = 'Zoom:';
                zoomLabel.className = 'text-gray-700 text-sm';
                const zoomSlider = document.createElement('input');
                zoomSlider.type = 'range';
                zoomSlider.min = '0.5';
                zoomSlider.max = '2';
                zoomSlider.step = '0.01';
                zoomSlider.value = tableZoom;
                zoomSlider.className = 'w-28 accent-purple-600';
                const zoomValue = document.createElement('span');
                zoomValue.textContent = Math.round(tableZoom * 100) + '%';
                zoomValue.className = 'text-gray-700 text-xs w-10 inline-block';
                zoomSlider.oninput = function() {
                    changeTableZoom(parseFloat(this.value), true);
                    zoomValue.textContent = Math.round(tableZoom * 100) + '%';
                };
                zoomContainer.appendChild(zoomLabel);
                zoomContainer.appendChild(zoomSlider);
                zoomContainer.appendChild(zoomValue);
                zoomWrapper.appendChild(zoomContainer);
                mainContent.insertBefore(zoomWrapper, mainContent.firstChild);
            }
        });
        </script>
        <div class="max-w-7xl w-full mx-auto">
            <!-- Header -->
            <div class="bg-white/90 shadow-sm border-b border-gray-200 p-4 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">School Form 1 (SF1)</h1>
                        <p class="text-gray-600">School Register for Senior High School</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- <button id="importBtn" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition flex items-center">
                            <i class="fas fa-upload mr-2"></i> Import Sheet
                        </button> -->
                        <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition flex items-center">
                            <i class="fas fa-download mr-2"></i> Export to Excel
                        </button>
                        <!-- <button onclick="exportToPDF()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center">
        <i class="fas fa-file-pdf mr-2"></i> Export to PDF
    </button> -->
<!-- <button onclick="saveAllData()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center" id="saveBtn">
                <i class="fas fa-save mr-2"></i> Save
            </button> -->
                        <!-- <button class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition flex items-center">
                            <i class="fas fa-print mr-2"></i> Print
                        </button> -->
                    </div>
                </div>
            </div>
            <!-- Excel Sheet Container -->
            <div class="bg-white rounded-b-lg shadow p-6 mt-0">
                <div class="excel-container">
                    <div id="excelEditor" class="excel-frame bg-white border border-gray-200 rounded-lg overflow-auto"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- Import Modal -->
    <div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Import Excel Sheet</h3>
                <button id="closeImportModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="importForm" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Excel File</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-400 transition-colors">
                        <input type="file" id="excelFile" name="excelFile" accept=".xlsx,.xls,.csv" class="hidden" required>
                        <div class="space-y-2">
                            <i class="fas fa-file-excel text-4xl text-gray-400"></i>
                            <p class="text-sm text-gray-600">Click to select or drag and drop</p>
                            <p class="text-xs text-gray-500">Supports .xlsx, .xls, .csv files</p>
                        </div>
                    </div>
                </div>
                
                <!-- <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sheet Name (Optional)</label>
                    <input type="text" id="sheetName" name="sheetName" placeholder="Enter sheet name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div> -->
                
                <!-- <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="replaceExisting" name="replaceExisting" class="mr-2">
                        <span class="text-sm text-gray-700">Replace existing sheet</span>
                    </label>
                </div> -->
                
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancelImport" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Import Sheet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main content scripts -->
    <script>
        // Main content initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Any initialization code can go here
        });
        // Listen for sidebar toggle events
        window.addEventListener('sidebarToggle', updateMainContentMargin);
        
        // Initial margin update
        updateMainContentMargin();

        // Import modal functionality
        const importBtn = document.getElementById('importBtn');
        const importModal = document.getElementById('importModal');
        const closeImportModal = document.getElementById('closeImportModal');
        const cancelImport = document.getElementById('cancelImport');
        const importForm = document.getElementById('importForm');
        const fileInput = document.getElementById('excelFile');
        const fileDropZone = document.querySelector('.border-dashed');

        // Open import modal
        importBtn.addEventListener('click', function() {
            importModal.classList.remove('hidden');
        });

        // Close import modal
        function closeModal() {
            importModal.classList.add('hidden');
            importForm.reset();
        }

        closeImportModal.addEventListener('click', closeModal);
        cancelImport.addEventListener('click', closeModal);

        // Close modal when clicking outside
        importModal.addEventListener('click', function(e) {
            if (e.target === importModal) {
                closeModal();
            }
        });

        // File drop zone functionality
        fileDropZone.addEventListener('click', function() {
            fileInput.click();
        });

        fileDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileDropZone.classList.add('border-purple-400', 'bg-purple-50');
        });

        fileDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileDropZone.classList.remove('border-purple-400', 'bg-purple-50');
        });

        fileDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            fileDropZone.classList.remove('border-purple-400', 'bg-purple-50');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });

        // File input change
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                updateFileDisplay(e.target.files[0]);
            }
        });

        function updateFileDisplay(file) {
            const dropZone = fileDropZone.querySelector('div');
            dropZone.innerHTML = `
                <i class="fas fa-file-excel text-4xl text-green-500"></i>
                <p class="text-sm text-gray-700 font-medium">${file.name}</p>
                <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
            `;
        }

        // Handle form submission
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(importForm);
            
            // Show loading state
            const submitBtn = importForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...';
            submitBtn.disabled = true;

            // Send form data to server
            fetch('upload_sheet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Import response:', data); // Debug log
                if (data.success) {
                    showNotification('Sheet imported successfully!', 'success');
                    closeModal();
                    if (data.data) {
                        renderImportedTable(data.data);
                        // Save imported data to database
                        saveImportedDataToDatabase(data.data);
                    }
                } else {
                    let debugMsg = data.debug ? (' Debug: ' + JSON.stringify(data.debug)) : '';
                    throw new Error((data.error || 'Upload failed') + debugMsg);
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showNotification(error.message || 'Upload failed. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Notification function
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Handle iframe load
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.querySelector('.excel-frame');
            iframe.addEventListener('load', function() {
                console.log('Excel sheet loaded successfully');
            });
        });
    </script>

    <script>
        // Editable Excel-like Table
        document.addEventListener('DOMContentLoaded', function() {
            createExcelEditor();
        });

        function createSF1Header() {
            const wrapper = document.createElement('div');
            wrapper.className = 'mb-6';
            wrapper.innerHTML = `
                <div class="flex flex-col md:flex-row items-center md:items-start gap-4 md:gap-8 mb-2">
                    <img src="../images/KElogo.png" alt="KE Logo" class="w-24 h-24 object-contain mb-2 md:mb-0">
                    <div class="flex-1">
                        <p class="text-lg font-semibold text-gray-800">School Forms 1 (SF 1) School Registrar</p>
                        <h6 class="text-sm text-gray-600 italic">(This replaces Form 1, Master List & STS Form 2-Family Background and Profile)</h6>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3">
                            <div>
                                <div class="text-xs text-gray-500">School ID</div>
                                <div class="font-bold text-gray-700">107921120657</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Region</div>
                                <div class="font-bold text-gray-700">IV-A</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Division</div>
                                <div class="font-bold text-gray-700">107921120657</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">District</div>
                                <div class="font-bold text-gray-700">107921120657</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">School Name</div>
                                <div class="font-bold text-gray-700">DASMARIÑAS INTEGRATED HIGH SCHOOL</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">School Year</div>
                                <div class="font-bold text-gray-700"></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Grade Level</div>
                                <div class="font-bold text-gray-700"></div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Section</div>
                                <div class="font-bold text-gray-700"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            return wrapper;
        }

        function createExcelEditor() {
            console.log('createExcelEditor called');
            const editor = document.getElementById('excelEditor');
            editor.innerHTML = '';
            // Add custom header above the table
            editor.appendChild(createSF1Header());
            // Table headers
            const headers = [
                'LRN',
                'NAME (Last Name, First Name, Name Extension, Middle Name)',
                'Sex',
                'BIRTHDATE',
                'AGE',
                'Religious Affiliation',
                'House No./ Street/ Sitio/ Purok',
                'Barangay',
                'Municipality/ City',
                'Province',
                "Father's Name",
                "Mother's Maiden Name",
                'Name (Guardian)',
                'Relationship',
                'Contact Number',
                'Remarks'
            ];
            // Create table
            const table = document.createElement('table');
            table.style.width = '100%';
            table.style.borderCollapse = 'collapse';
            table.style.fontSize = '12px';
            table.style.fontFamily = 'Arial, sans-serif';
            // Header row
            const headerRow = document.createElement('tr');
            headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                th.style.border = '1px solid #ccc';
                th.style.padding = '8px';
                th.style.backgroundColor = '#f0f0f0';
                th.style.fontWeight = 'bold';
                th.style.textAlign = 'center';
                th.style.minWidth = '120px';
                headerRow.appendChild(th);
            });
            table.appendChild(headerRow);
            
            // Load data from database
            loadDataFromDatabase(table, headers);
        }
        
        function loadDataFromDatabase(table, headers) {
            console.log('Loading data from database...');
            fetch('fetch_sf1_data.php')
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(result => {
                    console.log('Fetch result:', result);
                    if (result.success) {
                        console.log('Data loaded successfully, rows:', result.data.length);
                        populateTableWithData(table, headers, result.data);
                    } else {
                        console.error('Error loading data:', result.error);
                        // Create empty rows if no data
                        createEmptyRows(table, headers, 20);
                        const editor = document.getElementById('excelEditor');
                        editor.appendChild(table);
                        addTableControls(editor);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    // Create empty rows if error
                    createEmptyRows(table, headers, 20);
                    const editor = document.getElementById('excelEditor');
                    editor.appendChild(table);
                    addTableControls(editor);
                });
        }
        
        function populateTableWithData(table, headers, data) {
            console.log('Populating table with data:', data.length, 'rows');
            // Add existing data rows
            data.forEach((rowData, rowIndex) => {
                const row = document.createElement('tr');
                for (let j = 0; j < headers.length; j++) {
                    const cell = document.createElement('td');
                    cell.style.border = '1px solid #ccc';
                    cell.style.padding = '6px';
                    cell.style.minHeight = '30px';
                    cell.style.position = 'relative';
                    cell.contentEditable = false;
                    cell.style.cursor = 'text';
                    cell.textContent = rowData[j] || '';
                    
                    // Focus/blur events
                    cell.addEventListener('click', function() {
                        this.focus();
                        this.style.backgroundColor = '#fff3cd';
                    });
                    cell.addEventListener('blur', function() {
                        this.style.backgroundColor = '';
                        saveCellData(rowIndex, j, this.textContent);
                    });
                    cell.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            navigateToNextCell(this, 'down');
                        } else if (e.key === 'Tab') {
                            e.preventDefault();
                            navigateToNextCell(this, 'right');
                        }
                    });
                    row.appendChild(cell);
                }
                table.appendChild(row);
            });
            
            // Add additional empty rows if needed (minimum 20 rows total)
            const additionalRows = Math.max(0, 20 - data.length);
            if (additionalRows > 0) {
                createEmptyRows(table, headers, additionalRows, data.length);
            }
            
            const editor = document.getElementById('excelEditor');
            editor.appendChild(table);
            addTableControls(editor);
        }
        
        function createEmptyRows(table, headers, count, startIndex = 0) {
            for (let i = 0; i < count; i++) {
                const row = document.createElement('tr');
                for (let j = 0; j < headers.length; j++) {
                    const cell = document.createElement('td');
                    cell.style.border = '1px solid #ccc';
                    cell.style.padding = '6px';
                    cell.style.minHeight = '30px';
                    cell.style.position = 'relative';
                    cell.contentEditable = false;
                    cell.style.cursor = 'text';
                    
                    // Focus/blur events
                    cell.addEventListener('click', function() {
                        this.focus();
                        this.style.backgroundColor = '#fff3cd';
                    });
                    cell.addEventListener('blur', function() {
                        this.style.backgroundColor = '';
                        saveCellData(startIndex + i, j, this.textContent);
                    });
                    cell.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            navigateToNextCell(this, 'down');
                        } else if (e.key === 'Tab') {
                            e.preventDefault();
                            navigateToNextCell(this, 'right');
                        }
                    });
                    row.appendChild(cell);
                }
                table.appendChild(row);
            }
        }
        
        function addTableControls(editor) {
            // No controls below the table
        }

        // Table zoom logic
        let tableZoom = 1;
        function changeTableZoom(value, isAbsolute = false) {
            if (isAbsolute) {
                tableZoom = Math.max(0.5, Math.min(2, value));
            } else {
                tableZoom = Math.max(0.5, Math.min(2, tableZoom + value));
            }
            const table = document.querySelector('#excelEditor table');
            if (table) {
                table.style.transform = `scale(${tableZoom})`;
                table.style.transformOrigin = 'top left';
            }
        }
        function navigateToNextCell(currentCell, direction) {
            const table = currentCell.closest('table');
            const rows = table.rows;
            let currentRow = currentCell.parentElement;
            let currentCol = Array.from(currentRow.cells).indexOf(currentCell);
            let nextRow, nextCol;
            if (direction === 'down') {
                nextRow = currentRow.nextElementSibling;
                nextCol = currentCol;
            } else if (direction === 'right') {
                nextRow = currentRow;
                nextCol = currentCol + 1;
                if (nextCol >= currentRow.cells.length) {
                    nextRow = currentRow.nextElementSibling;
                    nextCol = 0;
                }
            }
            if (nextRow && nextRow.cells[nextCol]) {
                nextRow.cells[nextCol].focus();
            }
        }
        function saveCellData(row, col, data) {
            // Data will be saved to database when user clicks "Save Data" button
            // For now, we'll just store in memory or localStorage as backup
            const key = `excel_data_${row}_${col}`;
            localStorage.setItem(key, data);
        }
function saveAllData() {
    const table = document.querySelector('#excelEditor table');
    const saveBtn = document.getElementById('saveBtn');
    const originalText = saveBtn.textContent;
    
    // Show loading state
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    const data = [];
    for (let i = 1; i < table.rows.length; i++) { // Skip header row
        const rowData = [];
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            rowData.push(table.rows[i].cells[j].textContent);
        }
        data.push(rowData);
    }

    console.log('Saving data:', data); // Debug log to verify data being sent

    fetch('save_excel_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Save response status:', response.status); // Debug log
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(result => {
        console.log('Save result:', result); // Debug log
        if (result.success) {
            showNotification('Data saved successfully!', 'success');
        } else {
            showNotification('Error saving data: ' + result.error, 'error');
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        showNotification('Error saving data: ' + error.message, 'error');
    })
    .finally(() => {
        // Reset button state
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
    });
}
        function addNewRow() {
            const table = document.querySelector('#excelEditor table');
            const headers = [
                'LRN', 'NAME (Last Name, First Name, Name Extension, Middle Name)', 'Sex (M/F)',
                'BIRTHDATE (mm/dd/yyyy)', 'AGE', 'Religious Affiliation', 'House No./ Street/ Sitio/ Purok',
                'Barangay', 'Municipality/ City', 'Province', "Father's Name", "Mother's Maiden Name",
                'Guardian Name', 'Relationship', 'Contact Number', 'REMARKS'
            ];
            const newRow = document.createElement('tr');
            for (let j = 0; j < headers.length; j++) {
                const cell = document.createElement('td');
                cell.style.border = '1px solid #ccc';
                cell.style.padding = '6px';
                cell.style.minHeight = '30px';
                cell.style.position = 'relative';
                cell.contentEditable = false;
                cell.style.cursor = 'text';
                cell.addEventListener('click', function() {
                    this.focus();
                    this.style.backgroundColor = '#fff3cd';
                });
                cell.addEventListener('blur', function() {
                    this.style.backgroundColor = '';
                    const rowIndex = Array.from(table.rows).indexOf(this.parentElement) - 1;
                    saveCellData(rowIndex, j, this.textContent);
                });
                cell.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        navigateToNextCell(this, 'down');
                    } else if (e.key === 'Tab') {
                        e.preventDefault();
                        navigateToNextCell(this, 'right');
                    }
                });
                newRow.appendChild(cell);
            }
            table.appendChild(newRow);
            showNotification('New row added!', 'success');
        }
        function clearAllData() {
            if (confirm('Are you sure you want to clear all data? This action cannot be undone.')) {
                // Clear database
                fetch('clear_sf1_data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Clear table
                        const table = document.querySelector('#excelEditor table');
                        for (let i = 1; i < table.rows.length; i++) {
                            for (let j = 0; j < table.rows[i].cells.length; j++) {
                                table.rows[i].cells[j].textContent = '';
                            }
                        }
                        localStorage.clear();
                        showNotification('All data cleared from database!', 'success');
                    } else {
                        showNotification('Error clearing data: ' + result.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error clearing data', 'error');
                });
            }
        }
function exportToExcel() {
    const table = document.querySelector('#excelEditor table');
    const data = [];
    console.log('Table found:', table);
    console.log('Number of rows:', table ? table.rows.length : 0);
    for (let i = 1; i < table.rows.length; i++) {
        const rowData = [];
        let hasData = false;
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            const cellValue = table.rows[i].cells[j].textContent.trim();
            rowData.push(cellValue);
            if (cellValue !== '') {
                hasData = true;
            }
        }
        if (hasData) {
            data.push(rowData);
        }
    }
    console.log('Collected data:', data);
    console.log('Number of data rows:', data.length);
    if (data.length === 0) {
        console.log('No data found in table, using sample data for testing');
        data.push([
            '123456789012', 'Doe, John A. Smith', 'M', '01/15/2005', '18', 'Catholic',
            '123 Main St.', 'Barangay 1', 'Dasmariñas City', 'Cavite', 'John Doe Sr.',
            'Jane Smith', 'John Doe Sr.', 'Father', '09123456789', 'Active Student'
        ]);
        showNotification('No data found in table. Using sample data for export test.', 'info');
    }
    const exportBtn = document.querySelector('button[onclick="exportToExcel()"]');
    const originalText = exportBtn.textContent;
    exportBtn.textContent = 'Exporting...';
    exportBtn.disabled = true;
    fetch('export_to_excel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Excel export response status:', response.status);
        console.log('Excel export response headers:', response.headers);
        if (!response.ok) {
            return response.text().then(errorText => {
                console.error('Excel export error response:', errorText);
                try {
                    const errorData = JSON.parse(errorText);
                    throw new Error('Excel export failed: ' + (errorData.error || 'Unknown error'));
                } catch (e) {
                    throw new Error('Excel export failed: ' + errorText);
                }
            });
        }
        const contentType = response.headers.get('content-type');
        console.log('Excel export content type:', contentType);
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(errorData => {
                throw new Error('Excel export failed: ' + (errorData.error || 'Unknown error'));
            });
        }
        return response.blob();
    })
    .then(blob => {
        console.log('Excel export blob received:', blob);
        console.log('Excel export blob size:', blob.size);
        if (blob.size === 0) {
            throw new Error('Excel export returned empty file');
        }
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `School_Form_1_SF1_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        showNotification('Excel file exported successfully!', 'success');
    })
    .catch(error => {
        console.error('Export error:', error);
        showNotification('Error during Excel export: ' + error.message, 'error');
    })
    .finally(() => {
        exportBtn.textContent = originalText;
        exportBtn.disabled = false;
    });
}

function exportToPDF() {
    const table = document.querySelector('#excelEditor table');
    const data = [];
    console.log('Table found:', table);
    console.log('Number of rows:', table ? table.rows.length : 0);
    for (let i = 1; i < table.rows.length; i++) {
        const rowData = [];
        let hasData = false;
        for (let j = 0; j < table.rows[i].cells.length; j++) {
            const cellValue = table.rows[i].cells[j].textContent.trim();
            rowData.push(cellValue);
            if (cellValue !== '') {
                hasData = true;
            }
        }
        if (hasData) {
            data.push(rowData);
        }
    }
    console.log('Collected data for PDF:', data);
    console.log('Number of data rows:', data.length);
    if (data.length === 0) {
        console.log('No data found in table, using sample data for testing');
        data.push([
            '123456789012', 'Doe, John A. Smith', 'M', '01/15/2005', '18', 'Catholic',
            '123 Main St.', 'Barangay 1', 'Dasmariñas City', 'Cavite', 'John Doe Sr.',
            'Jane Smith', 'John Doe Sr.', 'Father', '09123456789', 'Active Student'
        ]);
        showNotification('No data found in table. Using sample data for PDF export test.', 'info');
    }
    const exportBtn = document.querySelector('button[onclick="exportToPDF()"]');
    const originalText = exportBtn.textContent;
    exportBtn.textContent = 'Exporting...';
    exportBtn.disabled = true;
    fetch('export_to_pdf.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('PDF export response status:', response.status);
        console.log('PDF export response headers:', response.headers);
        if (!response.ok) {
            return response.text().then(errorText => {
                console.error('PDF export error response:', errorText);
                try {
                    const errorData = JSON.parse(errorText);
                    throw new Error('PDF export failed: ' + (errorData.error || 'Unknown error'));
                } catch (e) {
                    throw new Error('PDF export failed: ' + errorText);
                }
            });
        }
        const contentType = response.headers.get('content-type');
        console.log('PDF export content type:', contentType);
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(errorData => {
                throw new Error('PDF export failed: ' + (errorData.error || 'Unknown error'));
            });
        }
        return response.blob();
    })
    .then(blob => {
        console.log('PDF export blob received:', blob);
        console.log('PDF export blob size:', blob.size);
        if (blob.size === 0) {
            throw new Error('PDF export returned empty file');
        }
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `School_Form_1_SF1_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        showNotification('PDF file exported successfully!', 'success');
    })
    .catch(error => {
        console.error('Export error:', error);
        showNotification('Error during PDF export: ' + error.message, 'error');
    })
    .finally(() => {
        exportBtn.textContent = originalText;
        exportBtn.disabled = false;
    });
}
    </script>
    <script>
        function renderImportedTable(data) {
            const editor = document.getElementById('excelEditor');
            editor.innerHTML = '';
            // Add custom header above the table
            editor.appendChild(createSF1Header());
            if (!data || !Array.isArray(data) || data.length === 0) {
                editor.innerHTML += '<p class="text-gray-500">No data found in the imported sheet.</p>';
                return;
            }
            const table = document.createElement('table');
            table.style.width = '100%';
            table.style.borderCollapse = 'collapse';
            table.style.fontSize = '12px';
            table.style.fontFamily = 'Arial, sans-serif';

            // Header row
            const headerRow = document.createElement('tr');
            data[0].forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                th.style.border = '1px solid #ccc';
                th.style.padding = '8px';
                th.style.backgroundColor = '#f0f0f0';
                th.style.fontWeight = 'bold';
                th.style.textAlign = 'center';
                th.style.minWidth = '120px';
                headerRow.appendChild(th);
            });
            table.appendChild(headerRow);

            // Data rows
            for (let i = 1; i < data.length; i++) {
                const row = document.createElement('tr');
                data[i].forEach(cellData => {
                    const cell = document.createElement('td');
                    cell.textContent = cellData;
                    cell.style.border = '1px solid #ccc';
                    cell.style.padding = '6px';
                    cell.style.minHeight = '30px';
                    cell.style.position = 'relative';
                    cell.contentEditable = false;
                    cell.style.cursor = 'text';
                    row.appendChild(cell);
                });
                table.appendChild(row);
            }
            editor.appendChild(table);
            addTableControls(editor); // Reuse your existing controls
        }
    </script>
    <script>
        function saveImportedDataToDatabase(tableData) {
            fetch('save_excel_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(tableData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showNotification('Imported data saved to database!', 'success');
                } else {
                    showNotification('Error saving imported data: ' + result.error, 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving imported data', 'error');
            });
        }
    </script>
</body>
</html> 
