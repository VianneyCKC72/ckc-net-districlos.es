<?php
/* Smarty version 4.3.4, created on 2025-01-15 01:36:13
  from '/var/www/districlos.ckc-net.com/themes/classic/templates/index.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.3.4',
  'unifunc' => 'content_678702fdd4d5d0_94007985',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '74c6521bfe04e3b083f8aa96fb3348607f564c80' => 
    array (
      0 => '/var/www/districlos.ckc-net.com/themes/classic/templates/index.tpl',
      1 => 1736900910,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_678702fdd4d5d0_94007985 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
?>


    <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_296955658678702fdd4c497_18266034', 'page_content_container');
?>

<?php $_smarty_tpl->inheritance->endChild($_smarty_tpl, 'page.tpl');
}
/* {block 'page_content_top'} */
class Block_928545107678702fdd4c732_84603878 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block 'page_content_top'} */
/* {block 'hook_home'} */
class Block_743132044678702fdd4cd55_35750316 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

            <?php echo $_smarty_tpl->tpl_vars['HOOK_HOME']->value;?>

          <?php
}
}
/* {/block 'hook_home'} */
/* {block 'page_content'} */
class Block_539399356678702fdd4cb58_20070396 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

          <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_743132044678702fdd4cd55_35750316', 'hook_home', $this->tplIndex);
?>

        <?php
}
}
/* {/block 'page_content'} */
/* {block 'page_content_container'} */
class Block_296955658678702fdd4c497_18266034 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'page_content_container' => 
  array (
    0 => 'Block_296955658678702fdd4c497_18266034',
  ),
  'page_content_top' => 
  array (
    0 => 'Block_928545107678702fdd4c732_84603878',
  ),
  'page_content' => 
  array (
    0 => 'Block_539399356678702fdd4cb58_20070396',
  ),
  'hook_home' => 
  array (
    0 => 'Block_743132044678702fdd4cd55_35750316',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

      <section id="content" class="page-home">
        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_928545107678702fdd4c732_84603878', 'page_content_top', $this->tplIndex);
?>


        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_539399356678702fdd4cb58_20070396', 'page_content', $this->tplIndex);
?>

      </section>
    <?php
}
}
/* {/block 'page_content_container'} */
}
