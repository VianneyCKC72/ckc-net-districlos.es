(function() {
    'use strict';
    
    const modal = document.getElementById('modal-noel');
    if (!modal) return;
    
    const STORAGE_KEY = 'dtc_popup_gift_noel_closed';
    const STORAGE_EXPIRY_KEY = 'dtc_popup_gift_noel_closed_date';
    const EXPIRY_DAYS = 30;
    
    function isPopupClosed() {
        try {
            const closed = localStorage.getItem(STORAGE_KEY);
            const closedDate = localStorage.getItem(STORAGE_EXPIRY_KEY);
            
            if (!closed || closed !== '1' || !closedDate) {
                return false;
            }
            
            const expiryDate = new Date(parseInt(closedDate, 10));
            const now = new Date();
            expiryDate.setDate(expiryDate.getDate() + EXPIRY_DAYS);
            
            if (now > expiryDate) {
                localStorage.removeItem(STORAGE_KEY);
                localStorage.removeItem(STORAGE_EXPIRY_KEY);
                return false;
            }
            
            return true;
        } catch (e) {
            console.warn('Erreur lors de la vérification du localStorage:', e);
            return false;
        }
    }
    
    function savePopupClosed() {
        try {
            localStorage.setItem(STORAGE_KEY, '1');
            localStorage.setItem(STORAGE_EXPIRY_KEY, Date.now().toString());
        } catch (e) {
            console.warn('Erreur lors de la sauvegarde dans localStorage:', e);
        }
    }
    
    function showPopup() {
        setTimeout(function() {
            modal.style.display = 'flex';
        }, 1000);
    }
    
    if (!isPopupClosed()) {
        showPopup();
    }
    
    function closePopup() {
        modal.style.display = 'none';
        savePopupClosed();
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.id === 'closeModalNoel' || e.target === modal) {
            closePopup();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closePopup();
        }
    });
})();
