<?php
$widget_html = <<<'NITROWIDGET'
<script type="text/javascript">
var nitro_xhr;
if (window.XMLHttpRequest){nitro_xhr=new XMLHttpRequest();}
else{nitro_xhr=new ActiveXObject("Microsoft.XMLHTTP");}
nitro_xhr.onreadystatechange=function(){if (nitro_xhr.readyState==4 && nitro_xhr.status==200){var nitroBarWrapper = document.createElement('div'); nitroBarWrapper.innerHTML = nitro_xhr.responseText; document.body.appendChild(nitroBarWrapper);}}
nitro_xhr.open("GET","index.php?route=extension/module/nitro/getwidget&cachefile={nitro_widget_cache_file}&render_time={nitro_widget_render_time}",true);
nitro_xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
nitro_xhr.send();
</script>
NITROWIDGET;
