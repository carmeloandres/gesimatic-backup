<?php

namespace GesimaticBackup\Translations;

if ( ! defined( 'ABSPATH' ) ) {exit;} ;

class Translations {

    public static function admin_translations(){
        $output = array(
             'creating_backup_download' =>  __('Creating backup and downloading file.. Please wait','gesimatic-backup'),
             'backup_downloaded_successfully' =>  __('The backup file has been downloaded successfully','gesimatic-backup'),
             'error_downloading_backup' =>  __('Error downloading the backup file','gesimatic-backup'),
             'backup_page' =>  __('Backup page','gesimatic-backup'),
             'download_backup' =>  __('Download backup','gesimatic-backup'),
            );

        return $output;

    }
}
