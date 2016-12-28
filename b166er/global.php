<?php
namespace b166er;

function import_part_file( $nam,  $res = 'class')
{
    global $_path;

    $tar = array(
        $_path . "/parts/" . $nam . "/" . $res . ".php",
        $_path . "/" . $nam . "/" . $res . ".php",
        __DIR__ . "/" . $nam . "-" . $res . ".php",
        __DIR__ . "/parts/" . $nam . "-" . $res . ".php"
    );

    try {
        foreach ($tar as $pth) {
            $tmp = $pth . "/" . $res . ".php";
            if (is_file($tmp)) {
                require_once($tmp);
                return true;
            }
        }
    } catch (\Exception $e) {
        exception_display($e);
        die();
    }

    return false;
}

function array_splice_assoc(&$input, $offset, $length, $replacement)
{
    // found on PHP Documentation
    // function by: royanee at yahoo dot com
    // http://php.net/manual/en/function.array-splice.php#111204

    $replacement = (array)$replacement;
    $key_indices = array_flip(array_keys($input));
    if (isset($input[$offset]) && is_string($offset)) {
        $offset = $key_indices[$offset];
    }
    if (isset($input[$length]) && is_string($length)) {
        $length = $key_indices[$length] - $offset;
    }

    $input = array_slice($input, 0, $offset, TRUE)
        + $replacement
        + array_slice($input, $offset + $length, NULL, TRUE);
}

function merge_ini_file_in_component($file, Component $con)
{
    try {

        if (is_file($file)) {
            $hnd = fopen($file, "r");
        } else {
            throw new \Exception("No $file found");
        }

        $temp = array();
        $ret = (bool)true;

        while (!feof($hnd)) {
            $line = trim(fgets($hnd));
            if (substr($line, 0, 1) != ';') {
                $equals = strpos($line, '=');
                switch ($equals) {
                    case false:

                        break;
                    case 0:

                        break;
                    default:
                        $temp[0] = trim(substr($line, 0, $equals));
                        $temp[1] = trim(substr($line, $equals + 1, strlen($line) - $equals - 1));
                        if (!empty($temp[0])) {
                            $con->set($temp[0], $temp[1], '.');
                        }
                        break;
                }
            }
        }
        return $ret;
    } catch (\Exception $e) {

        exception_display($e);
        die();

    }
}

function scan_all_tables_imported_from_ini_file(DatabaseCollection $db_coll)
{
    try {

        //iterate tru all databases as defined in .ini file
        $it = $db_coll->createIterator();

        while ($it->valid()) {
            $_DBMS = $it->current()->getDBMS();
            //you need admin rights to scan tables
            //to try better solution later on...
            if (!(DBU::isDriverAvailable($_DBMS))) throw new \Exception($_DBMS . " driver not supported on this server");

            $DBU = DBU::deploy($_DBMS);

            $stmt = $it->current()->getPDO()->prepare($DBU::query_table_names());
            if ($stmt->execute()) {
                $temp = $stmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_COLUMN, 0);
            } else {
                throw new \PDOException('pdo error');
            }
            if ($temp) {
                //$tables = new TableCollection('tables');
                $tables = new TableDefCollection('tables');
                foreach ($temp as $key => $value) {
                    //$tab = new Table($value);
                    $tab = new TableDef($value);
                    $tables->add($tab);
                }
                $it->current()->add($tables);

            }
            $temp = null;
            $it->next();
        }
    } catch (\Exception $e) {

        exception_display($e);
        die();

    }
}

function table_defined_in_db_collection( $tab, DatabaseCollection $db_coll)
{
    //if there's no database
    if ($db_coll->size() == 0) {
        return false;
    } else {
        //if in config there are databases defined
        $it = $db_coll->createIterator();

        while ($it->valid()) {
            //scroll down all tables inside
            $tables = $it->current()->contains('tables');
            if ($tables) {
                $iit = $tables->createIterator();
                while ($iit->valid()) {
                    //if matched
                    if ($iit->current()->key() == $tab) {
                        return $iit->current();
                    }
                    $iit->next();
                }
            }
            $it->next();
        }
    }
    return false;
}

function object_defined_in_db_collection( $obj, DatabaseCollection $db_coll) // : DatabaseStructure (waiting php 7.1)
{
    $arr = array(
        'tables',
        'views'
    );
    
    //if there's no database
    if ($db_coll->size() == 0) {
        return null;
    } else {
        //if in config there are databases defined
        $it = $db_coll->createIterator();

        while ($it->valid()) {
            foreach ($arr as $el) {
                $str_obj = $it->current()->contains($el.'/'.$obj);
                if ($str_obj) {
                    return $str_obj;
                }
            }
            $it->next();
        }
    }
    return null;
}

abstract class ERR
{
    const SUCCESS = 0b0;
    const WRONG_DATA = 0b1;
    const REQUIRED_DATA = 0b10;
    const AUTO_INCREMENT_FIELD = 0b100;
    const FETCH_ERROR = 0b1000;
    const FOREIGN_KEY_NOT_EXISTING = 0b10000;
}

function exception_display(\Exception $e)
{
    echo('
                <!DOCTYPE html>
                    <html>
                        <body>
                            <h1>Error</h1>
                                <p><b>' . $e->getMessage() . '</b></p>
                                <ul>
                                    <li>Error occurred in ' . $e->getFile() . '</li>
                                    <li>line: ' . $e->getLine() . '</li>
                                    <li>error code: ' . $e->getCode() . '</li>
                                    <li>exception class: ' . get_class($e) . '</li>
                                </ul>
                ');
}
