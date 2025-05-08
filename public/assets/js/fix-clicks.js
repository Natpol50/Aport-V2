document.addEventListener('DOMContentLoaded', function() {
    const heroStatic = document.querySelector('.hero-static');
    const footerStatic = document.querySelector('.footer-static');
    
    const Z_INDEX_BASE = getComputedStyle(document.documentElement).getPropertyValue('--z-base') || '1';
    const Z_INDEX_ABOVE = getComputedStyle(document.documentElement).getPropertyValue('--z-above') || '10';
    
    updateElementVisibility();
    
    window.addEventListener('scroll', throttle(updateElementVisibility, 100));
    window.addEventListener('resize', throttle(updateElementVisibility, 250));
    
    function updateElementVisibility() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = Math.max(
            document.body.scrollHeight, 
            document.body.offsetHeight, 
            document.documentElement.clientHeight, 
            document.documentElement.scrollHeight, 
            document.documentElement.offsetHeight
        );
        
        const distanceFromBottom = documentHeight - (scrollTop + windowHeight);
        const atTopThreshold = 10;
        const atBottomThreshold = 10;
        
        const isAtTop = scrollTop <= atTopThreshold;
        const isAtBottom = distanceFromBottom <= atBottomThreshold;
        
        if (heroStatic) {
            heroStatic.style.zIndex = isAtTop ? Z_INDEX_ABOVE : Z_INDEX_BASE;
        }
        
        if (footerStatic) {
            footerStatic.style.zIndex = isAtBottom ? Z_INDEX_ABOVE : Z_INDEX_BASE;
        }
    }
    
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
});

/**
 * Fix contact button clickability
 */
function fixContactButton() {
    const contactButtons = document.querySelectorAll('.contact-button, [data-action="contact"]');
    
    contactButtons.forEach(button => {
      if (button) {
        // Ensure proper z-index and pointer-events
        button.style.position = 'relative';
        button.style.zIndex = '100';
        button.style.pointerEvents = 'auto';
        
        // Add debug click handler to check if events are being received
        button.addEventListener('click', function(e) {
          console.log('Contact button clicked');
          e.stopPropagation(); // Prevent event bubbling
        });
      }
    });
  }
  
  document.addEventListener('DOMContentLoaded', function() {
    // ... other initializations
    fixContactButton();
  });