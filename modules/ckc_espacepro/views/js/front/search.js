       function searchFilter() {
            const searchInput = document.querySelector('input[name="search_query_items"]');
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            const items = document.querySelectorAll('.items_');
            const sections = document.querySelectorAll('.section-container');
            const noResultsMessage = document.getElementById('no-results');
            
            let hasVisibleItems = false;
            
            // Si la recherche est vide, affiche tous les éléments
            if (searchTerm === '') {
                items.forEach(item => {
                    item.style.display = 'block';
                });
                sections.forEach(section => {
                    section.style.display = 'block';
                });
                if (noResultsMessage) {
                    noResultsMessage.style.display = 'none';
                }
                return;
            }
            
            // Normalise les chaînes
            function normalize(str) {
                  return str.toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '') // Supprime les accents
                    .replace(/\s+/g, ' ')            // Réduit les espaces multiples
                    .trim();
            }
            
            // Vérifie si les mots de recherche sont présents
            function matchesSearch(productName, searchTerm) {
                const normalizedProduct = normalize(productName);
                const normalizedSearch = normalize(searchTerm);
                
                const searchWords = normalizedSearch.split(' ').filter(word => word.length > 0);
                
                return searchWords.every(word => normalizedProduct.includes(word));
            }
            
            // Filtre les éléments
            items.forEach(item => {
                const productName = item.querySelector('.title').textContent;
                
                if (matchesSearch(productName, searchTerm)) {
                    item.style.display = 'block';
                    hasVisibleItems = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Masquer les sections vides
            sections.forEach(section => {
                const visibleItems = section.querySelectorAll('.items_:not([style*="display: none"])');
                
                if (visibleItems.length === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            });
            
            // Afficher/masquer le message "aucun résultat"
            if (noResultsMessage) {
                noResultsMessage.style.display = hasVisibleItems ? 'none' : 'block';
            }
        }

        function resetSearch() {
            const searchInput = document.querySelector('input[name="search_query_items"]');
            searchInput.value = '';
            searchFilter();
        }

        function initRealTimeSearch() {
            const searchInput = document.querySelector('input[name="search_query_items"]');
            const searchInfo = document.getElementById('search-info');
            let searchTimeout;
            
            if (!searchInput) return; 
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                // Annuler le précédent timeout
                clearTimeout(searchTimeout);
                
                
                searchTimeout = setTimeout(function() {
                    searchFilter();
                }, 300);
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const searchTerm = this.value.trim();
                    if (searchTerm.length >= 3) {
                        clearTimeout(searchTimeout);
                        searchFilter();
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initRealTimeSearch();
        });