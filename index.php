<?php
/**
 * Plugin Name: Woo Delete Status Logs for WooCommerce
 * Plugin URI: https://github.com/michelmelo/woo-delete-status-logs
 * Description: This plugin deletes WooCommerce status log files automatically after a time period specified by the administrator.
 * Version:1.0.0
 * Author:michelmelo
 * Author URI:https://michelmelo.pt/woo-delete-status-logs/
 */

function woo_delete_statuslogs_activation_logic()
{

    if (!is_plugin_active('woocommerce/woocommerce.php'))
    {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Auto Delete status Logs for WooCommerce requires WooCommerce Plugin in order for it to work properly!', 'wooautodelete'));
    }
}
register_activation_hook(__FILE__, 'woo_delete_statuslogs_activation_logic');

add_filter('cron_schedules', 'woo_delete_statuslogs_add_every_twentyfour_hours');
function woo_delete_statuslogs_add_every_twentyfour_hours($schedules)
{
    $schedules['every_twentyfour_hours'] = array(
        'interval' => 86400,
        'display' => __('Every Day', 'wooautodelete')
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if (!wp_next_scheduled('woo_delete_statuslogs_add_every_twentyfour_hours'))
{
    wp_schedule_event(time() , 'every_twentyfour_hours', 'woo_delete_statuslogs_add_every_twentyfour_hours');
}
/*
 ** calculate datetime
 * @package  Woo Delete Status Logs for WooCommerce
 * @since 1.1.1
*/
if (!function_exists('woo_delete_statuslogs_remove_files_from_dir_older_than_seven_days'))
{
    function woo_delete_statuslogs_remove_files_from_dir_older_than_seven_days($dir, $seconds = 3600)
    {
        //$files = glob(rtrim($dir, '/')."/webhooks-delivery-*");
        $files = glob(rtrim($dir, '/') . "/*.log");
        $now = time();
        foreach ($files as $file)
        {
            if (is_file($file))
            {
                if ($now - filemtime($file) >= $seconds)
                {
                    unlink($file);
                }
            }
            else
            {
                woo_delete_statuslogs_remove_files_from_dir_older_than_seven_days($file, $seconds);
            }
        }
    }
}
/*
 ** plugin setting link
 * @package  Auto Delete System Status Logs
 * @since 1.1.1
*/
function woo_delete_statuslogs_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=sys-autodelete-statuslogs-setting">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'woo_delete_statuslogs_settings_link');

/*
 **Hook into that action that'll fire every three minutes
 * @package  Auto Delete System Status Logs
 * @since 1.1.1
*/
add_action('woo_delete_statuslogs_add_every_twentyfour_hours', 'woo_delete_statuslogs_every_twentyfour_hours_event_func');
function woo_delete_statuslogs_every_twentyfour_hours_event_func()
{
    $uploads = wp_upload_dir();
    $upload_path = $uploads['basedir'];
    $dir = $upload_path . '/wc-logs/';

    $sys_autodelete_intervaldays = (int)get_option('sys_autodelete_intervaldays');
    $week_days = 1;
    if (!empty($sys_autodelete_intervaldays))
    {
        $total_days = $sys_autodelete_intervaldays;
    }
    else
    {
        $total_days = $week_days;
    }
    remove_files_from_dir_older_than_seven_days($dir, (60 * 60 * 24 * $total_days)); // 1 day
    
}

/*
 ** clear all log on button click from option page
 * @package  Auto Delete System Status Logs
 * @since 1.1.1
*/

function woo_delete_statuslogs_atonce()
{

    $log_dir = WC_LOG_DIR;

    foreach (scandir($log_dir) as $file)
    {

        $path = pathinfo($file);
        // Only delete log files, don't delete the test.log file
        if ($path['extension'] === 'log' && $path['filename'] !== 'test-log')
        {
            unlink("{$log_dir}/{$file}");
        }

    }

    // return __('Log files deleted', 'sysautodelete');
    
}
/*
 ** option page of Auto Delete Status Logs
 * @package  Auto Delete System Status Logs
 * @since 1.1.1
*/
function woo_delete_statuslogs_register_options_page()
{
    add_options_page('Auto Delete Status Logs', 'Auto Delete Status Logs', 'manage_options', 'sys-autodelete-statuslogs-setting', 'woo_delete_statuslogs_options_page');
}
add_action('admin_menu', 'woo_delete_statuslogs_register_options_page');

/*
 ** Creating setting page of Auto Delete Status Logs
 * @package  Auto Delete System Status Logs
 * @since 1.1.1
*/
function woo_delete_statuslogs_register_settings()
{
    register_setting('woo_delete_statuslogs_options_group', 'sys_autodelete_intervaldays');
}
add_action('admin_init', 'woo_delete_statuslogs_register_settings');

function woo_delete_statuslogs_options_page()
{
    echo '<div class="sys-autodelete-autoexpired-main"><div class="sys-autodelete-autoexpired"><form class="sys-autodelete-clearlog-form" method="post" action="options.php"><h1>Auto Delete Status Logs for WooCommerce</h1>';
    settings_fields('woo_delete_statuslogs_options_group');
    do_settings_sections('woo_delete_statuslogs_options_group');

    echo ' <div class="sys-autodelete-form-field" style="margin-top:50px"><label for="sys_autodelete_set_interval">Schedule days to auto delete status logs </label>';
    echo "<input class='sys-autodelete-input-field' type='text' id='sys_autodelete_set_interval' name='sys_autodelete_intervaldays'  value='" . get_option('sys_autodelete_intervaldays') . "' /> </div>";
    submit_button();
    echo '</form> </div></div>';
    echo '	<div class="sysautodelete-divider" style="margin:10px;font-weight:bold;"> OR </div> <h1 class="sysautodelete-title">Clear log files right now!</h1>  <div class="sys-autodelete-form-field" style="margin-top:50px">';
    echo "<form action=' " . woo_delete_statuslogs_atonce() . "' method='post'>";
    echo '<input type="hidden" name="action" value="my_action">';
    echo '<input type="submit" class="sysautodelete-clearbtn" value="Clear All" onclick="sysautodelete_showMessage()">';
    echo '</form> </div>'; 

    ?>
       
<script type="text/javascript">
function sysautodelete_showMessage() { alert("Status Logs Cleared");}</script>
    <?php
}

