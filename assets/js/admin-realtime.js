/**
 * Real-time updates for Admin Dashboard
 */

// Configuration
const REFRESH_INTERVAL = 10000; // 10 seconds
let activeTab = 'books';
let updateTimers = {};
let lastFetchedData = {};

// Initialize real-time updates
document.addEventListener('DOMContentLoaded', function() {
    console.log('Real-time updates initialized');
    
    // Start the dashboard stats update loop
    startDashboardUpdates();
    
    // Listen for tab changes to manage appropriate update timers
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            activeTab = tabId;
            
            // Start appropriate update timer for the active tab
            startTabSpecificUpdates(tabId);
        });
    });
    
    // Initial update for the default tab
    startTabSpecificUpdates(activeTab);
});

/**
 * Start dashboard statistics update loop
 */
function startDashboardUpdates() {
    // Initial fetch
    fetchDashboardStats();
    
    // Set interval for regular updates
    updateTimers.dashboard = setInterval(fetchDashboardStats, REFRESH_INTERVAL);
}

/**
 * Start tab-specific update loop
 */
function startTabSpecificUpdates(tabId) {
    // Clear all existing tab update timers
    ['books', 'ebooks', 'borrow', 'returns', 'overdue', 'fines'].forEach(tab => {
        if (updateTimers[tab]) {
            clearInterval(updateTimers[tab]);
            updateTimers[tab] = null;
        }
    });
    
    // Start timer for the active tab
    switch(tabId) {
        case 'books':
            fetchBooks();
            updateTimers.books = setInterval(fetchBooks, REFRESH_INTERVAL);
            break;
            
        case 'ebooks':
            fetchEbooks();
            updateTimers.ebooks = setInterval(fetchEbooks, REFRESH_INTERVAL);
            break;
            
        case 'borrow':
            fetchBorrowRequests();
            updateTimers.borrow = setInterval(fetchBorrowRequests, REFRESH_INTERVAL);
            break;
            
        case 'returns':
            fetchReturnRequests();
            updateTimers.returns = setInterval(fetchReturnRequests, REFRESH_INTERVAL);
            break;
            
        case 'overdue':
            fetchOverdueBooks();
            updateTimers.overdue = setInterval(fetchOverdueBooks, REFRESH_INTERVAL);
            break;
        
        // Add other tabs as needed
    }
}

/**
 * Fetch dashboard statistics
 */
function fetchDashboardStats() {
    fetch('get_admin_data.php?type=dashboard_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.data);
            } else if (data.session_error) {
                handleSessionTimeout();
            }
        })
        .catch(error => console.error('Error fetching dashboard stats:', error));
}

/**
 * Update dashboard statistics in the UI
 */
function updateDashboardStats(stats) {
    // Update notification badges
    updateNotificationBadge('borrow', stats.pending_borrows);
    updateNotificationBadge('returns', stats.pending_returns);
    updateNotificationBadge('overdue', stats.overdue_books);
    
    // Update notification summary in header
    updateNotificationSummary(stats);
}

/**
 * Update notification summary in header
 */
function updateNotificationSummary(stats) {
    const notificationSummary = document.querySelector('.notification-summary');
    if (!notificationSummary) return;
    
    let html = '';
    
    if (stats.pending_borrows > 0) {
        html += `
            <div class="alert alert-info mb-0">
                <i class="fas fa-clock"></i>
                <span>Pending Requests: <strong>${stats.pending_borrows}</strong></span>
            </div>
        `;
    }
    
    if (stats.pending_returns > 0) {
        html += `
            <div class="alert alert-warning mb-0">
                <i class="fas fa-undo"></i>
                <span>Return Requests: <strong>${stats.pending_returns}</strong></span>
            </div>
        `;
    }
    
    if (stats.overdue_books > 0) {
        html += `
            <div class="alert alert-danger mb-0">
                <i class="fas fa-exclamation-circle"></i>
                <span>Overdue Books: <strong>${stats.overdue_books}</strong></span>
            </div>
        `;
    }
    
    notificationSummary.innerHTML = html;
}

/**
 * Fetch books data
 */
function fetchBooks() {
    if (activeTab !== 'books') return;
    
    fetch('get_admin_data.php?type=books')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateBooksTable(data.data);
                lastFetchedData.books = data.data;
            } else if (data.session_error) {
                handleSessionTimeout();
            }
        })
        .catch(error => console.error('Error fetching books:', error));
}

/**
 * Update books table in the UI
 */
function updateBooksTable(books) {
    const booksTableBody = document.querySelector('#books .table tbody');
    if (!booksTableBody) return;
    
    // Compare with last data to avoid unnecessary DOM updates
    if (JSON.stringify(books) === JSON.stringify(lastFetchedData.books || [])) {
        return;
    }
    
    let html = '';
    
    if (books.length > 0) {
        books.forEach(book => {
            html += `
                <tr data-book-id="${book.book_id}">
                    <td>
                        <img src="assets/images/${book.cover_image}" 
                             alt="" style="width: 50px; height: 75px; object-fit: cover;">
                    </td>
                    <td>${book.title}</td>
                    <td>${book.author}</td>
                    <td>${book.category}</td>
                    <td>${book.available_stock}</td>
                    <td>
                        <div class="status-indicator">
                            <span class="status-dot ${book.status.toLowerCase()}"></span>
                            <span class="status-text">${book.status}</span>
                        </div>
                    </td>
                    <td>
                        <div class="status-indicator">
                            ${book.status === 'Borrowed' ? 
                                `<span class="status-dot borrowed"></span>
                                <span class="status-text">Borrowed</span>` : 
                                
                                book.available_stock > 0 ? 
                                `<span class="status-dot available"></span>
                                <span class="status-text">In Stock (${book.available_stock})</span>` :
                                
                                `<span class="status-dot unavailable"></span>
                                <span class="status-text">Out of Stock</span>`
                            }
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editBook(${JSON.stringify(book).replace(/"/g, '&quot;')})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteBook(${book.book_id})">Delete</button>
                    </td>
                </tr>
            `;
        });
    } else {
        html = '<tr><td colspan="8" class="text-center">No books found.</td></tr>';
    }
    
    booksTableBody.innerHTML = html;
}

/**
 * Fetch eBooks data
 */
function fetchEbooks() {
    if (activeTab !== 'ebooks') return;
    
    fetch('get_admin_data.php?type=ebooks')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateEbooksTable(data.data);
                lastFetchedData.ebooks = data.data;
            } else if (data.session_error) {
                handleSessionTimeout();
            }
        })
        .catch(error => console.error('Error fetching ebooks:', error));
}

/**
 * Update eBooks table in the UI
 */
function updateEbooksTable(ebooks) {
    const ebooksTableBody = document.querySelector('#ebooks .table tbody');
    if (!ebooksTableBody) return;
    
    // Compare with last data to avoid unnecessary DOM updates
    if (JSON.stringify(ebooks) === JSON.stringify(lastFetchedData.ebooks || [])) {
        return;
    }
    
    let html = '';
    
    if (ebooks.length > 0) {
        ebooks.forEach(ebook => {
            html += `
                <tr data-ebook-id="${ebook.id}">
                    <td>
                        <img src="assets/images/${ebook.cover_image}" 
                             alt="" style="width: 50px; height: 75px; object-fit: cover;">
                    </td>
                    <td>${ebook.title}</td>
                    <td>${ebook.author}</td>
                    <td>${ebook.category}</td>
                    <td>$${parseFloat(ebook.price || 0).toFixed(2)}</td>
                    <td>
                        <div class="status-indicator">
                            <span class="status-dot ${(ebook.status || 'Unavailable').toLowerCase()}"></span>
                            <span class="status-text">${ebook.status || 'Unavailable'}</span>
                        </div>
                    </td>
                    <td>
                        <div class="status-indicator">
                            <span class="status-dot ${(ebook.download_status || 'Disabled').toLowerCase()}"></span>
                            <span class="status-text">${ebook.download_status || 'Disabled'}</span>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editEbook(${JSON.stringify(ebook).replace(/"/g, '&quot;')})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteEbook(${ebook.id})">Delete</button>
                    </td>
                </tr>
            `;
        });
    } else {
        html = '<tr><td colspan="8" class="text-center">No eBooks found.</td></tr>';
    }
    
    ebooksTableBody.innerHTML = html;
}

/**
 * Fetch borrow requests
 */
function fetchBorrowRequests() {
    if (activeTab !== 'borrow') return;
    
    fetch('get_admin_data.php?type=borrow_requests')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateBorrowRequestsList(data.data);
                lastFetchedData.borrow_requests = data.data;
                updateNotificationBadge('borrow', data.pending_count || 0);
            } else if (data.session_error) {
                handleSessionTimeout();
            }
        })
        .catch(error => console.error('Error fetching borrow requests:', error));
}

/**
 * Update borrow requests list in the UI
 */
function updateBorrowRequestsList(requests) {
    const borrowRequestsContainer = document.querySelector('#borrowRequests');
    if (!borrowRequestsContainer) return;
    
    // Compare with last data to avoid unnecessary DOM updates
    if (JSON.stringify(requests) === JSON.stringify(lastFetchedData.borrow_requests || [])) {
        return;
    }
    
    let html = '';
    
    if (requests && requests.length > 0) {
        requests.forEach(request => {
            html += `
                <div class="borrow-request" id="schedule-${request.schedule_id}">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="assets/images/${request.cover_image}" 
                                 alt="" style="width: 100px; height: 150px; object-fit: cover; border-radius: 5px;">
                        </div>
                        <div class="col-md-7">
                            <h5>${request.book_title}</h5>
                            <p class="student-info">
                                Requested by: ${request.student_name}<br>
                                Email: ${request.student_email}
                            </p>
                            <p class="schedule-date">
                                ${request.schedule_date ? 
                                    `Schedule Date: ${new Date(request.schedule_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}<br>` : 
                                    ''}
                                
                                ${request.return_date ? 
                                    `Expected Return: ${new Date(request.return_date).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'})}` : 
                                    ''}
                            </p>
                            <p>Purpose: ${request.purpose || 'Not specified'}</p>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success btn-sm" onclick="handleBorrowAction(${request.schedule_id}, ${request.book_id}, 'approve')">
                                Approve
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="handleBorrowAction(${request.schedule_id}, ${request.book_id}, 'reject')">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html = `
            <div class="alert alert-info">
                No pending borrow requests at this time.
            </div>
        `;
    }
    
    borrowRequestsContainer.innerHTML = html;
}

/**
 * Handle borrow request action (approve/reject)
 */
function handleBorrowAction(scheduleId, bookId, action) {
    if (!scheduleId || !action) {
        console.error('Invalid parameters for handleBorrowAction');
        return;
    }
    
    // Confirm action
    const confirmMessage = action === 'approve' 
        ? 'Are you sure you want to approve this borrow request?' 
        : 'Are you sure you want to reject this borrow request?';
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Show loading state
    const requestElement = document.getElementById(`schedule-${scheduleId}`);
    if (requestElement) {
        requestElement.classList.add('processing');
        const btnArea = requestElement.querySelector('.col-md-3');
        if (btnArea) {
            btnArea.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
        }
    }
    
    // Create form data for the request
    const formData = new FormData();
    formData.append('action', action === 'approve' ? 'approve_borrow' : 'reject_borrow');
    formData.append('schedule_id', scheduleId);
    if (action === 'approve') {
        formData.append('book_id', bookId);
    }
    
    // Send request using fetch API
    fetch('admin_page.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Try to parse as JSON if possible
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        return { success: response.ok };
    })
    .then(data => {
        // Show success notification
        showNotification(
            action === 'approve' 
                ? 'Borrow request approved successfully' 
                : 'Borrow request rejected successfully'
        );
        
        // Remove the request from the display with animation
        if (requestElement) {
            requestElement.style.transition = 'all 0.5s ease';
            requestElement.style.opacity = '0';
            requestElement.style.height = '0';
            requestElement.style.overflow = 'hidden';
            
            setTimeout(() => {
                requestElement.remove();
                
                // If no more requests, show the "no requests" message
                const container = document.getElementById('borrowRequests');
                if (container && container.children.length === 0) {
                    container.innerHTML = `
                        <div class="alert alert-info">
                            No pending borrow requests at this time.
                        </div>
                    `;
                }
            }, 500);
        }
        
        // Update dashboard stats and other related data
        fetchDashboardStats();
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('There was an error processing your request. Please try again.', 'error');
        
        // Restore the buttons if there was an error
        if (requestElement) {
            requestElement.classList.remove('processing');
            const btnArea = requestElement.querySelector('.col-md-3');
            if (btnArea) {
                btnArea.innerHTML = `
                    <button class="btn btn-success btn-sm" onclick="handleBorrowAction(${scheduleId}, ${bookId}, 'approve')">
                        Approve
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="handleBorrowAction(${scheduleId}, ${bookId}, 'reject')">
                        Reject
                    </button>
                `;
            }
        }
    });
}

/**
 * Fetch return requests
 */
function fetchReturnRequests() {
    if (activeTab !== 'returns') return;
    
    fetch('get_admin_data.php?type=return_requests')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReturnRequestsTable(data.data);
                lastFetchedData.return_requests = data.data;
                updateNotificationBadge('returns', data.return_count || 0);
            } else if (data.session_error) {
                handleSessionTimeout();
            }
        })
        .catch(error => console.error('Error fetching return requests:', error));
}

/**
 * Update return requests table in the UI
 */
function updateReturnRequestsTable(returns) {
    const returnsTableBody = document.querySelector('#returns .table tbody');
    if (!returnsTableBody) return;
    
    // Compare with last data to avoid unnecessary DOM updates
    if (JSON.stringify(returns) === JSON.stringify(lastFetchedData.return_requests || [])) {
        return;
    }
    
    let html = '';
    
    if (returns && returns.length > 0) {
        returns.forEach(returnItem => {
            html += `
                <tr id="return-row-${returnItem.record_id}">
                    <td>
                        <div class="d-flex align-items-center">
                            ${returnItem.cover_image ? 
                                `<img src="assets/images/${returnItem.cover_image}" 
                                     alt="${returnItem.book_title}"
                                     style="width: 50px; height: 75px; object-fit: cover; margin-right: 10px;">` : 
                                ''}
                            <span>${returnItem.book_title}</span>
                        </div>
                    </td>
                    <td>
                        <div class="student-info">
                            <strong>${returnItem.student_name}</strong><br>
                            <small class="text-muted">${returnItem.student_email}</small>
                        </div>
                    </td>
                    <td>${new Date(returnItem.borrow_date).toLocaleDateString()}</td>
                    <td>${new Date(returnItem.due_date).toLocaleDateString()}</td>
                    <td>${new Date(returnItem.return_date).toLocaleDateString()}</td>
                    <td>
                        ${returnItem.days_overdue > 0 ? 
                            `<span class="badge badge-danger">${returnItem.days_overdue} days</span>` : 
                            '<span class="badge badge-success">On time</span>'}
                    </td>
                    <td>
                        <button class="btn btn-success btn-sm" onclick="handleReturn(${returnItem.record_id}, 'approve')">
                            Approve
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="handleReturn(${returnItem.record_id}, 'reject')">
                            Reject
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        html = `
            <tr>
                <td colspan="7" class="text-center">
                    No pending return requests
                </td>
            </tr>
        `;
    }
    
    returnsTableBody.innerHTML = html;
}

/**
 * Fetch overdue books
 */
function fetchOverdueBooks() {
    if (activeTab !== 'overdue') return;
    
    fetch('get_admin_data.php?type=overdue')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateOverdueBooksTable(data.data);
                lastFetchedData.overdue = data.data;
                updateNotificationBadge('overdue', data.overdue_count || 0);
            } else if (data.session_error) {
                handleSessionTimeout();
            }
        })
        .catch(error => console.error('Error fetching overdue books:', error));
}

/**
 * Update overdue books table in the UI
 */
function updateOverdueBooksTable(overdueBooks) {
    const overdueTableBody = document.querySelector('#overdue .table tbody');
    if (!overdueTableBody) return;
    
    // Compare with last data to avoid unnecessary DOM updates
    if (JSON.stringify(overdueBooks) === JSON.stringify(lastFetchedData.overdue || [])) {
        return;
    }
    
    let html = '';
    
    if (overdueBooks && overdueBooks.length > 0) {
        overdueBooks.forEach(overdue => {
            html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            ${overdue.cover_image ? 
                                `<img src="assets/images/${overdue.cover_image}" 
                                     alt="${overdue.book_title}"
                                     style="width: 50px; height: 75px; object-fit: cover; margin-right: 10px;">` : 
                                `<div style="width: 50px; height: 75px; background: #404040; margin-right: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-book" style="color: #666;"></i>
                                </div>`}
                            <span>${overdue.book_title}</span>
                        </div>
                    </td>
                    <td>
                        <div class="student-info">
                            <strong>${overdue.student_name}</strong><br>
                            <small class="text-muted">${overdue.student_email}</small>
                        </div>
                    </td>
                    <td>${new Date(overdue.borrow_date).toLocaleDateString()}</td>
                    <td>${new Date(overdue.due_date).toLocaleDateString()}</td>
                    <td>
                        <span class="badge badge-danger days-overdue">
                            ${overdue.days_overdue} days
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-danger overdue-fee">
                            $${parseFloat(overdue.overdue_fee).toFixed(2)}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm" onclick="sendOverdueNotification(${overdue.record_id})">
                                <i class="fa fa-bell"></i> Remind
                            </button>
                            <button class="btn btn-success btn-sm" onclick="extendDueDate(${overdue.record_id})">
                                <i class="fa fa-calendar-plus"></i> Extend
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="sendFineNotification(${overdue.record_id}, '${overdue.book_title}', ${overdue.days_overdue})">
                                <i class="fa fa-money-bill"></i> Send Fine Notice
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    } else {
        html = `
            <tr>
                <td colspan="7" class="text-center">
                    No overdue books at this time.
                </td>
            </tr>
        `;
    }
    
    overdueTableBody.innerHTML = html;
}

/**
 * Update notification badge
 */
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

/**
 * Handle session timeout
 */
function handleSessionTimeout() {
    // Clear all update timers
    Object.keys(updateTimers).forEach(key => {
        clearInterval(updateTimers[key]);
    });
    
    // Show notification
    showNotification('Your session has expired. Please log in again.', 'error');
    
    // Redirect to login page after a delay
    setTimeout(() => {
        window.location.href = 'admin_login.php';
    }, 2000);
}

/**
 * Show notification
 */
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification') || createNotificationElement();
    
    notification.textContent = message;
    notification.className = 'notification'; // Reset classes
    
    // Add appropriate class based on type
    switch(type) {
        case 'success':
            notification.classList.add('bg-success');
            break;
        case 'error':
            notification.classList.add('bg-danger');
            break;
        case 'warning':
            notification.classList.add('bg-warning');
            break;
        default:
            notification.classList.add('bg-info');
    }
    
    // Show notification
    notification.style.display = 'block';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}

/**
 * Create notification element if it doesn't exist
 */
function createNotificationElement() {
    const notification = document.createElement('div');
    notification.id = 'notification';
    notification.className = 'notification';
    document.body.appendChild(notification);
    return notification;
} 