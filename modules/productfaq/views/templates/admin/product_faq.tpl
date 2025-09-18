<div class="panel">
    <div class="panel-heading">
        <i class="icon-question-circle"></i>
        {l s='FAQ du produit' mod='productfaq'}
    </div>
    <div class="panel-body">
        <div id="faq-container">
            <button type="button" class="btn btn-default" id="add-faq">
                <i class="icon-plus"></i>
                {l s='Ajouter une FAQ' mod='productfaq'}
            </button>
            
            <div id="faq-list">
                {if $faqs}
                    {foreach from=$faqs item=faq key=index}
                        <div class="faq-item" data-index="{$index}">
                            <div class="form-group">
                                <label>Question :</label>
                                <textarea name="product_faq[{$index}][question]" class="form-control rte" rows="2">{$faq.question}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Réponse :</label>
                                <textarea name="product_faq[{$index}][answer]" class="form-control rte" rows="4">{$faq.answer}</textarea>
                            </div>
                                   <div class="form-group">
                                       <button type="button" class="btn btn-success btn-sm" onclick="saveFaq(this, {$faq.id})">Sauvegarder</button>
                                       <button type="button" class="btn {if $faq.active}btn-warning{else}btn-info{/if} btn-sm" onclick="toggleFaq(this, {$faq.id})">
                                           {if $faq.active}Désactiver{else}Activer{/if}
                                       </button>
                                       <button type="button" class="btn btn-danger btn-sm" onclick="removeFaq(this, {$faq.id})">Supprimer</button>
                                   </div>
                        </div>
                    {/foreach}
                {/if}
            </div>
        </div>
    </div>
</div>

<script>
var faqIndex = {count($faqs)};

function addFaq() {
    var newIndex = faqIndex++;
    var faqHtml = '<div class="faq-item" data-index="' + newIndex + '">';
    faqHtml += '<div class="form-group">';
    faqHtml += '<label>Question :</label>';
    faqHtml += '<textarea name="product_faq[' + newIndex + '][question]" class="form-control rte" rows="2"></textarea>';
    faqHtml += '</div>';
    faqHtml += '<div class="form-group">';
    faqHtml += '<label>Réponse :</label>';
    faqHtml += '<textarea name="product_faq[' + newIndex + '][answer]" class="form-control rte" rows="4"></textarea>';
    faqHtml += '</div>';
    faqHtml += '<div class="form-group">';
    faqHtml += '<button type="button" class="btn btn-success btn-sm" onclick="saveFaq(this, 0)">Sauvegarder</button>';
    faqHtml += '<button type="button" class="btn btn-danger btn-sm" onclick="removeFaq(this)">Supprimer</button>';
    faqHtml += '</div>';
    faqHtml += '</div>';
    
    document.getElementById('faq-list').innerHTML += faqHtml;
    
    // Initialiser TinyMCE natif de PrestaShop
    tinySetup();
}

function saveFaq(button, faqId) {
    var faqItem = button.closest('.faq-item');
    var questionElement = faqItem.querySelector('textarea[name*="[question]"]');
    var answerElement = faqItem.querySelector('textarea[name*="[answer]"]');
    
    // Sauvegarder le contenu des éditeurs TinyMCE
    if (typeof tinyMCE !== 'undefined') {
        tinyMCE.triggerSave();
    }
    
    var question = questionElement.value;
    var answer = answerElement.value;
    
    if (!question.trim() || !answer.trim()) {
        // Changer le bouton en orange temporairement
        button.style.backgroundColor = '#f0ad4e';
        button.innerHTML = '⚠ Champs vides';
        setTimeout(function() {
            button.style.backgroundColor = '';
            button.innerHTML = 'Sauvegarder';
        }, 2000);
        return;
    }
    
    // Indicateur de chargement
    button.style.backgroundColor = '#337ab7';
    button.innerHTML = '⏳ Sauvegarde...';
    
    // Préparer les données
    var formData = new FormData();
    formData.append('action', 'save_faq');
    formData.append('id_product', {$id_product});
    formData.append('faq_id', faqId);
    formData.append('question', question);
    formData.append('answer', answer);
    
    // Envoyer la requête AJAX
    fetch('{$module_dir}ajax_save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes('success')) {
            // Changer le bouton en vert temporairement
            button.style.backgroundColor = '#5cb85c';
            button.innerHTML = '✓ Sauvegardé';
            setTimeout(function() {
                button.style.backgroundColor = '';
                button.innerHTML = 'Sauvegarder';
            }, 2000);
            
            // Mettre à jour l'ID de la FAQ si c'est une nouvelle
            if (faqId == 0 && data.includes('id:')) {
                var newId = data.match(/id:(\d+)/)[1];
                button.setAttribute('onclick', 'saveFaq(this, ' + newId + ')');
            }
        } else {
            // Changer le bouton en rouge temporairement
            button.style.backgroundColor = '#d9534f';
            button.innerHTML = '✗ Erreur';
            setTimeout(function() {
                button.style.backgroundColor = '';
                button.innerHTML = 'Sauvegarder';
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Changer le bouton en rouge temporairement
        button.style.backgroundColor = '#d9534f';
        button.innerHTML = '✗ Erreur';
        setTimeout(function() {
            button.style.backgroundColor = '';
            button.innerHTML = 'Sauvegarder';
        }, 2000);
    });
}

       function toggleFaq(button, faqId) {
           if (faqId <= 0) {
               alert('Veuillez d\'abord sauvegarder cette FAQ');
               return;
           }
           
           // Indicateur de chargement
           var originalText = button.innerHTML;
           button.innerHTML = '⏳ ...';
           button.disabled = true;
           
           // Préparer les données
           var formData = new FormData();
           formData.append('action', 'toggle_faq');
           formData.append('id_product', {$id_product});
           formData.append('faq_id', faqId);
           
           // Envoyer la requête AJAX
           fetch('{$module_dir}ajax_save.php', {
               method: 'POST',
               body: formData
           })
           .then(response => response.text())
           .then(data => {
               if (data.includes('success')) {
                   // Toggle l'apparence du bouton
                   if (button.classList.contains('btn-warning')) {
                       button.classList.remove('btn-warning');
                       button.classList.add('btn-info');
                       button.innerHTML = 'Activer';
                   } else {
                       button.classList.remove('btn-info');
                       button.classList.add('btn-warning');
                       button.innerHTML = 'Désactiver';
                   }
               } else {
                   button.innerHTML = '✗ Erreur';
                   setTimeout(function() {
                       button.innerHTML = originalText;
                   }, 2000);
               }
               button.disabled = false;
           })
           .catch(error => {
               console.error('Erreur:', error);
               button.innerHTML = '✗ Erreur';
               setTimeout(function() {
                   button.innerHTML = originalText;
               }, 2000);
               button.disabled = false;
           });
       }

       function removeFaq(button, faqId) {
           if (!confirm('Supprimer cette FAQ ?')) {
               return;
           }
           
           var faqItem = button.closest('.faq-item');
           
           // Si c'est une FAQ existante (faqId > 0), la supprimer de la base
           if (faqId > 0) {
               // Indicateur de chargement
               button.style.backgroundColor = '#d9534f';
               button.innerHTML = '⏳ Suppression...';
               
               // Préparer les données
               var formData = new FormData();
               formData.append('action', 'delete_faq');
               formData.append('id_product', {$id_product});
               formData.append('faq_id', faqId);
               
               // Envoyer la requête AJAX
               fetch('{$module_dir}ajax_save.php', {
                   method: 'POST',
                   body: formData
               })
               .then(response => response.text())
               .then(data => {
                   if (data.includes('success')) {
                       // Supprimer l'élément du DOM
                       faqItem.remove();
                   } else {
                       // Erreur - remettre le bouton normal
                       button.style.backgroundColor = '#d9534f';
                       button.innerHTML = '✗ Erreur';
                       setTimeout(function() {
                           button.style.backgroundColor = '';
                           button.innerHTML = 'Supprimer';
                       }, 2000);
                   }
               })
               .catch(error => {
                   console.error('Erreur:', error);
                   button.style.backgroundColor = '#d9534f';
                   button.innerHTML = '✗ Erreur';
                   setTimeout(function() {
                       button.style.backgroundColor = '';
                       button.innerHTML = 'Supprimer';
                   }, 2000);
               });
           } else {
               // C'est une nouvelle FAQ non sauvegardée, juste supprimer du DOM
               faqItem.remove();
           }
       }

document.getElementById('add-faq').addEventListener('click', addFaq);

// Initialiser TinyMCE natif au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    tinySetup();
});
</script>