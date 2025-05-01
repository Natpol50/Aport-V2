/**
 * navigation-enhancements.js
 * 
 * This script enhances the behavior of navigation elements and fixes
 * issues with spacing, language switching, and footer visibility.
 */

// Execute when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Fix navigation spacing and interactions
    enhanceNavigation();
    
    // Fix language switcher behavior
    enhanceLanguageSwitcher();
    
    // Ensure footer visibility
    enhanceFooter();
});

/**
 * Enhances navigation elements with proper spacing and interactions
 */
function enhanceNavigation() {
    // Get all navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    
    // Add hover effect while maintaining spacing
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            // Subtle scale effect on hover
            this.style.transform = 'scale(1.05)';
        });
        
        link.addEventListener('mouseleave', function() {
            // Reset scale
            this.style.transform = 'scale(1)';
        });
    });
    
    // Mobile menu toggle fix
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            
            if (isExpanded) {
                // Close menu
                mobileMenu.style.maxHeight = '0';
                setTimeout(() => {
                    mobileMenu.classList.add('hidden');
                }, 300);
            } else {
                // Open menu
                mobileMenu.classList.remove('hidden');
                // Set max height to scrollHeight for smooth animation
                mobileMenu.style.maxHeight = mobileMenu.scrollHeight + 'px';
            }
        });
    }
}

/**
 * Enhances language switcher behavior and appearance
 */
function enhanceLanguageSwitcher() {
    const languageSwitcher = document.querySelector('.language-switcher');
    
    if (!languageSwitcher) return;
    
    // Fix potential z-index issues by ensuring it's above all content
    languageSwitcher.style.zIndex = '1000';
    
    // Get all language links
    const languageLinks = languageSwitcher.querySelectorAll('a');
    
    languageLinks.forEach(link => {
        // Make inactive links more visible
        if (link.classList.contains('inactive')) {
            link.style.opacity = '0.6';
        }
        
        // Add transition effect for smoother hover
        link.addEventListener('mouseenter', function() {
            this.style.opacity = '1';
        });
        
        link.addEventListener('mouseleave', function() {
            if (this.classList.contains('inactive')) {
                this.style.opacity = '0.6';
            }
        });
    });
}

/**
 * Enhances footer visibility and positioning
 */
function enhanceFooter() {
    const footer = document.querySelector('.main-footer');
    const isHomePage = footer.classList.contains('footer-home');
    
    if (!footer) return;
    
    // Ensure footer is visible
    footer.style.opacity = '1';
    
    // If on home page, adjust positioning based on content height
    if (isHomePage) {
        // Check if content is tall enough to push footer naturally
        const documentHeight = document.documentElement.scrollHeight;
        const windowHeight = window.innerHeight;
        const contentHeight = documentHeight - footer.offsetHeight;
        
        // If content doesn't fill the page, adjust footer position
        if (contentHeight < windowHeight) {
            if (windowHeight >= 900) {
                // For tall screens, fix to bottom
                footer.style.position = 'fixed';
                footer.style.bottom = '0';
                footer.style.left = '0';
                footer.style.right = '0';
            } else {
                // For shorter screens, add margin to push footer down
                const extraMargin = windowHeight - contentHeight - 100; // 100px buffer
                if (extraMargin > 0) {
                    footer.style.marginTop = extraMargin + 'px';
                }
            }
        }
    }
    
    // Check if footer is in viewport on page load
    const footerPosition = footer.getBoundingClientRect();
    
    // If footer is not in viewport, scroll it into view
    if (footerPosition.top > window.innerHeight) {
        // Add a subtle scroll indicator for footer
        const scrollIndicator = document.createElement('div');
        scrollIndicator.classList.add('scroll-indicator');
        scrollIndicator.innerHTML = '⇣';
        scrollIndicator.style.position = 'fixed';
        scrollIndicator.style.bottom = '20px';
        scrollIndicator.style.left = '50%';
        scrollIndicator.style.transform = 'translateX(-50%)';
        scrollIndicator.style.backgroundColor = 'rgba(101, 140, 121, 0.7)';
        scrollIndicator.style.color = 'white';
        scrollIndicator.style.padding = '5px 10px';
        scrollIndicator.style.borderRadius = '20px';
        scrollIndicator.style.cursor = 'pointer';
        scrollIndicator.style.zIndex = '100';
        scrollIndicator.style.opacity = '0';
        scrollIndicator.style.transition = 'opacity 0.3s ease';
        
        document.body.appendChild(scrollIndicator);
        
        // Show indicator after a delay
        setTimeout(() => {
            scrollIndicator.style.opacity = '1';
        }, 2000);
        
        // Hide indicator when clicking or scrolling
        scrollIndicator.addEventListener('click', () => {
            footer.scrollIntoView({ behavior: 'smooth' });
            scrollIndicator.style.opacity = '0';
        });
        
        window.addEventListener('scroll', () => {
            scrollIndicator.style.opacity = '0';
        }, { once: true });
    }
}

/**
 * Observe footer visibility to ensure it's always accessible
 */
function observeFooterVisibility() {
    const footer = document.querySelector('.main-footer');
    
    if (!footer) return;
    
    // Set up intersection observer to check when footer is in viewport
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Footer is visible, no need for indicator
                const indicator = document.querySelector('.scroll-indicator');
                if (indicator) {
                    indicator.style.opacity = '0';
                }
            }
        });
    });
    
    // Start observing the footer
    observer.observe(footer);
}