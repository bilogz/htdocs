// Admin Dashboard Scripts
document.addEventListener('DOMContentLoaded', function() {
    // Initial active tab (default to books)
    showTab('books');
    
    // Mobile sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
    
    // Handle navigation clicks
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Extract tab id from href
            const tabId = this.getAttribute('data-tab');
            
            // Update active state in navigation
            navLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');
            
            // Show the corresponding tab
            showTab(tabId);
            
            // Close sidebar on mobile after click
            if (window.innerWidth <= 576) {
                toggleSidebar();
            }
        });
    });
    
    // Start periodic checks for notifications
    startNotificationChecks();
});

// Toggle mobile sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const body = document.body;
    
    if (sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
        body.classList.remove('sidebar-open');
    } else {
        sidebar.classList.add('show');
        body.classList.add('sidebar-open');
    }
}

// Show specific tab content
function showTab(tabId) {
    console.log('Showing tab:', tabId);
    
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(tabId);
    if (selectedTab) {
        selectedTab.style.display = 'block';
        selectedTab.classList.add('active');
        
        // Update the page title to reflect current section
        updatePageTitle(tabId);
        
        // If URL has a hash, update it
        history.replaceState(null, null, '#' + tabId);
    } else {
        console.error('Tab not found:', tabId);
    }
}

// Update page title based on active tab
function updatePageTitle(tabId) {
    const baseTitle = 'Admin Dashboard';
    let pageTitle = baseTitle;
    
    switch(tabId) {
        case 'books':
            pageTitle = 'Books Management - ' + baseTitle;
            break;
        case 'ebooks':
            pageTitle = 'eBooks Management - ' + baseTitle;
            break;
        case 'borrow':
            pageTitle = 'Borrow Requests - ' + baseTitle;
            break;
        case 'returns':
            pageTitle = 'Return Requests - ' + baseTitle;
            break;
        case 'students':
            pageTitle = 'Student Management - ' + baseTitle;
            break;
        case 'overdue':
            pageTitle = 'Overdue Books - ' + baseTitle;
            break;
        case 'fines':
            pageTitle = 'Fines Management - ' + baseTitle;
            break;
    }
    
    document.title = pageTitle;
}

// Student details modal
function viewStudentDetails(studentId) {
    console.log('Viewing details for student:', studentId);
    
    // Show loading state in the modal
    const modal = $('#studentDetailsModal');
    modal.find('.modal-body').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading student details...</p></div>');
    modal.modal('show');
    
    // Fetch student details and borrowed books
    $.ajax({
        url: 'get_student_details.php',
        method: 'GET',
        data: { student_id: studentId },
        success: function(response) {
            console.log('Response:', response);
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (data.success) {
                    // Update student info
                    updateStudentModalContent(data.student, data.borrowed_books);
                } else {
                    if (data.session_error) {
                        // Session expired, show error and redirect to login
                        showNotification('Your session has expired. Please log in again.', 'error');
                        setTimeout(() => {
                            window.location.href = 'admin_login.php';
                        }, 2000);
                    } else {
                        // Other error
                        modal.find('.modal-body').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                ${data.message || 'Error loading student details'}
                            </div>
                        `);
                    }
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                modal.find('.modal-body').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Error processing server response
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            modal.find('.modal-body').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Server error: ${error}
                </div>
            `);
        }
    });
}

// Update student modal content
function updateStudentModalContent(student, borrowedBooks) {
    const modal = $('#studentDetailsModal');
    
    // Build student info section
    let content = `
        <div class="student-info mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="admin-avatar mr-3" style="width: 60px; height: 60px;">
                    ${student.profile_pic ? 
                        `<img src="assets/images/${student.profile_pic}" alt="${student.full_name}">` : 
                        `<div class="avatar-placeholder">${student.full_name.charAt(0)}</div>`
                    }
                </div>
                <div>
                    <h4 class="mb-0">${student.full_name}</h4>
                    <p class="text-muted mb-0">ID: ${student.student_id}</p>
                </div>
            </div>
            <div class="student-details">
                <p><i class="fas fa-envelope mr-2"></i> ${student.email}</p>
                <p><i class="fas fa-phone mr-2"></i> ${student.phone || 'Not provided'}</p>
                <p><i class="fas fa-calendar mr-2"></i> Joined: ${new Date(student.created_at).toLocaleDateString()}</p>
            </div>
        </div>
    `;
    
    // Build borrowed books section
    content += `
        <div class="borrowed-books">
            <h5><i class="fas fa-book mr-2"></i> Borrowed Books</h5>
    `;
    
    if (borrowedBooks && borrowedBooks.length > 0) {
        content += `
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        borrowedBooks.forEach(book => {
            const isOverdue = book.status === 'overdue';
            const statusClass = getStatusBadgeClass(book.status);
            
            content += `
                <tr>
                    <td>${book.title}</td>
                    <td>${book.borrow_date}</td>
                    <td>
                        ${book.due_date}
                        ${isOverdue ? '<span class="badge badge-danger ml-2">Overdue</span>' : ''}
                    </td>
                    <td>
                        <span class="badge badge-${statusClass}">
                            ${book.status.charAt(0).toUpperCase() + book.status.slice(1)}
                        </span>
                    </td>
                    <td>
                        ${book.status === 'confirmed' || book.status === 'overdue' ? 
                            `<button class="btn btn-warning btn-sm" onclick="returnBook(${book.schedule_id})">
                                <i class="fas fa-undo mr-1"></i> Return
                            </button>` : 
                            ''
                        }
                    </td>
                </tr>
            `;
        });
        
        content += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        content += `
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                This student has no borrowed books.
            </div>
        `;
    }
    
    content += `</div>`;
    
    // Update modal content
    modal.find('.modal-body').html(content);
}

// Get status badge class
function getStatusBadgeClass(status) {
    switch(status.toLowerCase()) {
        case 'confirmed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'cancelled':
            return 'danger';
        case 'returned':
            return 'info';
        case 'overdue':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = $('#notification');
    
    notification.text(message);
    notification.removeClass('bg-success bg-danger bg-warning bg-info')
               .addClass(type === 'success' ? 'bg-success' : 
                        (type === 'error' ? 'bg-danger' : 
                        (type === 'warning' ? 'bg-warning' : 'bg-info')));
    
    notification.fadeIn();
    
    // Auto-hide after 3 seconds
    setTimeout(() => notification.fadeOut(), 3000);
}

// Periodically check for notifications
function startNotificationChecks() {
    // Initial check
    checkAdminNotifications();
    
    // Set interval for periodic checks (every 30 seconds)
    setInterval(checkAdminNotifications, 30000);
}

// Check for admin notifications
function checkAdminNotifications() {
    fetch('check_admin_notifications.php')
        .then(response => response.json())
        .then(data => {
            // Update borrow requests badge
            updateNotificationBadge('borrow', data.pending_borrow);
            
            // Update return requests badge
            updateNotificationBadge('returns', data.pending_returns);
            
            // Update overdue books badge
            updateNotificationBadge('overdue', data.overdue_books);
        })
        .catch(error => console.error('Error checking notifications:', error));
}

// Update notification badge
function updateNotificationBadge(tabId, count) {
    const badge = document.querySelector(`.nav-link[data-tab="${tabId}"] .notification-badge`);
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Handle return request approval/rejection
function handleReturn(recordId, action) {
    if (!recordId || !action) {
        console.error('Invalid parameters for handleReturn');
        return;
    }
    
    // Confirm action
    const confirmMessage = action === 'approve' 
        ? 'Are you sure you want to approve this return?' 
        : 'Are you sure you want to reject this return?';
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('record_id', recordId);
    formData.append('action', action === 'approve' ? 'approve_return' : 'reject_return');
    
    // Send AJAX request
    $.ajax({
        url: 'admin_page.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result && result.success) {
                    showNotification(action === 'approve' ? 'Return approved successfully' : 'Return rejected successfully');
                    
                    // Remove the row from the table with animation
                    $(`#return-row-${recordId}`).fadeOut(500, function() {
                        $(this).remove();
                        
                        // Check if there are no more return requests
                        if ($('#returns .table tbody tr').length === 0) {
                            $('#returns .table tbody').html(`
                                <tr>
                                    <td colspan="7" class="text-center">
                                        No pending return requests
                                    </td>
                                </tr>
                            `);
                        }
                        
                        // Trigger a refresh of the returns data via the real-time update functions
                        if (typeof fetchReturnRequests === 'function') {
                            fetchReturnRequests();
                        }
                        
                        // Also update dashboard stats
                        if (typeof fetchDashboardStats === 'function') {
                            fetchDashboardStats();
                        }
                    });
                } else {
                    showNotification(result && result.message ? result.message : 'Error processing return request', 'error');
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                showNotification('Error processing return request', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            showNotification('Server error: ' + error, 'error');
        }
    });
}

// Function to handle editing a book
function editBook(book) {
    console.log("Editing book:", book); // Debug log
    
    // Populate the edit form with book data
    $('#edit_book_id').val(book.book_id);
    $('#edit_book_title').val(book.title);
    $('#edit_book_author').val(book.author);
    $('#edit_book_category').val(book.category);
    $('#edit_book_status').val(book.status);
    $('#edit_book_stock').val(book.available_stock);
    $('#edit_book_description').val(book.description);
    $('#edit_current_cover').val(book.cover_image);
    
    // Add listeners for status/stock compatibility
    $('#edit_book_status, #edit_book_stock').on('change', function() {
        const status = $('#edit_book_status').val();
        const stock = parseInt($('#edit_book_stock').val()) || 0;
        
        // Clear any existing warning
        $('#status-stock-warning').remove();
        
        // Check for incompatible combinations
        if (status === 'Available' && stock <= 0) {
            $('#edit_book_status').after(
                '<div id="status-stock-warning" class="alert alert-warning mt-2">' +
                'Warning: An "Available" book should have stock greater than 0. ' +
                'The system will automatically change this to "Unavailable" if submitted.' +
                '</div>'
            );
        }
    });
    
    // Show the modal
    $('#editBookModal').modal('show');
    
    // Handle form submission via AJAX
    $('#editBookForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Show loading state in the submit button
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'admin_page.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        // Compare submitted values with what was in the form
                        const submittedStatus = $('#edit_book_status').val();
                        const submittedStock = parseInt($('#edit_book_stock').val()) || 0;
                        
                        if (submittedStatus === 'Available' && submittedStock <= 0) {
                            showNotification('Book updated, but status was changed to Unavailable due to zero stock');
                        } else {
                            showNotification(result.message || 'Book updated successfully');
                        }
                        
                        $('#editBookModal').modal('hide');
                        
                        // Update the book in the table without reload
                        updateBookInTable({
                            book_id: $('#edit_book_id').val(),
                            title: $('#edit_book_title').val(),
                            author: $('#edit_book_author').val(),
                            category: $('#edit_book_category').val(),
                            status: submittedStatus === 'Available' && submittedStock <= 0 ? 'Unavailable' : submittedStatus,
                            available_stock: submittedStock,
                            description: $('#edit_book_description').val(),
                            cover_image: book.cover_image // Use existing cover unless changed
                        });
                        
                        // Refresh books data in the background
                        if (typeof fetchBooks === 'function') {
                            fetchBooks();
                        }
                    } else {
                        showNotification(result.message || 'Error updating book', 'error');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showNotification('Error updating book', 'error');
                }
                
                // Restore button state
                submitBtn.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                showNotification('Server error: ' + error, 'error');
                
                // Restore button state
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
}

// Function to update a single book in the table without refreshing
function updateBookInTable(updatedBook) {
    const row = $(`tr[data-book-id="${updatedBook.book_id}"]`);
    if (row.length === 0) return;
    
    // Update values in the row
    row.find('td:nth-child(2)').text(updatedBook.title);
    row.find('td:nth-child(3)').text(updatedBook.author);
    row.find('td:nth-child(4)').text(updatedBook.category);
    row.find('td:nth-child(5)').text(updatedBook.available_stock);
    
    // Update status
    const statusCell = row.find('td:nth-child(6)');
    statusCell.html(`
        <div class="status-indicator">
            <span class="status-dot ${updatedBook.status.toLowerCase()}"></span>
            <span class="status-text">${updatedBook.status}</span>
        </div>
    `);
    
    // Update availability
    const availabilityCell = row.find('td:nth-child(7)');
    if (updatedBook.status === 'Borrowed') {
        availabilityCell.html(`
            <div class="status-indicator">
                <span class="status-dot borrowed"></span>
                <span class="status-text">Borrowed</span>
            </div>
        `);
    } else if (parseInt(updatedBook.available_stock) > 0) {
        availabilityCell.html(`
            <div class="status-indicator">
                <span class="status-dot available"></span>
                <span class="status-text">In Stock (${updatedBook.available_stock})</span>
            </div>
        `);
    } else {
        availabilityCell.html(`
            <div class="status-indicator">
                <span class="status-dot unavailable"></span>
                <span class="status-text">Out of Stock</span>
            </div>
        `);
    }
    
    // Update the edit button with the new data
    const actionsCell = row.find('td:nth-child(8)');
    actionsCell.find('.btn-warning').attr('onclick', `editBook(${JSON.stringify(updatedBook).replace(/"/g, '&quot;')})`);
    
    // Highlight the updated row
    row.addClass('highlight-update');
    setTimeout(() => {
        row.removeClass('highlight-update');
    }, 2000);
}

// Function to handle deleting a book
function deleteBook(bookId) {
    if (!confirm('Are you sure you want to delete this book?')) {
        return;
    }
    
    // Find and add processing state to the row
    const row = $(`tr[data-book-id="${bookId}"]`);
    if (row.length) {
        row.addClass('processing');
    }
    
    $.ajax({
        url: 'admin_page.php',
        type: 'POST',
        data: {
            action: 'delete_book',
            book_id: bookId
        },
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                showNotification('Book deleted successfully');
                
                // Remove the book row from the table with animation
                if (row.length) {
                    row.fadeOut(500, function() {
                        $(this).remove();
                        
                        // Check if the table is now empty
                        if ($('#books .table tbody tr').length === 0) {
                            $('#books .table tbody').html('<tr><td colspan="8" class="text-center">No books found.</td></tr>');
                        }
                    });
                }
                
                // Refresh books data in the background
                if (typeof fetchBooks === 'function') {
                    fetchBooks();
                }
                
                // Update dashboard stats
                if (typeof fetchDashboardStats === 'function') {
                    fetchDashboardStats();
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                showNotification('Error deleting book', 'error');
                
                // Remove processing state
                if (row.length) {
                    row.removeClass('processing');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            showNotification('Error deleting book: ' + error, 'error');
            
            // Remove processing state
            if (row.length) {
                row.removeClass('processing');
            }
        }
    });
}

// Function to handle editing an ebook
function editEbook(ebook) {
    // Populate the edit form with ebook data
    $('#edit_ebook_id').val(ebook.id);
    $('#edit_ebook_title').val(ebook.title);
    $('#edit_ebook_author').val(ebook.author);
    $('#edit_ebook_category').val(ebook.category);
    $('#edit_ebook_price').val(ebook.price);
    $('#edit_ebook_status').val(ebook.status);
    $('#edit_ebook_download_status').val(ebook.download_status);
    $('#edit_ebook_description').val(ebook.description);
    $('#edit_ebook_current_cover').val(ebook.cover_image);
    $('#edit_ebook_current_file').val(ebook.file_path);
    
    // Reset file inputs
    $('.custom-file-label').text('Choose file...');
    
    // Display current cover image
    if (ebook.cover_image) {
        const coverUrl = 'assets/images/' + ebook.cover_image;
        $('#current_cover_preview').show().find('img').attr('src', coverUrl);
    } else {
        $('#current_cover_preview').hide();
    }
    
    // Display current file info
    if (ebook.file_path) {
        const fileExtension = ebook.file_path.split('.').pop().toUpperCase();
        $('#current_file_info_preview .badge').text(fileExtension + ' FILE: ' + ebook.file_path).show();
    } else {
        $('#current_file_info_preview .badge').hide();
    }
    
    // Show the modal
    $('#editEbookModal').modal('show');
    
    // Handle form submission via AJAX
    $('#editEbookForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Show loading state in the submit button
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'handle_ebook.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        showNotification(result.message || 'eBook updated successfully');
                        $('#editEbookModal').modal('hide');
                        
                        // Refresh ebooks data
                        if (typeof loadEbooks === 'function') {
                            loadEbooks();
                        } else {
                            // Fallback to refresh the page
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        showNotification(result.message || 'Error updating eBook', 'error');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showNotification('Error updating eBook', 'error');
                }
                
                // Restore button state
                submitBtn.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showNotification('Error communicating with the server', 'error');
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Update file input labels when files are selected
    $('#edit_cover_image, #edit_ebook_file').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').text(fileName || 'Choose file...');
    });
}

// Function to update a single ebook in the table without refreshing
function updateEbookInTable(updatedEbook) {
    // Find the row by ebook ID using the data attribute
    const row = $(`tr[data-ebook-id="${updatedEbook.id}"]`);
    if (row.length === 0) return;
    
    // Update values in the row
    row.find('td:nth-child(2)').text(updatedEbook.title);
    row.find('td:nth-child(3)').text(updatedEbook.author);
    row.find('td:nth-child(4)').text(updatedEbook.category);
    row.find('td:nth-child(5)').text('$' + parseFloat(updatedEbook.price || 0).toFixed(2));
    
    // Update status
    const statusCell = row.find('td:nth-child(6)');
    statusCell.html(`
        <div class="status-indicator">
            <span class="status-dot ${(updatedEbook.status || 'Unavailable').toLowerCase()}"></span>
            <span class="status-text">${updatedEbook.status || 'Unavailable'}</span>
        </div>
    `);
    
    // Update download status
    const downloadStatusCell = row.find('td:nth-child(7)');
    downloadStatusCell.html(`
        <div class="status-indicator">
            <span class="status-dot ${(updatedEbook.download_status || 'Disabled').toLowerCase()}"></span>
            <span class="status-text">${updatedEbook.download_status || 'Disabled'}</span>
        </div>
    `);
    
    // Update the edit button with the new data
    const actionsCell = row.find('td:nth-child(8)');
    actionsCell.find('.btn-warning').attr('onclick', `editEbook(${JSON.stringify(updatedEbook).replace(/"/g, '&quot;')})`);
    
    // Highlight the updated row
    row.addClass('highlight-update');
    setTimeout(() => {
        row.removeClass('highlight-update');
    }, 2000);
}

// Function to handle deleting an ebook
function deleteEbook(ebookId) {
    if (!confirm('Are you sure you want to delete this eBook?')) {
        return;
    }
    
    // Find the ebook row using the data attribute
    const row = $(`tr[data-ebook-id="${ebookId}"]`);
    
    // Add processing state
    if (row.length) {
        row.addClass('processing');
    }
    
    $.ajax({
        url: 'admin_page.php',
        type: 'POST',
        data: {
            action: 'delete_ebook',
            ebook_id: ebookId
        },
        success: function(response) {
            try {
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                showNotification('eBook deleted successfully');
                
                // Remove the ebook row with animation
                if (row.length) {
                    row.fadeOut(500, function() {
                        $(this).remove();
                        
                        // Check if the table is now empty
                        if ($('#ebooks .table tbody tr').length === 0) {
                            $('#ebooks .table tbody').html('<tr><td colspan="8" class="text-center">No eBooks found.</td></tr>');
                        }
                    });
                }
                
                // Refresh ebooks data in the background
                if (typeof fetchEbooks === 'function') {
                    fetchEbooks();
                }
                
                // Update dashboard stats
                if (typeof fetchDashboardStats === 'function') {
                    fetchDashboardStats();
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                showNotification('Error deleting eBook', 'error');
                
                // Remove processing state
                if (row.length) {
                    row.removeClass('processing');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            showNotification('Error deleting eBook: ' + error, 'error');
            
            // Remove processing state
            if (row.length) {
                row.removeClass('processing');
            }
        }
    });
}

// Handler for the Add eBook form
$(document).ready(function() {
    // Update file input labels when files are selected
    $('.custom-file-input').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').text(fileName || 'Choose file...');
    });
    
    // Handle add eBook form submission
    $('#addEbookForm').submit(function(e) {
        e.preventDefault();
        
        // Show loading state in the submit button
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'add_new_ebook.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.success) {
                        showNotification(result.message || 'eBook added successfully');
                        $('#addEbookModal').modal('hide');
                        
                        // Reset form
                        $('#addEbookForm')[0].reset();
                        $('.custom-file-label').text('Choose file...');
                        
                        // Refresh ebooks data
                        if (typeof loadEbooks === 'function') {
                            loadEbooks();
                        } else {
                            // Fallback to refresh the page
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        showNotification(result.message || 'Error adding eBook', 'error');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showNotification('Error adding eBook', 'error');
                }
                
                // Restore button state
                submitBtn.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showNotification('Error communicating with the server', 'error');
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });
});

$(document).ready(function() {
    // Initialize modal events
    $('#editBookModal').on('hidden.bs.modal', function() {
        // Clear any warnings when modal is closed
        $('#status-stock-warning').remove();
    });
    
    // Initialize any other components or settings
    
    // Set active nav item based on hash
    const hash = window.location.hash || '#books';
    showTab(hash.replace('#', ''));
}); 