<?php
namespace App\Interfaces;

interface DatabaseInterface
{
    public function connect();
    public function query($sql, $params = []);
    public function close();
}