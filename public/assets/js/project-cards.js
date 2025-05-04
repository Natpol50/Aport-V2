/**
 * Project card interactions
 * 
 * This script handles the interactive elements of project cards including:
 * - Entrance animations as cards scroll into view
 * - Hover effects with 3D-like transforms
 * - Skill tag highlighting
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize project card animations and interactions
    initProjectCards();
    
    // Set up filtering functionality
    initProjectFilters();
  });
  
  /**
   * Initialize project card entrance animations and interactions
   */
  function initProjectCards() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');
    
    // Set up entrance animations
    projectCards.forEach((card, index) => {
      // Add fade-in class if not already present
      if (!card.classList.contains('fade-in')) {
        card.classList.add('fade-in');
      }
      
      // Add staggered delay for cards to create cascade effect
      card.style.transitionDelay = `${index * 0.1}s`;
      
      // Enhanced hover effect
      card.addEventListener('mouseenter', function() {
        // Subtle rotation for 3D effect (mathematical transformation)
        // The rotation angle alternates based on index for visual variety
        const rotateY = index % 2 === 0 ? 1 : -1;
        this.style.transform = `translateY(-5px) rotateY(${rotateY}deg)`;
        
        // Enhanced shadow for depth perception
        this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.15)';
      });
      
      card.addEventListener('mouseleave', function() {
        // Reset transformations
        this.style.transform = '';
        this.style.boxShadow = '';
      });
    });
    
    // Check initial visibility
    checkCardsInViewport();
    
    // Add scroll listener to check when cards come into view
    window.addEventListener('scroll', throttle(checkCardsInViewport, 100));
  }
  
  /**
   * Check which cards are in the viewport and animate them
   */
  function checkCardsInViewport() {
    const projectCards = document.querySelectorAll('.project-card.fade-in');
    
    projectCards.forEach(card => {
      if (isElementInViewport(card)) {
        card.classList.add('visible');
      }
    });
  }
  
  /**
   * Initialize project filtering functionality
   */
  function initProjectFilters() {
    const filterButtons = document.querySelectorAll('.project-filter-btn');
    const projectCards = document.querySelectorAll('.project-card');
    
    // Set up filter buttons
    filterButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Get filter value
        const filter = this.dataset.filter;
        
        // Update active button state
        filterButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        // Filter projects
        projectCards.forEach(card => {
          if (filter === 'all') {
            // Show all cards
            card.style.display = '';
            // Re-trigger animation
            setTimeout(() => {
              card.classList.add('visible');
            }, 50);
          } else {
            // Check if card has the selected type
            if (card.dataset.type === filter) {
              card.style.display = '';
              // Re-trigger animation
              setTimeout(() => {
                card.classList.add('visible');
              }, 50);
            } else {
              // Hide cards that don't match
              card.classList.remove('visible');
              setTimeout(() => {
                card.style.display = 'none';
              }, 300); // Match transition duration
            }
          }
        });
      });
    });
  }
  
  /**
   * Highlight matching skills when hovering over skill tags
   */
  function initSkillHighlighting() {
    const skillTags = document.querySelectorAll('.skill-tag');
    
    skillTags.forEach(tag => {
      tag.addEventListener('mouseenter', function() {
        const skill = this.textContent.trim().toLowerCase();
        
        // Find all skill tags with the same skill
        const matchingTags = document.querySelectorAll(`.skill-tag[data-skill="${skill}"]`);
        
        // Highlight matching tags
        matchingTags.forEach(matchingTag => {
          matchingTag.classList.add('highlight');
        });
      });
      
      tag.addEventListener('mouseleave', function() {
        // Remove highlight from all tags
        document.querySelectorAll('.skill-tag.highlight').forEach(highlightedTag => {
          highlightedTag.classList.remove('highlight');
        });
      });
    });
  }
  
  /**
   * Check if an element is in the viewport
   * 
   * @param {HTMLElement} element - The element to check
   * @return {boolean} - True if the element is in the viewport
   */
  function isElementInViewport(element) {
    const rect = element.getBoundingClientRect();
    
    return (
      rect.top <= (window.innerHeight || document.documentElement.clientHeight) * 0.85 &&
      rect.bottom >= 0 &&
      rect.left <= (window.innerWidth || document.documentElement.clientWidth) &&
      rect.right >= 0
    );
  }
  
  /**
   * Throttle function to limit how often a function is called
   * 
   * @param {Function} func - The function to throttle
   * @param {number} limit - Throttle time in milliseconds
   * @return {Function} - Throttled function
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