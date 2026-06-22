<?php

namespace GesimaticBackup\Api;

use Gesimatic\Api\Controllers\AdminController;
use Gesimatic\Api\Base\CommonResponse;

use GesimaticStaticForms\Api\Middleware\CredentialValidator;
use GesimaticStaticForms\Api\Middleware\SignatureValidator;
use GesimaticStaticForms\Api\Middleware\RequestValidator;
use GesimaticStaticForms\Api\Middleware\ResolveRole;

//use GesimaticLoginAttempts\Core\Setup;

/**
 * Class GetBackupDownloadAction
 *
 * This class contains the code necessary to manage the data from get_backup_download api request.
 *
 * @package gesimatic-backup
 */
class GetBackupDownloadAction {

    /** 
     * To validate 
     * 
     * This method perfoms the necesaria actions to validate data.
     * 
     */
    public static function validate($params){
        // check if action is as expected
        if(isset($params['action']) && ($params['action'] === 'get_backup_download')){
            return true;
        } else return false;

        return false;
    }

    /**
     * To handle 
     * 
     * This method perfoms the necesaria actions to handle data, to perform the request.
     * 
     */
    public static function handle($validated){

    	// wordpress prefers to use they own file system API to manage files
		global $wp_filesystem;

        if ($validated) {

        	if ( empty( $wp_filesystem ) ) {
			    require_once ABSPATH . 'wp-admin/includes/file.php';
			    WP_Filesystem();
		    }

            $backup_dir = $this->get_backup_dir();

            // checks if ABSPATH is readable
		    if ( ! $wp_filesystem->is_readable(ABSPATH)) return CommonResponse::error(['message' => es_html__("Your WordPress installation folder is protected and cannot be read. You should change the permissions to proceed again.",'gesimatic-backup')]); 

		    // checks if $backup_dir is writable
		    if ( ! $wp_filesystem->is_writable($backup_dir)) return CommonResponse::error(['message' => es_html__("Your folder is protected and cannot be written to. You should change the permissions to proceed again.",'gesimatic-backup')]);

		    $timestamp = gmdate('Y-m-d');
		    $home_url_raw = home_url();
    	    $parsed_url = wp_parse_url( $home_url_raw );

		    if ( ! isset($parsed_url['host'])) return CommonResponse::error(['message' => es_html__('Parsed home url failed.','gesimatic-backup')]);
		

		    // Replace dots with underscores
		    $home_url = str_replace('.', '_', $parsed_url['host']);

		    // To create a database restoration file
		    $db_file = $backup_dir . "/gesimatic_db_backup_{$timestamp}_{$home_url}.sql";
		    $backup_file = $backup_dir . "/gesimatic_backup_{$timestamp}_{$home_url}.zip";

	    	if ( ! $this->export_database($db_file)) return CommonResponse::error(['message' => esc_html__('Database export failed.','gesimatic-backup')]);

            if ($this->create_backup_file(ABSPATH,$backup_file)){
                //Schedule deletion no matter what happens at the end of the request
                register_shutdown_function(function() use ($backup_file, $db_file) {
                    // Delete files
                    wp_delete_file($backup_file);
                    Wp_delete_file($db_file);
                });

     			header( 'Content-Type: application/octet-stream' );
	    		header( 'Content-Disposition: attachment; filename="' . esc_attr( basename( $backup_file ) ) . '"' );
		    	header( 'Content-Length: ' . filesize( $backup_file ) );
			    header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );

			    // We tried to open the file using the WordPress stream (but we need the pointer).
			    // Since WP_Filesystem doesn't have a "stream" method for the official repository,
			    // the accepted way to stream without "readfile" is to open a read-only resource:
			    $file_handle = fopen( $backup_file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

			    if ( $file_handle ) {
                    // Forzamos la salida inmediata para evitar buffering del servidor
                    while ( ! feof( $file_handle ) ) {
                        echo fread( $file_handle, 8192 ); // Leemos en bloques de 8KB
                        flush(); // Envía el bloque al navegador
                        if ( ob_get_level() > 0 ) {
                            ob_flush();
                        }
                    }
                    fclose( $file_handle );
                } else {
                    CommonResponse::error(['message' => esc_html__( 'The backup file could not be opened for streaming.', 'gesimatic-backup' )]);
                }
            } else {
                // If the ZIP creation fails, we clean up the SQL that was created.
                if ( file_exists( $db_file ) ) {
                    Wp_delete_file( $db_file );
                }
                CommonResponse::error(['message' => esc_html__('Creating zip file failed.','gesimatic-backup')]);
            }

            exit;           
        }
    }

    /**
	 * Creates a zip backup file from source path.
	 *
	 * @param string $sourcePath the path to backup, $outputFile The file name to create the zip backup file.
	 * @return bool True on success, false on failure.
	 */
	function create_backup_file($source_path, $output_file)
	{
		// delete last '/' if exists
		$source_dir = rtrim($source_path, '/');
		$output_file = rtrim($output_file, '/');

		if (!is_dir($source_dir)) return false;

    	if (file_exists($output_file)) wp_delete_file($output_file);

		$zip = new \ZipArchive();
    	if ($zip->open($output_file, \ZipArchive::CREATE) !== true) {
        	return false;
    	}

		$it = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($source_dir, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($it as $fs) {
			$real  = $fs->getRealPath();
			$local = substr($real, strlen($source_dir) + 1); // ruta relativa dentro del zip
			if ($fs->isDir()) {
				$zip->addEmptyDir($local);
			} else {
				if (!$zip->addFile($real, $local)) {
					$zip->close();
					return false;
				}
			}
		}

	    $zip->close();
    	return file_exists($output_file) && filesize($output_file) > 0;
	}


    /**
	 * Export the WordPress database to a file.
	 *
	 * @param string $outputFile The path to save the SQL dump.
	 * @return bool True on success, false on failure.
	 */
	function export_database($outputFile)
	{
		global $wpdb;

		$dbHost = DB_HOST;
		$dbUser = DB_USER;
		$dbPass = DB_PASSWORD;
		$dbName = DB_NAME;

		$command = sprintf(
			'mysqldump --host=%s --user=%s --password=%s %s > %s',
			escapeshellarg($dbHost),
			escapeshellarg($dbUser),
			escapeshellarg($dbPass),
			escapeshellarg($dbName),
			escapeshellarg($outputFile)
		);

		exec($command, $output, $resultCode);
		return $resultCode === 0;
	}


    /**
     * Gets the backups path folder , if not exists it creates the folder and the files to deny the access
     *
     * @param void
     * @return string the full path to gesimatic-backups folder
     */
    function get_backup_dir(): string {

        // Create the gesimatic backups dir
		$backup_dir = WP_CONTENT_DIR . '/gesimatic-backups';
        
		if ( ! file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
			// Creating a empty index.php and .httacces files to protect the gesimatic-backups folder
            $empty_index = $backup_dir.'/index.php';
            $this->create_empty_index($empty_index);
            $deny_htaccess = $backup_dir.'/.htaccess';
            $this->create_htaccess($deny_htaccess);            
        }
        
        return $backup_dir;
    }
 
    /**
     * creates a empty index.php file to not show any 
     *
     * @param string $path Absolute, path and name to file
     * @return bool true if exit, false if file creation fails
     */
    function create_empty_index($full_file_name): bool {
        $result = false;

        $fichero = fopen($full_file_name,'w');
        if ($fichero !== false){
            fputs($fichero,'<?php'.PHP_EOL);
            fputs($fichero,'// silence is golden'.PHP_EOL);
            $result = fclose($fichero);
        }
        return $result;
    }

    /**
     * creates a deny htaccess file 
     *
     * @param string $path Absolute, path and name to file
     * @return bool true if exit, false if file creation fails
     */
    function create_htaccess($full_file_name): bool {
        $result = false;

        $fichero = fopen($full_file_name,'w');
        if ($fichero !== false){
            fputs($fichero,'# Deny all access to this directory'.PHP_EOL);
            fputs($fichero,'<IfModule mod_authz_core.c>'.PHP_EOL);
            fputs($fichero,'  Require all denied'.PHP_EOL);
            fputs($fichero,'</IfModule>'.PHP_EOL);
            fputs($fichero,PHP_EOL);
            fputs($fichero,'<IfModule !mod_authz_core.c>'.PHP_EOL);
            fputs($fichero,'  Deny from all'.PHP_EOL);
            fputs($fichero,'</IfModule>'.PHP_EOL);
            fputs($fichero,PHP_EOL);
            fputs($fichero,'# Disable directory listing'.PHP_EOL);
            fputs($fichero,'Options -Indexes'.PHP_EOL);
            $result = fclose($fichero);
        }
        return $result;
    }
}