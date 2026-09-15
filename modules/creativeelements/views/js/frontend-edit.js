/*!
 * Creative Elements - live Theme & Page Builder
 * Copyright 2019-2024 WebshopWorks.com
 */

document.addEventListener('DOMContentLoaded', function() {
	if (typeof ceFrontendEdit === 'undefined' || !ceFrontendEdit.editor_url) {
		return;
	}
	// Without token, AdminCEEditor redirects to BO login ("bad redirect")
	if (ceFrontendEdit.editor_url.indexOf('token=') === -1) {
		return;
	}

	var prev;
	$('.elementor:not(:empty)').each(function() {
		var uid = this.dataset.elementorId;
		if (uid && uid !== prev) {
			prev = uid;
			$(this).addClass('ce-edit-wrapper');
			var base = ceFrontendEdit.editor_url;
			var sep = (base.slice(-1) === '&' || base.slice(-1) === '?') ? '' : (base.indexOf('?') === -1 ? '?' : '&');
			$('<a class="ce-edit-btn"><i class="ce-icon">').attr({
				href: base + sep + 'uid=' + encodeURIComponent(uid),
				title: ceFrontendEdit.edit_title,
			}).appendTo(this);
			$('<div class="ce-edit-outline">').appendTo(this);
		}
	});
});
