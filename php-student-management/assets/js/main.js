/**
 * Student Management System - Main JavaScript
 * Performance Optimized with Event Delegation & Debouncing
 */

// Debounce utility for performance optimization
function debounce(func, delay = 300) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

// Throttle utility for high-frequency events
function throttle(func, delay = 300) {
    let lastCall = 0;
    return function(...args) {
        const now = Date.now();
        if (now - lastCall >= delay) {
            lastCall = now;
            return func.apply(this, args);
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips (lazy load on hover)
    document.addEventListener('mouseenter', function(e) {
        if (e.target.hasAttribute('data-bs-toggle') && e.target.getAttribute('data-bs-toggle') === 'tooltip') {
            if (!e.target._tooltip) {
                e.target._tooltip = new bootstrap.Tooltip(e.target);
            }
            e.target._tooltip.show();
        }
    }, true);

    // Initialize popovers (lazy load on click)
    document.addEventListener('click', function(e) {
        if (e.target.hasAttribute('data-bs-toggle') && e.target.getAttribute('data-bs-toggle') === 'popover') {
            if (!e.target._popover) {
                e.target._popover = new bootstrap.Popover(e.target);
            }
            e.target._popover.toggle();
        }
    }, true);

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
        setTimeout(function() {
            try {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } catch(e) {
                alert.style.display = 'none';
            }
        }, 5000);
    });

    // Event delegation for delete confirmations (improves performance)
    document.addEventListener('click', function(e) {
        if (e.target.matches('.btn-delete, .delete-confirm')) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        }
    });

    // Select all checkbox functionality with event delegation
    document.addEventListener('change', function(e) {
        if (e.target.id === 'selectAll') {
            const checkboxes = document.querySelectorAll('.select-item');
            checkboxes.forEach(checkbox => checkbox.checked = e.target.checked);
            
            // Update counter if exists
            const counter = document.getElementById('selectCount');
            if (counter) {
                counter.textContent = e.target.checked ? checkboxes.length : 0;
            }
        }
    });

    // Form validation
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('needs-validation')) {
            const form = e.target;
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        }
    });

    // Password visibility toggle using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.toggle-password')) {
            const btn = e.target.closest('.toggle-password');
            const input = document.querySelector(btn.dataset.target);
            const icon = btn.querySelector('i');
            
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                    btn.setAttribute('aria-label', 'Hide password');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                    btn.setAttribute('aria-label', 'Show password');
                }
            }
        }
    });

    // Search filter for tables with debouncing
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', debounce(function() {
            const searchTerm = this.value.toLowerCase().trim();
            const tableRows = document.querySelectorAll('#dataTable tbody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const isVisible = searchTerm === '' || text.includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });
            
            // Show no results message if needed
            const noResults = document.getElementById('noResults');
            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'table-row' : 'none';
            }
        }, 200));
    }
});

/**
 * Profile Picture Upload with Preview & Lazy Initialization
 */
function initProfilePictureUpload() {
    const fileInput = document.getElementById('profilePictureInput');
    const preview = document.getElementById('profilePicturePreview');
    const form = document.getElementById('profilePictureForm');

    if (fileInput && preview) {
        fileInput.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('error', 'Please select a valid image file (JPG, PNG, GIF, or WebP)');
                    fileInput.value = '';
                    return;
                }

                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('error', 'File size must be less than 5MB');
                    fileInput.value = '';
                    return;
                }

                // Show preview with image compression hint
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.setAttribute('alt', 'Profile picture preview');
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
 * Upload profile picture via AJAX with error handling
 */
function uploadProfilePicture(form) {
    const formData = new FormData(form);
    formData.append('ajax', '1');
    
    const uploadBtn = form.querySelector('button[type="submit"]');
    if (uploadBtn) {
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Uploading...';
    }

    fetch(form.action, {
        method: 'POST',
        body: formData,
        signal: AbortSignal.timeout(30000) // 30 second timeout
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('success', 'Profile picture updated successfully!');
        } else {
            showAlert('error', data.message || 'Failed to upload profile picture');
        }
    })
    .catch(error => {
        console.error('[v0] Upload Error:', error);
        showAlert('error', 'An error occurred while uploading the profile picture');
    })
    .finally(() => {
        if (uploadBtn) {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = 'Upload Picture';
        }
    });
}

/**
 * Show alert message with accessibility
 */
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                       type === 'error' ? 'alert-danger' : 
                       type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertIcon = type === 'success' ? 'bi-check-circle' :
                      type === 'error' ? 'bi-exclamation-circle' :
                      type === 'warning' ? 'bi-exclamation-triangle' : 'bi-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" aria-live="polite" aria-atomic="true">
            <i class="bi ${alertIcon} me-2" aria-hidden="true"></i>
            <span>${message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    const container = document.querySelector('.container-fluid');
    if (container) {
        const alertContainer = document.createElement('div');
        alertContainer.innerHTML = alertHtml;
        const alertElement = alertContainer.firstChild;
        container.insertBefore(alertElement, container.firstChild);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            try {
                const bsAlert = new bootstrap.Alert(alertElement);
                bsAlert.close();
            } catch(e) {
                alertElement.style.display = 'none';
            }
        }, 5000);
    }
}

/**
 * Batch enrollment functionality with validation
 */
function initBatchEnrollment() {
    const enrollForm = document.getElementById('batchEnrollForm');
    
    if (enrollForm) {
        enrollForm.addEventListener('submit', function(e) {
            const selectedStudents = document.querySelectorAll('.select-student:checked');
            
            if (selectedStudents.length === 0) {
                e.preventDefault();
                showAlert('warning', `Please select at least one student to enroll`);
            }
        });
    }
}

/**
 * Attendance marking functionality with event delegation
 */
function initAttendance() {
    document.addEventListener('click', function(e) {
        if (e.target.id === 'markAllPresent') {
            document.querySelectorAll('.attendance-status').forEach(select => {
                select.value = 'present';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        } else if (e.target.id === 'markAllAbsent') {
            document.querySelectorAll('.attendance-status').forEach(select => {
                select.value = 'absent';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });
}

/**
 * Data table initialization with accessible sorting
 */
function initDataTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    // Sort functionality with keyboard support
    const headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.setAttribute('role', 'button');
        header.setAttribute('tabindex', '0');
        header.setAttribute('aria-sort', 'none');
        
        const handleSort = () => {
            const column = header.dataset.sort;
            const order = header.dataset.order === 'asc' ? 'desc' : 'asc';
            header.dataset.order = order;
            header.setAttribute('aria-sort', order === 'asc' ? 'ascending' : 'descending');
            sortTable(table, column, order);
        };
        
        header.addEventListener('click', handleSort);
        header.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleSort();
            }
        });
    });
}

/**
 * Sort table by column (optimized)
 */
function sortTable(table, column, order) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const headerIndex = Array.from(table.querySelectorAll('th')).findIndex(th => th.dataset.sort === column);

    rows.sort((a, b) => {
        let aVal = a.cells[headerIndex].textContent.trim();
        let bVal = b.cells[headerIndex].textContent.trim();

        // Check if numeric
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return order === 'asc' ? aNum - bNum : bNum - aNum;
        }

        // String comparison
        return order === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });

    // Re-append sorted rows with minimal reflow
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Print functionality with error handling
 */
function printContent(elementId) {
    const content = document.getElementById(elementId);
    if (!content) {
        showAlert('error', 'Content not found for printing');
        return;
    }

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        showAlert('error', 'Could not open print window. Please allow popups.');
        return;
    }
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Print</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
            <style>
                body { padding: 20px; font-family: system-ui, -apple-system, sans-serif; }
                .no-print { display: none !important; }
                @media print {
                    body { margin: 0; padding: 0; }
                }
            </style>
        </head>
        <body>
            ${content.innerHTML}
            <script>
                window.addEventListener('load', () => window.print());
            </script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

/**
 * Export table to CSV with proper encoding
 */
function exportTableToCSV(tableId, filename = 'export') {
    const table = document.getElementById(tableId);
    if (!table) {
        showAlert('error', 'Table not found for export');
        return;
    }

    const csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        
        cols.forEach(col => {
            const text = col.textContent.replace(/"/g, '""').trim();
            rowData.push(`"${text}"`);
        });
        
        csv.push(rowData.join(','));
    });

    const csvContent = '\ufeff' + csv.join('\n'); // BOM for proper Excel encoding
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    link.href = URL.createObjectURL(blob);
    link.download = `${filename}_${new Date().getTime()}.csv`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showAlert('success', 'Table exported successfully');
}
