/**
 * Student Management System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Confirm delete actions
    document.querySelectorAll('.btn-delete, .delete-confirm').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Select all checkbox functionality
    var selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.select-item');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }

    // Form validation
    var forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = document.querySelector(this.dataset.target);
            var icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });

    // Search filter for tables
    var searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var searchTerm = this.value.toLowerCase();
            var tableRows = document.querySelectorAll('#dataTable tbody tr');
            
            tableRows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

/**
 * Profile Picture Upload with Preview
 */
function initProfilePictureUpload() {
    var fileInput = document.getElementById('profilePictureInput');
    var preview = document.getElementById('profilePicturePreview');
    var form = document.getElementById('profilePictureForm');

    if (fileInput && preview) {
        fileInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            
            if (file) {
                // Validate file type
                var allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('error', 'Please select a valid image file (JPG, PNG, or GIF)');
                    fileInput.value = '';
                    return;
                }

                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('error', 'File size must be less than 5MB');
                    fileInput.value = '';
                    return;
                }

                // Show preview
                var reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                };
                reader.readAsDataURL(file);

                // Upload via AJAX
                if (form) {
                    uploadProfilePicture(form);
                }
            }
        });
    }
}

/**
 * Upload profile picture via AJAX
 */
function uploadProfilePicture(form) {
    var formData = new FormData(form);
    formData.append('ajax', '1');

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Profile picture updated successfully!');
        } else {
            showAlert('error', data.message || 'Failed to upload profile picture');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while uploading the profile picture');
    });
}

/**
 * Show alert message
 */
function showAlert(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 
                     type === 'error' ? 'alert-danger' : 
                     type === 'warning' ? 'alert-warning' : 'alert-info';
    
    var alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    var container = document.querySelector('.container-fluid');
    if (container) {
        var alertContainer = document.createElement('div');
        alertContainer.innerHTML = alertHtml;
        container.insertBefore(alertContainer.firstChild, container.firstChild);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            var alert = container.querySelector('.alert');
            if (alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
}

/**
 * Batch enrollment functionality
 */
function initBatchEnrollment() {
    var enrollForm = document.getElementById('batchEnrollForm');
    
    if (enrollForm) {
        enrollForm.addEventListener('submit', function(e) {
            var selectedStudents = document.querySelectorAll('.select-student:checked');
            
            if (selectedStudents.length === 0) {
                e.preventDefault();
                showAlert('warning', 'Please select at least one student to enroll');
            }
        });
    }
}

/**
 * Attendance marking functionality
 */
function initAttendance() {
    var markAllPresent = document.getElementById('markAllPresent');
    var markAllAbsent = document.getElementById('markAllAbsent');

    if (markAllPresent) {
        markAllPresent.addEventListener('click', function() {
            document.querySelectorAll('.attendance-status').forEach(function(select) {
                select.value = 'present';
            });
        });
    }

    if (markAllAbsent) {
        markAllAbsent.addEventListener('click', function() {
            document.querySelectorAll('.attendance-status').forEach(function(select) {
                select.value = 'absent';
            });
        });
    }
}

/**
 * Data table initialization
 */
function initDataTable(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;

    // Sort functionality
    var headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(function(header) {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            var column = this.dataset.sort;
            var order = this.dataset.order === 'asc' ? 'desc' : 'asc';
            this.dataset.order = order;
            
            sortTable(table, column, order);
        });
    });
}

/**
 * Sort table by column
 */
function sortTable(table, column, order) {
    var tbody = table.querySelector('tbody');
    var rows = Array.from(tbody.querySelectorAll('tr'));
    var headerIndex = Array.from(table.querySelectorAll('th')).findIndex(function(th) {
        return th.dataset.sort === column;
    });

    rows.sort(function(a, b) {
        var aVal = a.cells[headerIndex].textContent.trim();
        var bVal = b.cells[headerIndex].textContent.trim();

        // Check if numeric
        if (!isNaN(aVal) && !isNaN(bVal)) {
            return order === 'asc' ? aVal - bVal : bVal - aVal;
        }

        // String comparison
        return order === 'asc' ? 
            aVal.localeCompare(bVal) : 
            bVal.localeCompare(aVal);
    });

    // Re-append sorted rows
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

/**
 * Print functionality
 */
function printContent(elementId) {
    var content = document.getElementById(elementId);
    if (!content) return;

    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                .no-print { display: none !important; }
            </style>
        </head>
        <body>
            ${content.innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) return;

    var csv = [];
    var rows = table.querySelectorAll('tr');

    rows.forEach(function(row) {
        var cols = row.querySelectorAll('td, th');
        var rowData = [];
        
        cols.forEach(function(col) {
            var text = col.textContent.replace(/"/g, '""').trim();
            rowData.push('"' + text + '"');
        });
        
        csv.push(rowData.join(','));
    });

    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    
    link.href = URL.createObjectURL(blob);
    link.download = filename || 'export.csv';
    link.click();
}
