{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
<!-- Bouton hamburger pour mobile -->
<button type="button" class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Ouvrir le menu">
	<i class="material-icons">menu</i>
</button>
<!-- Overlay pour fermer la sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div id="container" class="row">
	<div class="sidebar navigation col-md-3">
		<!-- Bouton de fermeture pour mobile -->
		<button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Fermer le menu" style="display: none;">
			<i class="material-icons">close</i>
		</button>
		<nav class="list-group categorieList">
		{if count($modules)}
			{foreach $modules as $module}
				{if $module_instance[$module.name]}
					<a class="list-group-item{if ($current_module_name && $current_module_name == $module.name)} active{/if}" href="{$current|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}&amp;module={$module.name}">{$module_instance[$module.name]->displayName}</a>
				{/if}
			{/foreach}
		{else}
			{l s='No module has been installed.' d='Admin.Notifications.Warning'}
		{/if}
		</nav>
	</div>

<script type="text/javascript">
	(function() {
		var sidebar = document.querySelector('.sidebar.navigation.col-md-3');
		var toggleBtn = document.getElementById('mobileSidebarToggle');
		var overlay = document.getElementById('sidebarOverlay');
		
		if (!sidebar || !toggleBtn || !overlay) return;
		
		function openSidebar() {
			sidebar.classList.add('mobile-open');
			overlay.classList.add('active');
			toggleBtn.classList.add('active');
			var closeBtn = document.getElementById('sidebarCloseBtn');
			if (closeBtn) closeBtn.style.display = 'flex';
			document.body.style.overflow = 'hidden';
		}
		
		function closeSidebar() {
			sidebar.classList.remove('mobile-open');
			overlay.classList.remove('active');
			toggleBtn.classList.remove('active');
			var closeBtn = document.getElementById('sidebarCloseBtn');
			if (closeBtn) closeBtn.style.display = 'none';
			document.body.style.overflow = '';
		}
		
		toggleBtn.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			if (sidebar.classList.contains('mobile-open')) {
				closeSidebar();
			} else {
				openSidebar();
			}
		});
		
		overlay.addEventListener('click', closeSidebar);
		
		// Bouton de fermeture dans la sidebar
		var closeBtn = document.getElementById('sidebarCloseBtn');
		if (closeBtn) {
			closeBtn.addEventListener('click', closeSidebar);
		}
		
		// Fermer la sidebar quand on clique sur un lien
		var menuLinks = sidebar.querySelectorAll('.list-group-item');
		menuLinks.forEach(function(link) {
			link.addEventListener('click', function() {
				setTimeout(closeSidebar, 300);
			});
		});
		
		// Fermer la sidebar avec la touche Escape
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
				closeSidebar();
			}
		});
	})();
</script>
