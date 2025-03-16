<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since      2.0.0
 * @package    Disposable_Email_Blocker_Contact_Form_7
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) die;

/**
 * Remove plugin added table on uninstall/delete
 */
global $wpdb;

$table_name = $wpdb->prefix . DISPOSABLE_EMAIL_BLOCKER_CONTACT_FORM_7_PLUGIN_TABLE_NAME;

// Drop the table
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Remove scheduled cron event (if it still exists)
wp_clear_scheduled_hook( 'cf7_create_disposable_email_domains_table' );
