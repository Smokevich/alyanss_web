var NitroImageGenerator = (function() {
  var canvas = document.createElement('canvas');
  var ctx = canvas.getContext('2d');

  return {
    getImage: function(width, height) {
      canvas.width = width;
      canvas.height = height;
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = 'white';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      return canvas.toDataURL("image/gif");
    }
  }
})();

var NitroImageLazyLoader = (function() {
  var watchList = [];
  var raf = requestAnimationFrame || mozRequestAnimationFrame ||
      webkitRequestAnimationFrame || msRequestAnimationFrame;

  var isElementVisible = function(el, onlyCheckInViewPort) {
    var onlyCheckInViewPort = onlyCheckInViewPort|false;

    var originalElement = el;
    var boundingRect = el.getBoundingClientRect();
    var top = Math.ceil(boundingRect.top);
    var left = Math.ceil(boundingRect.left);
    var width = Math.ceil(boundingRect.width);
    var height = Math.ceil(boundingRect.height);

    var inViewPort = (
      top < window.innerHeight &&
      left < window.innerWidth &&
      (top + height) >= 0 &&
      (left + width) >= 0
    );

    if (onlyCheckInViewPort || document.readyState !== 'complete') return inViewPort;

    if (inViewPort) {
      var corners = [
        {x: left, y: top},
        {x: left + width/2 - 1, y: top},
        {x: left + width - 1, y: top},
        {x: left, y: top + height/2 - 1},
        {x: left, y: top + height - 1},
        {x: left + width/2 - 1, y: top + height - 1},
        {x: left + width - 1, y: top + height - 1},
        {x: left + width - 1, y: top + height/2 - 1},
        {x: left + width/2, y: top + height/2}
      ];

      var coords;
      for (var i = 0; i < 4; i++) {
        coords = corners[i];
        if (originalElement == document.elementFromPoint(coords.x, coords.y)) {
          return true;
        }
      }
    }

    return false;
  }

  var loadRealImage = function(img) {
    img.src = img.getAttribute('nitro-src');
  }

  var findNitroLazyLoadImages = function(outerElement) {
    var nitroLazyLoadImages = [];
    var images = outerElement.getElementsByTagName('img');

    var img;
    for (var x = 1; x < images.length; x++) {
      img = images[x];
      if (img.hasAttribute('nitro-src') && watchList.indexOf(img) == -1) {
        watchList.push(img);
      }
    }
  }

  var loop = function() {
    var img;
    var visibleImages = [];

    for (var x = 0; x < watchList.length; x++) {
      img = watchList[x];

      if (isElementVisible(img) && img.getAttribute('nitro-src')) {
        loadRealImage(img);
        visibleImages.push(img);
      }
    }

    var index;
    for (var i = 0; i < visibleImages.length; i++) {
      index = watchList.indexOf(visibleImages[i]);
      watchList.splice(index, 1);
    }

    if (raf) raf(loop);
    else setTimeout(loop, 16);
  }

  return {
    isElementVisible: function(el) {
      return isElementVisible(el);
    },
    loadRealImage: function(img) {
      loadRealImage(img);
    },
    addImage: function(img, defaultWidth, defaultHeight) {
      img.src = NitroImageGenerator.getImage(defaultWidth, defaultHeight);

      if (isElementVisible(img, true)) {
        loadRealImage(img);
      } else {
        watchList.push(img);
      }
    },
    run: function() {
      document.addEventListener('DOMContentLoaded', function() {
        var mutationTimeout = null;

        var MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;

        var observer = new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
              findNitroLazyLoadImages(mutation.target);
            }
          });
        });

        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      });

      if (raf) raf(loop);
      else window.addEventListener('load', loop);
    }
  }
})();

NitroImageLazyLoader.run();
