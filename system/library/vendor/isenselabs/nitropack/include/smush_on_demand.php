<?php

if (
    isNitroEnabled() &&
    (
        (
            getNitroPersistence('Smush.Method') && getNitroPersistence('Smush.Method') == 'local' && getNitroPersistence('Smush.OnDemand')
        ) ||
        (
            getNitroPersistence('Smush.Method') && getNitroPersistence('Smush.Method') == 'remote' && NITRO_FORCE_REMOTE_SMUSH_ON_DEMAND
        )
    )
) {
    if (!empty($new_image) && file_exists(DIR_IMAGE . $new_image)) {
        loadNitroLib('NitroSmush/NitroSmush');
        $smusher = new NitroSmush();
        $smusher->setTempDir(NITRO_FOLDER . 'temp');
        $smusher->setForceExecEnabled(NITRO_FORCE_ENABLE_EXEC);
        global $registry;
        $smush_error_log = new Log(date('Y-m-d') . '_nitrosmush_on_demand_error.txt');

        try {
            $filename = DIR_IMAGE . $new_image;
            $res = $smusher->smush($filename, false, getNitroPersistence('Smush.Method') && getNitroPersistence('Smush.Method') == 'remote' && NITRO_FORCE_REMOTE_SMUSH_ON_DEMAND, (int)getNitroPersistence('Smush.Quality'));
            if (!empty($res['errors']) && NITRO_DEBUG_MODE == 1) {
                $smush_error_log->write($filename . " | " . var_export($res['errors'], true));
            }
        } catch(Exception $e) {
            if (NITRO_DEBUG_MODE) {
                $smush_error_log->write($filename . " | " . $e->getMessage());
            }
            set_time_limit(30);
        }
    }
}
