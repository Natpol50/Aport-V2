/**
 * main.js - Core JavaScript functionality
 * 
 * This file handles:
 * - Mobile menu interactions
 * - Portrait warning
 * - Flash messages
 * - Form validation
 * - Language switcher
 * - General UI interactions
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initPortraitWarning();
    initMobileMenu();
    initFlashMessages();
    initFormValidation();
    initLanguageSwitcher();
    initSmoothScrolling();
    
    // Log initialization complete
    console.log('Website initialization complete');
});

/**
 * Initialize portrait mode warning
 */
function initPortraitWarning() {
    const hasSeenWarning = localStorage.getItem('hasSeenPortraitWarning');
    const warningModal = document.getElementById('portraitWarning');
    
    if (!warningModal) return;
    
    // Check if orientation is portrait
    const isPortrait = window.innerHeight > window.innerWidth;
    
    if (isPortrait && !hasSeenWarning) {
        // If first time in portrait, show the modal with animation
        warningModal.style.opacity = '0';
        warningModal.style.transition = 'opacity 0.3s ease-in-out';
        warningModal.style.display = 'flex';
        
        // Delay to allow transition to take effect
        setTimeout(() => {
            warningModal.style.opacity = '1';
        }, 100);
    }
    
    // Set up close button
    const closeButton = document.getElementById('closePortraitWarning');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            // Animate closing
            warningModal.style.opacity = '0';
            setTimeout(() => {
                warningModal.style.display = 'none';
            }, 300);
            
            // Record that user has seen the warning
            localStorage.setItem('hasSeenPortraitWarning', 'true');
        });
    }
    
    // Handle orientation change
    window.addEventListener('resize', debounce(function() {
        const isPortraitNow = window.innerHeight > window.innerWidth;
        
        // If switching to portrait and haven't seen warning
        if (isPortraitNow && !localStorage.getItem('hasSeenPortraitWarning')) {
            warningModal.style.display = 'flex';
            setTimeout(() => {
                warningModal.style.opacity = '1';
            }, 100);
        } 
        // If switching to landscape
        else if (!isPortraitNow) {
            warningModal.style.opacity = '0';
            setTimeout(() => {
                warningModal.style.display = 'none';
            }, 300);
        }
    }, 250));
}

/**
 * Initialize mobile menu functionality
 */
function initMobileMenu() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (!menuToggle || !mobileMenu) return;
    
    menuToggle.addEventListener('click', function() {
        // Toggle aria-expanded attribute
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !isExpanded);
        
        // Toggle menu visibility with animation
        if (isExpanded) {
            // Closing menu
            mobileMenu.style.maxHeight = '0px';
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
            }, 300);
        } else {
            // Opening menu
            mobileMenu.classList.remove('hidden');
            // Use scrollHeight to determine the proper height
            mobileMenu.style.maxHeight = mobileMenu.scrollHeight + 'px';
        }
    });
}

/**
 * Initialize flash messages auto-dismiss
 */
function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    
    flashMessages.forEach(message => {
        // Add close button if not present
        if (!message.querySelector('.flash-close')) {
            const closeButton = document.createElement('button');
            closeButton.className = 'flash-close';
            closeButton.innerHTML = '&times;';
            closeButton.setAttribute('aria-label', 'Close');
            
            message.appendChild(closeButton);
            
            // Add click event to close button
            closeButton.addEventListener('click', () => {
                dismissMessage(message);
            });
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            dismissMessage(message);
        }, 5000);
    });
    
    function dismissMessage(message) {
        message.style.opacity = '0';
        setTimeout(() => {
            message.style.display = 'none';
        }, 300);
    }
}

/**
 * Initialize form validation
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
                const existingError = field.parentNode.querySelector('.form-error');
                if (existingError) {
                    existingError.remove();
                }
                
                // Reset field styling
                field.classList.remove('error');
                
                // Validate the field
                if (!field.value.trim()) {
                    isValid = false;
                    
                    // Add error styling
                    field.classList.add('error');
                    
                    // Add error message
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'form-error';
                    errorMessage.textContent = field.dataset.errorMessage || 'This field is required';
                    field.parentNode.appendChild(errorMessage);
                }
                
                // Additional validation for email fields
                if (field.type === 'email' && field.value.trim() && !validateEmail(field.value)) {
                    isValid = false;
                    
                    // Add error styling
                    field.classList.add('error');
                    
                    // Add error message
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'form-error';
                    errorMessage.textContent = field.dataset.emailErrorMessage || 'Please enter a valid email address';
                    field.parentNode.appendChild(errorMessage);
                }
            });
            
            // Prevent form submission if validation fails
            if (!isValid) {
                event.preventDefault();
                
                // Scroll to the first error
                const firstError = form.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    });
    
    // Helper function to validate email
    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
}

/**
 * Initialize language switcher behavior
 */
function initLanguageSwitcher() {
    const languageLinks = document.querySelectorAll('.language-switcher a');
    
    languageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't switch if already on that language
            if (!this.classList.contains('inactive')) {
                e.preventDefault();
                return;
            }
            
            // Store current scroll position
            localStorage.setItem('scrollPosition', window.scrollY);
        });
    });
    
    // Restore scroll position if available
    const savedScrollPosition = localStorage.getItem('scrollPosition');
    if (savedScrollPosition) {
        window.scrollTo(0, parseInt(savedScrollPosition));
        localStorage.removeItem('scrollPosition');
    }
}

/**
 * Initialize smooth scrolling for anchor links
 */
function initSmoothScrolling() {
    // Get all links that hash to an element ID
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Get the target element
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            // Only proceed if target exists
            if (targetElement) {
                e.preventDefault();
                
                // Scroll smoothly to the target
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update URL without reloading page
                history.pushState(null, null, targetId);
            }
        });
    });
}

/**
 * Debounce function to limit how often a function is called
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}