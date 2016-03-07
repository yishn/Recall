<?php

class Set extends Model {
    public function vocabularies() {
        return $this->has_many('Vocabulary');
    }
}
