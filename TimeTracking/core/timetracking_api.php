<?php
require_api( 'billing_api.php' );

/**
* merge sort
* @param array $left left side of an array
* @param array $right right side of an array
* @param string $column sort by which column
* @return array sorted array
* @access public
*/
function merge_Sorted_2DArrays($array1, $array2, $sortColumn) {
    $result = array();
    $index1 = $index2 = 0;

    while ($index1 < count($array1) && $index2 < count($array2)) {
        if ($array1[$index1][$sortColumn] < $array2[$index2][$sortColumn]) {
            $result[] = $array1[$index1];
            $index1++;
        } else {
            $result[] = $array2[$index2];
            $index2++;
        }
    }

    // Merge any remaining elements from both arrays
    while ($index1 < count($array1)) {
        $result[] = $array1[$index1];
        $index1++;
    }
    while ($index2 < count($array2)) {
        $result[] = $array2[$index2];
        $index2++;
    }

    return $result;
}
/**
* Returns an array of time tracking stats
* @param int $p_project_id project id
* @param string $p_from Starting date (yyyy-mm-dd) inclusive, if blank, then ignored.
* @param string $p_to Ending date (yyyy-mm-dd) inclusive, if blank, then ignored.
* @return array array of bugnote stats
* @access public
*/
function plugin_TimeTracking_stats_get_project_array( $p_project_id, $p_from, $p_to) {
	$t_project_id = db_prepare_int( $p_project_id );
	$t_to = date("Y-m-d", strtotime("$p_to")+ SECONDS_PER_DAY - 1); 
	$t_from = $p_from; //strtotime( $p_from ) 
	if ( $t_to === false || $t_from === false ) {
		error_parameters( array( $p_form, $p_to ) );
		trigger_error( ERROR_GENERIC, ERROR );
	}
	$t_timereport_table = plugin_table('data', 'TimeTracking');
	$t_bug_table = db_get_table( 'bug' );
	$t_user_table = db_get_table( 'user' );
	$t_project_table = db_get_table( 'project' );

	$t_core_TimeTracking_stats = billing_get_for_project($p_project_id, $t_from, $t_to, 0);
	$t_core_TimeTracking_stats_converted = array();
	$t_result_sorted = array();

	$t_query = 'SELECT u.username, p.name as project_name, bug_id, expenditure_date, hours, timestamp, category, info 
	FROM '.$t_timereport_table.' tr
	LEFT JOIN '.$t_bug_table.' b ON tr.bug_id=b.id
	LEFT JOIN '.$t_user_table.' u ON tr.user=u.id
	LEFT JOIN '.$t_project_table.' p ON p.id = b.project_id
	WHERE 1=1 ';
	
	db_param_push();
	$t_query_parameters = array();

	if( !is_blank( $t_from ) ) {
		$t_query .= " AND expenditure_date >= " . db_param();
		$t_query_parameters[] = $t_from;
	}
	if( !is_blank( $t_to ) ) {
		$t_query .= " AND expenditure_date <= " . db_param();
		$t_query_parameters[] = $t_to;
	}
	if( ALL_PROJECTS != $t_project_id ) {
		$t_query .= " AND b.project_id = " . db_param();
		$t_query_parameters[] = $t_project_id;
	}
	if ( !access_has_global_level( plugin_config_get( 'view_others_threshold' ) ) ){
		$t_user_id = auth_get_current_user_id(); 
		$t_query .= " AND user = " . db_param();
		$t_query_parameters[] = $t_user_id;
	}
	$t_query .= ' ORDER BY user, expenditure_date, bug_id';

	$t_results = array();
	
	//$t_project_where $t_from_where $t_to_where $t_user_where

	$t_dbresult = db_query( $t_query, $t_query_parameters );
	while( $row = db_fetch_array( $t_dbresult ) ) {
		$t_results[] = $row;
	}

	//Map columns from original timetracking to plugin
	$t_date_format = config_get( 'normal_date_format' );
	foreach ($t_core_TimeTracking_stats as $t_stat) {
		$t_core_TimeTracking_stats_converted[] = array(
			'username' => $t_stat['reporter_name'],
			'project_name' => $t_stat['project_name'],
			'bug_id' => $t_stat['bug_id'],
			'expenditure_date' => date( $t_date_format, $t_stat['date_submitted'] ),
			'hours' => round($t_stat['minutes'] / 60, 2),
			'category' => $t_stat['bug_category'],
			'timestamp' => date( $t_date_format, $t_stat['date_submitted'] ) . ':00',
			'info' => $t_stat['note']
		);
	}

	$t_result_sorted = merge_Sorted_2DArrays($t_results, $t_core_TimeTracking_stats_converted, 'bug_id');

	return $t_result_sorted;
}

/**
* Returns an integer of minutes
* @param string $p_hhmm Time (hh:mm)
* @return integer integer of minutes
* @access public
*/
function plugin_TimeTracking_hhmm_to_minutes( $p_hhmm) {
	sscanf($p_hhmm, "%d:%d", $hours, $minutes); 
	return $hours * 60 + $minutes;
}

/**
* convert hours to a time format [h]h:mm
* @param string $p_hhmm Time (hh:mm)
* @return integer integer of minutes
* @access public
*/
function plugin_TimeTracking_hours_to_hhmm( $p_hours ) {
	$t_min = round( $p_hours * 60 );
	return sprintf( '%02d:%02d', $t_min / 60, $t_min % 60 );
}

/**
* inserts new row with '\n' to make life easier while debugging
* @access public
*/
function plugin_excel_get_start_row() {
	return "<Row>\n";
}
function plugin_excel_get_end_row() {
	return "</Row>\n";
}
/**
* apply style to single cell
* @param string $p_text text value inside cell
* @param string $p_style id of style
* @param string $p_is_number is input a number?
* @return string return cell with ss:StyleID
* @access public
*/
function plugin_excel_get_cell_style( $p_text, $p_style, $p_is_number = false ){
	$t_type = $p_is_number ? 'Number' : 'String';
	return excel_get_cell( $p_text, $t_type, array( 'ss:StyleID' => $p_style ) );
}
?>
