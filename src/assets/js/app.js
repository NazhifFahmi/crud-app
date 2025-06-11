// Employee Management System JavaScript
$(document).ready(function() {
    // Initialize DataTables
    if ($('.data-table').length) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            columnDefs: [
                { orderable: false, targets: -1 } // Disable sorting on last column (actions)
            ]
        });
    }

    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const itemName = $(this).data('name') || 'this item';
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${itemName}. This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Form validation
    $('.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Success/Error messages
    if ($('.alert').length) {
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Profile image preview
    $('#profile_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(file);
        }
    });

    // Salary formatter
    $('.salary-input').on('input', function() {
        let value = $(this).val().replace(/[^\d]/g, '');
        value = parseInt(value).toLocaleString('id-ID');
        $(this).val(value);
    });

    // Auto-generate employee ID
    $('#generate-emp-id').on('click', function() {
        const timestamp = Date.now().toString().slice(-6);
        const empId = 'EMP' + timestamp;
        $('#employee_id').val(empId);
    });

    // Department statistics chart
    if ($('#departmentChart').length) {
        const ctx = document.getElementById('departmentChart').getContext('2d');
        
        // Fetch department data
        fetch('api/department-stats.php')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                '#FF6384',
                                '#36A2EB',
                                '#FFCE56',
                                '#4BC0C0',
                                '#9966FF',
                                '#FF9F40'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching department stats:', error);
                $('#departmentChart').parent().html('<p class="text-muted text-center">Unable to load chart data</p>');
            });
    }

    // Monthly attendance chart
    if ($('#attendanceChart').length) {
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        
        fetch('api/attendance-stats.php')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.months,
                        datasets: [{
                            label: 'Present',
                            data: data.present,
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4
                        }, {
                            label: 'Absent',
                            data: data.absent,
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching attendance stats:', error);
            });
    }

    // Real-time clock
    if ($('#current-time').length) {
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            $('#current-time').text(timeString);
            $('#current-date').text(dateString);
        }
        
        updateTime();
        setInterval(updateTime, 1000);
    }

    // Attendance check-in/out
    $('.btn-checkin').on('click', function() {
        const employeeId = $(this).data('employee-id');
        const action = $(this).data('action');
        
        $.ajax({
            url: 'api/attendance.php',
            method: 'POST',
            data: {
                employee_id: employeeId,
                action: action
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire('Success!', result.message, 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error!', result.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'An error occurred. Please try again.', 'error');
            }
        });
    });

    // Project progress update
    $('.progress-update').on('change', function() {
        const projectId = $(this).data('project-id');
        const progress = $(this).val();
        
        $.ajax({
            url: 'api/update-progress.php',
            method: 'POST',
            data: {
                project_id: projectId,
                progress: progress
            },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    // Update progress bar
                    $(`.progress-bar[data-project-id="${projectId}"]`)
                        .css('width', progress + '%')
                        .text(progress + '%');
                    
                    // Show success message
                    showToast('Progress updated successfully!', 'success');
                } else {
                    showToast('Failed to update progress', 'error');
                }
            },
            error: function() {
                showToast('An error occurred', 'error');
            }
        });
    });

    // Toast notifications
    function showToast(message, type = 'info') {
        const toast = $(`
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `);
        
        $('.toast-container').append(toast);
        const bsToast = new bootstrap.Toast(toast[0]);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }

    // Search functionality
    $('#search-input').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.searchable-item').each(function() {
            const text = $(this).text().toLowerCase();
            if (text.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Export functionality
    $('.btn-export').on('click', function() {
        const type = $(this).data('type');
        const table = $(this).data('table');
        
        window.open(`export.php?type=${type}&table=${table}`, '_blank');
    });

    // Animations
    $('.card').addClass('fade-in');
    
    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function calculateAge(birthDate) {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    
    return age;
}
