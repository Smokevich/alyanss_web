<?php
class HtmlDomScriptNode extends HtmlDomNode {
    public function parseDom(&$iterator) {
        $inScriptString = false;
        $inScriptComment = false;
        $inScriptRegex = false;
        $inSpecialScriptContext = false;
        $scriptQuoteChar = '';
        $scriptCommentType = 'oneline';

        $this->containingElement = $this;

        if ($iterator->key() > 0) {
            $iterator->next();
        } 

        foreach ($iterator as $char) {
            if ($char == '<') {
                if (!$inScriptString && strtolower($iterator->peek(7)) == '/script') {
                    $iterator->consume(8);
                    $this->readTillClosing($iterator);
                    return;
                }
            } else if ($inScriptComment) {
                $this->innerText .= $char;
                if ($scriptCommentType == 'oneline' && $char == "\n") {
                    $inScriptComment = false;
                } elseif ($scriptCommentType == 'multiline' && $char == "*" && $iterator->peek() == '/') {
                    $inScriptComment = false;
                    $this->innerText .= '/';
                    $iterator->consume(1);
                }
                continue;
            } else {
                if (($char == '"' || $char == "'")) {
                    if (!$inScriptString && !$inScriptComment && !$inScriptRegex) {
                        $inScriptString = true;
                        $scriptQuoteChar = $char;
                    } elseif ($char == $scriptQuoteChar) {
                        $inScriptString = false;
                        $scriptQuoteChar = '';
                    }
                } elseif ($inScriptString && $char == '\\') {
                    if ($iterator->peek() == $scriptQuoteChar) {
                        $char .= $scriptQuoteChar;// Append to the char so this can be included in the innerText
                        $iterator->consume(1);
                    }
                } elseif (!$inScriptString && $char == '/') {
                    switch ($iterator->peek()) {
                    case '/':
                        $char .= '/';// Append to the char so this can be included in the innerText
                        $iterator->consume(1);
                        $inScriptComment = true;
                        $scriptCommentType = 'oneline';
                        break;
                    case '*':
                        $char .= '*';// Append to the char so this can be included in the innerText
                        $iterator->consume(1);
                        $inScriptComment = true;
                        $scriptCommentType = 'multiline';
                        break;
                    default:
                        if ($inScriptRegex) {
                            $inScriptRegex = false;
                        } else if ($inSpecialScriptContext) {
                            $inScriptRegex = true;
                        }
                    }
                } elseif ($char == '(' || $char == '=' || $char == ',' || $char == ';') {
                    $inSpecialScriptContext = true;
                } elseif (!$this->isSpaceChar($char)) {
                    $inSpecialScriptContext = false;
                }
            }
            $this->innerText .= $char;
        }
    }
}
