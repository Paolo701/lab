<?php
/**
 * Created by PhpStorm.
 * User: JorEL
 * Date: 11/10/2016
 * Time: 11:28
 */





$session_creator = new \b166er\ClassCreator('session');

$session = $session_creator->new();

$sess_dao_creator =new \b166er\DaoCreator($session);

$sessionDao = $sess_dao_creator->new();

