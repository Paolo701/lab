<?php
/**
 * Created by PhpStorm.
 * User: JorEL
 * Date: 25/06/14
 * Time: 15.41
 */

namespace b166er;

interface ICreateIterator
{
    public function createIterator();
}

interface IDataset
{
    //php7: public function getPDO() : \PDO;
    public function getPDO();

    //php7: public function getDBMS() : string;
    public function getDBMS();
}

interface ISingleton
{
    public static function Instance();
}

interface IValidation
{
    public function Validate();
}
interface Irequest
{
    public function Part();
    public function Verb();

}
interface Iclass_definition_existance
{
    //php7: public function class_definition_exists(string $nam, string $res = 'class') : bool;
    public function class_definition_exists($nam, $res = 'class');
}