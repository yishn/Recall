<?php

class Set extends Model {
    public function get_vocabularies() {
        return $this->has_many('Vocabulary');
    }
}
