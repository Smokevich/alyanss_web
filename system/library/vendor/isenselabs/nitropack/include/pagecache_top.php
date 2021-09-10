<?php

if (isNitroEnabled() && getNitroPersistence('PageCache.Enabled')) {
  if (isset($_GET['tracking'])) {
    setcookie('tracking', $_GET['tracking'], time() + 3600 * 24 * 1000, '/');
  }

  require_once NITRO_CORE_FOLDER . 'top.php';
   open_nitro();
}
