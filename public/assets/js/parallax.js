/**
 * project-cards.js - Guaranteed Project Card Visibility
 * 
 * This script ensures project cards are visible immediately without any scroll requirement
 */

// Execute this script as early as possible
document.addEventListener('DOMContentLoaded', function() {
    // Immediately make project cards visible
    makeProjectCardsVisible();
    
    // Also run this after a small delay to ensure it works even if other scripts interfere
    setTimeout(makeProjectCardsVisible, 100);
    
    // Run again after complete page load to be absolutely certain
    window.addEventListener('load', function() {
        makeProjectCardsVisible();
        // Run one more time after a delay
        setTimeout(makeProjectCardsVisible, 500);
    });
    
    // Initialize hover effects and other behaviors
    initProjectCardInteractions();
});

/**
 * Force all project cards to be immediately visible
 * This function aggressively ensures cards are visible regardless of other scripts
 */
function makeProjectCardsVisible() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');
    
    console.log(`Making ${projectCards.length} project cards visible`);
    
    // Apply sequential animation to each card
    projectCards.forEach((card, index) => {
        // 1. Remove any classes that might hide the card
        card.classList.remove('fade-in');
        
        // 2. Add the visible class
        card.classList.add('visible');
        
        // 3. Force visibility with inline styles that override any CSS
        card.style.opacity = '1 !important';
        card.style.transform = 'none !important';
        
        // 4. Remove any transition delays
        card.style.transitionDelay = '0s';
        
        // 5. Apply important flag to ensure these styles take precedence
        const originalStyle = card.getAttribute('style') || '';
        card.setAttribute('style', originalStyle + ' opacity: 1 !important; transform: none !important; visibility: visible !important;');
    });
    
    // Also find and disable any scroll-based animation triggers
    disableScrollAnimations();
}

/**
 * Disable scroll-based animations for project cards
 */
function disableScrollAnimations() {
    // Find and modify any scroll event listeners (simplified approach)
    // This is a general approach - not all scroll listeners can be disabled this way
    
    // 1. Add a class to mark that scroll animations should be disabled for cards
    document.documentElement.classList.add('no-scroll-animations-for-cards');
    
    // 2. Find any elements that might control animations
    const animationControllers = document.querySelectorAll('.scrollable-content, .main-content');
    animationControllers.forEach(controller => {
        controller.classList.add('no-scroll-animations-for-cards');
    });
}

/**
 * Initialize project card hover interactions
 */
function initProjectCardInteractions() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');
    
    // Add hover effects
    projectCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'none';
            this.style.boxShadow = '';
        });
    });
}