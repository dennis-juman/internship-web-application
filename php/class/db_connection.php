<?php
class DatabaseConnection{
    private $rdbms; //RELATIONAL-DATABASE MANAGEMENT SYSTEM NAME. E.G. = MSQL, POSTGRESQL ETC.
    private $host; //127.0.0.1 <-- AS AN EXAMPLE OR LOCALHOST ETC. (DEPENDS ON MYSQL SERVER CONFIGURATIONS)
    private $port; //DB HOST PORT
    private $schema; //YOUR DB NAME
    private $username; //DB LOGIN NAME
    private $password; //DB LOGIN PASSWORD


    public function __construct(){
      $this->rdbms = "mysql"; //RELATIONAL-DATABASE MANAGEMENT SYSTEM NAME. E.G. = MSQL, POSTGRESQL ETC.
      $this->host = "127.0.0.1"; //127.0.0.1 <-- AS AN EXAMPLE OR LOCALHOST ETC. (DEPENDS ON MYSQL SERVER CONFIGURATIONS)
      $this->port = "8889";
      $this->schema = "digital_society_school"; //YOUR DB NAME
      $this->username = "root"; //DB LOGIN NAME
      $this->password = "root"; //DB LOGIN PASSWORD
    }

    // public function __construct(){
    //   $this->rdbms = "mysql"; //RELATIONAL-DATABASE MANAGEMENT SYSTEM NAME. E.G. = MSQL, POSTGRESQL ETC.
    //   $this->host = "127.0.0.1"; //127.0.0.1 <-- AS AN EXAMPLE OR LOCALHOST ETC. (DEPENDS ON MYSQL SERVER CONFIGURATIONS)
    //   $this->port = "3306";
    //   $this->schema = "DSS_Library"; //YOUR DB NAME
    //   $this->username = "dss_lib_usr"; //DB LOGIN NAME
    //   $this->password = "oMUhVpafFTem1zhR"; //DB LOGIN PASSWORD
    // }

    public function connection(){
        try {
          $dbh = new PDO($this->rdbms . ':host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->schema, $this->username, $this->password);
        } catch (PDOException $e) {
          print "Error!: " . $e->getMessage() . "<br/>";
          die();
        }
        return $dbh;
    }
}