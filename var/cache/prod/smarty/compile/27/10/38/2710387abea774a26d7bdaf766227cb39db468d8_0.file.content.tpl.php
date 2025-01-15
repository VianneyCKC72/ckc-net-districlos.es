<?php
/* Smarty version 4.3.4, created on 2025-01-15 01:34:26
  from '/var/www/districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/themes/default/template/content.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.3.4',
  'unifunc' => 'content_67870292636769_75639181',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2710387abea774a26d7bdaf766227cb39db468d8' => 
    array (
      0 => '/var/www/districlos.ckc-net.com/admin024h9xfokhvsbvfmnzn/themes/default/template/content.tpl',
      1 => 1736900914,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_67870292636769_75639181 (Smarty_Internal_Template $_smarty_tpl) {
?><div id="ajax_confirmation" class="alert alert-success hide"></div>
<div id="ajaxBox" style="display:none"></div>
<div id="content-message-box"></div>

<?php if ((isset($_smarty_tpl->tpl_vars['content']->value))) {?>
	<?php echo $_smarty_tpl->tpl_vars['content']->value;?>

<?php }
}
}
