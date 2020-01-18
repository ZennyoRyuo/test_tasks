<?php

final class undef {
    public function __toString() {
        return "undef";
    }
};

class BinarySearchLookup {
    private $fp;
    private $length;
    
    private $buffer;
    private $buffer_start = null;
    private $buffer_end = null;
    
    private $kv_separator;
    private $line_separator;
    private $buffer_size;
    
    public function __construct($filename, $kv_separator = "\x09", $line_separator = "\x0A", $buffer_size = 4096) {
        $this->fp = fopen($filename, 'r');
        $this->length = filesize($filename);
        
        $this->kv_separator = $kv_separator;
        $this->line_separator = $line_separator;
        $this->buffer_size = $buffer_size;
    }
    
    public function findValue($key) {
        if (PHP_INT_SIZE <= 4) {
            return new undef;
        }
        
        /* 
            These pointers mush always land on
            key start (i.e some line separator before)
            and value end (i.e. some line separator after)
        */
        $left_key_start = 0;
        $right_value_end = $this->length;
        
        $key .= $this->kv_separator;
        
        while (($right_value_end - $left_key_start) > 0) {
            $mid = ($right_value_end + $left_key_start) >> 1;
            $buffer_key_cursor = $this->findKeyStart($mid);
            $buffer_key_start = $buffer_key_cursor;
            $collector = null;
            $diff = null;
            $key_len = strlen($key);
            for ($i = 0; $i < $key_len; $i++, $buffer_key_cursor++) {
                if ($buffer_key_cursor == $this->buffer_end) {
                    $this->fillBuffer($buffer_key_cursor, $buffer_key_cursor + $this->buffer_size);
                    $buffer_key_cursor = $this->buffer_start;
                }
                $c = $this->buffer{$buffer_key_cursor - $this->buffer_start};
                if ($c == $this->kv_separator && $i != $key_len - 1) {
                    $left_key_start = $this->findValueEnd($buffer_key_cursor, $collector);
                    continue 2;
                } else {
                    $diff = ord($key{$i}) - ord($c);
                    if ($diff > 0) {
                        $left_key_start = $this->findValueEnd($buffer_key_cursor, $collector);
                        continue 2;
                    } else if ($diff < 0) {
                        $right_value_end = $buffer_key_start;
                        continue 2;
                    }
                }
            }
            $collector = "";
            $this->findValueEnd($buffer_key_cursor, $collector);
            return $collector;
        }
        return new undef();
    }
    
    private function findKeyStart($cursor) {
        while (true) {
            $start = max(0, $cursor - $this->buffer_size);
            $this->fillBuffer($start, $cursor);
            for ($i = $this->buffer_end - 1; $i >= $this->buffer_start; $i--) {
                $c = $this->buffer{$i - $this->buffer_start};
                if ($c == $this->line_separator) {
                    return $i + 1;
                } else if ($this->buffer_start == 0 && $i == 0) {
                    return $i;
                }
            }
            $cursor -= $this->buffer_size;
        }
    }
    
    private function findValueEnd($cursor, &$collector) {
         while (true) {
            $end = min($this->length, $cursor + $this->buffer_size);
            $this->fillBuffer($cursor, $end);
            for ($i = $this->buffer_start; $i < $this->buffer_end; $i++) {
                $c = $this->buffer{$i - $this->buffer_start};
                if ($c == $this->line_separator) {
                    return $i + 1;
                } else {
                    if ($this->buffer_end == $this->length && $i == $this->buffer_end - 1) {
                        return $i + 1;
                    }
                    if ($collector !== null) {
                        $collector .= $c;
                    }
                }
            }
            $cursor += $this->buffer_size;
        }
    }
    
    private function fillBuffer($start, $end) {
        fseek($this->fp, $start);
        $this->buffer_start = $start;
        if ($end - $start > 0)
            $this->buffer = fread($this->fp, $end - $start);
        else
            $this->buffer = "";
        $this->buffer_end = $end;
    }
}

function findValue(string $filename, string $key) {
    return (new BinarySearchLookup($filename))->findValue($key);
}

