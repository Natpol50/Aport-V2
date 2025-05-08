/**
 * Mouse Tracking for Project Cards
 * 
 * This script implements a 3D mouse tracking effect for project cards.
 * 
 * Mathematical approach:
 * - Calculates relative mouse position on the card using matrix transformations
 * - Applies proportional rotation based on mouse distance from center
 * - Creates a dynamic lighting effect that follows mouse movement
 * - Uses requestAnimationFrame for smooth performance
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize enhanced project cards
    initEnhancedProjectCards();
});

/**
 * Initialize enhanced project cards with 3D mouse tracking
 */
function initEnhancedProjectCards() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');
    
    // Exit if no cards found
    if (projectCards.length === 0) return;
    
    console.log(`Initializing 3D effects for ${projectCards.length} project cards`);
    
    // Setup mouse tracking for each card
    projectCards.forEach(card => {
        // Force 3D perspective
        card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
        
        // Store card dimensions for calculations
        let rect = card.getBoundingClientRect();
        let cardWidth = rect.width;
        let cardHeight = rect.height;
        
        // Set up event listeners for mouse movement
        card.addEventListener('mousemove', throttle(function(e) {
            // Skip if device is touch-only (mobile)
            if (window.matchMedia('(hover: none)').matches) return;
            
            // Get mouse position relative to card
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            
            // Calculate rotation values based on mouse position
            // Mathematical formula: (2 * relativePos - 1) * maxRotation
            // This maps mouse position from [0,1] to [-maxRotation,+maxRotation]
            const rotateY = ((mouseX / cardWidth) * 2 - 1) * 7; // -7° to +7° rotation for stronger effect
            const rotateX = ((mouseY / cardHeight) * 2 - 1) * -5; // +5° to -5° rotation (inverted)
            
            // Apply additional effect to badge element
            const badge = card.querySelector('.trapezoid-badge');
            if (badge) {
                // Make badge respond to mouse movement
                const badgeOffsetX = (mouseX / cardWidth - 0.5) * 5; // -2.5px to +2.5px
                badge.style.transform = `skewX(-25deg) translateX(${badgeOffsetX}px)`;
            }
            
            // Apply smooth rotation using CSS custom properties
            card.style.setProperty('--rotateY', `${rotateY}deg`);
            card.style.setProperty('--rotateX', `${rotateX}deg`);
            
            // Calculate highlight position for light reflection effect
            // Highlight follows mouse position
            const highlightX = `${mouseX / cardWidth * 100}%`;
            const highlightY = `${mouseY / cardHeight * 100}%`;
            const highlightOpacity = 0.7 - (Math.abs(rotateY) / 10) - (Math.abs(rotateX) / 10);
            
            card.style.setProperty('--highlight-x', highlightX);
            card.style.setProperty('--highlight-y', highlightY);
            card.style.setProperty('--highlight-opacity', highlightOpacity);
        }, 10));
        
        // Reset card position when mouse leaves
        card.addEventListener('mouseleave', function() {
            // Smoothly reset rotations with animation
            requestAnimationFrame(() => {
                // Use transition for smooth reset
                card.style.transition = 'transform 0.5s ease';
                card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
                
                // Reset CSS custom properties
                card.style.setProperty('--rotateY', '0deg');
                card.style.setProperty('--rotateX', '0deg');
                card.style.setProperty('--highlight-opacity', '0');
                
                // Reset badge position
                const badge = card.querySelector('.trapezoid-badge');
                if (badge) {
                    badge.style.transition = 'transform 0.3s ease';
                    badge.style.transform = 'skewX(-20deg)';
                }
                
                // Remove transition after animation completes
                setTimeout(() => {
                    card.style.transition = '';
                }, 500);
            });
        });
        
        // Update card dimensions on window resize
        window.addEventListener('resize', debounce(function() {
            rect = card.getBoundingClientRect();
            cardWidth = rect.width;
            cardHeight = rect.height;
        }, 250));
    });
}

/**
 * Throttle function to limit execution rate
 * Ensures smooth animation by controlling how often calculations occur
 * 
 * @param {Function} func - Function to throttle
 * @param {number} limit - Minimum milliseconds between executions
 * @return {Function} - Throttled function
 */
function throttle(func, limit) {
    let lastCall = 0;
    return function(...args) {
        const now = Date.now();
        if (now - lastCall >= limit) {
            lastCall = now;
            func.apply(this, args);
        }
    };
}

/**
 * Debounce function for expensive operations
 * Prevents excessive calculations during continuous events like resizing
 * 
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @return {Function} - Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * Check if a device supports hover
 * Useful for disabling effects on touch-only devices
 * 
 * @return {boolean} - True if device supports hover
 */
function supportsHover() {
    return window.matchMedia('(hover: hover)').matches;
}