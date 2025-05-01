/**
 * ui.js - UI-specific JavaScript functionality
 * 
 * This file contains JavaScript functionality specific to UI components
 * such as form validation, animations, and interactive elements.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    initFormValidation();
    
    // Initialize navigation active state
    initNavigationActiveState();
    
    // Initialize flash message auto-dismissal
    initFlashMessages();
});

/**
 * Initialize form validation for all forms
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Get all required fields in the form
            const requiredFields = form.querySelectorAll('[required]');
            
            // Check each required field
            requiredFields.forEach(field => {
                // Remove existing error messages
                const existingError = field.parentNode.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                // Reset field styling
                field.classList.remove('border-red-500');
                
                // Validate the field
                if (!field.value.trim()) {
                    isValid = false;
                    
                    // Add error styling
                    field.classList.add('border-red-500');
                    
                    // Add error message
                    const errorMessage = document.createElement('p');
                    errorMessage.className = 'error-message text-red-500 text-sm mt-1';
                    errorMessage.textContent = field.dataset.errorMessage || 'This field is required';
                    field.parentNode.appendChild(errorMessage);
                }
                
                // Additional validation for email fields
                if (field.type === 'email' && field.value.trim() && !validateEmail(field.value)) {
                    isValid = false;
                    
                    // Add error styling
                    field.classList.add('border-red-500');
                    
                    // Add error message
                    const errorMessage = document.createElement('p');
                    errorMessage.className = 'error-message text-red-500 text-sm mt-1';
                    errorMessage.textContent = field.dataset.emailErrorMessage || 'Please enter a valid email address';
                    field.parentNode.appendChild(errorMessage);
                }
            });
            
            // Prevent form submission if validation fails
            if (!isValid) {
                event.preventDefault();
                
                // Scroll to the first error
                const firstError = form.querySelector('.border-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    });
}

/**
 * Validate an email address
 * @param {string} email - The email address to validate
 * @return {boolean} - True if the email is valid, false otherwise
 */
function validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

/**
 * Initialize active state for navigation links
 */
function initNavigationActiveState() {
    const currentPath = window.location.pathname;
    
    // Get all navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Check each link
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        
        // Skip non-path links (e.g., '#contact')
        if (href.startsWith('#')) return;
        
        // Check if this link matches the current path
        if (href === currentPath || 
            (href === '/' && currentPath === '') || 
            (href !== '/' && currentPath.startsWith(href))) {
            link.classList.add('active');
            
            // Update nav underline position
            updateNavUnderline(link);
        }
    });
}

/**
 * Update the navigation underline position
 * @param {HTMLElement} activeLink - The active navigation link
 */
function updateNavUnderline(activeLink) {
    const underline = document.querySelector('.nav-underline');
    if (!underline || !activeLink) return;
    
    // Calculate position
    const linkRect = activeLink.getBoundingClientRect();
    const linkCenter = linkRect.left + (linkRect.width / 2);
    const underlineWidth = linkRect.width - 20; // Slightly shorter than the link
    
    // Update underline position
    underline.style.left = `${linkCenter - (underlineWidth / 2)}px`;
    underline.style.width = `${underlineWidth}px`;
}

/**
 * Initialize auto-dismissal of flash messages
 */
function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    
    flashMessages.forEach(message => {
        // Auto-dismiss flash messages after 5 seconds
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 300);
        }, 5000);
        
        // Add close button to flash messages
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '&times;';
        closeButton.className = 'absolute top-2 right-2 text-lg font-bold';
        closeButton.addEventListener('click', () => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 300);
        });
        
        // Make sure the message has relative positioning for absolute positioning of the close button
        message.style.position = 'relative';
        message.appendChild(closeButton);
    });
}