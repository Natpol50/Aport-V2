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
    
    // Initialize trapezoid hover effects
    initTrapezoidEffects();
    
    // Initialize mobile menu
    initMobileMenu();
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
                    errorMessage.className = 'error-message text-sm mt-1';
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
                    errorMessage.className = 'error-message text-sm mt-1';
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
 * Uses regex pattern to ensure valid email format
 * 
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
        if (href && href.startsWith('#')) return;
        
        // Check if this link matches the current path
        if (href === currentPath || 
            (href === '/' && currentPath === '') || 
            (href !== '/' && currentPath.startsWith(href))) {
            link.classList.add('active');
        }
    });
}

/**
 * Initialize trapezoid hover effects for cards
 * Uses mathematical transformations for visual effects
 */
function initTrapezoidEffects() {
    const trapezoidElements = document.querySelectorAll('.trapezoid, .contact-card');
    
    trapezoidElements.forEach(element => {
        // Store original transform for resetting
        const originalTransform = getComputedStyle(element).transform;
        
        element.addEventListener('mouseenter', function() {
            // More dramatic skew on hover - mathematical amplification
            const skewAngle = 20; // degrees
            
            // Calculate skew transformation
            this.style.transform = `skewX(-${skewAngle}deg)`;
            
            // Also lift element slightly
            this.style.transform += ' translateY(-5px)';
            
            // Add shadow for depth perception
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.3)';
            
            // If there's content inside that should stay straight
            const content = this.querySelector('.card-content, .trapezoid-text');
            if (content) {
                // Counter-skew the content to keep it readable
                content.style.transform = `skewX(${skewAngle}deg)`;
            }
        });
        
        element.addEventListener('mouseleave', function() {
            // Reset transformations
            this.style.transform = originalTransform || '';
            this.style.boxShadow = '';
            
            // Reset content skew
            const content = this.querySelector('.card-content, .trapezoid-text');
            if (content) {
                content.style.transform = '';
            }
        });
    });
}

/**
 * Initialize mobile menu behavior
 */
function initMobileMenu() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (!menuToggle || !mobileMenu) return;
    
    menuToggle.addEventListener('click', function() {
        // Toggle aria-expanded state
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !isExpanded);
        
        // Toggle mobile menu visibility with animation
        if (isExpanded) {
            // Close menu with animation
            mobileMenu.style.maxHeight = '0';
            
            // After animation completes, hide the menu
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
            }, 300); // Match transition duration
        } else {
            // Show menu first (unhide it)
            mobileMenu.classList.remove('hidden');
            
            // Then animate its height
            // Use requestAnimationFrame to ensure DOM has updated
            requestAnimationFrame(() => {
                mobileMenu.style.maxHeight = `${mobileMenu.scrollHeight}px`;
            });
        }
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        // Check if menu is open and click is outside menu and toggle button
        const isMenuOpen = menuToggle.getAttribute('aria-expanded') === 'true';
        
        if (isMenuOpen && 
            !mobileMenu.contains(event.target) && 
            !menuToggle.contains(event.target)) {
            
            // Set aria-expanded to false
            menuToggle.setAttribute('aria-expanded', 'false');
            
            // Close menu with animation
            mobileMenu.style.maxHeight = '0';
            
            // After animation completes, hide the menu
            setTimeout(() => {
                mobileMenu.classList.add('hidden');
            }, 300);
        }
    });
}