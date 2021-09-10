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
