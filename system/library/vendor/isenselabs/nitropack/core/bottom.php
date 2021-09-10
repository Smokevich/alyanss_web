<?php
session_write_close();

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'core.php';//core.php includes top.php

function writeToCacheFile() {
    if (!passesPageCacheValidation()) {
        return false;	
    }

    saveNitroProductCacheMap();
    saveNitroCategoryCacheMap();

    $cachefile = NITRO_PAGECACHE_FOLDER . generateNameOfCacheFile();

    if (!is_dir(dirname($cachefile))) {
        mkdir(dirname($cachefile), 0755, true);
    }

    if (isset($GLOBALS["nitro_final_output"])) {
        $ob_content = $GLOBALS["nitro_final_output"];
    } else {
        $ob_content = ob_get_contents();
    }

    $headers = getSpecialHeaders();
    $is_html = true;
    foreach (explode("\n", $headers) as $header) {
        if (stripos($header, 'content-type:') !== false && stripos($header, 'text/html') === false) {
            $is_html = false;
        }
    }

    if (
        $is_html &&
        getNitroPersistence('Mini.Enabled') && 
        (
            getNitroPersistence('Mini.CSSExtract') || 
            getNitroPersistence('Mini.JSExtract')
        )
    ) {

        if (!NITRO_USE_DEPRECATED_RESOURCE_EXTRACTION) {
            require_once NITRO_FOLDER . 'core' . DS . 'resources_fix_tool.php';
        } else {
            require_once NITRO_FOLDER . 'core' . DS . 'resources_fix_tool_deprecated.php';
        }

        function nitro_error_handler_bottom($errno, $errstr, $errfile, $errline) {
            return true;
        }

        set_error_handler('nitro_error_handler_bottom');

        if (!NITRO_DECOUPLE_MINIFICATION && !isAJAXRequest()) {
            try {
                $ob_content = extractHardcodedResources($ob_content);
            } catch (Exception $e) {}
        }

        restore_error_handler();
    }

    if ($is_html) {
        if (getNitroPersistence('PageCache.StoreFrontWidget') != "showNever") {
            include NITRO_INCLUDE_FOLDER . 'pagecache_widget.php';
            $widget_html = str_replace("{nitro_widget_cache_file}", base64_encode(generateNameOfCacheFile()), $widget_html);
            $widget_html = str_replace("{nitro_widget_render_time}", (microtime(true) - $GLOBALS['nitro.start.time']), $widget_html);
            $ob_content = str_replace("</body>", $widget_html . "\n</body>", $ob_content);
        }

        $ob_content = minifyHtmlIfNecessary($ob_content);

        $ob_content = addImageWHAttributesIfNecessary($ob_content);
    }

    if (empty($ob_content)) {
        // No need to write to cache if there is nothing to write
        return false;
    }

    $ob_content = str_replace("\xEF\xBB\xBF", '', $ob_content);

    $cached = fopen($cachefile, 'w');
    fwrite($cached, $ob_content);
    fclose($cached);

    if (getNitroPersistence('Compress.Enabled') && getNitroPersistence('Compress.HTML')) {  
        $ob_content = compressGzipIfNecessary($ob_content);

        $old_cachefile = $cachefile;
        $cachefile = $cachefile . '.gz';

        $cached = fopen($cachefile, 'w');
        fwrite($cached, $ob_content);
        fclose($cached);

        if (NITRO_SAVE_UNCOMPRESSED_SPACE) {
            file_put_contents($old_cachefile, '');
        }
    }

    if (!empty($headers)) {
        $headers_to_save = array();
        foreach (explode("\n", $headers) as $header) {
            if (stripos($header, 'set-cookie') !== 0) {
                $headers_to_save[] = $header;
            }
        }

        $headers_file = NITRO_HEADERS_FOLDER . generateNameOfCacheFile();
        $hf = fopen($headers_file, 'w');
        fwrite($hf, implode("\n", $headers_to_save));
        fclose($hf);
    }
}

function addImageWHAttributesIfNecessary($content) {
    if (getNitroPersistence('PageCache.AddWHImageAttributes')) {
        if (mobileCheck()) {
            return $content;
        }

        return preg_replace('/(?<=src\=)[\"\'][^\"\']*[-_]{1}(\d+)x(\d+)(-?_?[0-9]*)\.((jpe?g)|(png))[\"|\']/', '$0 width="$1" height="$2"', $content);
    }

    return $content;
}

function compressGzipIfNecessary($content) {
    $level = getNitroPersistence('Compress.HTMLLevel');

    if (getNitroPersistence('Compress.Enabled') && getNitroPersistence('Compress.HTML') && $level) {
        return gzencode($content, $level);
    }

    return $content;
}

function close_nitro() {
    writeToCacheFile();
}

close_nitro();
