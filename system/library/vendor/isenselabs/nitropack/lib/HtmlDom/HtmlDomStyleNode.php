<?php
class HtmlDomStyleNode extends HtmlDomNode {
    public function parseDom(&$iterator) {
        $this->containingElement = $this;

        if ($iterator->key() > 0) {
            $iterator->next();
        } 

        foreach ($iterator as $char) {
            if ($char == '<') {
                if (strtolower($iterator->peek(6)) == '/style') {
                    $iterator->consume(7);
                    $this->readTillClosing($iterator);
                    return;
                }
            }
            $this->innerText .= $char;
        }
    }
}
