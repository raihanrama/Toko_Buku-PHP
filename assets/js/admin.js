document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.getElementById('admin-sidebar');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = menuToggle.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }
    
    // Animation for cards and stat-cards
    const cards = document.querySelectorAll('.admin-card, .stat-card');
    
    function animateCards() {
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
    }
    
    if (cards.length > 0) {
        animateCards();
    }
    
    // Table row hover effect
    const tableRows = document.querySelectorAll('.admin-table tbody tr');
    
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(78, 115, 223, 0.05)';
            this.style.transition = 'background-color 0.3s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Form validation
    const adminForms = document.querySelectorAll('.admin-form');
    
    adminForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    
                    // Create or update error message
                    let errorMsg = field.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('div');
                        errorMsg.classList.add('error-message');
                        errorMsg.style.color = 'var(--danger-color)';
                        errorMsg.style.fontSize = '0.85rem';
                        errorMsg.style.marginTop = '0.25rem';
                        field.parentNode.insertBefore(errorMsg, field.nextSibling);
                    }
                    errorMsg.textContent = 'This field is required';
                } else {
                    field.classList.remove('is-invalid');
                    const errorMsg = field.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // Confirm deletes
    const deleteButtons = document.querySelectorAll('.delete-btn, [data-action="delete"]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Datepicker initialization (if any)
    const datepickers = document.querySelectorAll('.datepicker');
    
    if (datepickers.length > 0 && typeof flatpickr !== 'undefined') {
        datepickers.forEach(picker => {
            flatpickr(picker, {
                dateFormat: "Y-m-d",
                allowInput: true
            });
        });
    }
    
    // Toast/notification system
    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `notification notification-${type}`;
        toast.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
            </div>
            <button class="notification-close">&times;</button>
        `;
        
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.backgroundColor = type === 'success' ? 'var(--success-color)' : 
                                     type === 'error' ? 'var(--danger-color)' : 
                                     type === 'warning' ? 'var(--warning-color)' : 
                                     'var(--primary-color)';
        toast.style.color = '#fff';
        toast.style.padding = '1rem';
        toast.style.borderRadius = '0.35rem';
        toast.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
        toast.style.zIndex = '9999';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px)';
        toast.style.transition = 'all 0.3s ease';
        
        document.body.appendChild(toast);
        
        // Show with animation
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);
        
        // Auto close after 5 seconds
        const autoCloseTimeout = setTimeout(() => {
            closeToast();
        }, 5000);
        
        // Close button functionality
        const closeBtn = toast.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            clearTimeout(autoCloseTimeout);
            closeToast();
        });
        
        function closeToast() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    }
    
    // Expose to global scope for use in other scripts
    window.adminUtils = {
        showNotification
    };
    
    // Check for success/error messages in URL params and show notifications
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');
    
    if (successMsg) {
        showNotification(decodeURIComponent(successMsg), 'success');
        // Remove the parameter from URL without page refresh
        const newUrl = window.location.pathname + window.location.hash;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    if (errorMsg) {
        showNotification(decodeURIComponent(errorMsg), 'error');
        // Remove the parameter from URL without page refresh
        const newUrl = window.location.pathname + window.location.hash;
        window.history.replaceState({}, document.title, newUrl);
    }
}); 