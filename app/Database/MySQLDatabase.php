<?php
namespace App\Database;

use App\Interfaces\DatabaseInterface;

class MySQLDatabase implements DatabaseInterface
{
    private $connection;
    private $host;
    private $username;
    private $password;
    private $database;

    public function __construct($host, $username, $password, $database)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->connect();
    }

    public function connect()
    {
        try {
            $this->connection = new \mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                throw new \Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8");
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Database connection error: " . $e->getMessage());
        }
    }

    public function query($sql, $params = [])
    {
        try {
            if (!$this->connection) {
                $this->connect();
            }
    
            $stmt = $this->connection->prepare($sql);
            
            if (!$stmt) {
                error_log("Query preparation failed: " . $this->connection->error);
                error_log("SQL: " . $sql);
                throw new \Exception("Query preparation failed: " . $this->connection->error);
            }
    
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
    
            $executeResult = $stmt->execute();
            if (!$executeResult) {
                error_log("Query execution failed: " . $stmt->error);
                error_log("SQL: " . $sql);
                error_log("Params: " . print_r($params, true));
                throw new \Exception("Query execution failed: " . $stmt->error);
            }
    
            // Check if the query is a SELECT
            if ($stmt->field_count > 0) {
                return $stmt->get_result();
            } else {
                // For INSERT, UPDATE, DELETE queries
                return true;
            }
        } catch (\Exception $e) {
            error_log("Query error: " . $e->getMessage());
            throw $e;
        }
    }
    public function prepare($sql)
    {
        if (!$this->connection) {
            $this->connect();
        }

        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            error_log("Query preparation failed: " . $this->connection->error);
            error_log("SQL: " . $sql);
            throw new \Exception("Query preparation failed: " . $this->connection->error);
        }

        return $stmt;
    }
    public function fetchAssoc($result)
    {
        return $result->fetch_assoc();
    }

    public function fetchAll($result)
    {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function lastInsertId()
    {
        return $this->connection->insert_id;
    }

    public function beginTransaction()
    {
        $this->connection->begin_transaction();
    }
    public function error()
    {
        if (!$this->connection) {
            return "No active database connection";
        }
        return $this->connection->error;
    }


    public function commit()
    {
        $this->connection->commit();
    }

    public function rollback()
    {
        $this->connection->rollback();
    }

    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function isConnected()
    {
        return $this->connection !== null && !$this->connection->connect_error;
    }
    public function affectedRows()
    {
        if (!$this->connection) {
            throw new \Exception("No active database connection");
        }
        return $this->connection->affected_rows;
    }

    public function escapeString($string)
    {
        return $this->connection->real_escape_string($string);
    }
}

