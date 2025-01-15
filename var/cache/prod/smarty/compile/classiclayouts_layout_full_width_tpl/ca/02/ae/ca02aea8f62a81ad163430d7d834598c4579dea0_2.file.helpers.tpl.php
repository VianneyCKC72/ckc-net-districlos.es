<?php
/* Smarty version 4.3.4, created on 2025-01-15 01:36:13
  from '/var/www/districlos.ckc-net.com/themes/classic/templates/_partials/helpers.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.3.4',
  'unifunc' => 'content_678702fdd61d73_12516778',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'ca02aea8f62a81ad163430d7d834598c4579dea0' => 
    array (
      0 => '/var/www/districlos.ckc-net.com/themes/classic/templates/_partials/helpers.tpl',
      1 => 1736900910,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_678702fdd61d73_12516778 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->smarty->ext->_tplFunction->registerTplFunctions($_smarty_tpl, array (
  'renderLogo' => 
  array (
    'compiled_filepath' => '/var/www/districlos.ckc-net.com/var/cache/prod/smarty/compile/classiclayouts_layout_full_width_tpl/ca/02/ae/ca02aea8f62a81ad163430d7d834598c4579dea0_2.file.helpers.tpl.php',
    'uid' => 'ca02aea8f62a81ad163430d7d834598c4579dea0',
    'call_name' => 'smarty_template_function_renderLogo_1070491408678702fdd5e890_61986767',
  ),
));
?> 

<?php }
/* smarty_template_function_renderLogo_1070491408678702fdd5e890_61986767 */
if (!function_exists('smarty_template_function_renderLogo_1070491408678702fdd5e890_61986767')) {
function smarty_template_function_renderLogo_1070491408678702fdd5e890_61986767(Smarty_Internal_Template $_smarty_tpl,$params) {
foreach ($params as $key => $value) {
$_smarty_tpl->tpl_vars[$key] = new Smarty_Variable($value, $_smarty_tpl->isRenderingCache);
}
?>

  <a href="<?php echo htmlspecialchars((string) $_smarty_tpl->tpl_vars['urls']->value['pages']['index'], ENT_QUOTES, 'UTF-8');?>
">
    <img
      class="logo img-fluid"
      src="<?php echo htmlspecialchars((string) $_smarty_tpl->tpl_vars['shop']->value['logo_details']['src'], ENT_QUOTES, 'UTF-8');?>
"
      alt="<?php echo htmlspecialchars((string) $_smarty_tpl->tpl_vars['shop']->value['name'], ENT_QUOTES, 'UTF-8');?>
"
      width="<?php echo htmlspecialchars((string) $_smarty_tpl->tpl_vars['shop']->value['logo_details']['width'], ENT_QUOTES, 'UTF-8');?>
"
      height="<?php echo htmlspecialchars((string) $_smarty_tpl->tpl_vars['shop']->value['logo_details']['height'], ENT_QUOTES, 'UTF-8');?>
">
  </a>
<?php
}}
/*/ smarty_template_function_renderLogo_1070491408678702fdd5e890_61986767 */
}
