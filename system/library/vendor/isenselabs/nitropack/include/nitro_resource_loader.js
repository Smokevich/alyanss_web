NitroWindowOnLoad = (function() {
  var fired = false;
  var handlersQueue = [];

  return {
    setFired: function() {
      fired = true;

      if (handlersQueue.length > 0) {
        do {
          (handlersQueue.shift())();
        } while(handlersQueue.length > 0);
      }
    },
    add: function(callback) {
      if (document.readyState == 'complete') {
        if (fired) {
          callback();
        } else {
          handlersQueue.push(callback);
        }
      } else {
        window.addEventListener('load', callback);
      }
    }
  }
})();

NitroWindowProxy = (function() {
  onloadHandler = null;

  window.addEventListener('load', NitroWindowOnLoad.setFired);

  return new Proxy(window, {
    set: function(target, property, value, receiver) {
      if (property == 'onload') {
        if (document.readyState == 'complete') {
          value();
          NitroWindowOnLoad.setFired();
        } else {
          if (onLoadHandler) {
            window.removeEventListener('load', onLoadHandler);
          }

          onLoadHandler = value;

          window.addEventListener('load', onLoadHandler);
        }
      } else {
        target[property] = value;
      }

      return true;
    }
  });
})();

var NitroResourceLoader = (function() {
  var autoRemoveBaseCss = {BASE_CSS_AUTO_REMOVE};

  var stylesQueue = [];
  var styleQueueSize = 0;

  var scriptsQueue = [];
  var scriptQueueSize = 0;

  var onloadScript = function(e) {
    if (--scriptQueueSize == 0) {
      var inlineScripts = document.getElementsByClassName('nitropack-inline-script');
      for (var x = 0; x < inlineScripts.length; x++) {
        var script = document.createElement('script');
        var src = inlineScripts[x].innerHTML.replace('window.onload', 'NitroWindowProxy.onload');
        src = src.replace('$(window).load', 'NitroWindowOnLoad.add');
        script.innerHTML = src;
        document.head.appendChild(script);
      }
    }
  }

  var loadQueuedScripts = function() {
    if (autoRemoveBaseCss) {
      document.getElementById('nitro-base-css').remove();
    }

    var scriptSrc = scriptsQueue.shift();

    while (scriptSrc) {
      scriptQueueSize++;
      var script = document.createElement('script');
      script.src = scriptSrc;
      script.async = false;
      script.onload = onloadScript;
      document.head.appendChild(script);

      scriptSrc = scriptsQueue.shift();
    }
  }

  var loadQueuedStyles = function() {
    var stylesParent = document.getElementById('nitro-deferred-styles');
    var div = document.createElement('div');
    div.innerHTML = stylesParent.textContent;
    styleQueueSize = div.children.length;
    document.body.appendChild(div);

    var script = document.createElement('script');
    script.innerHTML = document.getElementById('nitropack-cssrelpreload').innerHTML;
    document.head.appendChild(script);
  }

  return {
    setAutoRemoBaseCss: function (flag) {
      autoRemoveBaseCss = flag;
    },
    registerScript: function(src) {
      scriptsQueue.push(src);
    },
    registerStyle: function(href, rel, media) {
      stylesQueue.push({
        href: href,
        rel: rel,
        media: media
      });
    },
    onLoadStyle: function(style) {
      if (style.rel == 'preload') {
        style.rel = style.getAttribute('nitro-rel');
      } else if (--styleQueueSize == 0) {
        loadQueuedScripts();
      }
    },
    loadQueuedResources: function() {
      var raf = requestAnimationFrame || mozRequestAnimationFrame ||
          webkitRequestAnimationFrame || msRequestAnimationFrame;

      if (raf) raf(function() { window.setTimeout(loadQueuedStyles, 0); });
      else window.addEventListener('load', loadQueuedStyles);
    }
  }
})();
