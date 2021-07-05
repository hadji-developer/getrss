<?php

namespace models;

use services\db_work_2;

class main_model
{
    public function __construct(){
        $parameters = include_once(__DIR__ . "/../configs/db.php");
        $this->db = new db_work_2($parameters);
        $this->db->out_exception  = false; 
    }

    public $db;

    public function GetAliasesUrls(){
        $sql = "SELECT * FROM urls";
        $psv = [];
        return $this->db->query($sql, $psv)->fetchAll();
    }

    //Здесь также получаем rss-ссылку, ассоциированную с псевдонимом
    public function CheckAlias($alias){
        $sql = "SELECT count(id_url) c, rss_url ru FROM urls WHERE alias = ?";
        $psv = [$alias];
        $result = $this->db->query($sql, $psv)->fetchAll()[0];
        $rss_url = $result['ru'];
        $check_status = true;
        if($result['c'] != 0){
            $check_status = false;
        }

        return ['check_status' => $check_status, 'rss_url' => $rss_url ];
    }

    public function AddFeed($url, $alias){
        $sql[0] = "INSERT INTO urls (rss_url, alias) VALUES (?, ?)";
        $psv[0] = [$url, $alias];
        $this->db->transaction($sql, $psv);
    }

    public function DeleteRss($alias){
        $sql[0] = "DELETE FROM urls WHERE alias = ?";
        $psv[0] = [$alias];
        $this->db->transaction($sql, $psv);
    }


    public function GetLastNews($limit, $alias){
        $sql[0] = "SELECT id_url INTO @idurl FROM urls WHERE alias = ? ";
        $psv[0] = [$alias];
        $sql[1] = "SELECT * FROM news WHERE url = @idurl ORDER BY new_date DESC LIMIT $limit";
        $psv[1] = [];
        return $this->db->transaction($sql, $psv)[1]->fetchAll();
    }
   
    public function GetLastRequests($limit, $alias){
        $sql[0] = "SELECT id_url INTO @idurl FROM urls WHERE alias = ? ";
        $psv[0] = [$alias];
        $sql[1] = "SELECT r.req_date rd, u.rss_url url, r.http_code hc " .
                  "FROM requests r " .
                  "LEFT JOIN urls u ON r.url = u.id_url " . 
                  "WHERE r.url = @idurl " . 
                  "ORDER BY r.req_date DESC LIMIT $limit";
        $psv[1] = [];
        return $this->db->transaction($sql, $psv)[1]->fetchAll();
    }


    public function LogRequest($req_info){
        $sql[0] = "SELECT id_url INTO @idurl FROM urls WHERE alias = ?";
        $psv[0] = [ $req_info['alias'] ];

        $sql[1] = "INSERT INTO requests (req_date, url, http_code) " . 
                  "VALUES (?, @idurl, ? ) ";
        $psv[1] = [ $req_info['datetime'], $req_info['http_code'] ];

        $this->db->transaction($sql, $psv);
    }//LogRequest


    private function GetFirstImage($enclosure){
        if(is_array($enclosure)){
            $enclosure = $enclosure[0];
        }
        
        $image_url = '';

        if(isset($enclosure->attributes()['type']) && isset($enclosure->attributes()['url'])){
            if(preg_match('/^image\/.+$/iu', $enclosure->attributes()['type']) == 1){
                $image_url = trim($enclosure->attributes()['url']);
            }
        }

        return $image_url;                
    }//GetFirstImage

    private function GetFirstImageMC($item){
        $image_url = '';
        if( !isset($this->rssnsp['media']) ){
            return $image_url;
        }
        
        $media_content = $item->children($this->rssnsp['media']);
        $n = 0;
        foreach($media_content as $i){
            if($n > 0){
                break;
            }
            $image_url = (string)$i->attributes()->url;
            $n++;
        }

        return $image_url;
    }//GetFirstImageMC


    private $rssnsp;


    private function CheckDuplicate($guid){
        $sql = "SELECT count(id_new) c FROM news WHERE guid = ?";
        $psv = [$guid];
        $c = $this->db->query($sql, $psv)->fetchAll()[0]['c'];
        if($c == '0'){
            return false;
        }
        else {
            return true;
        }    
    }

    public function SaveNews($news, $alias, $namespaces){
        if(count($news) == 0) {
            exit();
        }

        $this->rssnsp = $namespaces;

        $sql[0] = 'SELECT MAX(id_new) INTO @idn FROM news'; $psv[0] = [];
        $sql[1] = 'SELECT IF(@idn IS NULL, 0, @idn) INTO @idn'; $psv[1] = [];
        $sql[2] = 'SELECT id_url INTO @idurl FROM urls WHERE alias = ? '; $psv[2] = [$alias];
        $n = 3;
        foreach($news as $new){
            $params = $this->GetNewParameters($new);
            if(!$params['status']){
                continue;
            }

            $t = $params['params'];
            unset($params);
            $is_duplicate = $this->CheckDuplicate($t['guid']);

            if(!$is_duplicate){
                $sql[$n] = "INSERT INTO news (id_new, title, new_link, description, new_date, author, image, url, guid) " .
                           "VALUES (@idn := @idn + 1, ?, ?, ?, ?, ?, ?, @idurl, ?)";
            }
            else {
                $sql[$n] = "UPDATE news SET id_new = @idn := @idn + 1, title = ?, new_link = ?, description = ?,  " . 
                           "new_date = ?, author = ?, image = ?, url = @idurl WHERE guid = ?";  
            }
            $psv[$n] = [ $t['title'], $t['link'], $t['description'], $t['pubdate'], $t['author'], $t['image_url'], $t['guid'] ];
            $n++;
        }//foreach

        $this->db->transaction($sql, $psv);

    }//SaveNews


    private function GetNewParameters($new){
        $results['status'] = true;
        $results['params'] = [];

        if( !isset($new->title) || !isset($new->link) || !isset($new->pubDate) || !isset($new->guid) ){
            $results['status'] = false;
            return $results;
        }

        $guid = trim($new->guid);
        if(mb_strlen($guid) > 500){
            $results['status'] = false;
            return $results;
        }
       
        $link = trim($new->link);
        if(mb_strlen($link) > 1000){
            $results['status'] = false;
            return $results;
        }

        $title = trim($new->title);
        if(mb_strlen($title) > 1000){
            $title = mb_substr($title, 0, 1000);
        }

        $date = strtotime(trim($new->pubDate));
        if($date === false){
            $results['status'] = false;
            return $results;
        }
        $date = date('Y-m-d H:i:s', $date);

        $description = '';
        if(isset($new->description)){
            $description = trim($new->description);
            if(mb_strlen($description) > 3000){
                $description = mb_substr($description, 0, 3000);
            }
        }

        $author = '';
        if(isset($new->author)){
            $author = trim($new->author);
            if(mb_strlen($author) > 1000){
                $author = mb_substr($author, 0, 1000);
            }
        }

        $image_url = '';
        if(isset($new->enclosure)){
            $image_url = $this->GetFirstImage($new->enclosure);
            if(mb_strlen($image_url > 1000)){
                $image_url = '';
            }
        }
        if($image_url  == ''){
            $image_url = $this->GetFirstImageMC($new);
            if(mb_strlen($image_url > 1000)){
                $image_url = '';
            }
        }

        $results['params'] = [
            'title' => $title,
            'link' => $link,
            'pubdate' => $date,
            'author' => $author,
            'description' => $description,
            'image_url' => $image_url,
            'guid' => $guid
        ];    


        return $results;
    }//GetNewParameters

  


}//class    
