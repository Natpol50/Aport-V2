/**
 * main.js - Core JavaScript functionality
 * 
 * This file contains the main JavaScript functionality for the website.
 * It handles initialization, event binding, and core features.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initPortraitWarning();
    initSmoothScrolling();
    initParallaxEffects();
    initMobileMenu();
    initLanguageSwitcher();
    initContactForm();
    initFlashMessages();
    
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
        if (isPortraitNow && !hasSeenWarning) {
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
 * Initialize parallax effects for scrolling
 */
function initParallaxEffects() {
    // Elements that should fade in/out based on scroll position
    const contactButton = document.getElementById('contact-button');
    const contactLinks = document.getElementById('contact-links');
    const contactElements = document.querySelectorAll('.contact-link');
    
    // Throttle scroll event for better performance
    let ticking = false;
    
    function updateVisibility() {
        // Get viewport dimensions and scroll position
        const viewportHeight = window.innerHeight;
        const scrollPosition = window.scrollY;
        const documentHeight = document.documentElement.scrollHeight;
        
        // Calculate visibility thresholds
        const buttonThreshold = viewportHeight * 0.3;
        const linksThreshold = documentHeight - viewportHeight * 1.3;
        
        // Determine element visibility
        const showButton = scrollPosition < buttonThreshold;
        const showLinks = scrollPosition > linksThreshold;
        
        // Helper function to update element visibility
        function updateElement(element, show) {
            if (!element) return;
            
            element.style.opacity = show ? '1' : '0';
            element.style.pointerEvents = show ? 'auto' : 'none';
        }
        
        // Update visibility states
        updateElement(contactButton, showButton);
        updateElement(contactLinks, showLinks);
        
        // Update individual contact links with staggered delay
        contactElements.forEach((link, index) => {
            // Set a small delay for each link to create a staggered effect
            setTimeout(() => {
                updateElement(link, showLinks);
            }, index * 50);
        });
        
        // Reset ticking flag
        ticking = false;
    }
    
    // Handle scroll events with requestAnimationFrame for performance
    function onScroll() {
        if (!ticking) {
            requestAnimationFrame(updateVisibility);
            ticking = true;
        }
    }
    
    // Set up event listeners
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', debounce(updateVisibility, 250), { passive: true });
    
    // Initial visibility update
    updateVisibility();
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
 * Initialize contact form validation and submission
 */
function initContactForm() {
    const contactForm = document.getElementById('contact-form');
    
    if (!contactForm) return;
    
    contactForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Get form fields
        const emailField = document.getElementById('email');
        const subjectField = document.getElementById('subject');
        const messageField = document.getElementById('message');
        
        // Clear previous error messages
        document.querySelectorAll('.form-error').forEach(error => error.remove());
        document.querySelectorAll('.form-control.error').forEach(field => {
            field.classList.remove('error');
        });
        
        // Validate required fields
        if (subjectField && !subjectField.value.trim()) {
            isValid = false;
            markFieldAsInvalid(subjectField, 'Subject is required');
        }
        
        if (messageField && !messageField.value.trim()) {
            isValid = false;
            markFieldAsInvalid(messageField, 'Message is required');
        }
        
        // Validate email format if provided
        if (emailField && emailField.value.trim() && !isValidEmail(emailField.value)) {
            isValid = false;
            markFieldAsInvalid(emailField, 'Please enter a valid email address');
        }
        
        // Prevent form submission if validation fails
        if (!isValid) {
            e.preventDefault();
            
            // Focus on the first invalid field
            const firstInvalid = document.querySelector('.form-control.error');
            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
    });
    
    // Helper function to mark field as invalid
    function markFieldAsInvalid(field, errorMessage) {
        field.classList.add('error');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'form-error';
        errorElement.textContent = errorMessage;
        
        field.parentNode.appendChild(errorElement);
    }
    
    // Helper function to validate email format
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
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
 * Debounce function to limit how often a function is called
 * 
 * @param {Function} func - The function to debounce
 * @param {number} wait - The debounce wait time in milliseconds
 * @return {Function} - The debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}