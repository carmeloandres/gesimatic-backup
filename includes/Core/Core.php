<?php

namespace GesimaticBackup\Core;

/*
use GesimaticSmtp\Core\Setup;
use GesimaticSmtp\Admin\Admin;
*/
use GesimaticBackup\Api\GetBackupDownload;
/*use GesimaticSmtp\Api\SetSmtpSettings;
use GesimaticSmtp\Api\GetSmtpSettings;
*/

/**
 * Class Core
 *
 * This class contains the code necessary to manage the necesary function hooks.
 *
 * @package Gesimatic
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
    function __construct()
    {
        //call to parent constructor
//        parent::__construct();

        // To register the gesimatic-smtp admin page
        add_action('admin_menu',[$this,'register_admin_page']);

        // to load the smtp admin assets
        add_action('admin_enqueue_scripts',[$this,'admin_enqueue_assets'], 10, 1);

        // Gesimatic menu highlighting using CSS/JS
        add_action( 'admin_head', [ $this, 'force_menu_highlight' ] );

        // adding the smtp to gesimatic admin page
        add_filter( 'gesimatic_admin_tabs', function( $tabs ) {
                $tabs['gesimatic-smtp'] = esc_html__( 'SMTP', 'gesimatic-smtp' );
            return $tabs;
        });

        // adds the admin api actions
        add_filter('gesimatic_admin_actions',[$this,'register_gesimatic_backup_api_actions']);

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