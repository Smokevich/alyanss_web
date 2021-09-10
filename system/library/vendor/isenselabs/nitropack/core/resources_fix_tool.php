<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'core.php';//core.php includes top.php
require_once NITRO_CORE_FOLDER . 'minify_functions.php';

function extractHardcodedResources($content) {
    if (!isNitroEnabled() || !getNitroPersistence('Mini.Enabled')) {
        return $content;
    }
    
    loadNitroLib('HtmlDom');
    $dom = HtmlDom::fromString($content);

    $base = $dom->find('base');

    if ($base instanceof HtmlDomNode && $base->getAttribute('href')) {
        $urlToCurrentDir = $base->getAttribute('href')->value;
    } else {
        $urlToCurrentDir = getWebshopUrl();
        if (!empty($_SERVER['REQUEST_URI'])) {
            $urlToCurrentDir .= basename($_SERVER['REQUEST_URI']);
        }
    }

    $elements = $dom->find('link[rel="stylesheet"], style, script');
    $settings = getNitroPersistence();

    $cssExclude = array();
    $cssExcludeMeta = array();
    $cssLineExclude = array();
    $cssLineExcludeMeta = array();

    $jsExclude = array();
    $jsExcludeMeta = array();
    $jsLineExclude = array();
    $jsLineExcludeMeta = array();

    $cssExtractEnabled = false;
    $jsExtractEnabled = false;

    require_once NITRO_CORE_FOLDER . 'core.php';
    require_once NITRO_CORE_FOLDER . 'cdn.php';

    if (getNitroPersistence('Mini.CSSExtract')) {
        $cssExtractEnabled = true;

        if (getNitroPersistence('Mini.CSSExclude')) {
            $cssExclude = trim(getNitroPersistence('Mini.CSSExclude'), "\n\r ");
            $cssExclude = explode("\n", $cssExclude);
            foreach ($cssExclude as $k=>$stylename) {
                $stylename = html_entity_decode(trim($stylename, "\n\r "));
                if (!empty($stylename)) {
                    if (preg_match('/(.*?){{(NitroPack.*?)}}$/', $stylename, $matches)) {
                        $cssExclude[$k] = $matches[1];
                        $opts = explode('|', $matches[2]);
                        $cssExcludeMeta[$matches[1]] = array(
                            'extract' => in_array('extract', $opts) ? true : false,
                            'position' => in_array('before', $opts) ? 'before' : (in_array('after', $opts) ? 'after' : '')
                        );
                    } else {
                        $cssExcludeMeta[$stylename] = array(
                            'extract' => true,
                            'position' => getNitroPersistence('Mini.ExcludedCSSPosition')
                        );
                        $cssExclude[$k] = $stylename;
                    }
                }
            }
        }

        if (getNitroPersistence('Mini.CSSExcludeInline')) {
            $cssLineExclude = trim(getNitroPersistence('Mini.CSSExcludeInline'), "\n\r ");
            $cssLineExclude = explode("\n", $cssLineExclude);
            foreach ($cssLineExclude as $style) {
                $style = html_entity_decode(trim($style, "\n\r "));
                if (!empty($style)) {
                    if (preg_match('/(.*?){{(NitroPack.*?)}}$/', $style, $matches)) {
                        $cssLineExclude[] = $matches[1];
                        $opts = explode('|', $matches[2]);
                        $cssLineExcludeMeta[$matches[1]] = array(
                            'extract' => in_array('extract', $opts) ? true : false,
                            'position' => in_array('before', $opts) ? 'before' : (in_array('after', $opts) ? 'after' : '')
                        );
                    } else {
                        $cssLineExcludeMeta[$style] = array(
                            'extract' => true,
                            'position' => getNitroPersistence('Mini.ExcludedCSSPosition')
                        );
                        
                        $cssLineExclude[] = $style;
                    }
                }
            }
        }
    }

    if (getNitroPersistence('Mini.JSExtract')) {
        $jsExtractEnabled = true;

        if (getNitroPersistence('Mini.JSExclude')) {
            $jsExclude = trim(getNitroPersistence('Mini.JSExclude'), "\n\r ");
            $jsExclude = explode("\n", $jsExclude);
            foreach ($jsExclude as $script) {
                $script = html_entity_decode(trim($script, "\n\r "));
                if (!empty($script)) {
                    if (preg_match('/(.*?){{(NitroPack.*?)}}$/', $script, $matches)) {
                        $jsExclude[] = $matches[1];
                        $opts = explode('|', $matches[2]);
                        $jsExcludeMeta[$matches[1]] = array(
                            'extract' => in_array('extract', $opts) ? true : false,
                            'position' => in_array('before', $opts) ? 'before' : (in_array('after', $opts) ? 'after' : '')
                        );
                    } else {
                        $jsExclude[] = $script;
                    }
                }
            }
        }

        if (getNitroPersistence('Mini.JSExcludeInline')) {
            $jsLineExclude = trim(getNitroPersistence('Mini.JSExcludeInline'), "\n\r ");
            $jsLineExclude = explode("\n", $jsLineExclude);
            foreach ($jsLineExclude as $script) {
                $script = html_entity_decode(trim($script, "\n\r "));
                if (!empty($script)) {
                    if (preg_match('/(.*?){{(NitroPack.*?)}}$/', $script, $matches)) {
                        $jsLineExclude[] = $matches[1];
                        $opts = explode('|', $matches[2]);
                        $jsLineExcludeMeta[$matches[1]] = array(
                            'extract' => in_array('extract', $opts) ? true : false,
                            'position' => in_array('before', $opts) ? 'before' : (in_array('after', $opts) ? 'after' : '')
                        );
                    } else {
                        $jsLineExclude[] = $script;
                    }
                }
            }
        }

        if (NITRO_DEFAULT_EXCLUDES) {
            $jsLineExclude[] = 'flexslider';
            $jsLineExclude[] = '#button-cart';
        }
    }

    $combineInlineJS = getNitroPersistence("Mini.inlineJSCombine");

    $extractedJSFiles = array();
    $extractedJSScripts = array();
    $extractedCSSFiles = array();
        
    foreach ($elements as $el) {
        if (!($el instanceof HtmlDomNode)) continue;

        switch ($el->tagName) {
            case 'script':
                if (!$jsExtractEnabled) continue;

                $type = $el->getAttribute('type');
                if ($type && strtolower($type->value) !== 'text/javascript' && strtolower($type->value) !== 'application/javascript') continue;//skip stuff placed in script tag, which is not JavaScript. For example Google's structured data JSON

                $text = $el->getInnerText();
                $textTrimmed = trim($text);
                if (!empty($textTrimmed)) {
                    if ($combineInlineJS) {
                        $excluded = false;
                        $extract = false;
                    } else {
                        $excluded = true;
                        $extract = true;
                    }

                    foreach ($jsLineExclude as $line) {
                        if (strpos($text, $line) !== false) {
                            $excluded = true;
                            $extract = false;

                            if (!empty($jsLineExcludeMeta[$line])) {
                                $jsLineExcludeMeta[$text] = $jsLineExcludeMeta[$line];
                                $jsExclude[] = md5($text);
                                $jsExcludeMeta[md5($text)] = $jsLineExcludeMeta[$line];
                                if ($jsLineExcludeMeta[$line]['extract']) {
                                    $extract = true;
                                }
                            }

                            break;
                        }
                    }

                    if (!$excluded || $extract) {
                        if ($excluded) {
                            $extractedJSScripts[] = $text;
                        } else {
                            $extractedJSFiles[] = createTempScript($text);
                        }
                        $el->remove();
                    }
                } else {
                    $attr = $el->getAttribute('src');
                    if ($attr && !empty($attr->value) && (!nitroIsIgnoredUrl($attr->value, $jsExclude, $jsExcludeMeta) || nitroIsUrlToBeExtracted($attr->value, $jsExcludeMeta))) {
                        $extractedJSFiles[] = $attr->value;
                        $el->remove();
                    }
                }
                break;
            case 'link':
                if (!$cssExtractEnabled) continue;

                $attr = $el->getAttribute('href');
                if ($attr && !empty($attr->value)) {
                    if ((!nitroIsIgnoredUrl($attr->value, $cssExclude, $cssExcludeMeta) ||
                          nitroIsUrlToBeExtracted($attr->value, $cssExcludeMeta))) {
                        $extractedCSSFiles[] = $el;
                        $el->remove();
                    } else {
                        foreach ($cssExclude as $line) {
                            if (strpos($attr->value, $line) !== false) {
                                if (!empty($cssExcludeMeta[$line])) {
                                    $cssExclude[] = $attr->value;
                                    $cssExcludeMeta[$attr->value] = $cssExcludeMeta[$line];
                                }

                                break;
                            }
                        }
                    }
                }
                break;
            case 'style':
                if (!$cssExtractEnabled) continue;

                $text = $el->getInnerText();
                $textTrimmed = trim($text);

                if (!empty($textTrimmed)) {
                    $excluded = false;
                    $extract = false;
                    foreach ($cssLineExclude as $line) {
                        if (strpos($text, $line) !== false) {
                            $excluded = true;

                            if (!empty($cssLineExcludeMeta[$line])) {
                                $cssLineExcludeMeta[$text] = $cssLineExcludeMeta[$line];
                                $cssExclude[] = md5($text);
                                $cssExcludeMeta[md5($text)] = $cssLineExcludeMeta[$line];
                                if ($cssLineExcludeMeta[$line]['extract']) {
                                    $extract = true;
                                }
                            }

                            break;
                        }
                    }

                    if (!$excluded || $extract) {
                        $style = HtmlDom::createElement('link');
                        $style->setAttribute('href', createTempStyle($el->getInnerText(), $urlToCurrentDir));
                        $extractedCSSFiles[] = $style;
                        $el->remove();
                    }
                }
                break;
        }
    }
    
    $minJS = optimizeJS(generateJSMinificatorScripts($extractedJSFiles), $jsExclude, $jsExcludeMeta);
    $use_defer = getNitroPersistence('Mini.JSDefer');
    $jsPosition = getNitroPersistence('Mini.JSPosition');
    $jsNodes = new SplObjectStorage();
    $isJsInserted = false;

    foreach($minJS as $js_file) {
        $node = HtmlDom::createElement('script');
        $node->setAttribute('src', preg_replace('/^https?:/', '', $js_file));
        $node->setAttribute('type', 'text/javascript');

        if ($use_defer && empty($extractedJSScripts)) {
            $node->setAttribute('defer', '');
        }

        $jsNodes->attach($node);
    }

    foreach($extractedJSScripts as $script_code) {
        $node = HtmlDom::createElement('script');
        $node->setAttribute('type', 'text/javascript');
        $node->html($script_code);
        $jsNodes->attach($node);
    }

    $minCSS = optimizeCSS(generateCSSMinificatorStyles($extractedCSSFiles), $cssExclude, $cssExcludeMeta);

    $position = getNitroPersistence('Mini.CSSPosition');
    if ($position == 'bottom') {
        $insert_point = $dom->bodyNode;
    } else {
        $insert_point = $dom->headNode;
    }

    if ($position == 'bottom') {

        $base_css_file = nitroGetBaseCSSFile();

        if ($base_css_file) {
            $base_css = file_get_contents($base_css_file);
            $node = HtmlDom::createElement("style");
            $node->setAttribute("type", "text/css");
            $node->setAttribute("id", "nitro-base-css");
            $node->html($base_css);
            $dom->headNode->appendChild($node);
        }

        if ($jsPosition == 'bottom') {
            $stylesCount = count($minCSS);

            $loaderScript = HtmlDom::createElement('script');
            //$loaderScript->html(nitroMinifyJsCode(file_get_contents(NITRO_INCLUDE_FOLDER . 'nitro_resource_loader.js')));
            $loaderScript->html(nitroMinifyJsCode(str_replace('{BASE_CSS_AUTO_REMOVE}', (NITRO_BASE_CSS_AUTO_REMOVE ? 'true' : 'false'), file_get_contents(NITRO_INCLUDE_FOLDER . 'nitro_resource_loader.js'))));
            $insert_point->appendChild($loaderScript);

            $scriptsHTML = "";
            foreach ($jsNodes as $k=>$jsNode) {
                if (!$jsNode->getAttribute('src')) {
                    $jsNode->setAttribute('type', 'nitropack/inlinescript');
                    $jsNode->setAttribute('class', 'nitropack-inline-script');
                    $insert_point->appendChild($jsNode);
                } else {
                    $scriptsHTML .= 'NitroResourceLoader.registerScript("' . $jsNode->getAttribute('src')->value . '");';
                }
            }

            $registerScripts = HtmlDom::createElement('script');
            $registerScripts->html($scriptsHTML);
            $insert_point->appendChild($registerScripts);
            $isJsInserted = true;
        }
    }

    $cssHtml = '';
    foreach($minCSS as $css_file) {
        if ($position == 'top') {
            if (strpos($css_file['href'], 'system/nitro') !== false) { 
                $node = HtmlDom::createElement('style');
                $node->setAttribute('type', 'text/css');
                $node->html(file_get_contents($css_file['href']));
            } else {
                $node = HtmlDom::createElement('link');
                $node->setAttribute('rel', $css_file['rel']);
                $node->setAttribute('href', preg_replace('/^https?:/', '', $css_file['href']));
                $node->setAttribute('media', $css_file['media']);
                $node->setAttribute('type', 'text/css');
            }

            $insert_point->appendChild($node);
        } else if ($position == 'bottom') {
            $cssHtml .= '<link rel="preload" as="style" nitro-rel="' . $css_file['rel'] . '" type="text/css" media="' . $css_file['media'] . '" href="' . $css_file['href'] . '" onload="NitroResourceLoader.onLoadStyle(this)"/>';
        }
    }

    if ($position == 'bottom') {
        $noscript = HtmlDom::createElement('noscript');
        $noscript->setAttribute('id', 'nitro-deferred-styles');
        $noscript->html($cssHtml);
        $insert_point->appendChild($noscript);

        $cssrelpreload = HtmlDom::createElement('script');
        $cssrelpreload->setAttribute('type', 'nitropack/cssrelpreload');
        $cssrelpreload->setAttribute('id', 'nitropack-cssrelpreload');
        $cssrelpreload->html(nitroMinifyJsCode(file_get_contents(NITRO_INCLUDE_FOLDER . 'cssrelpreload.js')));
        $dom->bodyNode->appendChild($cssrelpreload);

        $resource_loader = HtmlDom::createElement('script');
        $resource_loader->html("NitroResourceLoader.loadQueuedResources();");
        $dom->bodyNode->appendChild($resource_loader);
    }

    $position = $jsPosition;
    if ($position == 'bottom') {
        $insert_point = $dom->bodyNode;
    } else {
        $insert_point = $dom->headNode;
    }

    if (!$isJsInserted) {
        foreach($jsNodes as $node) {
            $insert_point->appendChild($node);
        }
    }

    $include_comments = getNitroPersistence('Mini.HTMLComments');
    $minification_level = (getNitroPersistence('Mini.Enabled') && getNitroPersistence('Mini.HTML')) ? NITRO_HTML_MINIFICATION_LEVEL : 0;

    if (getNitroPersistence('CDNStandard.GenericURL') && !empty($_SERVER['HTTP_HOST'])) {
        $current_host = $_SERVER['HTTP_HOST'];
        $cdn_elements = $dom->find('link[rel="stylesheet"], script, img');
        foreach ($cdn_elements as $cdn_el) {
            $link_attr = 'src';
            if ($cdn_el->tagName == 'link') {
                $link_attr = 'href';
            }

            $el_link = $cdn_el->getAttribute($link_attr);

            if ($el_link) {
                $el_host = parse_url($el_link->value, PHP_URL_HOST);
                if (!$el_host || $el_host == $current_host) {
                    $cdn_link = nitroCDNResolve(parse_url($el_link->value, PHP_URL_PATH));
                    $cdn_el->setAttribute($link_attr, $cdn_link);
                }
            }
        }
    }

    if (getNitroPersistence('ImageLazyLoad.Enabled')) {
        $images = $dom->find('img');

        if ($images instanceof HtmlDomNode) {
            $images = array($images);
        }

        foreach ($images as $index => $img) {
            if ($img->getAttribute('data-default-src') || $img->getAttribute('data-src') || $img->getAttribute('src')) {
                $src = $img->getAttribute('data-default-src');

                if (!$src) {
                    $src = $img->getAttribute('data-src');
                }

                if (!$src) {
                    $src = $img->getAttribute('src');
                }

                if (preg_match('/-(\d+)x(\d+)(?!(\d+)x(\d+)).*?\.\w+$/', $src->value, $matches)) {
                    //$filler_image = getEmptyBase64Image($matches[1], $matches[2]);
                    //$img->setAttribute('src', $filler_image);

                    $img->setAttribute('nitro-src', $src->value);
                    $img->setAttribute('src', '');
                    $imgId = base64_encode(microtime(true) + $index);
                    $id = $img->getAttribute('id');
                    if ($id) {
                        $imgId = $id->value;
                    } else {
                        $img->setAttribute('id', $imgId);
                    }

                    $js_fill = HtmlDom::createElement('script');
                    $js_fill->html('NitroImageLazyLoader.addImage(document.getElementById("' . $imgId . '"), ' . $matches[1] . ', ' . $matches[2] . ');');
                    $img->after($js_fill);
                }
            }
        }

        $lazy_load_code = nitroMinifyJsCode(file_get_contents(NITRO_INCLUDE_FOLDER . 'nitro_lazy_load.js'));
        $lazy_load_script = HtmlDom::createElement('script');
        $lazy_load_script->html($lazy_load_code);

        //$lazy_load_start = HtmlDom::createElement('script');
        //$lazy_load_start->html("window.addEventListener('load', function(e) { NitroImageLazyLoader.run(); });");

        $dom->bodyNode->first($lazy_load_script);
        //$dom->bodyNode->appendChild($lazy_load_start);
    }

    return $dom->getHtml($include_comments, $minification_level);
}

function createTempScript($code) {
    if (!file_exists(NITRO_FOLDER.'temp') || !is_dir(NITRO_FOLDER.'temp')) {
        mkdir(NITRO_FOLDER.'temp');
    }
    
    if (!file_exists(NITRO_FOLDER.'temp'.DS.'js') || !is_dir(NITRO_FOLDER.'temp'.DS.'js')) {
        mkdir(NITRO_FOLDER.'temp'.DS.'js');
    }
    
    $scriptname = md5($code) . '.js';
    $script_path = NITRO_FOLDER.'temp'.DS.'js'.DS.$scriptname;
    $code = str_replace(array('<!--', '-->'), '', $code);
    file_put_contents($script_path, $code);
    $script_path = str_replace(array('/', '\\'), array(DS, DS), $script_path);
    return str_replace(str_replace('/', DS, dirname(DIR_APPLICATION).DS), '', $script_path);
}

function createTempStyle($code, $urlToCurrentDir) {
    if (!file_exists(NITRO_FOLDER.'temp') || !is_dir(NITRO_FOLDER.'temp')) {
        mkdir(NITRO_FOLDER.'temp');
    }
    
    if (!file_exists(NITRO_FOLDER.'temp'.DS.'css') || !is_dir(NITRO_FOLDER.'temp'.DS.'css')) {
        mkdir(NITRO_FOLDER.'temp'.DS.'css');
    }
    
    nitroCSSRelativePathsFix($code, $urlToCurrentDir);
    $scriptname = md5($code) . '.css';
    $script_path = NITRO_FOLDER.'temp'.DS.'css'.DS.$scriptname;
    file_put_contents($script_path, $code);
    $script_path = str_replace(array('/', '\\'), array(DS, DS), $script_path);
    return str_replace(str_replace('/', DS, dirname(DIR_APPLICATION).DS), '', $script_path);
}

function nitroIsIgnoredUrl($url, $ignored_urls, &$ignored_urls_meta = NULL) {
    if (!empty($ignored_urls)) {
        foreach($ignored_urls as $ignoredUrl) {
            if (!empty($ignoredUrl) && is_string($ignoredUrl)) { 
                if (strpos($url, $ignoredUrl) !== false) {
                    if ($ignored_urls_meta && !empty($ignored_urls_meta[$ignoredUrl])) {
                        $ignored_urls_meta[$url] = $ignored_urls_meta[$ignoredUrl];
                    }
                    return true;
                }
            }
        }
    }
    
    return false;
}

function nitroIsUrlToBeExtracted($url, $ignored_urls_meta) {
    if (!empty($ignored_urls_meta[$url])) {
        return $ignored_urls_meta[$url]['extract'];
    }
    
    return false;
}

function generateCSSMinificatorStyles($styles) {
    $formatted_styles = array();
    foreach ($styles as $style) {
        $href = $style->getAttribute('href')->value;
        $rel = $style->getAttribute('rel');
        $media = $style->getAttribute('media');
        $formatted_styles[md5($href)] = array(
            'href'  => $href,
            'rel'   => $rel ? $rel->value : 'stylesheet',
            'media' => $media ? $media->value : 'all'
        );
    }
    return $formatted_styles;
}

function generateJSMinificatorScripts($scripts) {
    $formatted_scripts = array();
    
    foreach ($scripts as $script) {
        $formatted_scripts[md5($script)] = $script;
    }

    return $formatted_scripts;
}

function getEmptyBase64Image($width, $height) {
    $im = imagecreatetruecolor($width, $height);
    imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, 0xFFFFFF);
    ob_start();
    imagegif($im);
    imagedestroy($im);
    $image_data = ob_get_clean();
    return 'data:image/gif;base64,' . base64_encode($image_data);
}
