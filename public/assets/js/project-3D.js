
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the enhanced 3D effect
    initDynamic3DCardEffect();
    
    // Make sure cards are visible immediately (fixing the original issue)
    forceCardVisibility();
  });
  
  /**
   * Initialize dynamic 3D effect for project cards
   * This creates a responsive 3D tilt based on mouse position
   */
  function initDynamic3DCardEffect() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');
    
    // Apply the effect to each card
    projectCards.forEach(card => {
      // Add class for specific styling
      card.classList.add('dynamic-3d-hover');
      
      // Variables for 3D effect
      const height = card.clientHeight;
      const width = card.clientWidth;
      
      // Set up mouse enter event
      card.addEventListener('mouseenter', () => {
        // Add active state class
        card.classList.add('hover-active');
      });
      
      // Set up mouse move event for dynamic tilt
      card.addEventListener('mousemove', (e) => {
        if (!card.classList.contains('hover-active')) return;
        
        // Refresh dimensions in case of resize or late loading
        const currentHeight = card.clientHeight;
        const currentWidth = card.clientWidth;
        
        // Calculate mouse position relative to card center
        const rect = card.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        const xRotation = ((mouseY / currentHeight) - 0.5) * -1;
        const yRotation = (mouseX / currentWidth) - 0.5;
        
        const xAngle = xRotation * 5;
        const yAngle = yRotation * 5;
        
        card.style.setProperty('transform', `perspective(1000px) rotateX(${xAngle}deg) rotateY(${yAngle}deg) scale3d(1.02, 1.02, 1.02)`, 'important');
        
        const shadowX = yRotation * 15;
        const shadowY = xRotation * 15 * -1;
        card.style.setProperty('box-shadow', `${shadowX}px ${shadowY}px 25px rgba(0, 0, 0, 0.25)`, 'important');
      });
      
      // Reset card on mouse leave
      card.addEventListener('mouseleave', () => {
        // Remove active state
        card.classList.remove('hover-active');
        
        // Animate back to default state with easing
        card.style.setProperty('transition', 'transform 0.5s ease, box-shadow 0.5s ease', 'important');
        card.style.setProperty('transform', 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)', 'important');
        card.style.setProperty('box-shadow', '', 'important');
        
        // Clear transition after animation completes
        setTimeout(() => {
          card.style.setProperty('transition', '', 'important');
        }, 500);
      });
    });
    
    console.log(`Applied dynamic 3D effect to ${projectCards.length} project cards`);
  }
  
  /**
   * Force all project cards to be immediately visible
   * This fixes the original visibility issue
   */
  function forceCardVisibility() {
    // Get all project cards
    const projectCards = document.querySelectorAll('.project-card');
    
    // Apply visibility to each card
    projectCards.forEach(card => {
      // Ensure card is visible with no animations
      card.classList.add('visible');
      card.style.opacity = '1';
      card.style.visibility = 'visible';
      
      // Remove any classes that might interfere with visibility
      card.classList.remove('fade-in-hidden');
    });
  }