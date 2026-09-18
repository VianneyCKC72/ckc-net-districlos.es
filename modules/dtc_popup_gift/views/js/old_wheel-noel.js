(function() {
    'use strict';
    
    const modal = document.getElementById('modal-noel');
    if (!modal) return;
    
    setTimeout(function() {
        modal.style.display = 'flex';
    }, 2000);
    
    document.addEventListener('click', function(e) {
        if (e.target.id === 'closeModalNoel' || e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modal.style.display = 'none';
        }
    });
})();
