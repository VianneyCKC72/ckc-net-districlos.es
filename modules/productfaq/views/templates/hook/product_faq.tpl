{assign var="faq_module" value=Module::getInstanceByName('productfaq')}
{assign var="product_id" value=$smarty.get.id_product|default:$product.id|default:0}
{assign var="faq_faqs" value=$faq_module->getFaqsByProduct($product_id, true)}

<link rel="stylesheet" href="{$faq_module->getPathUri()}views/css/front.css" type="text/css" media="all">

<div class="product-faq">
    {if $faq_faqs}
        <div class="faq-accordion">
            {foreach from=$faq_faqs item=faq key=index}
                <div class="faq-item">
                    <div class="faq-question" data-faq-index="{$index}">
                        <span class="faq-question-text">{$faq.question nofilter}</span>
                        <span class="faq-icon">+</span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-content">{$faq.answer nofilter}</div>
                    </div>
                </div>
            {/foreach}
        </div>
    {else}
        <p class="text-muted">Aucune FAQ disponible pour ce produit.</p>
    {/if}
</div>

<script>
(function() {
    var faqInitialized = false;
    
    function initFAQ() {
        // Éviter les doublons d'initialisation
        if (faqInitialized) {
            return;
        }
        
        var faqContainer = document.querySelector('#product-faq');
        if (!faqContainer) {
            return;
        }
        
        var faqQuestions = faqContainer.querySelectorAll('.faq-question');
        
        if (faqQuestions.length === 0) {
            return;
        }
        
        faqQuestions.forEach(function(question) {
            // Retirer l'ancien listener si présent
            var newQuestion = question.cloneNode(true);
            question.parentNode.replaceChild(newQuestion, question);
            
            // Réattacher le listener
            newQuestion.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var answer = this.nextElementSibling;
                var icon = this.querySelector('.faq-icon');
                
                if (!answer || !icon) {
                    return;
                }
                
                // Fermer toutes les autres FAQ
                var allQuestions = faqContainer.querySelectorAll('.faq-question');
                allQuestions.forEach(function(otherQuestion) {
                    if (otherQuestion !== newQuestion) {
                        var otherAnswer = otherQuestion.nextElementSibling;
                        var otherIcon = otherQuestion.querySelector('.faq-icon');
                        if (otherAnswer && otherIcon) {
                            otherAnswer.style.display = 'none';
                            otherIcon.innerHTML = '+';
                            otherIcon.classList.remove('faq-icon-minus');
                            otherIcon.classList.add('faq-icon-plus');
                        }
                    }
                });
                
                // Toggle la FAQ actuelle
                if (answer.style.display === 'none' || answer.style.display === '') {
                    answer.style.display = 'block';
                    icon.innerHTML = '−';
                    icon.classList.remove('faq-icon-plus');
                    icon.classList.add('faq-icon-minus');
                } else {
                    answer.style.display = 'none';
                    icon.innerHTML = '+';
                    icon.classList.remove('faq-icon-minus');
                    icon.classList.add('faq-icon-plus');
                }
            });
        });
        
        faqInitialized = true;
    }
    
    // Fonction pour réinitialiser quand l'onglet est affiché
    function resetFAQ() {
        faqInitialized = false;
        setTimeout(initFAQ, 100);
    }
    
    // Initialisation au chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initFAQ, 200);
        });
    } else {
        setTimeout(initFAQ, 200);
    }
    
    // Réinitialiser quand l'onglet FAQ est affiché (Bootstrap event)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('shown.bs.tab', 'a[href="#product-faq"]', function() {
            resetFAQ();
        });
        
        // Aussi pour les liens mobiles
        jQuery(document).on('shown.bs.tab', 'a.mobile-nav-link[href="#product-faq"]', function() {
            resetFAQ();
        });
    }
    
    // Observer les changements de classe de l'onglet comme fallback
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                var target = mutation.target;
                if (target.id === 'product-faq' && 
                    (target.classList.contains('active') || target.classList.contains('show'))) {
                    resetFAQ();
                }
            }
        });
    });
    
    // Attendre que l'élément existe
    var checkElement = setInterval(function() {
        var faqTab = document.querySelector('#product-faq');
        if (faqTab) {
            clearInterval(checkElement);
            observer.observe(faqTab, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
    }, 100);
    
    // Nettoyer après 10 secondes si l'élément n'existe toujours pas
    setTimeout(function() {
        clearInterval(checkElement);
    }, 10000);
})();
</script>
