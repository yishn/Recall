<?php

class Set extends Model {
    public function set() {
        return $this->belongs_to('Set');
    }
}
