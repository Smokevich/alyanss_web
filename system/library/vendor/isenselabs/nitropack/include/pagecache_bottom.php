<?php

if (isNitroEnabled() && getNitroPersistence('PageCache.Enabled')) {
  require_once NITRO_CORE_FOLDER . 'bottom.php';
}
