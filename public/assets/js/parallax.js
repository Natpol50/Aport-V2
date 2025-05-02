/**
 * parallax.js - Enhanced parallax scrolling and trapezoid transformations
 * 
 * This script manages advanced parallax effects and the dynamic transformation
 * of elements with trapezoid shapes. The mathematical principles involve:
 * 
 * 1. Perspective transforms for realistic depth perception
 * 2. Z-translation to create parallax movements
 * 3. Scale adjustments to compensate for perspective distortion
 * 4. Dynamic section height measurements ensuring proper spacing
 */

// Execute when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initParallaxEffects();
    initTrapezoidShapes();
    initSectionVisibility();
    
    // Initialize scroll indicator
    initScrollIndicator();
    
    // Update on resize
    window.addEventListener('resize', debounce(function() {
      measureSectionHeights();
      updateTrapezoidAngles();
    }, 200));
    
    // Log initialization
    console.log('Parallax and visual effects initialized');
  });
  
  /**
   * Initialize parallax scrolling effects
   * Uses mathematical transformations based on scroll position
   */
  function initParallaxEffects() {
    // First measure the heights of fixed sections
    measureSectionHeights();
    
    // Elements that will move during scroll
    const heroContent = document.querySelector('.hero-content');
    const footerContent = document.querySelector('.footer-content');
    
    // Listen for scroll events
    window.addEventListener('scroll', throttle(function() {
      const scrollY = window.scrollY;
      const windowHeight = window.innerHeight;
      const documentHeight = document.documentElement.scrollHeight;
      
      // Hero section parallax (move down slowly as user scrolls)
      if (heroContent) {
        // Parallax factor - smaller values create more subtle movement
        const parallaxFactor = 0.4; 
        
        // Calculate translateY value based on scroll position
        const translateY = Math.min(scrollY * parallaxFactor, 200);
        
        // Apply transform - move down as user scrolls
        heroContent.style.transform = `translateY(${translateY}px)`;
        
        // Calculate opacity - fade out as user scrolls down
        const opacity = Math.max(0, 1 - (scrollY / (windowHeight * 0.6)));
        heroContent.style.opacity = opacity.toString();
      }
      
      // Footer parallax (move up as user approaches footer)
      if (footerContent) {
        // Calculate distance from bottom of document
        const distanceFromBottom = documentHeight - windowHeight - scrollY;
        
        // Get footer height from CSS variable
        const footerHeight = parseInt(getComputedStyle(document.documentElement)
                                    .getPropertyValue('--footer-height'));
        
        // Calculate translateY based on proximity to footer
        // Only start moving when we're within 2x footer height from bottom
        if (distanceFromBottom < footerHeight * 2) {
          const progress = 1 - (distanceFromBottom / (footerHeight * 2));
          const translateY = -100 + (progress * 100); // Start at -100px and move up to 0
          
          footerContent.style.transform = `translateY(${translateY}px)`;
          footerContent.style.opacity = progress.toString();
        } else {
          footerContent.style.transform = 'translateY(-100px)';
          footerContent.style.opacity = '0';
        }
      }
      
      // Check for elements to animate as they enter viewport
      animateElementsInView();
    }, 10));
  }
  
  /**
   * Initialize trapezoid shape effects
   * Creates dynamic, aggressive styling with skewed elements
   */
  function initTrapezoidShapes() {
    // Apply trapezoid transforms to all elements with the trapezoid class
    updateTrapezoidAngles();
    
    // Add hover effect to trapezoid contact cards
    const contactCards = document.querySelectorAll('.contact-card');
    
    contactCards.forEach(card => {
      // Store original transform for resetting
      const originalTransform = getComputedStyle(card).transform;
      
      card.addEventListener('mouseenter', function() {
        // More dramatic skew on hover - mathematical amplification
        const pseudo = window.getComputedStyle(this, '::before');
        const currentTransform = pseudo.transform || pseudo.webkitTransform;
        
        // Get current skew angle and increase it by 50%
        this.style.transform = 'skewX(-20deg)';
        
        // Also adjust the card content to maintain readability
        const content = this.querySelector('.card-content');
        if (content) {
          content.style.transform = 'skewX(20deg)'; // Counter-skew
        }
        
        // Slight float effect - move up by 5px
        this.style.transform += ' translateY(-5px)';
        
        // Add shadow for depth
        this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.3)';
      });
      
      card.addEventListener('mouseleave', function() {
        // Reset all transformations
        this.style.transform = '';
        
        // Reset content skew
        const content = this.querySelector('.card-content');
        if (content) {
          content.style.transform = '';
        }
        
        // Reset shadow
        this.style.boxShadow = '';
      });
    });
    
    // Apply clip-path to content sections for trapezoid shape
    const contentSections = document.querySelectorAll('.content-section');
    contentSections.forEach((section, index) => {
      // Alternate the direction of the trapezoid
      if (index % 2 === 0) {
        section.style.clipPath = 'polygon(0 var(--clip-top), 100% 0, 100% 100%, 0 calc(100% - var(--clip-bottom)))';
      } else {
        section.style.clipPath = 'polygon(0 0, 100% var(--clip-top), 100% calc(100% - var(--clip-bottom)), 0 100%)';
      }
      
      // Determine the clip size based on section height
      const height = section.offsetHeight;
      const clipSize = Math.min(Math.max(height * 0.05, 20), 80); // 5% of height, min 20px, max 80px
      
      section.style.setProperty('--clip-top', `${clipSize}px`);
      section.style.setProperty('--clip-bottom', `${clipSize}px`);
    });
  }
  
  /**
   * Measure heights of fixed sections and apply as CSS variables
   * This ensures that spacers match exactly with fixed sections
   */
  function measureSectionHeights() {
    // Get elements
    const heroSection = document.querySelector('.hero-section');
    const footerSection = document.querySelector('.site-footer');
    const header = document.querySelector('.site-header');
    
    // Get current heights
    const headerHeight = header ? header.offsetHeight : 0;
    
    // Calculate hero height (full viewport minus header)
    const heroHeight = window.innerHeight - headerHeight;
    
    // Get footer height
    const footerHeight = footerSection ? footerSection.offsetHeight : 0;
    
    // Set CSS variables
    document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
    document.documentElement.style.setProperty('--hero-height', `${heroHeight}px`);
    document.documentElement.style.setProperty('--footer-height', `${footerHeight}px`);
    
    console.log(`Section heights measured: Hero=${heroHeight}px, Footer=${footerHeight}px`);
    
    // Update spacer heights
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
   * Update trapezoid angles based on screen size
   * Larger screens get more dramatic angles
   */
  function updateTrapezoidAngles() {
    // Determine base angle based on screen width
    // Mathematical formula: angle = 3 + (screen width / 1000)
    // This produces more dramatic angles on wider screens
    const screenWidth = window.innerWidth;
    const baseAngle = Math.min(8, 3 + screenWidth / 1000);
    
    // Set CSS variables for different angle sizes
    document.documentElement.style.setProperty('--trapezoid-angle-sm', `${baseAngle * 0.6}deg`);
    document.documentElement.style.setProperty('--trapezoid-angle-md', `${baseAngle}deg`);
    document.documentElement.style.setProperty('--trapezoid-angle-lg', `${baseAngle * 1.5}deg`);
    
    console.log(`Trapezoid angles updated: base angle=${baseAngle}deg`);
  }
  
  /**
   * Initialize scroll-based visibility for elements
   * Adds/removes 'visible' class based on element's position in viewport
   */
  function initSectionVisibility() {
    // Add fade-in class to elements that should animate into view
    const sections = document.querySelectorAll('.content-section, .project-card');
    sections.forEach(section => {
      if (!section.classList.contains('fade-in')) {
        section.classList.add('fade-in');
      }
    });
    
    // Initial check for elements in viewport
    animateElementsInView();
  }
  
  /**
   * Check which elements are in the viewport and animate them
   * Uses mathematical calculations to determine visibility
   */
  function animateElementsInView() {
    const fadeElements = document.querySelectorAll('.fade-in');
    
    fadeElements.forEach(element => {
      // Calculate element's position relative to viewport
      const rect = element.getBoundingClientRect();
      const windowHeight = window.innerHeight;
      
      // Element is considered "in view" when its top is within viewport
      // plus a small offset for better timing
      const inView = rect.top <= windowHeight * 0.85;
      
      if (inView) {
        element.classList.add('visible');
      } else {
        // Optional: remove class if scrolled past to re-trigger animation
        // element.classList.remove('visible');
      }
    });
  }
  
  /**
   * Initialize scroll indicator behavior
   */
  function initScrollIndicator() {
    const scrollIndicator = document.querySelector('.scroll-indicator');
    
    if (scrollIndicator) {
      scrollIndicator.addEventListener('click', function() {
        // Scroll to content section (skip hero)
        const scrollTarget = document.querySelector('.main-content');
        
        if (scrollTarget) {
          // Smooth scroll to content
          scrollTarget.scrollIntoView({ behavior: 'smooth' });
        }
      });
      
      // Hide scroll indicator when scrolled past hero
      window.addEventListener('scroll', function() {
        const scrollY = window.scrollY;
        const heroHeight = parseInt(getComputedStyle(document.documentElement)
                                  .getPropertyValue('--hero-height'));
        
        // Fade out scroll indicator as user scrolls down
        const opacity = Math.max(0, 1 - (scrollY / (heroHeight / 2)));
        scrollIndicator.style.opacity = opacity.toString();
        
        // Disable pointer events when fully transparent
        if (opacity < 0.1) {
          scrollIndicator.style.pointerEvents = 'none';
        } else {
          scrollIndicator.style.pointerEvents = 'auto';
        }
      });
    }
  }
  
  /**
   * Throttle function to limit how often a function runs
   * @param {Function} func - Function to throttle
   * @param {number} limit - Time limit in milliseconds
   * @return {Function} Throttled function
   */
  function throttle(func, limit) {
    let lastFunc;
    let lastRan;
    
    return function() {
      const context = this;
      const args = arguments;
      
      if (!lastRan) {
        func.apply(context, args);
        lastRan = Date.now();
      } else {
        clearTimeout(lastFunc);
        lastFunc = setTimeout(function() {
          if ((Date.now() - lastRan) >= limit) {
            func.apply(context, args);
            lastRan = Date.now();
          }
        }, limit - (Date.now() - lastRan));
      }
    };
  }
  
  /**
   * Debounce function to delay execution until after events stop
   * @param {Function} func - Function to debounce
   * @param {number} wait - Wait time in milliseconds
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