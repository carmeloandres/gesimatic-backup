<?php

namespace GesimaticBackup\Admin;

/**
 * Class AdminMenu.
 * 
 * @package GesimaticBackup\Admin.
*/
class Admin {

    /**
     * Register the gesimatic-smtp module page.
     */
    public function register_admin_page(): void {
            
        add_submenu_page(
            'non_existent_parent',
            'Gesimatic Backup',
            'Gesimatic Backup', 
            'manage_options',
            'gesimatic-backup',
            [$this,'show_admin_page']);
    }

    /**
     * Loads the Admin Gesimatic assets only in the gesimatic admin page.
     *
     * @param string $hook The name of the current page in the admin.
     */
    public function admin_enqueue_assets( $hook ) {

        // Only load if the hook matches the slug from gesimatic-backup.
        if ( 'admin_page_gesimatic-backup' !== $hook ) {
            return;
        }
        
        wp_enqueue_style(
            'gesimatic-backup-admin-css',                               
            GESIMATIC_BACKUP_URL . 'assets/css/gesimatic-backup-admin.min.css', 
            array(),                                             
            GESIMATIC_BACKUP_VERSION                                    
        );

        wp_enqueue_script(
            'gesimatic-backup-admin-js',
            GESIMATIC_BACKUP_URL . 'assets/js/gesimatic-backup-admin.js',
            array(),                                            
            GESIMATIC_BACKUP_VERSION,                                    
            true                                             
        );

        if (function_exists( 'is_multisite' ) && is_multisite() && is_super_admin())
            $isSuperAdmin = true;
        else $isSuperAdmin = false;            

        wp_localize_script(
            'gesimatic-backup-admin-js',
            'gesimaticBackupAdmin',
            array(
                "restUrl" => rest_url( '/gesimatic/v1/admin' ),
                "nonce" => wp_create_nonce( 'wp_rest' ),
                "isSuperAdmin" => $isSuperAdmin
            )
        );
    }

    /**
     * Show admin page.
     */
    public function show_admin_page(): void {
        // Get the roles through the wp_roles object
        global $wp_roles;

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.','gesimatic-backup' ) );
        }

        do_action( 'gesimatic_admin_header', 'gesimatic-backup' );
        
        ?>

                <div id="gesimatic-backup-admin"><?php esc_html_e( 'There has been an error, the component did not render.', 'gesimatic-backup' ); ?></div>
            </div>
        <?php
    }

        /**
     * Force highlighting of the main menu using CSS/JS
     * when on a hidden modular page.
     */
    public function force_menu_highlight() {
        global $plugin_page;

        if ( 'gesimatic-backup' !== $plugin_page ) {
            return;
        }

        // The main menu slug (id="toplevel_page_gesimatic")
        // WordPress usually adds "toplevel_page_" to your main menu slug.
        $parent_id = 'toplevel_page_gesimatic'; 

        ?>
        <style>
            /* We highlight the main menu */
            #<?php echo $parent_id; ?> {
                background-color: #f0f0f1; /* Background color active */
            }            
             #<?php echo $parent_id; ?> .wp-menu-image:before {
                color: #72aee6 !important; /* Color of the blue icon of WP */
            }
            #<?php echo $parent_id; ?> .wp-menu-name::after {
                content: "";
                position: absolute;
                top: 7px; /* WP standard settings */
                right: 0;
                border-top: 10px solid transparent;
                border-bottom: 10px solid transparent;
                border-right: 10px solid #f0f0f1; /* Background color of the content area */
                display: block !important;
            }
            /* We ensure that the container allows for absolute positioning of the after. */
            #<?php echo $parent_id; ?> .wp-menu-name {
                position: relative;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var parentMenu = document.getElementById('<?php echo $parent_id; ?>');
                if (parentMenu) {
                    parentMenu.classList.add('wp-has-current-submenu', 'wp-menu-open', 'current');
                    parentMenu.classList.remove('wp-not-current-submenu');
                    
                    // If you have submenus, we can also highlight the "Home" link.
//                    var firstSub = parentMenu.querySelector('ul li a');
//                    if (firstSub) firstSub.classList.add('current');
                }
            });
        </script>
        <?php
    }

}
    