<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
require_once( 'core.php' );
require_api( 'billing_api.php' );
require_api( 'bug_api.php' );
require_api( 'excel_api.php' );

helper_begin_long_process();

$f_plugin_project = helper_get_current_project();
$t_from = gpc_get_string('plugin_TimeTracking_tfrom_hidden');
$t_to = gpc_get_string('plugin_TimeTracking_tto_hidden');
$t_plugin_TimeTracking_stats = plugin_TimeTracking_stats_get_project_array($f_plugin_project, $t_from, $t_to);

$t_filename = excel_get_default_filename();
$t_date_format = config_get( 'normal_date_format' );

$t_styles = array(
	'bold' => new ExcelStyle('bold'),
	'bg_bold' => new ExcelStyle('bg_bold'),
	'align_center' => new ExcelStyle('align_center')
);
$t_styles['bold']->setFont(1);
$t_styles['bg_bold']->setFont(1);
$t_styles['bg_bold']->setBackgroundColor('#B4C6E7');
$t_styles['align_center']->setAlignment(0, 'Center');

header( 'Content-Type: application/vnd.ms-excel; charset=UTF-8' );
header( 'Pragma: public' );
header( 'Content-Disposition: attachment; filename="' . urlencode( file_clean_name( $t_filename ) ) . '.xml"' ) ;

echo excel_get_header( $t_filename, $t_styles );

echo str_repeat('<Column ss:AutoFitWidth="1" ss:Width="110"/>'."\n", 8);
echo plugin_excel_get_start_row();
echo excel_get_cell( lang_get( 'project_name' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( lang_get( 'issue_id' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( plugin_lang_get( 'category' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( plugin_lang_get( 'user' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( plugin_lang_get( 'expenditure_date' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( plugin_lang_get( 'hours' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( lang_get( 'timestamp' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( plugin_lang_get( 'information' ), 'String', plugin_get_style_to_array('bg_bold') );
echo plugin_excel_get_end_row();

$t_sum_in_hours = 0;
$t_user_summary = array();
foreach( $t_plugin_TimeTracking_stats as $t_stat ) {
	echo plugin_excel_get_start_row();
	echo excel_prepare_string( $t_stat['project_name'] );
	echo excel_prepare_string( bug_format_summary( $t_stat['bug_id'], SUMMARY_FIELD ) );
	echo excel_prepare_string( $t_stat['category'] );
	echo excel_prepare_string( $t_stat['username'] );
	echo excel_prepare_string( date( config_get("short_date_format"), strtotime($t_stat['expenditure_date'])) );
	echo excel_get_cell( $t_stat['hours'], 'Number', plugin_get_style_to_array('align_center') );
	echo excel_prepare_string( $t_stat['timestamp'] );
	echo excel_prepare_string( $t_stat['info'] );
	echo plugin_excel_get_end_row();

	$t_user_summary[$t_stat['username']] = 0;
	$t_sum_in_hours += $t_stat['hours'];
}

foreach ( $t_plugin_TimeTracking_stats as $t_item ) {
	$t_user_summary[$t_item['username']] += $t_item['hours'];
	$t_sum_in_hours += $t_item['hours'];
}

echo plugin_excel_get_start_row();
echo plugin_excel_get_end_row();

echo plugin_excel_get_start_row();
echo excel_get_cell( plugin_lang_get( 'user' ), 'String', plugin_get_style_to_array('bg_bold') );
echo excel_get_cell( plugin_lang_get( 'hours' ), 'String', plugin_get_style_to_array('bg_bold') );
echo plugin_excel_get_end_row();
foreach ( $t_user_summary as $t_key => $t_user){
	echo plugin_excel_get_start_row();
	echo excel_prepare_string( $t_key );
	echo excel_prepare_number( $t_user );
	echo plugin_excel_get_end_row();
};
echo plugin_excel_get_start_row();
echo excel_prepare_string( 'Total: ' );
echo excel_prepare_number( $t_sum_in_hours );
echo plugin_excel_get_end_row();

echo excel_get_footer();
?>