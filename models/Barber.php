<?php
class Barbershop {
    private $conn;
    private $table = 'barbershops';

    public $id = null;
    public $name = null;
    public $city = null;
    public $phone = null;

    public function __construct($db){
        $this->conn = $db;
    }

    public function read() {
        $querry = "SELECT * FROM " . $this->table;
        $stmt = $this->conn->prepare($querry);
        $stmt->execute();

        return $stmt;
    }
}