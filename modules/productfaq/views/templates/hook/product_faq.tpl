{assign var="faq_module" value=Module::getInstanceByName('productfaq')}
{assign var="product_id" value=$smarty.get.id_product|default:$product.id|default:0}
{assign var="faq_faqs" value=$faq_module->getFaqsByProduct($product_id, true)}

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
    function initFAQ() {
        var faqQuestions = document.querySelectorAll('.faq-question');
        
        faqQuestions.forEach(function(question) {
            question.addEventListener('click', function() {
                var answer = this.nextElementSibling;
                var icon = this.querySelector('.faq-icon');
                
                // Fermer toutes les autres FAQ
                faqQuestions.forEach(function(otherQuestion) {
                    if (otherQuestion !== question) {
                        var otherAnswer = otherQuestion.nextElementSibling;
                        var otherIcon = otherQuestion.querySelector('.faq-icon');
                        otherAnswer.style.display = 'none';
                        otherIcon.innerHTML = '+';
                        otherIcon.classList.remove('faq-icon-minus');
                        otherIcon.classList.add('faq-icon-plus');
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
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFAQ);
    } else {
        initFAQ();
    }
})();
</script>
