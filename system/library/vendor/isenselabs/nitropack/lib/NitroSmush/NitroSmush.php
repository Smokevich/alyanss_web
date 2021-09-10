<?php

class NitroSmush {
    const VERSION = '1.0';

    private $_temp_file_prefix = 'nitrosmush_temp';
    private $_file_loaded = 'loaded.temp';
    private $_file_version = 'loaded.version';
    private $_output_errors = 'output_errors.temp';
    private $_filesize_limit = 524288000; // 500 MB - Nobody is that brave to upload an image > 500 MB, right?
    private $_debug = false;
    private $_force_exec_enabled = false;
    private $_temp_dir = '';
    private $_session = '';

    private $_api = array(
        'http_url' => 'http://nitrosmush.com/api.php'
    );

    private $_config = array(
        '~\.gif$~i' => array(
            'gifsicle' => array(
                'linux32' => '',
                'linux64' => 'gifsicle_linux',
                'mac32' => '',
                'mac64' => 'gifsicle_mac',
                'win32' => '',
                'win64' => 'gifsicle_win.exe',
                'help_test' => array(
                    'flag' => '--help',
                    'expect' => 'manipulates GIF images'
                ),
                'extension' => 'gif',
                'quality' => null,
                'settings' => array(
                    '{EXE} --no-warnings --careful --optimize=3 --output={DESTINATION} {SOURCE} 2> {STDERR}'
                )
            )
        ),
        '~\.jpe?g$~i' => array(
            'imagemagick' => array(
                'linux32' => '',
                'linux64' => 'bin/convert',
                'mac32' => '',
                'mac64' => '',
                'win32' => '',
                'win64' => '',
                'extension' => 'jpg',
                'quality' => '-quality {N}',
                'settings' => array(
                    '{EXE} {SOURCE} -sampling-factor 4:2:0 -strip {QUALITY} -interlace JPEG -colorspace RGB {DESTINATION} 2> {STDERR}'
                )
            ),
            'jpegoptim' => array(
                'linux32' => '',
                'linux64' => 'jpegoptim_linux',
                'mac32' => '',
                'mac64' => 'jpegoptim_mac',
                'win32' => '',
                'win64' => 'jpegoptim_win.exe',
                'help_test' => array(
                    'flag' => '-V',
                    'expect' => 'jpegoptim'
                ),
                'extension' => 'jpg',
                'quality' => '-m{N}',
                'settings' => array(
                    '{EXE} {QUALITY} -o -f -q --strip-all --all-normal --stdout {SOURCE} > {DESTINATION} 2> {STDERR}',
                    '{EXE} {QUALITY} -o -f -q --strip-all --all-progressive --stdout {SOURCE} > {DESTINATION} 2> {STDERR}'
                )
            ),
            'jpegtran' => array(
                'linux32' => '',
                'linux64' => 'jpegtran_linux',
                'mac32' => '',
                'mac64' => 'jpegtran_mac',
                'win32' => '',
                'win64' => 'jpegtran_win.exe',
                'extension' => 'jpg',
                'quality' => null,
                'settings' => array(
                    '{EXE} -copy none -progressive -optimize -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} -copy none -optimize -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} -copy none -progressive -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} -copy none -outfile {DESTINATION} {SOURCE} 2> {STDERR}'
                )
            ),
            //'guetzli' => array(
            //    'linux32' => '',
            //    'linux64' => 'guetzli_linux',
            //    'mac32' => '',
            //    'mac64' => 'guetzli_mac',
            //    'win32' => '',
            //    'win64' => 'guetzli_win.exe',
            //    'extension' => 'jpg',
            //    'quality' => '--quality {N}',
            //    'settings' => array(
            //        '{EXE} {QUALITY} {SOURCE} {DESTINATION} 2> {STDERR}'
            //    )
            //),
            'mozjpeg' => array(
                'linux32' => '',
                'linux64' => 'cjpeg_linux',
                'mac32' => '',
                'mac64' => '',
                'win32' => '',
                'win64' => '',
                'extension' => 'jpg',
                'quality' => '-quality {N}',
                'settings' => array(
                    '{EXE} {QUALITY} -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} {QUALITY} -targa -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} {QUALITY} -revert -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} {QUALITY} -dc-scan-opt {FOR0-2} -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} {QUALITY} -tune-psnr -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} {QUALITY} -tune-hvs-psnr -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} {QUALITY} -tune-ssim -outfile {DESTINATION} {SOURCE} 2> {STDERR}',
                    '{EXE} {QUALITY} -tune-ms-ssim -outfile {DESTINATION} {SOURCE} 2> {STDERR}'
                )
            )
        ),
        '~\.png$~i' => array(
            'pngquant' => array(
                'linux32' => '',
                'linux64' => 'pngquant_linux',
                'mac32' => '',
                'mac64' => '',
                'win32' => '',
                'win64' => '',
                'help_test' => array(
                    'flag' => '--help',
                    'expect' => 'Color profiles'
                ),
                'extension' => 'png',
                'quality' => '--quality {N}',
                'settings' => array(
                    '{EXE} {QUALITY} --strip --force 256 --output {DESTINATION} {SOURCE} 2> {STDERR}'
                )
            )
            //'optipng' => array(
            //    'linux32' => '',
            //    'linux64' => 'optipng_linux',
            //    'mac32' => '',
            //    'mac64' => 'optipng_mac',
            //    'win32' => '',
            //    'win64' => 'optipng_win.exe',
            //    'help_test' => array(
            //        'flag' => '--help',
            //        'expect' => 'chosen heuristically'
            //    ),
            //    'extension' => 'png',
            //    'quality' => null,
            //    'settings' => array(
            //        '{EXE} -o2 -silent -clobber -strip all -out {DESTINATION} {SOURCE} 2> {STDERR}'
            //        //'{EXE} -o2 -strip all -silent -clobber -out {DESTINATION} {SOURCE} 2> {STDERR}'
            //    )
            //)
            // 'pngcrush' => array(
            //     'linux32' => '',
            //     'linux64' => 'pngcrush_linux',
            //     'mac32' => '',
            //     'mac64' => '',
            //     'win32' => '',
            //     'win64' => 'pngcrush_win.exe',
            //     'extension' => 'png',
            //     'quality' => null,
            //     'settings' => array(
            //         '{EXE} -z 0 -w 4 -reduce -plte_len 1 -s -force {SOURCE} {DESTINATION} 2> {STDERR}',
            //         '{EXE} -reduce -brute -s -force {SOURCE} {DESTINATION} 2> {STDERR}'
            //     )
            // )
            // 'pngout' => array(
            //     'linux32' => '',
            //     'linux64' => 'pngout_linux',
            //     'mac32' => '',
            //     'mac64' => 'pngout_mac',
            //     'win32' => '',
            //     'win64' => 'pngout_win.exe',
            //     'extension' => 'png',
            //     'quality' => null,
            //     'settings' => array(
            //         '{EXE} -c6 -f2 -s0 -b192 -y -q -force {SOURCE} {DESTINATION} 2> {STDERR}'
            //     )
            // )
        )
    );

    public function __construct() {
        $this->_session = md5(strval(microtime(true)));
    }

    public function setTempDir($tmp_dir) {
        $this->_temp_dir = $tmp_dir;
    }

    public function setForceExecEnabled($exec_enabled) {
        $this->_force_exec_enabled = $exec_enabled;
    }

    public function debug($state) {
        $this->_debug = $state;
    }

    /*
        Run this in the admin panel, best to do it with an AJAX call, since it takes time and might timeout the admin panel on slower servers.
        Returns: Array() with all errors encountered.
    */
    public function loadExecutables($reload = false) {
        if (!file_exists($this->_temp_path($this->_file_loaded, true)) || $reload || !$this->_loaded_version_matches()) {
            return $this->_load_executables();
        }
        return array();
    }

    /*
        $source: The source of the image. Can be a relative ot absolute path. Accepted extensions: GIF, PNG, JP(E)G
        $destination: The destination image. Can be a different name, or the same name. If not specified, the smusher tries to overwrite the original image

        Returns:

        Array (
            smushed: Boolean value, indicating if the image has been smushed
            savings_b: Bytes saved
            savings_percent: How many percent have been saved
            errors: Array() containing errors
        )
    */

    public function smush($source, $destination = false, $use_api = false, $quality = 100) {
        $loadErrors = $use_api ? array() : $this->loadExecutables();
        if (empty($destination)) {
            $destination = $source;
        }

        $this->_set_error_handler();

        $source = realpath($source);
        touch($destination);
        $destination = realpath($destination);

        $temp_outputs = array();
        $index = 0;
        $errors = array();

        if (!$use_api) {
            try {
                $this->_check_compatibility();
                
                $os = $this->_get_os_suffix();

                foreach ($this->_config as $filetype_regex => $filetype_config) {
                    if (!preg_match($filetype_regex, $source)) continue;

                    foreach ($filetype_config as $tool_name => $tool_config) {
                        if (!$this->_executable_is_loaded($tool_name)) continue;

                        if (empty($tool_config[$os])) continue;

                        $exe = $this->_get_exe($tool_name, $tool_config[$os]);

                        foreach ($this->_parse_config_for($tool_config['settings']) as $setting) {
                            try {
                                $temp_dest = $this->_temp_path(basename($source));
                                $this->_process_image($exe, $setting, $source, $temp_dest, $tool_config['quality'], $quality);

                                if ($this->_is_readable($temp_dest) && $this->_is_writable($this->_temp_path(''), false)) {
                                    copy($temp_dest, $temp_dest . '.' . $index);

                                    $temp_outputs[] = $temp_dest . '.' . $index;

                                    $index++;
                                }
                            } catch (Exception $e) {
                                $errors[] = $e->getMessage();
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $min = $this->_filesize_limit;
        $winner = false;

        foreach ($temp_outputs as $temp_output) {
            $temp_size = filesize($temp_output);

            if ($temp_size > 0 && $temp_size < $min) {
                $min = $temp_size;
                $winner = $temp_output;
            }
        }

        $result = array(
            'smushed' => false,
            'savings_b' => 0,
            'savings_percent' => 0,
            'errors' => $errors
        );

        try {
            if ($use_api) {
                $api_result = $this->_smush_api(array($this, '_curl_api'), array($this, '_file_api'), array('source' => $source, 'quality' => $quality));

                $winner = $api_result['result_file'];
                $min = $api_result['result_size'];
            }

            if (!empty($winner)) {
                $previous_size = filesize($source);

                if ($this->_is_readable($winner) && $this->_is_writable($destination, false) && $min < $previous_size) {
                    copy($winner, $destination);

                    $result['smushed'] = true;
                    $result['savings_b'] = $previous_size - $min;
                    $result['savings_percent'] = round((1 - ($min / $previous_size)) * 100, 2);
                }
            }
        
            $this->_clean_temp();
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
        }

        if (!empty($loadErrors)) {
            $result['errors'] = array_merge($loadErrors, $result['errors']);
        }

        $this->_restore_error_handler();

        return $result;
    }

    private function _loaded_version_matches() {
        $version_file = $this->_temp_path($this->_file_version, true);
        return file_exists($version_file) && file_get_contents($version_file) == self::VERSION;
    }

    private function _set_error_handler() {
        set_error_handler(
            create_function(
                '$severity, $message, $file, $line',
                'throw new Exception($message . " in file " . $file . " on line " . $line);'
            )
        );
    }

    private function _restore_error_handler() {
        restore_error_handler();
    }

    private function _get_mime($file) {
        if (preg_match('~\.gif$~i', $file)) {
            return 'image/gif';
        } else if (preg_match('~\.jpe?g$~i', $file)) {
            return 'image/jpeg';
        } else if (preg_match('~\.png$~i', $file)) {
            return 'image/png';
        } else {
            throw new Exception("Invalid extension of " . $file);
        }
    }

    private function _curl_api($args) {
        $source = $args['source'];
        $quality = $args['quality'];

        $ch = curl_init($this->_api['http_url']);

        if (class_exists('CURLFile')) {
            $post_file = new CURLFile($source, $this->_get_mime($source), basename($source));
        } else {
            $post_file = '@' . $source;
        }

        curl_setopt_array($ch, array(
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array(
                'image' => $post_file,
                'quality' => $quality
            ),
            CURLOPT_RETURNTRANSFER => 1
        ));

        $json = curl_exec($ch);

        $response = json_decode($json, true);

        curl_close($ch);

        if (!empty($response['error'])) {
            throw new Exception($response['error']);
        }

        return $this->_download_file($response['result_file']);
    }

    private function _file_api($args) {
        $source = $args['source'];
        $quality = $args['quality'];

        $data = ""; 

        $boundary = "---------------------" . substr(md5(mt_rand(0, 32000)), 0, 10);

        $data .= "--" . $boundary . "\n";

        $data .= "Content-Disposition: form-data; name=\"quality\"\n\n"; 
        $data .= $quality."\n";
        $data .= "--" . $boundary . "\n";

        $fileContents = file_get_contents($source); 

        $data .= "Content-Disposition: form-data; name=\"image\"; filename=\"" . basename($source) . "\"\n"; 
        $data .= "Content-Type: " . $this->_get_mime($source) . "\n"; 
        $data .= "Content-Transfer-Encoding: binary\n\n"; 
        $data .= $fileContents."\n";

        $data .= "--" . $boundary . "\n";

        $params = array('http' => array( 
            'method' => 'POST', 
            'header' => 'Content-Type: multipart/form-data; boundary='.$boundary, 
            'content' => $data 
        ));

        $url = $this->_api['http_url'];

        $ctx = stream_context_create($params); 
        $fp = fopen($url, 'rb', false, $ctx); 

        if (!$fp) { 
            throw new Exception("There was a problem with $url");
        } 

        $json = @stream_get_contents($fp);

        fclose($fp);

        if ($json === false) { 
            throw new Exception("Problem reading data from $url"); 
        }

        $response = json_decode($json, true);

        if (!empty($response['error'])) {
            throw new Exception($response['error']);
        }

        return $this->_download_file($response['result_file']);
    }

    private function _download_file($url) {
        $result = array(
            'result_file' => false,
            'result_size' => 0
        );

        $download = $this->_smush_api(array($this, '_curl_download'), array($this, '_file_download'), $url);

        $this->_is_readable($download);

        $result['result_file'] = $download;
        $result['result_size'] = filesize($download);

        return $result;
    }

    private function _curl_download($file) {
        $destination = $this->_temp_path('downloaded');

        $ch = curl_init($file);

        touch($destination);
        $this->_is_writable($destination);
        $fh = fopen($destination, 'wb');

        curl_setopt_array($ch, array(
            CURLOPT_FILE => $fh
        ));

        curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code != 200) {
            fclose($fh);
            curl_close($ch);
            throw new Exception("Response code is " . $code . " for file " . $file);
        }

        fclose($fh);

        if (filesize($destination) == 0) {
            throw new Exception("An error has been encountered while downloading " . $file);
        }

        curl_close($ch);

        return $destination;
    }

    private function _file_download($file) {
        $destination = $this->_temp_path('downloaded');

        touch($destination);
        $this->_is_writable($destination);

        file_put_contents($destination, file_get_contents($file));

        return $destination;
    }

    private function _smush_api($callable_curl, $callable_file, $arg) {
        if (function_exists('curl_init')) {
            return call_user_func($callable_curl, $arg);
        } else if (ini_get('allow_url_fopen') == '1') {
            return call_user_func($callable_file, $arg);
        } else {
            throw new Exception("Your server does not support CURL and allow_url_fopen is disalbed. At least one of these options must be enabled on your server. Please contact your web hosting provider.");
        }
    }

    private function _clean_temp() {
        $dir = $this->_temp_path('');

        $items = scandir($dir);

        foreach ($items as $item) {
            if (!in_array($item, array('.', '..'))) {
                unlink($dir . $item);
            }
        }

        rmdir($dir);
    }

    private function _check_compatibility() {
        if (!$this->_exec_enabled()) {
            throw new Exception("exec() function not allowed! Please contact your web hosting provider to enable it.");
        }
    }

    private function _exec_enabled() {
        if ($this->_force_exec_enabled) return true;

        $command = function_exists('exec') &&
                !in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions')))) &&
                !(strtolower(ini_get('safe_mode')) != 'off' && ini_get('safe_mode') != 0);

        if ($command) {
            $result = array();
            exec('whoami', $result);
            return !empty($result);
        }

        return false;
    }

    private function _substr_caps($text, $length) {
        return strtoupper(substr($text, 0, $length));
    }

    private function _os() {
        return php_uname('s');
    }

    private function _check_dir_create($tmp_dir, $target_dir) {
        if (!is_dir($target_dir)) {
            if ($this->_is_writable($tmp_dir, false)) {
                mkdir($target_dir, 0755);

                $this->_is_writable($target_dir, false);
                $this->_is_readable($target_dir, false);
            }
        }
    }

    private function _temp_path($file, $base = false) {
        $tmp_dir = !empty($this->_temp_dir) ? $this->_temp_dir : sys_get_temp_dir();
        $target_dir = rtrim($tmp_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->_temp_file_prefix;

        $this->_check_dir_create($tmp_dir, $target_dir);

        if (!empty($this->_session) && !$base) {
            $target_dir .= DIRECTORY_SEPARATOR . $this->_session;

            $this->_check_dir_create($tmp_dir, $target_dir);
        }
        
        $path = $target_dir . DIRECTORY_SEPARATOR . $file;

        return $path;
    }

    private function _chmod($path, $mode = false) {
        $path = realpath($path);

        $uname = $this->_os();

        if ($this->_substr_caps($uname, 5) != 'LINUX' && $this->_substr_caps($uname, 6) != 'DARWIN') {
            return false;
        }

        if ($mode === false) {
            if (is_file($path)) {
                $mode = 0644;
            } else if (is_dir($path)) {
                $mode = 0755;
            } else {
                return false;
            }
        }

        return chmod($path, $mode);
    }

    private function _is_readable($path, $touch = true) {
        if ($touch) {
            touch($path);
        }

        $path = realpath($path);

        if (is_readable($path)) {
            return true;
        } else {
            $this->_chmod($path);

            if (is_readable($path)) {
                return true;
            } else {
                throw new Exception("Path is not readable: " . $path);
            }
        }
    }

    private function _is_writable($path, $touch = true) {
        if ($touch) {
            touch($path);
        }

        $path = realpath($path);

        if (is_writable($path)) {
            return true;
        } else {
            $this->_chmod($path);

            if (is_writable($path)) {
                return true;
            } else {
                throw new Exception("Path is not writable: " . $path);
            }
        }
    }

    private function _arch() {
        return 64;
        
        // $arch = php_uname('m');

        // if ($arch == 'x86_64' || $arch == 'amd64') {
        //     return 64;
        // } else {
        //     return 32;
        // }
    }

    private function _process_image($exe, $setting, $source_path, $destination_path, $quality_str, $quality) {
        $output_errors = $this->_temp_path($this->_output_errors);

        $this->_is_readable($source_path);
        $this->_is_writable(dirname($source_path), false);
        $this->_is_writable(dirname($destination_path), false);
        $this->_is_readable($output_errors);
        $this->_is_writable($output_errors);

        if (is_numeric($quality) && $quality > 0 && $quality < 100 && !empty($quality_str)) {
            $quality_replacement = str_replace('{N}', $quality, $quality_str);
        } else {
            $quality_replacement = '';
        }

        if (filesize($source_path) > $this->_filesize_limit) {
            throw new Exception("File " . $source_path . " is larger than 500 MB!");
        }

        $find = array(
            '{EXE}',
            '{SOURCE}',
            '{DESTINATION}',
            '{DESTINATION_DIR}',
            '{STDERR}',
            '{QUALITY}'
        );

        //If weird filename issues, take a look at the _escapeshellarg function below

        $replace = array(
            $exe,
            $this->mb_escapeshellarg($source_path),
            $this->mb_escapeshellarg($destination_path),
            $this->mb_escapeshellarg(dirname($destination_path)),
            $this->mb_escapeshellarg($output_errors),
            $quality_replacement
        );

        $command = str_replace($find, $replace, $setting);

        if (basename($exe) == "convert" || basename($exe) == "guetzli_linux") {
            $ld_library_path = getenv("LD_LIBRARY_PATH");
            $lib_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Executables' . DIRECTORY_SEPARATOR . 'imagemagick' . DIRECTORY_SEPARATOR . 'lib';
            $lib_define = "";

            if (empty($ld_library_path)) {
                $lib_define = "LD_LIBRARY_PATH=$lib_path ";
            } else if (strpos($ld_library_path, "Executables/imagemagick") === false) {
                $lib_define = "LD_LIBRARY_PATH=$lib_path:$ld_library_path ";
            }

            $command = $lib_define . $command;
        }

        exec($command);

        $errors = file_get_contents($output_errors);

        if (!empty($errors)) {
            throw new Exception('Executing ' . $command . ' returned the following error(s): ' . $errors);
        }

        if (!file_exists($destination_path) || filesize($destination_path) == 0) {
            throw new Exception('Executing ' . $command . ' resulted in an empty file.');
        }
    }

    private function mb_escapeshellarg($arg) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return '"' . str_replace(array('"', '%'), array('', ''), $arg) . '"';
        } else {
            return "'" . str_replace("'", "'\\''", $arg) . "'";
        }
    }

    private function _escapeshellarg($text) {
        if (preg_match('~[^\w\/\\\]{1}~u', $text)) {
            return "'" . $text . "'";
        } else {
            return escapeshellarg($text);
        }
    }

    private function _test_config($exe, $extension, $setting, $quality_str) {
        $source_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Images' . DIRECTORY_SEPARATOR . 'source.' . $extension;
        $destination_path = $this->_temp_path('result.' . $extension);

        $this->_process_image($exe, $setting, $source_path, $destination_path, $quality_str, 100);
    }

    private function _parse_config_for($settings) {
        $new_settings = array();

        foreach ($settings as $setting) {
            $for_matches = array();

            preg_match_all('~{FOR(.*?)}~', $setting, $for_matches);

            if (!empty($for_matches[1][0])) {
                $for_match = $for_matches[1][0];
                
                $search = '{FOR' . $for_match . '}';

                $delimiter = stripos($for_match, '-') !== FALSE ? '-' : ',';

                $bounds = explode($delimiter, $for_match);

                if ($delimiter == '-') {
                    for ($i = (int)$bounds[0]; $i <= (int)$bounds[1]; $i++) {
                        $new_settings[] = str_replace($search, $i, $setting);
                    }
                } else {
                    foreach ($bounds as $replace) {
                        $new_settings[] = str_replace($search, $replace, $setting);
                    }
                }

                $new_settings = $this->_parse_config_for($new_settings);

            } else {
                $new_settings[] = $setting;
            }
        }

        return $new_settings;
    }

    private function _get_exe($name, $os_suffix) {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Executables' . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $os_suffix;
    }

    private function _clear_dir($dir) {
        foreach (new RecursiveDirectoryIterator($dir) as $fileInfo) {
            if (preg_match('~[^\.]\.?\.$~', $fileInfo->getPathname())) {
                continue;
            }

            if ($fileInfo->isDir()) {
                $this->_clear_dir($fileInfo->getPathname());
                rmdir(realpath($fileInfo->getPathname()));
            } else {
                unlink(realpath($fileInfo->getPathname()));
            }
        }
    }

    private function _download_execs($destination_dir) {
        $url = 'http://isenselabs.com/files/uploads/nitrosmush_execs_compiled.zip';
        $local_src_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'nitrosmush_execs_compiled.zip';
        $destination_file = $destination_dir . 'nitrosmush_execs.zip';

        if (!class_exists('ZipArchive')) {
            throw new Exception("Missing ZIP extension. Please install/load PHP ZipArchive and try again");
        }

        if (!is_dir($destination_dir)) {
            if (!mkdir($destination_dir, 0755, true)) {
                throw new Exception("Could not create destination directory $destination_dir for the NitroSmush executables");
            }
        }

        $this->_clear_dir($destination_dir);

        $file_loaded = $this->_temp_path($this->_file_loaded, true);
        $file_version = $this->_temp_path($this->_file_version, true);

        if (file_exists($file_loaded) && !is_writable($file_loaded)) {
            throw new Exception("Could not clear file with loaded executables.");
        }

        if (file_exists($file_version) && !is_writable($file_version)) {
            throw new Exception("Could not clear version file.");
        }

        if (file_exists($file_loaded)) {
            unlink($file_loaded);
        }

        if (file_exists($file_version)) {
            unlink($file_version);
        }

        if (!is_dir($destination_dir)) {
            if (!mkdir($destination_dir, 0755, true)) {
                throw new Exception("Could not create destination directory $destination_dir for the NitroSmush executables");
            }
        }

        require_once NITRO_LIB_FOLDER . "browser.php";
        $browser = new NitroBrowser($url);
        $browser->setDataDrainFile($destination_file);
        $browser->max_response_size = 1024 * 1024 * 100;

        try {
            $browser->fetch();
            if ($browser->getStatusCode() !== 200) {
                throw new Exception("Unexpected server response");
            }
        } catch (Exception $e) {
            if (!file_exists($local_src_file) || !copy($local_src_file, $destination_file)) {
                if (file_exists($destination_file)) {
                    unlink($destination_file);
                }
                rmdir($destination_dir); //This is needed, because otherwise this function will not be called the next time we try to smush something, since the dir will exist
                throw new Exception("Could not download needed executable files. Error: " . $e->getMessage());
            }
        }

        $zip = new ZipArchive();
        $zip->open($destination_file);
        $zip->extractTo($destination_dir);
        $zip->close();

        unlink($destination_file);

        file_put_contents($file_version, self::VERSION);
    }

    private function _load_executable($name, $os, $config) {
        if (empty($config[$os])) return;

        $exe = $this->_get_exe($name, $config[$os]);

        if (!is_executable($exe)) {
            $this->_chmod($exe, 0555);

            if (!is_executable($exe)) {
                throw new Exception("File is not executable: " . $exe);
            }
        }

        try {
            foreach ($this->_parse_config_for($config['settings']) as $setting) {
                $this->_test_config($exe, $config['extension'], $setting, $config['quality'], 100);
            }

            $this->_executable_is_loaded($name, true);
        } catch (Exception $e) {
            
        }
    }

    private function _load_contents($file) {
        $key = 'nitrosmush.' . md5($file);

        if (!isset($GLOBALS[$key])) {
            if ($this->_is_readable($file)) {
                $GLOBALS[$key] = file_get_contents($file);
            }
        }

        return $GLOBALS[$key];
    }

    private function _executable_is_loaded($name, $load_write = false) {
        $file_loaded = $this->_temp_path($this->_file_loaded, true);

        $contents = $this->_load_contents($file_loaded);

        $loaded = stripos($contents, $name) !== FALSE;

        if (!$loaded && $load_write) {
            if ($this->_is_writable($file_loaded)) {
                file_put_contents($file_loaded, $name . PHP_EOL, FILE_APPEND);

                $key = 'nitrosmush.' . md5($file_loaded);
                unset($GLOBALS[$key]);

                return true;
            }
        }

        return $loaded;
    }

    private function _get_os_suffix() {
        $uname = $this->_os();
        $arch = $this->_arch();

        if ($this->_substr_caps($uname, 3) == 'WIN') {
            $os_suffix = 'win' . $arch;
        } else if ($this->_substr_caps($uname, 5) == 'LINUX') {
            $os_suffix = 'linux' . $arch;
        } else if ($this->_substr_caps($uname, 6) == 'DARWIN') {
            $os_suffix = 'mac' . $arch;
        } else {
            throw new Exception("php_uname('s') returned a non-supported OS: " . $uname . "! Valid values are: WIN, LINUX, DARWIN");
        }

        return $os_suffix;
    }

    private function _load_executables() {
        $folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Executables' . DIRECTORY_SEPARATOR;

        $load_errors = array();

        $this->_set_error_handler();

        try {
            if (!is_dir($folder) || !$this->_loaded_version_matches()) {
                $this->_download_execs($folder);
            }
            
            $this->_check_compatibility();

            $os_suffix = $this->_get_os_suffix();

            $file_loaded = $this->_temp_path($this->_file_loaded, true);
            if ($this->_is_writable($file_loaded)) {
                file_put_contents($file_loaded, '');
            }

            foreach ($this->_config as $filetype_regex => $filetype_config) {
                foreach ($filetype_config as $tool_name => $tool_config) {
                    try {
                        $this->_load_executable($tool_name, $os_suffix, $tool_config);
                    } catch (Exception $e) {
                        $load_errors[] = $e->getMessage();
                    }
                }
            }

            $this->_clean_temp();
        } catch (Exception $e) {
            $load_errors[] = $e->getMessage();
        }

        $this->_restore_error_handler();

        return $load_errors;
    }
}

// $smusher = new NitroSmush();
// $smusher->loadExecutables();
// $result = $smusher->smush('./Images/source.jpg', '../nitrosmush.result.jpg');
