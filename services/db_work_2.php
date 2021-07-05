<?php

namespace services;

use \PDO;


class db_work_2
{
    private $server;
    private $db;
    private $user;
    private $ent;

    private $conn = 'connection_closed';
    public $conn_status = ['status'=>'close', 'message'=>''];

    public $out_exception = true;


    public function __construct($db_params)
    {
        $this->server = $db_params['server'];
        $this->db = $db_params['db'];
        $this->user = $db_params['user'];
        $this->ent = $db_params['ent'];

        $this->connect();
    }

    private function OutException($message){
        if($this->out_exception){
            $message = "DB:$this->db; U:$this->user; S:$this->server;\nM: $message";
            throw new \Error($message, 777888);
        }          
    }

    public function connect() {
        try{
            if($this->conn === 'connection_closed') {
                $this->conn = new PDO("mysql:host=$this->server;dbname=$this->db;charset=utf8", $this->user, $this->ent,
                                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $this->conn_status['status'] = 'open';
                $this->conn_status['message'] = '';
            }
        }
        catch (\Exception $e) {
            $this->conn_status['status'] = 'close';
            $this->conn_status['message'] = "ERROR!  " . $e->getMessage();
            $this->conn = 'connection_closed';
            $this->OutException($e->getMessage());            
        }
    }

    public function close(){
        $this->conn = null;
        $this->conn = 'connection_closed';
        $this->conn_status['status'] = 'close';
        $this->conn_status['message'] = '';
    }

    //$psv  - prepared statesments variables
    public function query($sql, $psv){
        if($this->conn === "connection_closed") {
            $this->connect();
            if($this->conn_status['status'] === 'close'){
                return; 
            }
        }

        try{
            $var_t = $this->conn->prepare($sql);
            $var_t->execute($psv);
            return $var_t;
        }
        catch (\Exception $e){
            $this->conn_status['message'] = $e->getMessage();
            $this->OutException($e->getMessage());            
        }
    }//query

    public function transaction($sqls, $psvs){
        $v_ar = [];
        if($this->conn === "connection_closed") {
            $this->connect();
            if($this->conn_status['status'] === 'close'){
                return; 
            }
        }
        try{
            $n = count($sqls);
            $this->conn->beginTransaction();
            for($i=0; $i < $n; $i++){
                $var_t = $this->conn->prepare($sqls[$i]);
                $var_t->execute($psvs[$i]);
                $v_ar[] = $var_t;
            }
            $this->conn->commit();
            return $v_ar;
        }
        catch(\Exception $e){
            $this->conn_status['message'] = $e->getMessage();
            $this->conn->rollBack();
            $this->OutException($e->getMessage());            
        }
    }//transaction

    public function LastInsertId(){
        return $this->conn->lastInsertId();
    }

}

//--------------------
