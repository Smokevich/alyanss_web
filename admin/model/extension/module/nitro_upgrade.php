<?php
class ModelExtensionModuleNitroUpgrade extends ModelExtensionModuleNitro {
  public function run_upgrade() {
      if (
          !empty($this->request->post['Nitro']['PageCache']['ClearCacheOnProductEdit']) && 
          $this->request->post['Nitro']['PageCache']['ClearCacheOnProductEdit'] == 'yes'
      ) {
          try {
              $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "nitro_product_cache");
              $this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "nitro_category_cache");
              initNitroProductCacheDb();
          } catch (Exception $e) {}
      }

      $upgradeFile = getNitroUpgradeFilename();
      if (!file_exists($upgradeFile) || filemtime(__FILE__) > filemtime($upgradeFile)) {
          $this->model_extension_module_nitro->setupEventHandlers();
          touch($upgradeFile);
      }
  }
}
