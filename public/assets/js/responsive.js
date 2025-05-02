/**
 * responsive.js - Responsive features handling
 * 
 * This file contains JavaScript functionality for responsive features,
 * such as orientation detection and responsive layout adjustments.
 */

// Initialize responsive behavior when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set up orientation change detection
    detectOrientationChange();
    
    // Initialize responsive navigation
    initResponsiveNav();
    
    // Handle window resize events for responsive adjustments
    window.addEventListener('resize', debounce(handleResize, 250));
    
    // Trigger initial resize handler
    handleResize();
});

/**
 * Handle orientation changes
 */
function detectOrientationChange() {
    // Check if orientation API is available
    if (window.matchMedia) {
        const portraitMatcher = window.matchMedia("(orientation: portrait)");
        
        // Initial check
        handleOrientationChange(portraitMatcher);
        
        // Add listener for changes
        if (portraitMatcher.addEventListener) {
            // Modern browsers
            portraitMatcher.addEventListener('change', handleOrientationChange);
        } else if (portraitMatcher.addListener) {
            // Older browsers
            portraitMatcher.addListener(handleOrientationChange);
        }
    } else {
        // Fallback for browsers that don't support matchMedia
        window.addEventListener('resize', function() {
            const isPortrait = window.innerHeight > window.innerWidth;
            document.body.classList.toggle('portrait-mode', isPortrait);
            document.body.classList.toggle('landscape-mode', !isPortrait);
        });
    }
}

/**
 * Handle orientation change events
 * @param {MediaQueryListEvent|MediaQueryList} event - The orientation change event or initial matcher
 */
function handleOrientationChange(event) {
    const isPortrait = event.matches;
    
    // Add appropriate class to body
    document.body.classList.toggle('portrait-mode', isPortrait);
    document.body.classList.toggle('landscape-mode', !isPortrait);
    
    // Toggle visibility of portrait warning
    const warningElement = document.getElementById('portraitWarning');
    if (warningElement) {
        if (isPortrait && localStorage.getItem('hasSeenPortraitWarning') !== 'true') {
            warningElement.style.display = 'flex';
        } else {
            warningElement.style.display = 'none';
        }
    }
    
    // Handle navigation changes
    const desktopNav = document.querySelector('.desktop-nav');
    const mobileNav = document.querySelector('.mobile-nav');
    
    if (desktopNav && mobileNav) {
        if (isPortrait) {
            desktopNav.style.display = 'none';
            mobileNav.style.display = 'flex';
        } else {
            desktopNav.style.display = 'flex';
            mobileNav.style.display = 'none';
        }
    }
    
    // Update language switcher position
    const langSwitcher = document.querySelector('.language-switcher');
    if (langSwitcher) {
        if (isPortrait) {
            langSwitcher.classList.add('portrait-position');
        } else {
            langSwitcher.classList.remove('portrait-position');
        }
    }
}

/**
 * Initialize responsive navigation
 */
function initResponsiveNav() {
    // Handle mobile navigation toggles
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            const isExpanded = mobileMenuToggle.getAttribute('aria-expanded') === 'true';
            mobileMenuToggle.setAttribute('aria-expanded', !isExpanded);
            
            // Toggle menu visibility with animation
            if (isExpanded) {
                mobileMenu.style.maxHeight = '0';
                setTimeout(() => {
                    mobileMenu.classList.add('hidden');
                }, 300);
            } else {
                mobileMenu.classList.remove('hidden');
                setTimeout(() => {
                    mobileMenu.style.maxHeight = mobileMenu.scrollHeight + 'px';
                }, 10);
            }
        });
    }
}

/**
 * Handle window resize events
 */
function handleResize() {
    // Update navigation underline position for active link
    const activeLink = document.querySelector('.nav-link.active');
    if (activeLink) {
        updateNavUnderline(activeLink);
    }
    
    // Update any responsive containers
    updateResponsiveContainers();
    
    // Check if portrait warning should be shown
    checkPortraitWarning();
}

/**
 * Update responsive containers based on current viewport
 */
function updateResponsiveContainers() {
    // Get viewport dimensions
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    // Handle aspect ratio dependent elements
    const aspectRatioElements = document.querySelectorAll('[data-min-aspect-ratio]');
    
    aspectRatioElements.forEach(element => {
        const minAspectRatio = element.dataset.minAspectRatio.split('/');
        const currentAspectRatio = viewportWidth / viewportHeight;
        const minRatio = parseFloat(minAspectRatio[0]) / parseFloat(minAspectRatio[1]);
        
        // Toggle visibility based on aspect ratio
        if (currentAspectRatio >= minRatio) {
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    });
}

/**
 * Check if portrait warning should be shown
 */
function checkPortraitWarning() {
    const isPortrait = window.innerHeight > window.innerWidth;
    const hasSeenWarning = localStorage.getItem('hasSeenPortraitWarning') === 'true';
    const warningElement = document.getElementById('portraitWarning');
    
    if (warningElement) {
        if (isPortrait && !hasSeenWarning) {
            warningElement.style.display = 'flex';
            warningElement.style.opacity = '1';
        } else {
            warningElement.style.display = 'none';
        }
    }
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
 * Debounce function to limit how often a function is called
 * Uses mathematical principles to improve performance
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