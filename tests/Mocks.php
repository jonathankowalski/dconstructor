<?php

namespace Mocks;

class Mock {

    /**
     * @var \Singleton
     */
    private $singleton;

    /**
     * @var Singleton
     */
    private $nsSingleton;

    public function getSingleton(){
        return $this->singleton;
    }

    public function getNsSingleton(){
        return $this->nsSingleton;
    }
}

/**
 * Class Singleton
 * @package Mocks
 * @Singleton
 */
class Singleton {

}