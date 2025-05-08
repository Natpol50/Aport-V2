
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
        
        // Calculate mouse position relative to card center
        // This creates the coordinate system for our 3D effect
        const rect = card.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;
        
        // Calculate position as a percentage of card dimensions
        // This normalizes our values to a range of -0.5 to 0.5
        // where (0,0) is the center of the card
        const xRotation = ((mouseY / height) - 0.5) * -1;
        const yRotation = (mouseX / width) - 0.5;
        
        // Calculate rotation angles with proper strength
        // The 20 factor controls the maximum tilt angle (degrees)
        const xAngle = xRotation * 20;
        const yAngle = yRotation * 20;
        
        // Apply the 3D transform
        // We use matrix mathematics internally via these CSS transforms
        card.style.transform = `
          perspective(1000px) 
          rotateX(${xAngle}deg) 
          rotateY(${yAngle}deg) 
          translateZ(10px)
        `;
        
        // Calculate shadow position to match tilt
        // This enhances the 3D effect by moving the shadow opposite to the tilt
        const shadowX = yRotation * 20;
        const shadowY = xRotation * 20 * -1;
        const shadowBlur = 30 - Math.abs(xAngle) - Math.abs(yAngle);
        
        // Apply dynamic shadow based on tilt angle
        card.style.boxShadow = `
          ${shadowX}px ${shadowY}px ${shadowBlur}px rgba(0, 0, 0, 0.2)
        `;
      });
      
      // Reset card on mouse leave
      card.addEventListener('mouseleave', () => {
        // Remove active state
        card.classList.remove('hover-active');
        
        // Animate back to default state with easing
        card.style.transition = 'transform 0.5s ease, box-shadow 0.5s ease';
        card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
        card.style.boxShadow = '';
        
        // Clear transition after animation completes
        setTimeout(() => {
          card.style.transition = '';
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