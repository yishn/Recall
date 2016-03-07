<?php

class Vocabulary extends Model {
    public function set() {
        return $this->belongs_to('Set');
    }
}
