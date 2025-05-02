/**
 * main.js - Handles fixed section heights and scroll behavior
 * 
 * This script calculates the heights of the hero and footer sections
 * and applies them as CSS custom properties. This ensures the spacers
 * in the scrollable content accurately match the fixed sections.
 * 
 * Mathematical approach:
 * - We measure the rendered heights of the hero and footer sections
 * - These measurements are then applied as CSS variables
 * - The spacers use these variables to create the exact same space
 * - This creates the illusion that content is scrolling over static backgrounds
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the layout
    initLayout();
    
    // Update on resize
    window.addEventListener('resize', debounce(initLayout, 200));
    
    // Initialize language switcher
    initLanguageSwitcher();
    
    // Initialize trapezoid hover effects
    initTrapezoidEffects();
  });
  
  /**
   * Initialize the layout by measuring and setting heights
   */
  function initLayout() {
    // Measure the hero and footer sections
    measureFixedSections();
    
    // Apply trapezoid clip paths with the correct angles
    applyTrapezoidStyles();
    
    // Initialize scroll effects
    initScrollEffects();
    
    console.log('Layout initialized');
  }
  
  /**
   * Measure fixed sections and set CSS variables
   */
  function measureFixedSections() {
    // Get the hero and footer elements
    const heroSection = document.querySelector('.hero-section');
    const footerSection = document.querySelector('.site-footer');
    const headerHeight = document.querySelector('.site-header').offsetHeight;
    
    // Get their computed heights
    let heroHeight = heroSection ? heroSection.offsetHeight : 0;
    let footerHeight = footerSection ? footerSection.offsetHeight : 0;
    
    // Account for header in hero height calculations
    heroHeight = window.innerHeight - headerHeight;
    
    // Set CSS variables for these heights
    document.documentElement.style.setProperty('--hero-height', `${heroHeight}px`);
    document.documentElement.style.setProperty('--footer-height', `${footerHeight}px`);
    document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
    
    // Also set the trapezoid offset based on window width (responsive)
    const trapezoidOffset = window.innerWidth < 768 ? '2rem' : '4rem';
    document.documentElement.style.setProperty('--trapezoid-offset', trapezoidOffset);
    
    console.log(`Fixed sections measured: Hero=${heroHeight}px, Footer=${footerHeight}px, Header=${headerHeight}px`);
    
    // Update spacers to match these heights exactly
    updateSpacers(heroHeight, footerHeight);
  }
  
  /**
   * Update spacers to match fixed section heights
   * @param {number} heroHeight - Height of hero section
   * @param {number} footerHeight - Height of footer section
   */
  function updateSpacers(heroHeight, footerHeight) {
    const topSpacer = document.querySelector('.spacer-top');
    const bottomSpacer = document.querySelector('.spacer-bottom');
    
    if (topSpacer) {
      topSpacer.style.height = `${heroHeight}px`;
    }
    
    if (bottomSpacer) {
      bottomSpacer.style.height = `${footerHeight}px`;
    }
  }
  
  /**
   * Apply trapezoid styles to the main content and cards
   */
  function applyTrapezoidStyles() {
    // Get all elements with trapezoid class
    const trapezoids = document.querySelectorAll('.trapezoid');
    
    // Set random rotation angles for varied effects
    trapezoids.forEach((element, index) => {
      // Alternate directions for visual variety (positive and negative angles)
      const direction = index % 2 === 0 ? 1 : -1;
      // Random angle between 3 and 7 degrees
      const angle = (3 + Math.random() * 4) * direction;
      
      element.style.setProperty('--trapezoid-angle', `${angle}deg`);
    });
    
    // Also apply clip path to main content for trapezoid shape
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
      const offset = parseInt(getComputedStyle(document.documentElement)
                             .getPropertyValue('--trapezoid-offset'));
      
      // Create trapezoid shape with clip-path
      // This creates angled cuts at top and bottom
      mainContent.style.clipPath = `polygon(
        0 ${offset}px, 
        100% 0, 
        100% calc(100% - ${offset}px), 
        0 100%
      )`;
    }
  }
  
  /**
   * Initialize scroll effects
   */
  function initScrollEffects() {
    // Intensity factor for parallax effect
    const PARALLAX_FACTOR = 0.4;
    
    window.addEventListener('scroll', function() {
      const scrollY = window.scrollY;
      
      // Apply subtle parallax effect to hero content
      const heroContent = document.querySelector('.hero-content');
      if (heroContent) {
        // Move hero content slightly as user scrolls
        heroContent.style.transform = `translateY(${scrollY * PARALLAX_FACTOR}px)`;
        
        // Fade out as user scrolls down
        const opacity = Math.max(0, 1 - (scrollY / 500));
        heroContent.style.opacity = opacity;
      }
    });
  }
  
  /**
   * Initialize language switcher behavior
   */
  function initLanguageSwitcher() {
    const langLinks = document.querySelectorAll('.language-switcher .lang-link');
    
    langLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        // If already on this language, don't do anything
        if (!this.classList.contains('inactive')) {
          e.preventDefault();
          return;
        }
        
        // Save scroll position to session storage before navigation
        sessionStorage.setItem('scrollPosition', window.scrollY);
      });
    });
    
    // Restore scroll position after language switch if available
    const savedScrollPosition = sessionStorage.getItem('scrollPosition');
    if (savedScrollPosition !== null) {
      window.scrollTo(0, parseInt(savedScrollPosition));
      sessionStorage.removeItem('scrollPosition');
    }
  }
  
  /**
   * Initialize trapezoid hover effects for cards
   */
  function initTrapezoidEffects() {
    const contactCards = document.querySelectorAll('.contact-card');
    
    contactCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        // Increase the rotation angle on hover
        const currentAngle = parseFloat(getComputedStyle(this)
                                       .getPropertyValue('--trapezoid-angle'));
        this.style.setProperty('--trapezoid-angle', `${currentAngle * 1.5}deg`);
      });
      
      card.addEventListener('mouseleave', function() {
        // Reset to original angle
        const originalAngle = parseFloat(getComputedStyle(document.documentElement)
                                        .getPropertyValue('--trapezoid-angle'));
        this.style.setProperty('--trapezoid-angle', `${originalAngle}deg`);
      });
    });
  }
  
  /**
   * Debounce function to limit how often a function is called
   * @param {Function} func - The function to debounce
   * @param {number} wait - The debounce delay in milliseconds
   * @return {Function} Debounced function
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