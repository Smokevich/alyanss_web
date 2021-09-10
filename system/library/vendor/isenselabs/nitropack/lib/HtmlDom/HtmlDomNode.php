<?php
class HtmlDomNode {
    public $tagName;
    public $children;
    public $parent;
    public $root;
    public $detached;
    public $htmlNode;
    public $headNode;
    public $bodyNode;
    public $isVoid;

    protected $attributes;
    protected $comments;
    protected $innerText;
    protected $doctype = '';
    protected $containingElement;

    public static $self_closing_tags = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');
    public static $head_tags = array('meta', 'link', 'script', 'noscript', 'style', 'template', 'title', 'base');
    public static $optional_closing_tags_map = array(
        'p' => array('address', 'article', 'aside', 'blockquote', 'div', 'dl', 'fieldset', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'main', 'nav', 'ol', 'p', 'pre', 'section', 'table', 'ul'),
        'dt' => array('dt', 'dd'),
        'dd' => array('dd', 'dt'),
        'li' => array('li'),
        'rb' => array('rb', 'rt', 'rtc', 'rp'),
        'rt' => array('rb', 'rt', 'rtc', 'rp'),
        'rp' => array('rb', 'rt', 'rtc', 'rp'),
        'rtc' => array('rb', 'rtc', 'rp'),
        'optgroup' => array('optgroup'),
        'option' => array('option', 'optgroup'),
        'thead' => array('tbody', 'tfoot'),
        'tbody' => array('tbody', 'tfoot'),
        'tfoot' => array('tbody'),
        'tr' => array('tr'),
        'td' => array('tr', 'td', 'th'),
        'th' => array('td', 'th'),
    );

    public function __construct($tagName, $parent = NULL, $root = NULL) {
        $this->tagName = strtolower($tagName);
        $this->parent = $parent;

        $this->htmlNode = NULL;
        $this->headNode = NULL;
        $this->bodyNode = NULL;

        $this->isVoid = false;
        $this->detached = true;
        $this->attributes = array();
        $this->children = new SplObjectStorage();
        $this->comments = new SplObjectStorage();

        $this->innerText = '';

        if ($root) {
            $this->root = $root;
        } else {
            $this->root = $this;

            if (!$this->tagName) {//This is important for the cases where somebody creates a new node from outside of this class like this `new HtmlDomNode('div')` and he then wants to append this to an existing DOM for example
                $this->htmlNode = new HtmlDomNode('html', $this, $this);
                $this->headNode = new HtmlDomNode('head', $this->htmlNode, $this);
                $this->bodyNode = new HtmlDomNode('body', $this->htmlNode, $this);
            }
        }

    }

    public function getDoctype() {
        return $this->root->doctype;
    }

    public function addComment(&$comment) {
        $this->comments->attach(new HtmlDomComment($comment));
        $this->innerText .= '{comment_content}';
    }

    public function addCdata(&$cdata) {
        $this->innerText .= $cdata;
    }

    public function setAttribute($name, $value, $attrWrapperChar = false, $override = true) {
        if (empty($name)) {
            return;
        }

        if ($this->isVoid) {
            switch ($this->tagName) {
            case 'html':
                $this->root->htmlNode->setAttribute($name, $value, $attrWrapperChar, $override);
                return;
            case 'head':
                $this->root->headNode->setAttribute($name, $value, $attrWrapperChar, $override);
                return;
            case 'body':
                $this->root->bodyNode->setAttribute($name, $value, $attrWrapperChar, $override);
                return;
            }
        }

        if ($attrWrapperChar === false) {
            if (strpos($value, '"') === false) {
                $attrWrapperChar = '"';
            } else if (strpos($value, "'") === false) {
                $attrWrapperChar = "'";
            } else {
                $attrWrapperChar = '';
            }
        }

        $name = strtolower($name);
        $attr = $this->getAttribute($name);
        if (!$attr || $override) {
            if ($attr) {
                unset($this->attributes[$name]);
            }

            $attr = new DomNodeAttribute($name, $value, $attrWrapperChar);
            $this->attributes[$name] = $attr;;
        }
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function getComments() {
        return $this->comments;
    }

    public function getAttribute($name) {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : NULL;
    }

    public function remove($node = null) {
        if (!$node) {
            $this->parent->remove($this);
        } else {
            foreach ($this->children as $k=>$child) {
                if ($node == $child) {
                    $text_parts = explode('{child_node_content}', $this->innerText);
                    $combined_parts = array_splice($text_parts, $k, 2);
                    array_splice($text_parts, $k, 0, implode('', $combined_parts));
                    $this->innerText = implode('{child_node_content}', $text_parts);
                    break;
                }
            }
            $this->children->detach($node);
            $node->detached = true;
            $node->parent = NULL;
            $node->root = NULL;
        }
    }

    public function appendChild(&$node) {
        $this->children->attach($node);
        $node->parent = $this;
        $node->root = $this->root;
        $this->innerText .= '{child_node_content}';
        $node->detached = false;
    }

    public function after(&$node) {
        $detached_nodes = new SplObjectStorage();
        $start_detaching = false;
        foreach ($this->parent->children as $child) {
            if ($start_detaching) {
                $detached_nodes->attach($child);
            }
            if ($child == $this) $start_detaching = true;
        }

        foreach ($detached_nodes as $child) {
            $child->remove();
        }

        $this->parent->appendChild($node);

        foreach ($detached_nodes as $child) {
            $this->parent->appendChild($child);
        }
    }

    public function before(&$node) {
        $detached_nodes = new SplObjectStorage();
        $start_detaching = false;
        $parent = $this->parent;

        foreach ($parent->children as $child) {
            if ($child == $this) $start_detaching = true;
            if ($start_detaching) {
                $detached_nodes->attach($child);
            }
        }

        foreach ($detached_nodes as $child) {
            $child->remove();
        }

        $parent->appendChild($node);

        foreach ($detached_nodes as $child) {
            $parent->appendChild($child);
        }
    }

    public function first(&$node) {
        if ($this->children->count()) {
            $this->children->rewind();
            $this->children->current()->before($node);
        } else {
            $this->appendChild($node);
        }
    }

    public function html($html) {
        foreach ($this->children as $child) {
            $child->remove();
        }

        $iterator = new StringIterator($html);
        $this->parseDom($iterator);
    }

    public function find($selector, &$matches = null, &$selectorObject = null, $innerCall = false) {
        if (!$matches) {
            $matches = new SplObjectStorage();
        }

        if (empty($selector)) return $matches;

        if (!$selectorObject) {
            $selectorObject = new HtmlDomSelector($selector);
        }

        if (!$this->isVoid && $selectorObject->test($this)) {
            $matches->attach($this);
        }

        foreach ($this->children as $child) {
            $child->find($selector, $matches, $selectorObject, true);
        }

        if (!$innerCall) {
            if ($matches->count() == 1) {
                $matches->rewind();
                return $matches->current();
            }

            return $matches;
        }
    }

    public function isSelfClosing() {
        return in_array($this->tagName, HtmlDomNode::$self_closing_tags);
    }

    public function getInnerText() {
        $texts = explode('{child_node_content}', str_replace('{comment_content}', '', $this->innerText));
        foreach ($this->children as $k => $child) {
            if (isset($texts[$k])) {
                $texts[$k] .= $child->getInnerText();
            }
        }

        return implode('', $texts);
    }

    public function dumpDom($lvl = 0) {
        foreach ($this->children as $child) {
            $tagHeader = str_repeat('| ', $lvl/2) . $child->tagName;

            foreach ($child->getAttributes() as $attr) {
                if ($attr->value !== false) {
                    $tagHeader .= ' ' . $attr->name . '=' . $attr->wrapperChar . $attr->value . $attr->wrapperChar;
                } else {
                    $tagHeader .= ' ' . $attr->name;
                }
            }

            if (!$child->isVoid) {
                echo $tagHeader . "\n";
            } else {
                echo $tagHeader . " (VOID)\n";
            }

            $child->dumpDom($lvl+2);
        }
    }

    public function getHtml($include_comments = false, $minify_level = 0) {
        if (!empty($this->tagName) && !$this->isVoid) {
            $tagHeader = '<' . $this->tagName;
            foreach ($this->attributes as $attr) {
                if ($attr->value !== false) {
                    $tagHeader .= ' ' . $attr->name . '=' . $attr->wrapperChar . $attr->value . $attr->wrapperChar;
                } else {
                    $tagHeader .= ' ' . $attr->name;
                }
            }
            if ($this->isSelfClosing()) return $tagHeader . '/>';

            $tagHeader .= '>';
        }

        $tmp_html = $this->getInnerHtml($include_comments, $minify_level);

        if (!empty($this->tagName)) {
            if (!$this->isVoid) {
                return $tagHeader . $tmp_html . '</' . $this->tagName . '>';
            } else {
                return $tmp_html;
            }
        } else {
            return $this->root->doctype . $tmp_html;
        }
    }

    public function getInnerHtml($include_comments = false, $minify_level = 0) {
        $tmp_html = $this->innerText;

        if ($include_comments) {
            $texts = explode('{comment_content}', $tmp_html);
            foreach ($this->comments as $k => $comment) {
                if (isset($texts[$k])) {
                    $texts[$k] .= $comment->text;
                }
            }
            $tmp_html = implode('', $texts);
        } else {
            $tmp_html = str_replace('{comment_content}', '', $tmp_html);
        }


        if ($minify_level && !in_array($this->tagName, array("script", "style", "pre"))) {
            $tmp_html = str_replace(array("\n", "\r", "\t", "\f"), " ", $tmp_html);
            $tmp_html = preg_replace("/\s+/", " ", $tmp_html);
        }

        $texts = explode('{child_node_content}', $tmp_html);
        foreach ($this->children as $k => $child) {
            if (isset($texts[$k])) {
                $texts[$k] .= $child->getHtml($include_comments, $minify_level);
            }
        }

        $tmp_html = implode('', $texts);

        if ($minify_level === 2 && !in_array($this->tagName, array("script", "style", "pre"))) {
            $tmp_html = preg_replace('/([^\s])\s+$/', '$1', $tmp_html);
        }

        return $tmp_html;
    }

    public function parseDom(&$iterator) {
        if ($this->isSelfClosing()) return;

        $tagNameCached = $this->tagName;

        $inTag = false;
        $inTagHeader = false;
        $tagNameRead = false;

        $tagName = '';
        $buffer = '';
        $attributeName = '';
        $readingAttrValue = false;
        $attrValueWrapperChar = '';
        $inComment = false;

        $nextNode = null;

        $this->containingElement = $this;

        if ($iterator->key() > 0) {
            $iterator->next();//Move to the next character because foreach does not call next() when starting iteration and the the node will not be able to read correctly. It will read the last > from the tag.
        } 

        foreach ($iterator as $char) {
            $nextChar = $iterator->peek();

            if (!$inTagHeader) {
                if ($char == '<') {
                    if ($nextChar == '!' || $nextChar == '?') {
                        if ($iterator->peek(3) == "!--") {
                            $this->parseComment($iterator);
                            continue;
                        } elseif (strtolower($iterator->peek(4)) == '!doc' || strtolower($iterator->peek(4) == '?xml')) {
                            foreach ($iterator as $subchar) {//read untill the closing >
                                $this->root->doctype .= $subchar;//Don't touch this!
                                if ($subchar == '>') break;
                            }
                            $buffer = '';
                            continue;
                        } else if ($iterator->peek(8) == "![CDATA[") {
                            $this->parseCdata($iterator);
                            continue;
                        }
                    }

                    if ($nextChar != ' ' && $nextChar != '<') {
                        $inTagHeader = true;
                        $tagNameRead = false;
                        $tagName = '';
                        $buffer = '';

                        if ($nextChar == '/') {
                            $tagNameLen = strlen($tagNameCached);
                            if (strtolower($iterator->peek($tagNameLen+1)) == '/' . $tagNameCached) {
                                $iterator->consume($tagNameLen+2);
                                $this->readTillClosing($iterator);
                                return;
                            }
                        }
                        continue;
                    }
                }

                $this->innerText .= $char;
            } else {
                $buffer .= $char;

                if (!$tagNameRead) {
                    if ($char == '>' || $this->isSpaceChar($char)) {
                        $tagName = rtrim($buffer, $char);

                        if ($tagName[0] == "/") {
                            $tagNameLower = strtolower(substr($tagName, 1));
                            $parent = $this->parent;

                            while ($parent) {
                                if ($tagNameLower == $parent->tagName) {
                                    $iterator->consume(-(strlen($tagName) + 2));//This way when we return to the parseDom of the parent we will be able to read it's closing tag properly
                                    return;
                                }
                                $parent = $parent->parent;
                            }

                            if ($char != '>') {
                                $this->readTillClosing($iterator);
                            }

                            $inTagHeader = false;
                            $tagNameRead = false;
                            $tagName = '';
                            $buffer = '';
                            continue;
                        } else if (empty($tagName) || is_numeric($tagName)) {//is_numeric is here to handle stuff like that "<3". Some people have this in their HTML...
                            $this->innerText .= '<' . $buffer;
                            $inTagHeader = false;
                            $nextNode = null;
                            $tagName = '';
                            $tagNameRead = false;
                            $buffer = '';
                            continue;
                        } else {
                            $tagNameRead = true;
                            $tagNameTrimmed = strtolower(trim($tagName, '/'));//trim is to handle tags like this <br/>, because in this case the detected tag will be br/ not br

                            if ($tagNameTrimmed === 'script') {
                                $nextNode = new HtmlDomScriptNode($tagNameTrimmed, $this, $this->root);
                            } elseif ($tagNameTrimmed === 'style') {
                                $nextNode = new HtmlDomStyleNode($tagNameTrimmed, $this, $this->root);
                            } elseif ($tagNameTrimmed === 'noscript') {
                                $nextNode = new HtmlDomNoScriptNode($tagNameTrimmed, $this, $this->root);
                            } else {
                                $nextNode = new HtmlDomNode($tagNameTrimmed, $this, $this->root);
                            }

                            switch ($tagNameTrimmed) {
                            case 'html':
                                if (!$this->root->bodyNode->detached) {//This may need to check the htmlNode object instead of body - so feel free to experiment if needed
                                    $nextNode->isVoid = true;

                                    if ($this == $this->root) {
                                        $this->containingElement = $this->root->bodyNode;
                                    }
                                } else {
                                    $this->containingElement = $this->root;
                                    $nextNode = $this->root->htmlNode;
                                }
                                break;
                            case 'head':
                                if (!$this->root->bodyNode->detached) {//This may need to check the headNode object instead of body - so feel free to experiment if needed
                                    $nextNode->isVoid = true;

                                    if ($this == $this->root) {
                                        $this->containingElement = $this->root->bodyNode;
                                    }
                                } else {
                                    $this->containingElement = $this->root->htmlNode;
                                    $nextNode = $this->root->headNode;

                                    if ($this->root->htmlNode->detached) {
                                        $this->root->appendChild($this->root->htmlNode);
                                    }
                                }
                                break;
                            case 'body':
                                if (!$this->root->bodyNode->detached) {
                                    $nextNode->isVoid = true;

                                    if ($this == $this->root) {
                                        $this->containingElement = $this->root->bodyNode;
                                    }
                                } else {
                                    $this->containingElement = $this->root->htmlNode;
                                    $nextNode = $this->root->bodyNode;

                                    if ($this->root->htmlNode->detached) {
                                        $this->root->appendChild($this->root->htmlNode);
                                    }

                                    if ($this->root->headNode->detached) {
                                        $this->root->htmlNode->appendChild($this->root->headNode);
                                    }
                                }
                                break;
                            default:
                                if ($this->root->bodyNode->detached) {
                                    if (in_array($tagNameTrimmed, HtmlDomNode::$head_tags)) {
                                        $this->containingElement = $this->root->headNode;

                                        if ($this->root->htmlNode->detached) {
                                            $this->root->appendChild($this->root->htmlNode);
                                        }

                                        if ($this->root->headNode->detached) {
                                            $this->root->htmlNode->appendChild($this->root->headNode);
                                        }
                                    } else {
                                        $this->containingElement = $this->root->bodyNode;

                                        if ($this->root->htmlNode->detached) {
                                            $this->root->appendChild($this->root->htmlNode);
                                        }

                                        if ($this->root->headNode->detached) {
                                            $this->root->htmlNode->appendChild($this->root->headNode);
                                        }

                                        if ($this->root->bodyNode->detached) {
                                            $this->root->htmlNode->appendChild($this->root->bodyNode);
                                        }
                                    }
                                } else if ($this == $this->root){
                                    $this->containingElement = $this->root->bodyNode;
                                }
                                break;
                            }

                            $readingAttrValue = false;
                        }
                        $buffer = '';
                        $iterator->consume(-1);
                    }
                } else {
                    if ($char == '>' && (!$readingAttrValue || $attrValueWrapperChar == '')) {
                        if ($nextNode) {
                            if ($attrValueWrapperChar == '') {
                                if ($readingAttrValue) {
                                    $attrValue = rtrim($buffer, $char);
                                    if ($nextNode->isSelfClosing() && substr($attrValue, -1) == '/') {
                                        $attrValue = substr($attrValue, 0, -1);
                                    }
                                    $nextNode->setAttribute($attributeName, $attrValue, $attrValueWrapperChar, false);
                                    $readingAttrValue = false;
                                    $attrValueWrapperChar = '';
                                } else {
                                    $attributeName = trim($buffer, " >");
                                    if ($attributeName && $attributeName != '/') {
                                        $nextNode->setAttribute($attributeName, false, $attrValueWrapperChar, false);
                                    }
                                }
                                $buffer = '';
                            }

                            while ($nextNode) {
                                if (isset(HtmlDomNode::$optional_closing_tags_map[$tagNameCached]) && in_array($nextNode->tagName, HtmlDomNode::$optional_closing_tags_map[$tagNameCached])) return $nextNode;

                                if ($nextNode->detached) {
                                    $this->containingElement->appendChild($nextNode);
                                }

                                $nextNode = $nextNode->parseDom($iterator);
                            }
                        }

                        $inTagHeader = false;
                        $nextNode = null;
                        $tagName = '';
                        $tagNameRead = false;
                        continue;
                    } else {
                        if (!$readingAttrValue) {
                            if ($nextChar == '=' || $this->isSpaceChar($nextChar)) {
                                $attributeName = trim($buffer);
                                $readingAttrValue = true;
                                $attrValueWrapperChar = '';
                                $iterator->consume(2);//consume the next "=" or space char so we can start reading the value
                                $buffer = '';
                                $passedThroughEquals = $nextChar == '=';

                                foreach ($iterator as $subchar) {
                                    if (!$this->isSpaceChar($subchar)) {
                                        if ($subchar == "'" || $subchar == '"') {
                                            $iterator->consume(-1);
                                        } else if ($subchar == '=') {
                                            $passedThroughEquals = true;
                                            continue;
                                        } else {
                                            $iterator->consume(-1);

                                            if (!$passedThroughEquals) {
                                                $nextNode->setAttribute($attributeName, false, '', false);//if there were spaces after the = and the next non space char and this char is not ['"] then the attribute does not have a value and this is probably another attribute
                                                $readingAttrValue = false;
                                                $attrValueWrapperChar = '';
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                        } else {
                            if ($char == '"' || $char == "'") {
                                if($attrValueWrapperChar == $char) {
                                    $nextNode->setAttribute($attributeName, rtrim($buffer, $char), $attrValueWrapperChar, false);
                                    $readingAttrValue = false;
                                    $attrValueWrapperChar = '';
                                    $buffer = '';
                                } else if ($buffer == $char) {
                                    $attrValueWrapperChar = $char;
                                    $buffer = '';
                                }
                            } elseif ($attrValueWrapperChar == '' && $this->isSpaceChar($char)) {
                                $nextNode->setAttribute($attributeName, rtrim($buffer, $char), $attrValueWrapperChar, false);
                                $readingAttrValue = false;
                                $buffer = '';
                            }
                        }
                    }
                }
            }
        }
    }

    protected function parseComment($iterator) {
        $comment = '';
        foreach ($iterator as $subchar) {
            $comment .= $subchar;
            if ($subchar == '-'){
                $isCommentEnd = false;

                if ($iterator->peek(2) == '->') {
                    $iterator->consume(2);
                    $isCommentEnd = true;
                } else if ($iterator->peek(3) == '-!>') {
                    $iterator->consume(3);
                    $isCommentEnd = true;
                }

                if ($isCommentEnd) {
                    $comment .= '->';
                    if ($this->containingElement == $this->root && !$this->root->bodyNode->detached) {
                        $this->root->bodyNode->addComment($comment);
                    } else {
                        $this->containingElement->addComment($comment);
                    }
                    break;
                }
            }
        }
    }

    protected function parseCdata($iterator) {
        $cdata = '';
        foreach ($iterator as $subchar) {
            $cdata .= $subchar;
            if ($subchar == ']'){

                if ($iterator->peek(2) == ']>') {
                    $iterator->consume(2);

                    $cdata .= ']>';

                    if ($this->containingElement == $this->root && !$this->root->bodyNode->detached) {
                        $this->root->bodyNode->addCdata($cdata);
                    } else {
                        $this->containingElement->addCdata($cdata);
                    }
                    break;
                }
            }
        }
    }

    protected function isSpaceChar($char) {
        return $char == ' ' || $char == "\t" || $char == "\r" || $char == "\n" || $char == "\f";
    }

    protected function readTillClosing(&$iterator) {
        $subchar = $iterator->current();
        while ($subchar != '>' && $iterator->valid()) {
            $iterator->next();
            $subchar = $iterator->current();
        }
    }
}
