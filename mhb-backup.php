<?php
/*
Plugin Name: MHB Backup
Description: Backing up will be like drinking water.
Version: 1.0.0
Author: beshavard_mh
Author URI: https://instagram.com/beshavard_mh
License: GPLv2 or later
*/

defined('ABSPATH') OR die('Access denied!');

class MHB_Backup
{

    protected $dir;

    protected $zipFileName;
    protected $exclusions_files = [];
    protected $included_files = [];

    protected $DBFileName;
    protected $exclusions_tables = [];
    protected $included_tables = [];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'create_admin_menu']);
        $this->settings_controller();
    }

    public function create_admin_menu()
    {
        add_menu_page(
            'MHB backup settings',
            'MHB backup',
            'manage_options',
            'mhb-backup',
            [$this, 'settings_view'],
            'dashicons-archive'
        );
    }

    public function settings_view()
    {
        include('settings_view.php');
    }

    public function settings_controller()
    {
        if (isset($_POST['submit_backup'])) {
            $zipFileName = sanitize_text_field($_POST['filename']);

            $exclusions = sanitize_textarea_field($_POST['exclusions']);
            if (!empty($exclusions)) {
                $exclusions = explode(',', $exclusions);
                $exclusions = array_filter(array_map('mb_strtolower', array_map('trim', $exclusions)));
            }

            $included = sanitize_textarea_field($_POST['included']);
            if (!empty($included)) {
                $included = explode(',', $included);
                $included = array_filter(array_map('mb_strtolower', array_map('trim', $included)));
            }

            $this->zipFileName = !empty($zipFileName) ? pathinfo($zipFileName, PATHINFO_FILENAME) . '.zip' : 'backup.zip';
            $this->exclusions_files = !empty($exclusions) ? $exclusions : [];
            $this->included_files = !empty($included) ? $included : [];
            $this->dir = ABSPATH;
            $this->create_zip();
        }

        if (isset($_POST['submit_DB_backup'])) {
            $DBFileName = sanitize_text_field($_POST['filename']);
            $exclusions = sanitize_textarea_field($_POST['exclusions']);

            if (!empty($exclusions)) {
                $exclusions = explode(',', $exclusions);
                $exclusions = array_filter(array_map('mb_strtolower', array_map('trim', $exclusions)));
            }

            $included = sanitize_textarea_field($_POST['included']);
            if (!empty($included)) {
                $included = explode(',', $included);
                $included = array_filter(array_map('mb_strtolower', array_map('trim', $included)));
            }

            $this->DBFileName = !empty($DBFileName) ? pathinfo($DBFileName, PATHINFO_FILENAME) . '.sql' : 'DB-backup.sql';
            $this->exclusions_tables = !empty($exclusions) ? $exclusions : [];
            $this->included_tables = !empty($included) ? $included : [];
            $this->create_backup_tables();
        }
        
    }

    public function create_zip()
    {
        if (file_exists($this->dir)) {

            ini_set('max_execution_time', 1000);
            ini_set('memory_limit', '1024M');

            $zip = new ZipArchive();
            $zip->open($this->zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $this->dir = realpath($this->dir);
            if (is_dir($this->dir)) {
                if (!empty($this->included_files)){
                    $files = [];
                    foreach ($this->included_files as $included_file) {
                        $path = realpath($this->dir . DIRECTORY_SEPARATOR . $included_file);
                        if (is_dir($path)){
                            $iterator = new RecursiveDirectoryIterator($path);
                            $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                            $recursive_files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                            foreach ($recursive_files as $recursive_file){
                                $recursive_file = realpath($recursive_file);
                                $files[] = $recursive_file;
                            }
                        }
                        else{
                            $files[] = $path;
                        }
                    }
                }
                else{
                    $iterator = new RecursiveDirectoryIterator($this->dir);
                    $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                    $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                }

                $this->exclusions_files = !empty($this->exclusions_files) ? array_map('realpath', array_map(function ($n) {
                    return $this->dir . DIRECTORY_SEPARATOR . $n;
                }, $this->exclusions_files)) : [];

                foreach ($files as $file) {
                    $file = realpath($file);

                    $ignore = false;

                    foreach ($this->exclusions_files as $exclusion){
                        if (strpos($file, $exclusion) !== false) {
                            $ignore = true;
                        }
                    }

                    if (!$ignore) {
                        if (is_dir($file)) {
                            $zip->addEmptyDir(str_replace($this->dir . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
                        } else if (is_file($file)) {
                            $zip->addFromString(str_replace($this->dir . DIRECTORY_SEPARATOR, '', $file), file_get_contents($file));
                        }
                    }
                }

            } else if (is_file($this->dir)) {
                $zip->addFromString(basename($this->dir), file_get_contents($this->dir));
            }
            $zip->close();

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($this->zipFileName));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($this->zipFileName));
            readfile($this->zipFileName);

            $file = getcwd() . DIRECTORY_SEPARATOR . $this->zipFileName;

            if (file_exists($file)){
                unlink($file);
            }

            exit();

        } else {
            die('Could not create a zip archive');
        }
    }

    public function create_backup_tables()
    {
        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if (mysqli_connect_errno()) {
            die("Failed to connect to MySQL: " . mysqli_connect_error());
        }

        mysqli_query($link, "SET NAMES 'utf8'");

        if (empty($this->included_tables)) {
            $tables = array();
            $result = mysqli_query($link, 'SHOW TABLES');
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }
        }else {
            $tables = $this->included_tables;
        }

        if (!empty($this->exclusions_tables)){
            $tables = array_diff($tables, $this->exclusions_tables);
        }

        $return = '';

        foreach ($tables as $table) {
            $result = mysqli_query($link, 'SELECT * FROM ' . $table);
            $num_fields = mysqli_num_fields($result);
            $num_rows = mysqli_num_rows($result);

            $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
            $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE ' . $table));
            $return .= "\n\n" . $row2[1] . ";\n\n";
            $counter = 1;

            for ($i = 0; $i < $num_fields; $i++) {
                while ($row = mysqli_fetch_row($result)) {
                    if ($counter == 1) {
                        $return .= 'INSERT INTO ' . $table . ' VALUES(';
                    } else {
                        $return .= '(';
                    }

                    for ($j = 0; $j < $num_fields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        if (isset($row[$j])) {
                            $return .= '"' . $row[$j] . '"';
                        } else {
                            $return .= '""';
                        }
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }

                    if ($num_rows == $counter) {
                        $return .= ");\n";
                    } else {
                        $return .= "),\n";
                    }
                    ++$counter;
                }
            }
            $return .= "\n\n\n";
        }

        $file = getcwd() . DIRECTORY_SEPARATOR . $this->DBFileName;

        file_put_contents($file, $return);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($this->DBFileName).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);

        unlink($file);

        exit();
    }

}

$mhb_backup = new MHB_Backup();