<?php

namespace GesimaticBackup\Backup;

use wpdb;

class Backup {
    protected wpdb $db;

    public function __construct(wpdb $db = null) {
        global $wpdb;
        $this->db = $db ?? $wpdb;
    }

    /**
     * Backup database: structure and data to CSV files
     *
     * @param string $path Absolute path to save backup files
     * @return void
     */
    public function backup(string $path): void {
        global $wpdb, $wp_filesystem;

        // To ensure the filesystem API is initialized
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $tables = $wpdb->get_col( "SHOW TABLES" );
//        $tables = $this->db->get_col("SHOW TABLES");

        foreach ($tables as $table) {
            $columns = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `%i`", $table ), ARRAY_A );
            $structure_csv = '';

//            $structureFile = $path . "/structure_{$table}.csv";
//            $dataFile = $path . "/data_{$table}.csv";

//            error_log ('backup, $table: '.var_export($table,true));
//            error_log ('backup, $columns: '.var_export($columns,true));

            // Normalize structure default values
            if ( ! empty( $columns ) ) {
                foreach ($columns as &$col) {
                    $type = strtolower($col['Type']);
                    $default = $col['Default'];

                    if (is_null($default)) continue;
                    
                    if (preg_match('/^(int|decimal|float|double|tinyint|smallint|mediumint|bigint)/', $type)) {
                        if ($default === '') {
                            $col['Default'] = null;
                        } elseif (! is_numeric($default)) {
                            $col['Default'] = 0;
                        }
                    }
                    // To replace the '0000-00-00 00:00:00' default field value by null, it is better
                    if (preg_match('/^(datetime|timestamp)/', $type)  && $default === '0000-00-00 00:00:00') {
                            $col['Default'] = null;
                    }                
                }

// 	    	    error_log ('backup, $columns: '.var_export($columns,true));
                // Generar contenido CSV de estructura
                $structure_csv .= $this->generate_csv_row( array_keys( $columns[0] ) );
                foreach ( $columns as $col ) {
                    $structure_csv .= $this->generate_csv_row( array_values( $col ) );
                }

                $wp_filesystem->put_contents( $path . "/structure_{$table}.csv", $structure_csv, FS_CHMOD_FILE );
            }    

            $rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `%i`", $table ), ARRAY_A );

            if ( ! empty( $rows ) ) {
                $data_csv = $this->generate_csv_row( array_keys( $rows[0] ) );
                    
                foreach ( $rows as $row ) {
                    foreach ( $row as $key => $value ) {
                        if ( '0000-00-00 00:00:00' === $value ) {
                            $row[ $key ] = 'NULL';
                        }
                    }
                    $data_csv .= $this->generate_csv_row( array_values( $row ) );
                }    
                $wp_filesystem->put_contents( $path . "/data_{$table}.csv", $data_csv, FS_CHMOD_FILE );
            }
        }
/*
            // Save structure
            $fh = fopen($structureFile, 'w');
            if (!empty($columns)) {
                fputcsv($fh, array_keys($columns[0]));
                $length = count($columns);
                for ($index = 0; $index < $length; $index++){
                    fputcsv($fh, array_values($columns[$index]));
                }
            }
            fclose($fh);

            // Save data
            $rows = $this->db->get_results("SELECT * FROM `$table`", ARRAY_A);
            if (! empty($rows)) {
                $fh = fopen($dataFile, 'w');
                fputcsv($fh, array_keys($rows[0]));
                foreach ($rows as $row) {
                    $newrow= array();
                    foreach($row as $index => $value){
                        // To replace the '0000-00-00 00:00:00' default field value by null, it is better
                        if ($value === '0000-00-00 00:00:00') {
                            $value = 'NULL';
                        }
                        $newrow[$index] = $value; 
                    }
                    fputcsv($fh, $newrow);
                }
                fclose($fh);
            }
            */
        
            
    }

    /**
     * to generate a csv line without use fopen in disk 
     * 
     * @param array data to generate the string
     * @return string the string generated
     */
    private function generate_csv_row( array $data ): string {
        $fp = fopen( 'php://temp', 'r+' );
        fputcsv( $fp, $data );
        rewind( $fp );
        $row = stream_get_contents( $fp );
        fclose( $fp );
        return $row;
    }


    /**
     * Gets the backups path folder , if not exists it creates the folder and teh files to deny the access
     *
     * @param void
     * @return string the full path to gsmtc-backups folder
     */
    public function get_backup_dir(): string {

        // Create the gsmtc backups dir
		$backup_dir = WP_CONTENT_DIR . '/gsmtc-backups';
        
		if ( ! file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
			// Creating a empty index.php and .httacces files to protect the gsmtc-backups folder
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
    public function create_empty_index($full_file_name): bool {
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
    public function create_htaccess($full_file_name): bool {
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



/**
 * Restore database from CSV files using mysqli
 *
 * @param string $path Path where CSV files are stored
 * @param string $host Database host
 * @param string $user Database user
 * @param string $password Database password
 * @param string $database Target database name
 * @return void
 */
function restore_backup_from_csv(string $path, string $host, string $user, string $password, string $database): void {
    $mysqli = new \mysqli($host, $user, $password, $database);
    if ($mysqli->connect_error) {
        die("Connection failed: " . esc_html($mysqli->connect_error) );
    }

    $structureFiles = glob($path . "/structure_*.csv");

    foreach ($structureFiles as $structureFile) {
        $table = basename($structureFile, '.csv');
        $table = str_replace('structure_', '', $table);

        $fh = fopen($structureFile, 'r');
        $headers = fgetcsv($fh);
        $columns = [];

        while (($row = fgetcsv($fh)) !== false) {
            $columns[] = array_combine($headers, $row);
        }
        fclose($fh);

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (\n";
        $defs = [];
        $primary = '';

        foreach ($columns as $col) {
            $line = "`{$col['Field']}` {$col['Type']}";
            if ($col['Null'] === 'NO') $line .= " NOT NULL";
            if ($col['Default'] !== null) $line .= " DEFAULT '{$col['Default']}'";
            if ($col['Extra']) $line .= " {$col['Extra']}";
            $defs[] = $line;

            if ($col['Key'] === 'PRI') {
                $primary = $col['Field'];
            }
        }

        if ($primary) {
            $defs[] = "PRIMARY KEY (`$primary`)";
        }

        $sql .= implode(",\n", $defs) . "\n);";
        $mysqli->query("DROP TABLE IF EXISTS `$table`");
        $mysqli->query($sql);

        // Restore data
        $dataFile = $path . "/data_{$table}.csv";
        if (file_exists($dataFile)) {
            $fh = fopen($dataFile, 'r');
            $headers = fgetcsv($fh);

            while (($row = fgetcsv($fh)) !== false) {
                $assoc = array_combine($headers, $row);
                $cols = implode(", ", array_map(fn($k) => "`$k`", array_keys($assoc)));
                $vals = implode(", ", array_map(fn($v) => "'" . $mysqli->real_escape_string($v) . "'", array_values($assoc)));
                $mysqli->query("INSERT INTO `$table` ($cols) VALUES ($vals)");
            }
            fclose($fh);
        }
    }

    $mysqli->close();
}