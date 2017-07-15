/**
 * wcj-track-users.js
 *
 * @version 2.9.1
 * @version 2.9.1
 */

jQuery(document).ready(function() {
	var data = {
		'action': 'wcj_track_users',
		'wcj_http_referer': track_users_ajax_object.http_referer,
		'wcj_user_ip': track_users_ajax_object.user_ip
	};
	jQuery.ajax({
		type: "POST",
		url: track_users_ajax_object.ajax_url,
		data: data,
	});
});
