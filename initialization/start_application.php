<?php

namespace initialization;

use services\router;
use controllers\main_controller;


class start_application
{
    public function Begin($arguments){
      
       $router = new router();
        
       $action_info = $router->ParseArguments($arguments);

       if(!$action_info['status']){
           echo 'Неверные параметры.' . PHP_EOL . PHP_EOL;
           $action_info['action'] = 'Help';
       }

       call_user_func_array([new main_controller(), $action_info['action']], [ $action_info['arguments'] ]);
    }

}    
