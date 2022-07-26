/**
 * Wcj-track-users.js
 *
 * @version 5.6.2
 *
 * @package Booster_For_WooCommerce/includes/JS
 */

jQuery( document ).ready(
	function() {
		var data = {
			'action': 'wcj_track_users',
			'wcj_http_referer': track_users_ajax_object.http_referer,
			'wcj_user_ip': track_users_ajax_object.user_ip
		};
		jQuery.ajax(
			{
				type: "POST",
				url: track_users_ajax_object.ajax_url,
				data: data,
			}
		);
	}
);
