<?php


namespace services;


class router
{
    private function actions(){
        return [
            '/^--get$/iu' => 'GetRss',
            '/^--last$/iu' => 'GetLastNews',
            '/^--requests?$/iu' => 'GetLastRequests',
            '/^--add$/iu' => 'AddFeed',
            '/^--help$/iu' => 'Help',
            '/^--remove$/iu' => 'DeleteRss',
            '/^--list$/iu' => 'List',
        ];    
    }
   
    public function ParseArguments($arguments){
        $action_info['status'] = false;
        $action_info['action'] = '';
        $action_info['arguments'] = [];

        $length = count($arguments);
        if($length == 1){
            $action_info['status'] = true;
            $action_info['action'] = 'Help';
            return $action_info;
        }
        
        $actions = $this->actions();

        foreach($actions as $pattern => $action){
            if(preg_match($pattern, $arguments[1]) == 1){
                $action_info['action'] = $action;
                $action_info['status'] = true;
                $action_info['arguments'] = $arguments;
                return $action_info;
            }
        }
        
        return $action_info;

    }//ParseArguments


}//class    
