/**
 * visual-fixes.js
 * 
 * This script enhances the visual elements of the site,
 * fixing issues with hero positioning, footer display,
 * and responsive behavior.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fix hero section positioning
    fixHeroPosition();
    
    // Initialize scroll indicator
    initScrollIndicator();
    
    // Fix footer display
    fixFooterDisplay();
    
    // Handle window resize
    window.addEventListener('resize', debounce(function() {
      fixHeroPosition();
      fixFooterDisplay();
    }, 200));
  });
  
  /**
   * Fix hero section positioning
   * The mathematical principle is to dynamically calculate the optimal 
   * vertical position based on viewport height and header size
   */
  function fixHeroPosition() {
    const heroSection = document.querySelector('.hero-section');
    const header = document.querySelector('.site-header');
    
    if (!heroSection || !header) return;
    
    // Calculate available viewport height
    const viewportHeight = window.innerHeight;
    const headerHeight = header.offsetHeight;
    
    // Calculate optimal hero height (minimum 70% of available space)
    const optimalHeight = Math.max(viewportHeight * 0.7, viewportHeight - headerHeight - 100);
    
    // Apply height
    heroSection.style.minHeight = `${optimalHeight}px`;
    
    // Center hero content vertically
    const heroContent = heroSection.querySelector('.hero-content');
    if (heroContent) {
      // Reset paddingTop first
      heroContent.style.paddingTop = '0';
      
      // Calculate vertical center position
      const heroHeight = heroSection.offsetHeight;
      const contentHeight = heroContent.offsetHeight;
      const paddingTop = Math.max(0, (heroHeight - contentHeight) / 2 - headerHeight / 2);
      
      // Apply centering via padding
      heroContent.style.paddingTop = `${paddingTop}px`;
    }
  }
  
  /**
   * Initialize scroll indicator functionality
   */
  function initScrollIndicator() {
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (!scrollIndicator) return;
    
    // Add click event to scroll down
    scrollIndicator.addEventListener('click', function() {
      // Find the main content section
      const mainContent = document.querySelector('.main-content');
      if (mainContent) {
        // Smooth scroll to main content
        mainContent.scrollIntoView({ behavior: 'smooth' });
      }
    });
    
    // Hide scroll indicator when scrolled down
    window.addEventListener('scroll', function() {
      const scrollY = window.scrollY;
      const heroHeight = document.querySelector('.hero-section')?.offsetHeight || 0;
      
      // Fade out scroll indicator as user scrolls down
      if (scrollY > heroHeight / 3) {
        scrollIndicator.style.opacity = '0';
        // Disable pointer events when hidden
        scrollIndicator.style.pointerEvents = 'none';
      } else {
        // Calculate opacity based on scroll position
        const opacity = 1 - (scrollY / (heroHeight / 3));
        scrollIndicator.style.opacity = opacity.toString();
        scrollIndicator.style.pointerEvents = 'auto';
      }
    });
  }
  
  /**
   * Fix footer display issues
   * Ensures footer has correct height and positioning
   */
  function fixFooterDisplay() {
    const footer = document.querySelector('.site-footer');
    if (!footer) return;
    
    // On mobile (screen width <= 768px), adjust footer height
    if (window.innerWidth <= 768) {
      // Check if About section is visible or hidden
      const aboutSection = footer.querySelector('.footer-about');
      if (aboutSection && getComputedStyle(aboutSection).display === 'none') {
        // About section is hidden, adjust footer height
        const contactSection = footer.querySelector('.footer-contact');
        const copyright = footer.querySelector('.footer-copyright');
        
        if (contactSection && copyright) {
          // Calculate minimum required height
          const minHeight = contactSection.offsetHeight + copyright.offsetHeight + 80; // add some padding
          footer.style.minHeight = `${minHeight}px`;
        }
      }
    } else {
      // On desktop, reset to default height from CSS variables
      footer.style.minHeight = 'var(--footer-height, 350px)';
    }
  }
  
  /**
   * Debounce function to limit how often a function is called
   * 
   * This function implements a mathematical concept from control theory
   * that prevents rapid execution of the same function by delaying execution
   * until after events stop firing.
   * 
   * @param {Function} func - Function to debounce
   * @param {number} wait - Wait time in milliseconds
   * @return {Function} - Debounced function
   */
  function debounce(func, wait) {
    let timeout;
    
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }