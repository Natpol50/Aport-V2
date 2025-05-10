/**
 * contact-standalone.js - Interactive functionality for standalone contact page
 * 
 * This script enhances the contact page with interactive effects:
 * 1. Advanced form validation with visual feedback
 * 2. Interactive trapezoid hover effects with 3D-like transforms
 * 3. Automatic flash message handling
 * 4. Mathematical transforms for visual interest
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Contact standalone script loaded');
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize trapezoid effects
    initTrapezoidEffects();
    
    // Initialize flash message handling
    initFlashMessages();
    
    // Initialize 3D-like hover effects
    init3DHoverEffects();
    
    // Initialize parallax effect for the page title
    initParallaxTitle();
});

/**
 * Initialize form validation with visual feedback
 * 
 * This function provides client-side validation with mathematically
 * calculated visual feedback using CSS transforms
 */
function initFormValidation() {
    const form = document.querySelector('.contact-form');
    
    if (!form) {
        console.warn('Contact form not found on page');
        return;
    }
    
    console.log('Initializing contact form validation');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Get all required fields
        const requiredFields = form.querySelectorAll('[required]');
        
        // Validate each required field
        requiredFields.forEach(field => {
            // Clear previous error state
            removeErrorState(field);
            
            // Check if field is empty
            if (!field.value.trim()) {
                isValid = false;
                setErrorState(field, field.dataset.errorMessage || 'This field is required');
            }
        });
        
        // Validate email field if it has a value
        const emailField = form.querySelector('input[type="email"]');
        if (emailField && emailField.value.trim()) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailPattern.test(emailField.value)) {
                isValid = false;
                setErrorState(emailField, 'Please enter a valid email address');
            }
        }
        
        // If validation failed, prevent form submission
        if (!isValid) {
            event.preventDefault();
            
            // Find first error field and focus it
            const firstErrorField = form.querySelector('.error-field');
            if (firstErrorField) {
                // Scroll to first error with smooth animation
                firstErrorField.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Focus the field after scrolling completes
                setTimeout(() => {
                    firstErrorField.focus();
                }, 500);
            }
        }
    });
    
    /**
     * Set error state on a form field
     * 
     * @param {HTMLElement} field - The form field element
     * @param {string} message - Error message to display
     */
    function setErrorState(field, message) {
        // Add error class to field
        field.classList.add('error-field');
        
        // Create error message element with transformed styling
        const errorMessage = document.createElement('div');
        errorMessage.className = 'field-error';
        errorMessage.textContent = message;
        
        // Insert error message after the field
        field.parentNode.appendChild(errorMessage);
        
        // Enhance error visual feedback with transform
        field.style.transform = 'skewX(2deg)';
    }
    
    /**
     * Remove error state from a form field
     * 
     * @param {HTMLElement} field - The form field element
     */
    function removeErrorState(field) {
        // Remove error class from field
        field.classList.remove('error-field');
        
        // Remove any existing error message
        const errorMessage = field.parentNode.querySelector('.field-error');
        if (errorMessage) {
            errorMessage.remove();
        }
        
        // Reset transform
        field.style.transform = '';
    }
    
    // Add real-time validation on input
    form.querySelectorAll('input, textarea').forEach(field => {
        field.addEventListener('input', function() {
            if (field.classList.contains('error-field')) {
                // Revalidate field on input if it was previously marked as error
                if (field.value.trim()) {
                    // For email field, check valid format
                    if (field.type === 'email') {
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        
                        if (emailPattern.test(field.value)) {
                            removeErrorState(field);
                        }
                    } else {
                        // For other fields, just check if not empty
                        removeErrorState(field);
                    }
                }
            }
        });
    });
}

/**
 * Initialize trapezoid effects on UI elements
 * 
 * This function enhances the trapezoid visual elements with
 * interactive effects based on mathematical transformations
 */
function initTrapezoidEffects() {
    // Enhance contact icon containers with hover effects
    const iconContainers = document.querySelectorAll('.contact-icon-container');
    
    iconContainers.forEach(container => {
        // Get the icon element
        const icon = container.querySelector('svg');
        
        // Add enhanced hover effect
        container.addEventListener('mouseenter', function() {
            // Calculate more dramatic skew angle
            const defaultSkew = -15; // degrees
            const hoverSkew = -25; // degrees
            
            // Apply enhanced transform with transition
            this.style.transform = `skewX(${hoverSkew}deg)`;
            
            // Adjust icon skew to compensate
            if (icon) {
                icon.style.transform = `skewX(${Math.abs(hoverSkew)}deg)`;
            }
            
            // Add shadow for depth
            this.style.boxShadow = '4px 4px 8px rgba(0, 0, 0, 0.2)';
        });
        
        // Reset on mouse leave
        container.addEventListener('mouseleave', function() {
            // Reset to default skew
            this.style.transform = 'skewX(-15deg)';
            
            // Reset icon
            if (icon) {
                icon.style.transform = 'skewX(15deg)';
            }
            
            // Remove shadow
            this.style.boxShadow = '';
        });
    });
    
    // Enhance submit button with dramatic effect
    const submitButton = document.querySelector('.submit-btn');
    
    if (submitButton) {
        // Get the span inside the button
        const buttonText = submitButton.querySelector('span');
        
        submitButton.addEventListener('mouseenter', function() {
            // Dramatic skew and lift effect
            this.style.transform = 'skewX(-20deg) translateY(-5px)';
            this.style.boxShadow = '0 8px 15px rgba(0, 0, 0, 0.2)';
            
            // Ensure text remains readable
            if (buttonText) {
                buttonText.style.transform = 'skewX(20deg)';
            }
        });
        
        submitButton.addEventListener('mouseleave', function() {
            // Reset to default state
            this.style.transform = 'skewX(-15deg)';
            this.style.boxShadow = '';
            
            // Reset text
            if (buttonText) {
                buttonText.style.transform = 'skewX(15deg)';
            }
        });
        
        // Add press effect
        submitButton.addEventListener('mousedown', function() {
            this.style.transform = 'skewX(-15deg) translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.15)';
        });
        
        submitButton.addEventListener('mouseup', function() {
            this.style.transform = 'skewX(-15deg) translateY(-5px)';
            this.style.boxShadow = '0 8px 15px rgba(0, 0, 0, 0.2)';
        });
    }
}

/**
 * Initialize flash message handling
 * 
 * This function adds auto-dismiss and close button functionality
 * to flash messages with animated transitions
 */
function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    
    if (flashMessages.length === 0) {
        return;
    }
    
    console.log(`Initializing ${flashMessages.length} flash messages`);
    
    flashMessages.forEach((message, index) => {
        // Add staggered animation delay for multiple messages
        const delay = index * 200; // milliseconds
        
        // Set initial state with delay
        setTimeout(() => {
            message.style.opacity = '1';
            message.style.transform = 'translateY(0)';
        }, delay);
        
        // Create close button for the message
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '&times;';
        closeButton.className = 'flash-close-btn';
        closeButton.style.cssText = `
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        `;
        
        // Add hover effect to close button
        closeButton.addEventListener('mouseenter', function() {
            this.style.opacity = '1';
        });
        
        closeButton.addEventListener('mouseleave', function() {
            this.style.opacity = '0.7';
        });
        
        // Add click event to close button
        closeButton.addEventListener('click', function() {
            dismissMessage(message);
        });
        
        // Add the close button to the message
        message.appendChild(closeButton);
        
        // Auto-dismiss after 5 seconds
        const autoDismissDelay = 5000 + delay; // Base delay + staggered delay
        message.autoDismissTimeout = setTimeout(() => {
            dismissMessage(message);
        }, autoDismissDelay);
    });
    
    /**
     * Dismiss a flash message with animation
     * 
     * @param {HTMLElement} message - The flash message element to dismiss
     */
    function dismissMessage(message) {
        // Clear auto-dismiss timeout if it exists
        if (message.autoDismissTimeout) {
            clearTimeout(message.autoDismissTimeout);
        }
        
        // Animate out
        message.style.opacity = '0';
        message.style.transform = 'translateY(-20px)';
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            message.remove();
        }, 300);
    }
}

/**
 * Initialize 3D-like hover effects for interactive elements
 * 
 * This function applies mathematical transformations to create
 * 3D-like effects that respond to mouse movement
 */
function init3DHoverEffects() {
    // Apply effect to contact info section
    const contactInfo = document.querySelector('.contact-info');
    
    if (contactInfo) {
        // Define transform parameters
        const maxRotate = 2; // Maximum rotation in degrees
        const maxLift = 5; // Maximum lift in pixels
        const perspective = 1000; // Perspective distance in pixels
        
        // Add mouse move listener
        contactInfo.addEventListener('mousemove', function(e) {
            // Calculate relative mouse position within the element
            const rect = this.getBoundingClientRect();
            const mouseX = e.clientX - rect.left; // Mouse X position relative to element
            const mouseY = e.clientY - rect.top; // Mouse Y position relative to element
            
            // Calculate position as percentage of element dimensions
            const posX = (mouseX / rect.width) - 0.5; // -0.5 to 0.5
            const posY = (mouseY / rect.height) - 0.5; // -0.5 to 0.5
            
            // Calculate rotation angles
            const rotateY = posX * maxRotate * 2; // Horizontal axis rotation (pitch)
            const rotateX = posY * maxRotate * -2; // Vertical axis rotation (roll)
            
            // Calculate lift amount
            const lift = Math.max(
                0,
                maxLift * (1 - Math.hypot(posX * 2, posY * 2))
            );
            
            // Apply 3D transform
            this.style.transform = `
                perspective(${perspective}px)
                rotateX(${rotateX}deg)
                rotateY(${rotateY}deg)
                translateZ(${lift}px)
            `;
            
            // Apply shadow based on position
            const shadowX = posX * 10;
            const shadowY = posY * 10;
            this.style.boxShadow = `
                ${shadowX}px ${shadowY}px 20px rgba(0, 0, 0, 0.2)
            `;
        });
        
        // Reset on mouse leave
        contactInfo.addEventListener('mouseleave', function() {
            // Transition back to default state
            this.style.transition = 'transform 0.5s ease, box-shadow 0.5s ease';
            this.style.transform = '';
            this.style.boxShadow = '';
            
            // Clear transition after animation completes
            setTimeout(() => {
                this.style.transition = '';
            }, 500);
        });
    }
    
    // Apply subtle 3D effect to contact form container
    const formContainer = document.querySelector('.contact-form-container');
    
    if (formContainer) {
        // Get form elements for enhanced interaction
        const formElements = formContainer.querySelectorAll('input, textarea, button');
        
        // Apply mouse tracking effect
        formContainer.addEventListener('mousemove', function(e) {
            // Get container dimensions
            const rect = this.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            // Calculate mouse distance from center
            const distanceX = (e.clientX - centerX) / (rect.width / 2);
            const distanceY = (e.clientY - centerY) / (rect.height / 2);
            
            // Calculate subtle rotation angle (max 1 degree)
            const rotateX = distanceY * -1;
            const rotateY = distanceX * 1;
            
            // Apply subtle transform
            this.style.transform = `
                perspective(1000px)
                rotateX(${rotateX}deg)
                rotateY(${rotateY}deg)
            `;
            
            // Apply subtle shadow
            const shadowX = distanceX * 5;
            const shadowY = distanceY * 5;
            this.style.boxShadow = `
                ${shadowX}px ${shadowY}px 10px rgba(0, 0, 0, 0.1)
            `;
            
            // Create interactive effect on form elements
            formElements.forEach(element => {
                // Calculate position relative to mouse
                const elRect = element.getBoundingClientRect();
                const elCenterX = elRect.left + elRect.width / 2;
                const elCenterY = elRect.top + elRect.height / 2;
                
                // Calculate distance from mouse to element center
                const dx = e.clientX - elCenterX;
                const dy = e.clientY - elCenterY;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                // Maximum distance for effect (pixels)
                const maxDistance = 300;
                
                // Calculate lift amount based on proximity
                if (distance < maxDistance) {
                    // Normalize distance (0 to 1)
                    const normalizedDistance = 1 - (distance / maxDistance);
                    
                    // Apply subtle lift effect
                    const lift = normalizedDistance * 5; // max 5px lift
                    
                    // Apply transform
                    element.style.transform = `translateZ(${lift}px)`;
                } else {
                    element.style.transform = '';
                }
            });
        });
        
        // Reset on mouse leave
        formContainer.addEventListener('mouseleave', function() {
            // Reset container
            this.style.transform = '';
            this.style.boxShadow = '';
            
            // Reset all form elements
            formElements.forEach(element => {
                element.style.transform = '';
            });
        });
    }
}

/**
 * Initialize the parallax effect for the page title
 * 
 * This creates a floating effect for the title that responds
 * to page scrolling using mathematical calculations
 */
function initParallaxTitle() {
    const title = document.querySelector('.contact-page-title');
    
    if (!title) return;
    
    // Define parallax parameters
    const parallaxFactor = 0.2; // Strength of the effect
    const maxOffset = 15; // Maximum pixel offset
    
    // Get initial position
    const initialOffset = title.offsetTop;
    
    // Add scroll listener
    window.addEventListener('scroll', function() {
        // Get scroll position
        const scrollY = window.scrollY || window.pageYOffset;
        
        // Calculate new position
        let offset = scrollY * parallaxFactor;
        
        // Limit maximum offset
        offset = Math.min(offset, maxOffset);
        
        // Apply transform
        title.style.transform = `translateY(${offset}px) skewX(-15deg)`;
    });
}
