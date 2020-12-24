<?php

/**
 * @file
 * Contains \Drupal\my_module\Controller\MyModuleController
*/

namespace Drupal\my_module\Controller;

class MyModuleController{

    /**
     * Generates an example page
     */

    public function test(){
        return array(
            '#markup' => t( 'Hola Mundo '),
        );
    }
}