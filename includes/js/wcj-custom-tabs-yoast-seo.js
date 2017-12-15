/**
 * wcj-custom-tabs-yoast-seo.
 *
 * @version 3.2.4
 * @since   3.2.4
 * @see     https://return-true.com/adding-content-to-yoast-seo-analysis-using-yoastseojs/
 */

(function($) {

	var WCJ_Yoast_Plugin = function() {
		YoastSEO.app.registerPlugin('wcj_yoast_plugin', {status: 'loading'});
		this.getData();
	};

	WCJ_Yoast_Plugin.prototype.getData = function() {
		var _self = this;
		YoastSEO.app.pluginReady('wcj_yoast_plugin');
		YoastSEO.app.registerModification('content', $.proxy(_self.getCustomContent, _self), 'wcj_yoast_plugin', 5);
	};

	WCJ_Yoast_Plugin.prototype.getCustomContent = function (content) {
		var custom_product_tabs_content = "";
		jQuery("textarea[id^='wcj_custom_product_tabs_content_local_']").each(function() {
			custom_product_tabs_content += " " + jQuery(this).val();
		});
		return content + custom_product_tabs_content;
	};

	$(window).on('YoastSEO:ready', function () {
		new WCJ_Yoast_Plugin();
	});

})(jQuery);
