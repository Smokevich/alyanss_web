<?php
class StringIterator implements Iterator {
    private $position = 0;
    private $string = null;
    private $length = 0;

    public function __construct(&$str) {
        $this->position = 0;
        $this->string = $str;
        $this->length = strlen($this->string);
    }

    public function true_rewind() {
        $this->position = 0;
    }

    public function rewind() {
        //Do nothing muhahaha
    }

    public function current() {
        return $this->string[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        $this->position++;
    }

    public function valid() {
        return isset($this->string[$this->position]);
    }

    public function peek($num_chars = 1) {
        if ($num_chars == 1) {
            if (isset($this->string[$this->position+1])) {
                return $this->string[$this->position+1];
            }
        } else {
            $result = '';
            $start_point = $this->position + 1;
            $end_point = min($this->position + $num_chars, $this->length-1);

            for($x = $start_point; $x <= $end_point; $x++) {
                $result .= $this->string[$x];
            }
            
            return $result;
        }

        return null;
    }

    public function consume($num = 1) {
        $this->position += $num;
    }
}
