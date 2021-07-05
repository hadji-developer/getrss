<?php

namespace controllers;

use models\main_model;
use views\main_view;

class main_controller
{
    public function __construct(){
        $this->model = new main_model();
        $this->view = new main_view();
    }    
    
    private $model;
    private $view;

    public $cm = [
        'list' => "Список всех псевдонимов можно вывести командой getrss --list",
        'no_alias' => "Такого псевдонима нет в базе.",
        'input_alias' => "Введите псевдоним rss-ссылки",
    ]; //common messages - общие сообщения ошибок


    public function List($args){
        $this->view->ShowAliasesUrls($this->model->GetAliasesUrls());
    }

    public function Help($args){
        $this->view->ShowHelp();
    }


    private function CheckArgsAlias($args, $args_number){
        if(count($args) < $args_number){
            exit( $this->cm['input_alias'] . PHP_EOL . $this->cm['list'] . PHP_EOL);
        }

        $temp = $this->model->CheckAlias($args[$args_number - 1]);
        if($temp['check_status']){
            exit($this->cm['no_alias'] . PHP_EOL . $this->cm['list'] . PHP_EOL);     
        }
        return $temp;
    }//CheckArgs


    public function AddFeed($args){
        if(count($args) < 4){
            exit('Введите ссылку на rss-ленту и ее короткий псевдоним'. PHP_EOL);
        }
        if(preg_match('/^https?:\/\/.+$/iu', $args[2]) != 1){
            exit('Ссылка введена неправильно. В ссылке обязательно наличие протокола http(s).' . PHP_EOL);
        }
        if(!$this->model->CheckAlias($args[3])['check_status']){
            exit('Такой псевдоним уже есть.' . PHP_EOL);
        }
        if(mb_strlen($args[3]) > 50){
            exit('Длина псевдонима не должна превышать 50 символов' . PHP_EOL);            
        }
         
        $this->model->AddFeed($args[2], $args[3]);
        $this->GetRss(['', '--get', $args[3] ]);
    }//AddFeed


    public function DeleteRss($args){
        $this->CheckArgsAlias($args, 3);

        echo "Вы уверены? Введите 'да' или 'нет'" . PHP_EOL;
        $stdin = fopen('php://stdin', 'r');
        $line = fgets($stdin);
        while(preg_match('/^(да|нет)$/iu', $line) !== 1){
            echo "Введите 'да' или 'нет'" . PHP_EOL;
            $line = fgets($stdin);
        }
        
        fclose($stdin);

        if($line == 'нет'){
            exit();
        }
        else {
            $this->model->DeleteRss($args[2]);
        }
    }//DeleteRss


    public function GetLastNews($args){
        $this->CheckArgsAlias($args, 3);

        $limit = 1;
        if(isset($args[3])){
            if(preg_match('/^\d+$/u', $args[3]) == 1){
                $limit = $args[3];
            }
        } 

        $this->view->ShowLastNews($this->model->GetLastNews($limit, $args[2]));
    }//GetLastNews

    public function GetLastRequests($args){
        $this->CheckArgsAlias($args, 3);

        $limit = 1;
        if(isset($args[3])){
            if(preg_match('/^\d+$/u', $args[3]) == 1){
                $limit = $args[3];
            }
        } 

        $this->view->ShowLastRequests($this->model->GetLastRequests($limit, $args[2]));
    }//GetLastRequests


    public function GetRss($args){
        $start = microtime(true);
        $url = $this->CheckArgsAlias($args, 3)['rss_url'];
        $alias = $args[2];

        // запрос
        $ch = curl_init($url);
        $options = [
            CURLOPT_USERAGENT => 'cURL Request',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 10
        ];
        curl_setopt_array($ch, $options);

        $content = curl_exec($ch);

        $req_info = [
            'datetime' => date('Y-m-d H:i:s'),
            'url' => $url,
            'http_code' => '',
            'alias' => $alias,
        ];

        if($content === false) {
            $this->model->LogRequest($req_info);
            exit('Не удалось получить ответ. Ресурс не отвечает или отсутствует интернет-соединение.' . PHP_EOL );        
        }

        $req_info['http_code'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if($req_info['http_code'] !== 200){
            $this->model->LogRequest($req_info);
            exit('Неудачный запрос. Попробуйте позже.' . PHP_EOL );        
        }

        $this->model->LogRequest($req_info);

        $parsobj = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOCDATA);

        if($parsobj === false){
            exit('Ошибка. Полученный файл не соответствует стандарту xml.' . PHP_EOL);
        }
        
        if( !isset($parsobj->channel) || !isset($parsobj->channel->item) ){
            exit('Ошибка. Приложение не может обработать данную структуру rss.' . PHP_EOL);
        }

        $namespaces = $parsobj->getNamespaces(true);

        $this->model->SaveNews($parsobj->channel->item, $alias, $namespaces);
        $this->model->db->close();

        $end = microtime(true);
        $this->view->ShowExecutionTime($end - $start);

    }//GetRss
       

  

}//class    
