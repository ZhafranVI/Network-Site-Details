<?php
/**
 * Plugin Name: Network Site Details
 * Description: Displays additional details like post count, last accessed date, and visitor count for each subsite in a WordPress multisite network. Also supports exporting data and displays them on a dashboard with charts.
 * Version: 1.0.0
 * Author: MBKM Team
 * License: MBKM Team PUTI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require 'vendor/autoload.php';

// Include PhpSpreadsheet manually
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Hook to add the dashboard page in Network Admin.
add_action( 'network_admin_menu', 'nsd_add_dashboard_menu' );

/**
 * Add a dashboard page to display post count, views, and percentages.
 */
function nsd_add_dashboard_menu() {
    add_menu_page(
        __( 'Site Details Dashboard', 'network-site-details' ),
        __( 'Dashboard', 'network-site-details' ),
        'manage_network',
        'nsd-dashboard',
        'nsd_render_dashboard_page',
        'dashicons-chart-bar',
        6
    );
}

/**
 * Render the dashboard page with charts and tables.
 */
function nsd_render_dashboard_page() {
    
    // Get the search query from the URL
    $search_query = isset($_GET['nsd_search_query']) ? sanitize_text_field($_GET['nsd_search_query']) : '';

    $sites = get_sites();
    $site_data = [];
    $total_posts = 0;
    $total_visitors = 0;

    foreach ($sites as $site) {
        switch_to_blog( $site->blog_id );

        $post_count = wp_count_posts()->publish;
        $visitor_count = get_option( 'nsd_visitor_count_last_three_months', 0 );
        $site_name = get_bloginfo( 'name' );

        // Get last updated post date
        $last_updated_post = nsd_get_last_updated_post_date();

        // Get last login user email
        $last_login_email = nsd_get_last_login_user_email();

        $site_data[] = [
            'name' => $site_name,
            'post_count' => $post_count,
            'visitor_count' => $visitor_count,
            'last_updated_post' => $last_updated_post,
            'last_login_email' => $last_login_email,
        ];

        $total_posts += $post_count;
        $total_visitors += $visitor_count;
        restore_current_blog();
    }
    // Sort the site data by post_count and visitor_count in descending order
    usort($site_data, function($a, $b) {
        return $b['post_count'] <=> $a['post_count'] ?: $b['visitor_count'] <=> $a['visitor_count'];
    });

    ?>
    <div class="wrap">
        <h1><?php _e( 'Multisite Dashboard', 'network-site-details' ); ?></h1>
        <p><?php _e( 'Overview of post counts and visitor data across all subsites.', 'network-site-details' ); ?></p>
        
        <!-- Export Button -->
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=nsd_export_to_excel')); ?>" class="button button-primary">
                <?php _e('Export to Excel', 'network-site-details'); ?>
            </a>
        </div>
        
        <!-- Display Data -->
        <h2><?php _e( 'Subsite Data', 'network-site-details' ); ?></h2>

        <table class="widefat fixed" cellspacing="0" id="network-site-details-table">
    <thead>
        <tr>
            <th><?php _e( 'Subsite', 'network-site-details' ); ?></th>
            <th>
                <?php _e( 'Post Count', 'network-site-details' ); ?>
                <button onclick="sortTable(1, 'desc')">🡹</button>
                <button onclick="sortTable(1, 'asc')">🡻</button>
            </th>
            <th>
                <?php _e( 'Visitor Count (Last 3 Months)', 'network-site-details' ); ?>
                <button onclick="sortTable(2, 'desc')">🡹️</button>
                <button onclick="sortTable(2, 'asc')">🡻</button>
            </th>
            <th><?php _e( 'Last Updated Post Date', 'network-site-details' ); ?></th>
            <th><?php _e( 'Last Login User Email', 'network-site-details' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $site_data as $site ) : ?>
        <tr>
            <td><?php echo esc_html( $site['name'] ); ?></td>
            <td><?php echo esc_html( $site['post_count'] ); ?></td>
            <td><?php echo esc_html( $site['visitor_count'] ); ?></td>
            <td><?php echo esc_html( $site['last_updated_post'] ); ?></td>
            <td><?php echo esc_html( $site['last_login_email'] ); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
function sortTable(columnIndex, order) {
    var table = document.getElementById("network-site-details-table");
    var rows = Array.from(table.rows).slice(1);
    rows.sort(function(a, b) {
        var cellA = a.cells[columnIndex].innerText;
        var cellB = a.cells[columnIndex].innerText;
        var valueA = isNaN(cellA) ? cellA : parseInt(cellA);
        var valueB = isNaN(cellB) ? cellB : parseInt(cellB);
        if (order === 'asc') {
            return valueA > valueB ? 1 : -1;
        } else {
            return valueA < valueB ? 1 : -1;
        }
    });
    rows.forEach(function(row) {
        table.tBodies[0].appendChild(row);
    });
}
</script>

        <h2><?php _e( 'Post Count Chart', 'network-site-details' ); ?></h2>
        <canvas id="postCountChart" width="400" height="200"></canvas>

        <h2><?php _e( 'Visitor Count Chart', 'network-site-details' ); ?></h2>
        <canvas id="visitorCountChart" width="400" height="200"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var postCtx = document.getElementById('postCountChart').getContext('2d');
        var postCountChart = new Chart(postCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode( array_column( $site_data, 'name' ) ); ?>,
                datasets: [{
                    label: '<?php _e( 'Post Count', 'network-site-details' ); ?>',
                    data: <?php echo json_encode( array_column( $site_data, 'post_count' ) ); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        var visitorCtx = document.getElementById('visitorCountChart').getContext('2d');
        var visitorCountChart = new Chart(visitorCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode( array_column( $site_data, 'name' ) ); ?>,
                datasets: [{
                    label: '<?php _e( 'Visitor Count', 'network-site-details' ); ?>',
                    data: <?php echo json_encode( array_column( $site_data, 'visitor_count' ) ); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php
}

/**
 * Export site data to an Excel file.
 */
add_action('admin_post_nsd_export_to_excel', 'nsd_export_to_excel');

function nsd_export_to_excel() {
    if (!current_user_can('manage_network')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'network-site-details'));
    }

    $sites = get_sites();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'Subsite');
    $sheet->setCellValue('B1', 'Post Count');
    $sheet->setCellValue('C1', 'Visitor Count (Last 3 Months)');
    $sheet->setCellValue('D1', 'Last Updated Post Date');
    $sheet->setCellValue('E1', 'Last Login User Email');

    $row = 2; // Start from the second row
    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);

        $subsite_name = get_bloginfo('name');
        $post_count = wp_count_posts()->publish;
        $visitor_count = get_option('nsd_visitor_count_last_three_months', 0);
        $last_updated_post = nsd_get_last_updated_post_date();
        $last_login_email = nsd_get_last_login_user_email();

        $sheet->setCellValue("A{$row}", $subsite_name);
        $sheet->setCellValue("B{$row}", $post_count);
        $sheet->setCellValue("C{$row}", $visitor_count);
        $sheet->setCellValue("D{$row}", $last_updated_post);
        $sheet->setCellValue("E{$row}", $last_login_email);

        $row++;
        restore_current_blog();
    }

    // Prepare file for download
    $filename = 'network-sites-details.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename={$filename}");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Get the date of the most recently updated post.
 */
function nsd_get_last_updated_post_date() {
    global $wpdb;

    $query = "
        SELECT post_modified
        FROM $wpdb->posts
        WHERE post_status = 'publish'
        ORDER BY post_modified DESC
        LIMIT 1
    ";

    $last_modified = $wpdb->get_var( $query );

    return $last_modified ? date_i18n( 'Y-m-d H:i:s', strtotime( $last_modified ) ) : __( 'No Posts', 'network-site-details' );
}

/**
 * Get the email of the most recently logged in user.
 */
function nsd_get_last_login_user_email() {
    global $wpdb;

    $user_login = $wpdb->get_var("SELECT user_email FROM $wpdb->users ORDER BY user_registered DESC LIMIT 1");

    return $user_login ? $user_login : __( 'No Logins', 'network-site-details' );
}
?>
