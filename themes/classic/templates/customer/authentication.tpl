{* themes/classic/templates/customer/authentication.tpl *}
{extends file='page.tpl'}

{block name='page_content'}
  <section class="login-form">
    {render file='customer/_partials/login-form.tpl' ui=$login_form show_title=false}
  </section>

  {block name='display_after_login_form'}
    {hook h='displayCustomerLoginFormAfter'}
  {/block}
{/block}
