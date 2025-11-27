(function ($) {
    $(function () {
        var $sel = $('#id_product');
        if (!$sel.length) return;

        // matcher accent-insensible + recherche par #id
        function strip(s){ try{ return (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase(); }catch(e){return (s||'').toString().toLowerCase();} }
        function matcher(params, data){
            if ($.trim(params.term) === '') return data;
            var term = params.term.toString();
            // "#123" => match sur id
            if (term.charAt(0) === '#') {
                var wanted = term.replace('#','').trim();
                return (String(data.id||'').indexOf(wanted) !== -1) ? data : null;
            }
            // sous-chaîne accent-insensible
            if (strip(data.text || '').indexOf(strip(term)) > -1) return data;
            return null;
        }

        $sel.select2({
            width: '100%',
            dropdownAutoWidth: true,
            allowClear: true,
            placeholder: '— Aucun produit —',
            language: {
                noResults: function(){ return 'Aucun résultat'; },
                searching: function(){ return 'Recherche…'; },
                inputTooShort: function(){ return 'Tapez pour rechercher'; }
            },
            matcher: matcher,
            // évite des soucis de z-index dans les panels :
            dropdownParent: $sel.closest('.form-group')
        });

        // Champ de recherche avec placeholder clair
        $sel.on('select2:open', function(){
            $('.select2-search__field').attr('placeholder', 'Rechercher un produit… (#123 possible)');
        });
    });
})(jQuery);
