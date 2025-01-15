<?php
/* Smarty version 4.3.4, created on 2025-01-15 01:36:13
  from '/var/www/districlos.ckc-net.com/themes/classic/templates/page.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.3.4',
  'unifunc' => 'content_678702fdd517b2_50998740',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '06a303225b7f6ac0f6d3d3c63491b7991e6dca32' => 
    array (
      0 => '/var/www/districlos.ckc-net.com/themes/classic/templates/page.tpl',
      1 => 1736900910,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_678702fdd517b2_50998740 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
?>


<?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_1693454736678702fdd4f0b6_75446494', 'content');
?>

<?php $_smarty_tpl->inheritance->endChild($_smarty_tpl, $_smarty_tpl->tpl_vars['layout']->value);
}
/* {block 'page_title'} */
class Block_1667089492678702fdd4f584_83200329 extends Smarty_Internal_Block
{
public $callsChild = 'true';
public $hide = 'true';
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

        <header class="page-header">
          <h1><?php 
$_smarty_tpl->inheritance->callChild($_smarty_tpl, $this);
?>
</h1>
        </header>
      <?php
}
}
/* {/block 'page_title'} */
/* {block 'page_header_container'} */
class Block_1340493709678702fdd4f2e4_82204955 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

      <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_1667089492678702fdd4f584_83200329', 'page_title', $this->tplIndex);
?>

    <?php
}
}
/* {/block 'page_header_container'} */
/* {block 'page_content_top'} */
class Block_1230423965678702fdd50766_02357268 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block 'page_content_top'} */
/* {block 'page_content'} */
class Block_1650689931678702fdd50a67_51229707 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

          <!-- Page content -->
        <?php
}
}
/* {/block 'page_content'} */
/* {block 'page_content_container'} */
class Block_2112835306678702fdd50544_13062195 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

      <div id="content" class="page-content card card-block">
        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_1230423965678702fdd50766_02357268', 'page_content_top', $this->tplIndex);
?>

        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_1650689931678702fdd50a67_51229707', 'page_content', $this->tplIndex);
?>

      </div>
    <?php
}
}
/* {/block 'page_content_container'} */
/* {block 'page_footer'} */
class Block_670974316678702fdd51267_28769761 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

          <!-- Footer content -->
        <?php
}
}
/* {/block 'page_footer'} */
/* {block 'page_footer_container'} */
class Block_889494686678702fdd50f42_75359424 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

      <footer class="page-footer">
        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_670974316678702fdd51267_28769761', 'page_footer', $this->tplIndex);
?>

      </footer>
    <?php
}
}
/* {/block 'page_footer_container'} */
/* {block 'content'} */
class Block_1693454736678702fdd4f0b6_75446494 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'content' => 
  array (
    0 => 'Block_1693454736678702fdd4f0b6_75446494',
  ),
  'page_header_container' => 
  array (
    0 => 'Block_1340493709678702fdd4f2e4_82204955',
  ),
  'page_title' => 
  array (
    0 => 'Block_1667089492678702fdd4f584_83200329',
  ),
  'page_content_container' => 
  array (
    0 => 'Block_2112835306678702fdd50544_13062195',
  ),
  'page_content_top' => 
  array (
    0 => 'Block_1230423965678702fdd50766_02357268',
  ),
  'page_content' => 
  array (
    0 => 'Block_1650689931678702fdd50a67_51229707',
  ),
  'page_footer_container' => 
  array (
    0 => 'Block_889494686678702fdd50f42_75359424',
  ),
  'page_footer' => 
  array (
    0 => 'Block_670974316678702fdd51267_28769761',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>


  <section id="main">

    <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_1340493709678702fdd4f2e4_82204955', 'page_header_container', $this->tplIndex);
?>


    <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_2112835306678702fdd50544_13062195', 'page_content_container', $this->tplIndex);
?>


    <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_889494686678702fdd50f42_75359424', 'page_footer_container', $this->tplIndex);
?>


  </section>

<?php
}
}
/* {/block 'content'} */
}
