<?php

function nitro_minify_css($styles) {

	if (defined('HTTP_CATALOG') || getNitroPersistence('Mini.CSSExtract')) {
	  return $styles;
	} else {
	  require_once NITRO_CORE_FOLDER . 'minify_functions.php';
	  return optimizeCSS($styles);
	}

}
