<?php
function wp_list_pluck( $input_list, $field, $index_key = null ) {
	if ( ! is_array( $input_list ) ) {
		return array();
	}

	$util = new WP_List_Util( $input_list );

	return $util->pluck( $field, $index_key );
}
