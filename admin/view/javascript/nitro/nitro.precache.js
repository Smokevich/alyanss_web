nitro.register('precache', (function($){
  var precache_total;
  var config;
  var processHandle;
  var progressInterval = null;

  var init = function(callback) {
    precache_total = 0;
    processHandle = '';

    if (progressInterval !== null) {
      clearInterval(progressInterval);
    }

    message('');
    callback();
  };

  var set_progress = function (percent) {
    $(config.progressbar_selector).css('width', percent + '%');
  }

  var message = function(message) {
    $(config.output_selector).html(message);
  }

  var checkForActiveProcess = function() {
    if (!config.stack_url || !config.license) return;

    $.ajax({
      url: '//nitrosmush.com/precache/getHandle.php',
      type: 'GET',
      dataType: 'json',
      data: {
        stackURL: config.stack_url,
        licenseKey: config.license,
      },
      success: function(json) {
        if (json.success) {
          processHandle = json.data.processHandle;
          checkProgress(function(json) {
            if (json.success && json.data.total && json.data.total > json.data.precached) {
              progressInterval = setInterval(checkProgress, 1000);
            }
          });
        }
      },
      error: function() {
        message('Error: Could not schedule a precache process. Please contact technical support.');
      }
    });
  }

  var schedulePrecache = function() {
    message("Scheduling a precache job. Please wait..");

    $.ajax({
      url: '//nitrosmush.com/precache/schedule.php',
      type: 'POST',
      dataType: 'json',
      data: {
        stackURL: config.stack_url,
        licenseKey: config.license,
        MID: '12658',
        domain: config.domain
      },
      success: function(json) {
        if (json.success) {
          processHandle = json.data.processHandle;
          precache_total = json.data.scheduledEntriesCount;
          message(precache_total + ' entries have been scheduled successfilly. The process will start shortly.');
          progressInterval = setInterval(checkProgress, 1000);
        } else {
          message('Error (' + json.error_code + '): ' + json.error);
        }
      },
      error: function() {
        message('Error: Could not schedule a precache process. Please contact technical support.');
      }
    });
  }

  var checkProgress = function(callback) {
    if (!callback) {
      callback = function(json) {
        if (json.success && json.data.total) {
          set_progress(json.data.percent);
          message(json.data.precached + '/' + json.data.total + ' precached pages');

          if (json.data.total <= json.data.precached) {
            clearInterval(progressInterval);
          }
        } else {
          clearInterval(progressInterval);
        }
      };
    }

    $.ajax({
      url: '//nitrosmush.com/precache/progress.php',
      type: 'GET',
      dataType: 'json',
      data: {
        processHandle: processHandle
      },
      success: callback
    });
  }

  var cancelPrecache = function(success, error) {
    $.ajax({
      url: '//nitrosmush.com/precache/cancel.php',
      type: 'POST',
      dataType: 'json',
      data: {
        processHandle: processHandle
      },
      success: success,
      error: error
    });
  }

  var start = function() {
    init(function() {
      set_progress(0);
      schedulePrecache();
    });
  }

  var abort = function() {
    set_progress(0);
    clearInterval(progressInterval);
    progressInterval = null;

    cancelPrecache(function () {
      message('Task aborted.');
    }, function() {
      message('Error: Could not cancel the process at the moment. If the issue persists, please contact technical support.');
    });
  }

  return {
    setConfig: function(c) {
      config = c;
      checkForActiveProcess();
    },
    start: function() {
      start();
    },
    abort: function() {
      abort();
    }
  }
})(jQuery));
