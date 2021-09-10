<?php
class HtmlDomNoScriptNode extends HtmlDomNode {
    public function parseDom(&$iterator) {
        $this->containingElement = $this;

        if ($iterator->key() > 0) {
            $iterator->next();
        } 

        foreach ($iterator as $char) {
            if ($char == '<') {
                if (strtolower($iterator->peek(9)) == '/noscript') {
                    $iterator->consume(10);
                    $this->readTillClosing($iterator);
                    return;
                }
            }
            $this->innerText .= $char;
        }
    }
}
