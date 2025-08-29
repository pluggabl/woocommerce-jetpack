/**
 * Sales Notifications Lite - Frontend Script
 *
 * @version 7.3.0
 */

(function() {
	'use strict';

	if (window.__boosterSNLite) {
		return;
	}
	window.__boosterSNLite = true;

	if (typeof wcjSalesNotificationsLite === 'undefined' || !wcjSalesNotificationsLite.items || wcjSalesNotificationsLite.items.length === 0) {
		return;
	}

	var items = wcjSalesNotificationsLite.items;
	var settings = wcjSalesNotificationsLite.settings;
	var currentIndex = 0;
	var isRunning = false;
	var container = null;
	var notification = null;
	var timeouts = [];

	function init() {
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			settings.firstDelay = 1000;
			settings.displayDuration = 3000;
			settings.gapBetween = 8000;
		}

		container = document.getElementById('wcj-sales-notifications-lite-container');
		if (!container) {
			return;
		}

		notification = container.querySelector('.wcj-sn-lite-notification');
		if (!notification) {
			return;
		}

		if (settings.theme && settings.theme !== 'light') {
			container.classList.add('wcj-sn-lite-theme-' + settings.theme);
		}

		var closeButton = notification.querySelector('.wcj-sn-lite-close');
		if (closeButton) {
			closeButton.addEventListener('click', stopNotifications);
		}

		document.addEventListener('keydown', handleKeydown);

		var keyboardButton = document.createElement('button');
		keyboardButton.style.position = 'absolute';
		keyboardButton.style.left = '-9999px';
		keyboardButton.style.opacity = '0';
		keyboardButton.setAttribute('aria-label', 'Stop sales notifications');
		keyboardButton.addEventListener('click', stopNotifications);
		container.appendChild(keyboardButton);

		setTimeout(startNotifications, settings.firstDelay);
	}

	function handleKeydown(event) {
		if (event.key === 'Escape' && isRunning) {
			stopNotifications();
		}
	}

	function startNotifications() {
		if (currentIndex >= items.length || currentIndex >= settings.maxPerPage) {
			return;
		}

		isRunning = true;
		showNotification(currentIndex);
	}

	function showNotification(index) {
		if (index >= items.length || index >= settings.maxPerPage) {
			return;
		}

		var item = items[index];
		var template = '{first_initial} from {city}, {country} bought {product} — {time_ago}';
		
		var message = template
			.replace('{first_initial}', item.first_initial)
			.replace('{city}', item.city)
			.replace('{country}', item.country)
			.replace('{product}', item.product)
			.replace('{time_ago}', item.time_ago);

		var textElement = notification.querySelector('.wcj-sn-lite-text');
		if (textElement) {
			textElement.textContent = message;
		}

		container.style.display = 'block';
		setTimeout(function() {
			notification.classList.add('wcj-sn-lite-show');
		}, 50);

		var hideTimeout = setTimeout(function() {
			hideNotification();
		}, settings.displayDuration);
		timeouts.push(hideTimeout);

		currentIndex++;
		if (currentIndex < items.length && currentIndex < settings.maxPerPage) {
			var nextTimeout = setTimeout(function() {
				showNotification(currentIndex);
			}, settings.displayDuration + settings.gapBetween);
			timeouts.push(nextTimeout);
		} else {
			var finishTimeout = setTimeout(function() {
				isRunning = false;
			}, settings.displayDuration);
			timeouts.push(finishTimeout);
		}
	}

	function hideNotification() {
		if (notification) {
			notification.classList.remove('wcj-sn-lite-show');
			notification.classList.add('wcj-sn-lite-hide');
			
			setTimeout(function() {
				if (container) {
					container.style.display = 'none';
				}
				if (notification) {
					notification.classList.remove('wcj-sn-lite-hide');
				}
			}, 300);
		}
	}

	function stopNotifications() {
		isRunning = false;
		
		timeouts.forEach(function(timeout) {
			clearTimeout(timeout);
		});
		timeouts = [];

		hideNotification();

		document.removeEventListener('keydown', handleKeydown);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
