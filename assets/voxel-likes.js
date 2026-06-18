(function () {
	'use strict';

	function setButtonState(button, liked, count) {
		button.dataset.liked = liked ? '1' : '0';
		button.classList.toggle('active', liked);
		button.setAttribute('aria-pressed', liked ? 'true' : 'false');
		button.setAttribute('aria-label', liked ? button.dataset.activeLabel : button.dataset.label);

		button.querySelectorAll('.voxel-like-count, .publicacion-like-count').forEach(function (node) {
			node.textContent = String(count);
		});
	}

	function syncCounters(postId, count) {
		if (!postId) {
			return;
		}

		document.querySelectorAll('[data-voxel-likes-count][data-post-id="' + String(postId) + '"]').forEach(function (node) {
			node.textContent = String(count);
		});
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest('.voxel-like-action, .publicacion-like-action');
		if (!button) {
			return;
		}

		event.preventDefault();

		if (button.dataset.loading === '1') {
			return;
		}

		button.dataset.loading = '1';

		fetch(button.href, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data) {
					return;
				}

				setButtonState(button, !!payload.data.liked, payload.data.count || 0);
				syncCounters(payload.data.post_id, payload.data.count || 0);
			})
			.catch(function () {})
			.finally(function () {
				button.dataset.loading = '0';
			});
	});
})();
