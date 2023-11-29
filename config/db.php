<?php

class Db {
    private $sqlite = "sqlite:../config/db.sqlite";

    public function connect(){
        $conn_str = $this->sqlite;
        $conn = new PDO($conn_str);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }


} 