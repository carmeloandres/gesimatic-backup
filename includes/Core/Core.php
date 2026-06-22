<?php

namespace GesimaticBackup\Core;

use GesimaticBackup\Admin\Admin;
use GesimaticBackup\Api\GetBackupDownload;

/**
 * Class Core
 *
 * This class contains the code necessary to manage the necesary function hooks.
 *
 * @package GesimaticBackup\Core
 */
class Core {

    /**
     * Array to store dinamicaly the instances of each class when they are required.
     *
     * @var array
     */
    protected array $instances = [];

    /**
     * Class constructor.
     *
     * Sets the value of the properties, adds the actions necessary for the operation of
     * class.
     */    
    function __construct(){

        // To register the gesimatic-smtp admin page
        add_action('admin_menu',[$this,'register_admin_page']);

        // to load the smtp admin assets
        add_action('admin_enqueue_scripts',[$this,'admin_enqueue_assets'], 10, 1);

        // Gesimatic menu highlighting using CSS/JS
        add_action( 'admin_head', [ $this, 'force_menu_highlight' ] );

        // adding the smtp to gesimatic admin page
        add_filter( 'gesimatic_admin_tabs', function( $tabs ) {
                $tabs['gesimatic-backup'] = esc_html__( 'Backup', 'gesimatic-backup' );
            return $tabs;
        });

        // adds the admin api actions
        add_filter('gesimatic_admin_actions',[$this,'register_gesimatic_backup_api_actions']);

    }
    /**
     * Loads the Admin class to register the gesimatic-smtp admin page
     * 
     * @param void
     * @return void
     */
    function register_admin_page(): void{

        // Load the Admin class if not is loaded
        if (! isset($this->instances['admin']))
            $this->instances['admin'] = new Admin();
        $this->instances['admin']->register_admin_page();
    }

    /**
     * Loads the Admin class to enqueue the gesimatic-smtp assets
     * 
     * @param void
     * @return void
     */
    function admin_enqueue_assets($hook): void{

        // Load the Admin class if not is loaded
        if (! isset($this->instances['admin']))
            $this->instances['admin'] = new Admin();
        $this->instances['admin']->admin_enqueue_assets($hook);
    }

    /**
     * Force highlighting of the main menu using CSS/JS when on a hidden modular page.
     * 
     * @param void
     * @return void
     */
    function force_menu_highlight(): void{

        // Load the Admin class if not is loaded
        if (! isset($this->instances['admin']))
            $this->instances['admin'] = new Admin();
        $this->instances['admin']->force_menu_highlight();
    }

    /**
     * Registers the gesimatic backup api actions
     * 
     * @param array 
     * @return array
     */
    public function register_gesimatic_backup_api_actions($actions){

        $new_actions = $actions;

        $new_actions['get_backup_download'] = [
            'validate' => [GetBackupDownloadAction::class, 'validate'],
            'handle' => [GetBackupDownloadAction::class, 'handle'],
        ];

        return $new_actions;
    }

}