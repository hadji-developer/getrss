<?php

namespace views;


class main_view
{
    public function ShowLastNews($news){
        $output = 'Новостей нет' . PHP_EOL;
        if(count($news) > 0) {
            $output = "";
            foreach($news as $new){
                $date = $new['new_date'];
                $title = $new['title'];
                $author = $new['author'];
                $link = $new['new_link'];
                $img = $new['image'];
                $output .= "Дата : $date " . PHP_EOL;
                $output .= "Заголовок : $title " . PHP_EOL;
                $output .= "Автор(ы) : $author " . PHP_EOL;
                $output .= "Ссылка : $link " . PHP_EOL;
                $output .= "Ссылка на картинку : $img " . PHP_EOL;
                $output .= "------------------------------------------------------------------------" . PHP_EOL . PHP_EOL; 
            }
        }

        exit($output);

    }//ShowLastNews

    public function ShowLastRequests($requests){
        $output = 'Запросов нет' . PHP_EOL;
        if(count($requests) > 0) {
            $output = "";
            foreach($requests as $request){
                $date = $request['rd'];
                $url = $request['url'];
                $code = $request['hc'];
                if($code == ''){
                    $code = 'Код не был получен. Ресурс не ответил или же возникли проблемы с интернет-соединением';
                }
                $output .= "Дата : $date | Код ответа : $code " . PHP_EOL;
                $output .= "Запрошенный ресурс : $url " . PHP_EOL;
                $output .= "------------------------------------------------------------------------" . PHP_EOL . PHP_EOL; 
            }
        }

        exit($output);

    }//ShowLastRequests

    public function ShowAliasesUrls($urls){
        $output = 'RSS каналов нет' . PHP_EOL ;
        if(count($urls) > 0) {
            $output = "";
            foreach ($urls as $url){
                list($id, $rss_url, $alias) = $url;
                $output .= "$alias  -  $rss_url" . PHP_EOL;
            }
        }
        exit($output);
    }

    public function ShowHelp(){
        $output = "Версия: 0.1.0, в процессе активной разработки." . PHP_EOL;

        $output = "СПРАВКА GETRSS" . PHP_EOL;
        $output .= "Структура команды: getrss [action] [parameters]" . PHP_EOL;
        $output .= "Действия: " . PHP_EOL;
        $output .= "    --add : добавить rss-канал, первый параметр - http(s) ссылка, второй параметр - алиас(псевдоним) длиной не более 10 символов " . PHP_EOL .
                   "    --get : загрузить в базу данных новости, параметр - алиас rss-канала" . PHP_EOL .
                   "    --remove : удалить rss-канал, параметр - алиас rss-канала " . PHP_EOL .
                   "    --last : вывести последние новости, первый параметр - алиас rss-канала, второй параметр (необязательный) - количество новостей" . PHP_EOL .
                   "    --request : вывести информацию о последних запросах, первый параметр - алиас rss-канала, второй параметр - количество запросов" . PHP_EOL .
                   "    --list : список rss-каналов и их псевдонимов" . PHP_EOL .
                   "    --help : данная справка" . PHP_EOL;
        $output .= "Примеры: " . PHP_EOL .
                   "getrss --add https://somesite.somedomain//somersschannel  somesite  -   добавили канал" . PHP_EOL .
                   "getrss --remove somesite - удалили канал" . PHP_EOL .
                   "getrss --last somesite 10 - вывели 10 последних новостей канала somesite" . PHP_EOL . "и так далее." . PHP_EOL ;
        exit($output);  
    }//ShowHelp


    public function ShowExecutionTime($time){
        $time = round($time, 3);
        exit("Время выполнения (секунды) : $time " . PHP_EOL);
    }


}//class    
