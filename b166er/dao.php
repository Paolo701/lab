<?php

namespace b166er;

abstract class DBU
{
    const Tables = 0;
    const Views = 1;

    abstract public static function query_database_object_names($type);

    public static function format_field_value_for_sql_query(Field $fld)
    {
        /*
         * questa funzione restituisce il valore stesso formattato per
         * inserirlo in una stringa sql. In pratica aggiunge gli apici
         * (anche se su sqlite pare che aggiunga lo stesso gli apici anche
         * se si tratta di un valore numerico. ma potrebbe essere solo
         * una caratteristica di sqlite che tratta le variabili un po' a
         * cazzo.
         */
        $value = $fld->get('');
        if (is_string($value)) {
            return "\"" . $value . "\"";
        } elseif (is_null($value)) {
            return "NULL";
        } else {
            return $value;
        }
    }

    //php7: public static function isDriverAvailable($_dbms) : bool
    public static function isDriverAvailable($_dbms) 
    {
        $ar = \PDO::getAvailableDrivers();

        foreach ($ar as $k => $v) {
            if ($v == $_dbms) {
                return true;
            }
        }
        return false;

    }

    //php7: public static function deploy(string $DBMS) : DBU
    public static function deploy($DBMS)
    {
        $xDBU = "\\b166er\\" . $DBMS . 'DBU';
        return new $xDBU();
    }

    public static function query_table_names()
    {

    }

    public static function query_view_names()
    {

    }

    public static function load_statement(Part $part)
    {

        $temp = '';
        $DBU = DBU::deploy($part->getDBMS());
        $it = $part->createAttributesIterator(Field::SQL_PRIMARY_KEY);
        while ($it->valid()) {
            $field = $it->current()->key();
            $value = $DBU::format_field_value_for_sql_query($it->current());
            $temp .= $part->key() . '.' . $field . ' = ' . $value;
            $it->next();
            if ($it->valid()) {
                $temp .= ' AND ';
            }
        }
        return 'SELECT * FROM ' . $part->key() . ' WHERE (' . $temp . ')';
    }

    public static function save_new_statement(Part $part)
    {
        $keys = "(";
        $values = "(";
        $DBU = DBU::deploy($part->getDBMS());
        $it = $part->createIterator();
        do {
            $r = $it->current();
            if (($r->get('') != '') && (!is_null($r->get('')))) {

                $keys = $keys . $r->key() . ", ";
                $f_sql = $DBU::format_field_value_for_sql_query($r);
                $values = $values . $f_sql . ", ";
            }

        } while ($it->next());

        $keys = trim($keys);
        $keys = rtrim($keys, ",") . ")";
        $values = trim($values);
        $values = rtrim($values, ",") . ")";

        return 'INSERT INTO ' . $part->key() . $keys . " VALUES" . $values;
    }

    public static function update_existing_statement(Part $part)
    {
        $set_values = '';
        $where_values = '';

        $DBU = DBU::deploy($part->getDBMS());
        $it = $part->createIterator();
        do {
            $r = $it->current();
            $temp = $r->key() . '=' . $DBU::format_field_value_for_sql_query($r) . ', ';
            if (($r->get('ATTRIBUTES') & Field::SQL_PRIMARY_KEY) == Field::SQL_PRIMARY_KEY) {
                $where_values .= $temp;
            } else {
                if (!is_null($r->get())) {
                    $set_values .= $temp;
                }
            }
        } while ($it->next());

        $set_values = trim($set_values);
        $set_values = rtrim($set_values, ",");
        $where_values = trim($where_values);
        $where_values = rtrim($where_values, ",");

        return 'UPDATE ' . $part->key() . ' SET ' . $set_values . " WHERE " . $where_values . 'LIMIT 1';
    }

    public static function delete_statement(Part $part)
    {

        $temp = '';
        $DBU = DBU::deploy($part->getDBMS());
        $it = $part->createAttributesIterator(Field::SQL_PRIMARY_KEY);
        while ($it->valid()) {
            $field = $it->current()->key();
            $value = $DBU::format_field_value_for_sql_query($it->current());
            $temp .= $part->key() . '.' . $field . ' = ' . $value;
            $it->next();
            if ($it->valid()) {
                $temp .= ' AND ';
            }
        }
        return 'DELETE FROM ' . $part->key() . ' WHERE (' . $temp . ') LIMIT 1';
    }
//    public static function query_table_schema_from_database($table):Part
//    {
//
//    }
//
//    public static function query_view_schema_from_database($view):Part
//    {
//
//    }
//    public static function isADatabaseObject(string $obj) : DatabaseStructure
//    {
////        $temp = new Table();
//        try {
//            //if there's no database
//            if (__app()->contains('config/database')->size() == 0) {
//                throw new \Exception('Appears there is no database defined.');
//            } else {
//                //if in config there are databases defined
//                $it = __app()->contains('config/database')->createIterator();
//
//                while ($it->valid()) {
//                    //scroll down all tables inside
//                    $tables = $it->current()->contains('tables');
//                    if ($tables) {
//                        $iit = $tables->createIterator();
//                        while ($iit->valid()) {
//                            //if matched
//                            if ($iit->current()->key() == $obj) {
////                                //$temp->import($it->current()->toArray());
////                                $temp->set('pdo', $it->current()->get('pdo'));
////                                $temp->set('DBMS', $it->current()->get('DBMS'));
////                                $temp->pdo = $it->current()->get('pdo');
////                                $temp->DBMS = $it->current()->get('DBMS');
////                                $temp->setKey($iit->current()->key());
////
////                                //$iit->current()->import($it->current()->Child());
//                                return $iit->current();
//                            }
//                            $iit->next();
//                        }
//                    }
//                    $it->next();
//
//                }
//            }
//            return null;
//
//        } catch (\Exception $e) {
//
//            exception_display($e);
//            die();
//
//        }
//    }
    public static function merge_table_schema_from_database(Part $prt, TableDef $tbd)
    {
    }

    public static function merge_view_schema_from_database(Part $prt, ViewDef $vid)
    {
    }

    public static function merge_schema_from_database(Part $prt, DatabaseStructure $obj)
    {
        try {
            if ($obj instanceof TableDef) {
                static::merge_table_schema_from_database($prt, $obj);
            } elseif ($obj instanceof ViewDef) {
                static::merge_view_schema_from_database($prt, $obj);
            } else {
                throw new \InvalidArgumentException('che minchia scrivi');
            }
        } catch (\Exception $e) {
            exception_display($e);
            die();
        }

    }

}

class sqliteDBU extends DBU
{
    static $r = '';

    public static function query_table_names()
    {
        return "SELECT tbl_name FROM sqlite_master WHERE (sqlite_master.type = 'table' AND sqlite_master.tbl_name !='sqlite_sequence') ORDER BY name;";

    }

    public static function query_view_names()
    {
        return "SELECT tbl_name FROM sqlite_master WHERE (sqlite_master.type = 'view') ORDER BY name;";
    }
//    public static function query_table_schema_from_database($table) : Part
//    {
//
//        $parent = parent::query_table_schema_from_database($table);
//        if ($parent) return $parent;
//
//        // consider adding a require once to get the
//        // class definition only if required
//        // Should be here
//
//        //check whether the class is already defined
//        $class = '\\b166er\\' . $table->key();
//        //if so, create the class with that name
//        if (class_exists($class)) {
//            $part = new $class();
//        } else {
//            //or create a generic part class
//            $part = new Part();
//        }
//        //set the key to the part name
//        $part->setKey($table->key());
//
//        //sqlite way to retrieve table schema.  oh my god! forgive me
//        $sql = "SELECT sqlite_master.sql FROM sqlite_master WHERE (sqlite_master.tbl_name='" . $table->key() . "')";
//        $pdo = $table->getPDO();
//        try {
//            //execute query
//            $stmt = $pdo->prepare($sql);
//            //
//            if ($stmt->execute()) {
//                $ret = $stmt->fetch();
//                //at this point $schema contains the table definition:
//                //CREATE TABLE etc....
//                $schema = $ret['sql'];
//                //rip in lines
//                $schema = explode(PHP_EOL, $schema);
//                //this var will contain the database type schema
//                //as reported on alpha.ini
//                $table_def_container = new Container();
//
//                //scan every line
//                foreach ($schema as $n => $s) {
//                    $s = trim($s);
//                    $s = trim($s, ',');
//                    $table_def_container->set($n, $s);
//                }
//
//                // create the iterator
//                $it = $table_def_container->createIterator();
//                //scan every member
//                while ($it->valid()) {
//                    //the value contains the line of the database query
//                    $str = $it->current()->get('');
//                    //creates an array of words
//                    $arr = explode(' ', $str);
//                    //scans every word the line starts with.
//                    //default case is referred to field description lines.
//                    switch ($arr[0]) {
//                        case ')':
//                            break;
//                        case 'CREATE':
//                            //the table name should be $arr[2]
//                            //should be a check here
//                            break;
//                        case 'PRIMARY':
//                            $ts = '';
//                            foreach ($arr as $w) {
//                                $ts .= $w;
//                            }
//                            $ts = substr($ts, 10);
//                            $ts = trim($ts, '()');
//                            $p_arr = explode(',', $ts);
//                            //makes an array of all the parent keys
//                            //and put it in $par_field
//                            $par_field = trim($arr[7], '(`)');
//                            foreach ($p_arr as $pk) {
//                                $temp_fld = trim($pk, ' ');
//                                $part->set($temp_fld . '/ATTRIBUTES', $part->get($temp_fld . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
//                                $part->set($temp_fld . '/PRIMARY_KEY', '');
//                            }
//                            break;
//                        case 'FOREIGN':
//                            //first make an array of all the foreign keys
//                            //and put it in $chd
//                            $chd = explode(', ', trim($arr[2], '()'));
//                            //in $arr[4] there's the parent table name
//                            $par_table = $arr[4];
//                            //makes an array of all the parent keys
//                            //and put it in $par_field
//                            $par_field = explode(', ', trim($arr[5], '()'));
//
//                            // $ou means ON UPDATE.
//                            $ou = '';
//                            // Guess what $od means
//                            $od = '';
//
//                            // Now it's time to give a look to all the words
//                            //in line
//                            foreach ($arr as $a => $b) {
//                                // to give a look
//                                // if doesn't seem to be the best choice
//                                // good for now. To recode later
//                                if ($b == 'ON') {
//                                    //checks the word next to 'ON' (on the right of course)
//                                    switch ($arr[$a + 1]) {
//                                        // self explanatory
//                                        case 'UPDATE':
//                                            if ($arr[$a + 2] == 'NO') {
//                                                $ou = 'NO ACTION';
//                                                break;
//                                            }
//                                            $ou = $arr[$a + 2];
//                                            break;
//                                        case 'DELETE':
//                                            if ($arr[$a + 2] == 'NO') {
//                                                $od = 'NO ACTION';
//                                                break;
//                                            }
//                                            if ($arr[$a + 2] == 'SET') {
//                                                $od = 'SET ' . $arr[$a + 3];
//                                                break;
//                                            }
//                                            $od = $arr[$a + 2];
//                                            break;
//                                    }
//                                }
//                            }
//
//                            // $chd contains all the foreign key names.
//                            // This foreach creates subkeys in the field container
//                            // to describe the foreign key data.
//                            // provided that all the fields have already been created
//                            foreach ($chd as $a => $b) {
//                                $part->set($b . '/ATTRIBUTES', $part->get($b . '/ATTRIBUTES') | Field::SQL_FOREIGN_KEY);
//                                $part->set($b . '/FOREIGN_KEY', '');
//                                $part->set($b . '/FOREIGN_KEY/PARENT', $par_table . '.' . $par_field[$a]);
//                                $part->set($b . '/FOREIGN_KEY/PARENT/TABLE', $par_table);
//                                $part->set($b . '/FOREIGN_KEY/PARENT/FIELD', $par_field[$a]);
//                                $part->set($b . '/FOREIGN_KEY/ON_UPDATE', $ou);
//                                $part->set($b . '/FOREIGN_KEY/ON_DELETE', $od);
//                            }
//
//                            break;
//                        //supposedly a field description line
//                        default:
//                            //type is the second word
//                            $type = $arr[1];
//                            $type_len = 0;
//
//                            //check if len is specified.
//                            //if so captures type_len
//                            if (strpos($type, '(')) {
//                                $type_arr = explode('(', $type);
//                                $type = $type_arr[0];
//                                $type_len = intval(trim($type_arr[1], ')'));
//
//                            }
//
//                            // this line traduces the DBMS type name
//                            // in this application's field type name.
//                            // as found in alpha.ini
//                            //$lib_type = 'dummy';
//                            $lib_type = __app()->get('library/' . $table->getDBMS() . '/types/' . $type);
//                            // if this type exists
//                            if ($lib_type) {
//                                $class = '\\b166er\\' . $lib_type . 'Field';
//                                //if so, create the class with that name
//                                if (class_exists($class)) {
//                                    $fld = new $class($arr[0], Field::SQL_NO_ATTRIBUTE, $type_len);
//                                } else {
//                                    throw new \Exception('No class with name: ' . $class);
//                                }
//                                //adds the field to the part object
//                                $part->add($fld);
//                            } else {
//                                throw new \Exception('No class with name: ' . $class);
//                            }
//
//                            // at this point iterate tru all line words
//                            // looking for field informations
//                            foreach ($arr as $a => $b) {
//                                switch ($b) {
//                                    case 'AUTOINCREMENT':
//                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_AUTO_INCREMENT);
//                                        $part->set($arr[0] . '/AUTO_INCREMENT', '');
//                                        break;
//                                    case 'PRIMARY':
//                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
//                                        $part->set($arr[0] . '/PRIMARY_KEY', '');
//                                        break;
//                                    case 'UNIQUE':
//                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_UNIQUE);
//                                        $part->set($arr[0] . '/UNIQUE', '');
//                                        break;
//                                    case 'NOT':
//                                        //checks whether the next word is NULL
//                                        if ($arr[$a + 1] == 'NULL') {
//                                            $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_REQUIRED);
//                                            $part->set($arr[0] . '/REQUIRED', '');
//                                        }
//                                        break;
//                                }
//                            }
//                    } //end switch
//                    $it->next();
//                } //end while
//                return $part;
//            } else {
//                throw new \PDOException('PDO error');
//            }
//
//        } catch (\Exception $e) {
//
//            exception_display($e);
//            die();
//
//        }
//    }
//
//    public static function query_table_schema_from_database_temp(TableDef $table) : Part
//    {
//
//        $parent = parent::query_table_schema_from_database($table);
//        if ($parent) return $parent;
//
//        // consider adding a require once to get the
//        // class definition only if required
//        // Should be here
//
//        //check whether the class is already defined
//        $class = '\\b166er\\' . $table->key();
//        //if so, create the class with that name
//        if (class_exists($class)) {
//            $part = new $class();
//        } else {
//            //or create a generic part class
//            $part = new Part();
//        }
//        //set the key to the part name
//        $part->setKey($table->key());
//
//        //sqlite way to retrieve table schema.  oh my god! forgive me
//        $sql = "SELECT sqlite_master.sql FROM sqlite_master WHERE (sqlite_master.tbl_name='" . $table->key() . "')";
//        $pdo = $table->getPDO();
//        try {
//            //execute query
//            $stmt = $pdo->prepare($sql);
//            //
//            if ($stmt->execute()) {
//                $ret = $stmt->fetch();
//                //at this point $schema contains the table definition:
//                //CREATE TABLE etc....
//                $schema = $ret['sql'];
//                //rip in lines
//                $schema = explode(PHP_EOL, $schema);
//                //this var will contain the database type schema
//                //as reported on alpha.ini
//                $table_def_container = new Container();
//
//                //scan every line
//                foreach ($schema as $n => $s) {
//                    $s = trim($s);
//                    $s = trim($s, ',');
//                    $table_def_container->set($n, $s);
//                }
//
//                // create the iterator
//                $it = $table_def_container->createIterator();
//                //scan every member
//                while ($it->valid()) {
//                    //the value contains the line of the database query
//                    $str = $it->current()->get('');
//                    //creates an array of words
//                    $arr = explode(' ', $str);
//                    //scans every word the line starts with.
//                    //default case is referred to field description lines.
//                    switch ($arr[0]) {
//                        case ')':
//                            break;
//                        case 'CREATE':
//                            //the table name should be $arr[2]
//                            //should be a check here
//                            break;
//                        case 'PRIMARY':
//                            $ts = '';
//                            foreach ($arr as $w) {
//                                $ts .= $w;
//                            }
//                            $ts = substr($ts, 10);
//                            $ts = trim($ts, '()');
//                            $p_arr = explode(',', $ts);
//                            //makes an array of all the parent keys
//                            //and put it in $par_field
//                            $par_field = trim($arr[7], '(`)');
//                            foreach ($p_arr as $pk) {
//                                $temp_fld = trim($pk, ' ');
//                                $part->set($temp_fld . '/ATTRIBUTES', $part->get($temp_fld . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
//                                $part->set($temp_fld . '/PRIMARY_KEY', '');
//                            }
//                            break;
//                        case 'FOREIGN':
//                            //first make an array of all the foreign keys
//                            //and put it in $chd
//                            $chd = explode(', ', trim($arr[2], '()'));
//                            //in $arr[4] there's the parent table name
//                            $par_table = $arr[4];
//                            //makes an array of all the parent keys
//                            //and put it in $par_field
//                            $par_field = explode(', ', trim($arr[5], '()'));
//
//                            // $ou means ON UPDATE.
//                            $ou = '';
//                            // Guess what $od means
//                            $od = '';
//
//                            // Now it's time to give a look to all the words
//                            //in line
//                            foreach ($arr as $a => $b) {
//                                // to give a look
//                                // if doesn't seem to be the best choice
//                                // good for now. To recode later
//                                if ($b == 'ON') {
//                                    //checks the word next to 'ON' (on the right of course)
//                                    switch ($arr[$a + 1]) {
//                                        // self explanatory
//                                        case 'UPDATE':
//                                            if ($arr[$a + 2] == 'NO') {
//                                                $ou = 'NO ACTION';
//                                                break;
//                                            }
//                                            $ou = $arr[$a + 2];
//                                            break;
//                                        case 'DELETE':
//                                            if ($arr[$a + 2] == 'NO') {
//                                                $od = 'NO ACTION';
//                                                break;
//                                            }
//                                            if ($arr[$a + 2] == 'SET') {
//                                                $od = 'SET ' . $arr[$a + 3];
//                                                break;
//                                            }
//                                            $od = $arr[$a + 2];
//                                            break;
//                                    }
//                                }
//                            }
//
//                            // $chd contains all the foreign key names.
//                            // This foreach creates subkeys in the field container
//                            // to describe the foreign key data.
//                            // provided that all the fields have already been created
//                            foreach ($chd as $a => $b) {
//                                $part->set($b . '/ATTRIBUTES', $part->get($b . '/ATTRIBUTES') | Field::SQL_FOREIGN_KEY);
//                                $part->set($b . '/FOREIGN_KEY', '');
//                                $part->set($b . '/FOREIGN_KEY/PARENT', $par_table . '.' . $par_field[$a]);
//                                $part->set($b . '/FOREIGN_KEY/PARENT/TABLE', $par_table);
//                                $part->set($b . '/FOREIGN_KEY/PARENT/FIELD', $par_field[$a]);
//                                $part->set($b . '/FOREIGN_KEY/ON_UPDATE', $ou);
//                                $part->set($b . '/FOREIGN_KEY/ON_DELETE', $od);
//                            }
//
//                            break;
//                        //supposedly a field description line
//                        default:
//                            //type is the second word
//                            $type = $arr[1];
//                            $type_len = 0;
//
//                            //check if len is specified.
//                            //if so captures type_len
//                            if (strpos($type, '(')) {
//                                $type_arr = explode('(', $type);
//                                $type = $type_arr[0];
//                                $type_len = intval(trim($type_arr[1], ')'));
//
//                            }
//
//                            // this line traduces the DBMS type name
//                            // in this application's field type name.
//                            // as found in alpha.ini
//                            //$lib_type = 'dummy';
//                            $lib_type = __app()->get('library/' . $table->getDBMS() . '/types/' . $type);
//                            // if this type exists
//                            if ($lib_type) {
//                                $class = '\\b166er\\' . $lib_type . 'Field';
//                                //if so, create the class with that name
//                                if (class_exists($class)) {
//                                    $fld = new $class($arr[0], Field::SQL_NO_ATTRIBUTE, $type_len);
//                                } else {
//                                    throw new \Exception('No class with name: ' . $class);
//                                }
//                                //adds the field to the part object
//                                $part->add($fld);
//                            } else {
//                                throw new \Exception('No class with name: ' . $class);
//                            }
//
//                            // at this point iterate tru all line words
//                            // looking for field informations
//                            foreach ($arr as $a => $b) {
//                                switch ($b) {
//                                    case 'AUTOINCREMENT':
//                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_AUTO_INCREMENT);
//                                        $part->set($arr[0] . '/AUTO_INCREMENT', '');
//                                        break;
//                                    case 'PRIMARY':
//                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
//                                        $part->set($arr[0] . '/PRIMARY_KEY', '');
//                                        break;
//                                    case 'UNIQUE':
//                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_UNIQUE);
//                                        $part->set($arr[0] . '/UNIQUE', '');
//                                        break;
//                                    case 'NOT':
//                                        //checks whether the next word is NULL
//                                        if ($arr[$a + 1] == 'NULL') {
//                                            $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_REQUIRED);
//                                            $part->set($arr[0] . '/REQUIRED', '');
//                                        }
//                                        break;
//                                }
//                            }
//                    } //end switch
//                    $it->next();
//                } //end while
//                return $part;
//            } else {
//                throw new \PDOException('PDO error');
//            }
//
//        } catch (\Exception $e) {
//
//            exception_display($e);
//            die();
//
//        }
//    }
////    public static function query_table_schema_from_database2(Part $part)
////    {
////
////        //sqlite way to retrieve table schema.  oh my god! forgive me
////        $sql = "SELECT sqlite_master.sql FROM sqlite_master WHERE (sqlite_master.tbl_name='" . $part->key() . "')";
////        $DBU = __app()->isTable($part->key());
////        $pdo = $DBU->getPDO();//!!!!!!!!!!!!!!!
////        try {
////            //execute query
////            $stmt = $pdo->prepare($sql);
////            //
////            if ($stmt->execute()) {
////                $ret = $stmt->fetch();
////                //at this point $schema contains the table definition:
////                //CREATE TABLE etc....
////                $schema = $ret['sql'];
////                //rip in lines
////                $schema = explode(PHP_EOL, $schema);
////                //this var will contain the database type schema
////                //as reported on alpha.ini
////                $table_def_container = new Container();
////
////                //scan every line
////                foreach ($schema as $n => $s) {
////                    $s = trim($s);
////                    $s = trim($s, ',');
////                    $table_def_container->set($n, $s);
////                }
////
////                // create the iterator
////                $it = $table_def_container->createIterator();
////                //scan every member
////                while ($it->valid()) {
////                    //the value contains the line of the database query
////                    $str = $it->current()->get('');
////                    //creates an array of words
////                    $arr = explode(' ', $str);
////                    //scans every word the line starts with.
////                    //default case is referred to field description lines.
////                    switch ($arr[0]) {
////                        case ')':
////                            break;
////                        case 'CREATE':
////                            //the table name should be $arr[2]
////                            //should be a check here
////                            break;
////                        case 'PRIMARY':
////                            $ts = '';
////                            foreach ($arr as $w) {
////                                $ts .= $w;
////                            }
////                            $ts = substr($ts, 10);
////                            $ts = trim($ts, '()');
////                            $p_arr = explode(',', $ts);
////                            //makes an array of all the parent keys
////                            //and puts it in $par_field
////                            $par_field = trim($arr[7], '(`)');
////                            foreach ($p_arr as $pk) {
////                                $temp_fld = trim($pk, ' ');
////                                $part->set($temp_fld . '/ATTRIBUTES', $part->get($temp_fld . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
////                                $part->set($temp_fld . '/PRIMARY_KEY', '');
////                            }
////                            break;
////                        case 'FOREIGN':
////                            //first makes an array of all the foreign keys
////                            //and puts it in $chd
////                            $chd = explode(', ', trim($arr[2], '()'));
////                            //in $arr[4] there's the parent table name
////                            $par_table = $arr[4];
////                            //makes an array of all the parent keys
////                            //and put it in $par_field
////                            $par_field = explode(', ', trim($arr[5], '()'));
////
////                            // $ou means ON UPDATE.
////                            $ou = '';
////                            // Guess what $od means
////                            $od = '';
////
////                            // Now it's time to give a look to all the words
////                            //in line
////                            foreach ($arr as $a => $b) {
////                                // to give a look
////                                // if doesn't seem to be the best choice
////                                // good for now. To recode later
////                                if ($b == 'ON') {
////                                    //checks the word next to 'ON' (on the right of course)
////                                    switch ($arr[$a + 1]) {
////                                        // self explanatory
////                                        case 'UPDATE':
////                                            if ($arr[$a + 2] == 'NO') {
////                                                $ou = 'NO ACTION';
////                                                break;
////                                            }
////                                            $ou = $arr[$a + 2];
////                                            break;
////                                        case 'DELETE':
////                                            if ($arr[$a + 2] == 'NO') {
////                                                $od = 'NO ACTION';
////                                                break;
////                                            }
////                                            if ($arr[$a + 2] == 'SET') {
////                                                $od = 'SET ' . $arr[$a + 3];
////                                                break;
////                                            }
////                                            $od = $arr[$a + 2];
////                                            break;
////                                    }
////                                }
////                            }
////
////                            // $chd contains all the foreign key names.
////                            // This foreach creates subkeys in the field container
////                            // to describe the foreign key data.
////                            // provided that all the fields have already been created
////                            foreach ($chd as $a => $b) {
////                                $part->set($b . '/ATTRIBUTES', $part->get($b . '/ATTRIBUTES') | Field::SQL_FOREIGN_KEY);
////                                $part->set($b . '/FOREIGN_KEY', '');
////                                $part->set($b . '/FOREIGN_KEY/PARENT', $par_table . '.' . $par_field[$a]);
////                                $part->set($b . '/FOREIGN_KEY/PARENT/TABLE', $par_table);
////                                $part->set($b . '/FOREIGN_KEY/PARENT/FIELD', $par_field[$a]);
////                                $part->set($b . '/FOREIGN_KEY/ON_UPDATE', $ou);
////                                $part->set($b . '/FOREIGN_KEY/ON_DELETE', $od);
////                            }
////
////                            break;
////                        //supposedly a field description line
////                        default:
////                            //type is the second word
////                            $type = $arr[1];
////                            $type_len = 0;
////
////                            //check if len is specified.
////                            //if so captures type_len
////                            if (strpos($type, '(')) {
////                                $type_arr = explode('(', $type);
////                                $type = $type_arr[0];
////                                $type_len = intval(trim($type_arr[1], ')'));
////
////                            }
////
////                            // this line traduces the DBMS type name
////                            // in this application's field type name.
////                            // as found in alpha.ini
////                            //$lib_type = 'dummy';
////                            $table = new Table();
////
////                            $lib_type = __app()->get('library/' . $table->getDBMS() . '/types/' . $type);
////
////                            $class = '\\b166er\\' . $lib_type . 'Field';
////
////                            // if this type exists
////                            if ($lib_type) {
////
////                                //if so, create the class with that name
////                                if (class_exists($class)) {
////                                    $fld = new $class($arr[0], Field::SQL_NO_ATTRIBUTE, $type_len);
////                                } else {
////                                    throw new \Exception('No class with name: ' . $class);
////                                }
////                                //adds the field to the part object
////                                $part->add($fld);
////                            } else {
////                                throw new \Exception('No class with name: ' . $class);
////                            }
////
////                            // at this point iterate tru all line words
////                            // looking for field informations
////                            foreach ($arr as $a => $b) {
////                                switch ($b) {
////                                    case 'AUTOINCREMENT':
////                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_AUTO_INCREMENT);
////                                        $part->set($arr[0] . '/AUTO_INCREMENT', '');
////                                        break;
////                                    case 'PRIMARY':
////                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
////                                        $part->set($arr[0] . '/PRIMARY_KEY', '');
////                                        break;
////                                    case 'UNIQUE':
////                                        $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_UNIQUE);
////                                        $part->set($arr[0] . '/UNIQUE', '');
////                                        break;
////                                    case 'NOT':
////                                        //checks whether the next word is NULL
////                                        if ($arr[$a + 1] == 'NULL') {
////                                            $part->set($arr[0] . '/ATTRIBUTES', $part->get($arr[0] . '/ATTRIBUTES') | Field::SQL_REQUIRED);
////                                            $part->set($arr[0] . '/REQUIRED', '');
////                                        }
////                                        break;
////                                }
////                            }
////                    } //end switch
////                    $it->next();
////                } //end while
////                return $part;
////            } else {
////                throw new \PDOException('PDO error');
////            }
////
////        } catch (\Exception $e) {
////
////            exception_display($e);
////            die();
////
////        }
////    }
////
////
//    public static function query_view_schema_from_database($view) : Part
//    {
//
//    }
////    public static function query_schema_from_database($object):Part{
////        //check whether the class is already defined
////        $class = '\\b166er\\' . $object;
////        //if so, create the class with that name
////        if (class_exists($class)) {
////            $part = new $class();
////        } else {
////            //or create a generic part class
////            $part = new Part();
////        }
////        //set the key to the part name
////        $part->setKey($object);
////
////    }
    public static function query_database_object_names($type)
    {
        try {
            switch ($type) {
                case DBU::Tables :
                    return "SELECT tbl_name FROM sqlite_master WHERE (sqlite_master.type = 'table' AND sqlite_master.tbl_name !='sqlite_sequence') ORDER BY name;";
                    break;
                case DBU::Views :
                    return "SELECT tbl_name FROM sqlite_master WHERE (sqlite_master.type = 'view') ORDER BY name;";
                    break;
                default :
                    throw new \InvalidArgumentException('Passed value is not a table or a view');
            }
        } catch (\Exception $e) {
            exception_display($e);
            die();
        }

    }

    public static function merge_table_schema_from_database(Part $prt, TableDef $tbd)
    {

        //sqlite way to retrieve table schema.  oh my god! forgive me
        $sql = "SELECT sqlite_master.sql FROM sqlite_master WHERE (sqlite_master.tbl_name='" . $tbd->key() . "')";
        $pdo = $tbd->getPDO();
        try {
            //execute query
            $stmt = $pdo->prepare($sql);
            //
            if ($stmt->execute()) {
                $ret = $stmt->fetch();
                //at this point $schema contains the table definition:
                //CREATE TABLE etc....
                $schema = $ret['sql'];
                //rip in lines
                $schema = explode(PHP_EOL, $schema);
                //this var will contain the database type schema
                //as reported on alpha.ini
                $table_def_container = new Container();

                //scan every line
                foreach ($schema as $n => $s) {
                    $s = trim($s);
                    $s = trim($s, ',');
                    $table_def_container->set($n, $s);
                }

                // create the iterator
                $it = $table_def_container->createIterator();
                //scan every member
                while ($it->valid()) {
                    //the value contains the line of the database query
                    $str = $it->current()->get('');
                    //creates an array of words
                    $arr = explode(' ', $str);
                    //scans every word the line starts with.
                    //default case is referred to field description lines.
                    switch ($arr[0]) {
                        case ')':
                            break;
                        case 'CREATE':
                            //the table name should be $arr[2]
                            //should be a check here
                            break;
                        case 'PRIMARY':
                            $ts = '';
                            foreach ($arr as $w) {
                                $ts .= $w;
                            }
                            $ts = substr($ts, 10);
                            $ts = trim($ts, '()');
                            $p_arr = explode(',', $ts);
                            //makes an array of all the parent keys
                            //and put it in $par_field
                            $par_field = trim($arr[7], '(`)');
                            foreach ($p_arr as $pk) {
                                $temp_fld = trim($pk, ' ');
                                $prt->set($temp_fld . '/ATTRIBUTES', $prt->get($temp_fld . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
                                $prt->set($temp_fld . '/PRIMARY_KEY', '');
                            }
                            break;
                        case 'FOREIGN':
                            //first make an array of all the foreign keys
                            //and put it in $chd
                            $chd = explode(', ', trim($arr[2], '()'));
                            //in $arr[4] there's the parent table name
                            $par_table = $arr[4];
                            //makes an array of all the parent keys
                            //and put it in $par_field
                            $par_field = explode(', ', trim($arr[5], '()'));

                            // $ou means ON UPDATE.
                            $ou = '';
                            // Guess what $od means
                            $od = '';

                            // Now it's time to give a look to all the words
                            //in line
                            foreach ($arr as $a => $b) {
                                // to give a look
                                // if doesn't seem to be the best choice
                                // good for now. To recode later
                                if ($b == 'ON') {
                                    //checks the word next to 'ON' (on the right of course)
                                    switch ($arr[$a + 1]) {
                                        // self explanatory
                                        case 'UPDATE':
                                            if ($arr[$a + 2] == 'NO') {
                                                $ou = 'NO ACTION';
                                                break;
                                            }
                                            $ou = $arr[$a + 2];
                                            break;
                                        case 'DELETE':
                                            if ($arr[$a + 2] == 'NO') {
                                                $od = 'NO ACTION';
                                                break;
                                            }
                                            if ($arr[$a + 2] == 'SET') {
                                                $od = 'SET ' . $arr[$a + 3];
                                                break;
                                            }
                                            $od = $arr[$a + 2];
                                            break;
                                    }
                                }
                            }

                            // $chd contains all the foreign key names.
                            // This foreach creates subkeys in the field container
                            // to describe the foreign key data.
                            // provided that all the fields have already been created
                            foreach ($chd as $a => $b) {
                                $prt->set($b . '/ATTRIBUTES', $prt->get($b . '/ATTRIBUTES') | Field::SQL_FOREIGN_KEY);
                                $prt->set($b . '/FOREIGN_KEY', '');
                                $prt->set($b . '/FOREIGN_KEY/PARENT', $par_table . '.' . $par_field[$a]);
                                $prt->set($b . '/FOREIGN_KEY/PARENT/TABLE', $par_table);
                                $prt->set($b . '/FOREIGN_KEY/PARENT/FIELD', $par_field[$a]);
                                $prt->set($b . '/FOREIGN_KEY/ON_UPDATE', $ou);
                                $prt->set($b . '/FOREIGN_KEY/ON_DELETE', $od);
                            }

                            break;
                        //supposedly a field description line
                        default:
                            //type is the second word
                            $type = $arr[1];
                            $type_len = 0;

                            //check if len is specified.
                            //if so captures type_len
                            if (strpos($type, '(')) {
                                $type_arr = explode('(', $type);
                                $type = $type_arr[0];
                                $type_len = intval(trim($type_arr[1], ')'));

                            }

                            // this line traduces the DBMS type name
                            // in this application's field type name.
                            // as found in alpha.ini
                            //$lib_type = 'dummy';
                            $lib_type = __app()->get('library/' . $tbd->getDBMS() . '/types/' . $type);
                            $class = '';
                            $_name = trim($arr[0], '"' . "'");
                            // if this type exists
                            if ($lib_type) {
                                $class = '\\b166er\\' . $lib_type . 'Field';
                                //if so, create the class with that name
                                if (class_exists($class)) {
                                    $fld = new $class($_name, Field::SQL_NO_ATTRIBUTE, $type_len);
                                } else {
                                    throw new \Exception('No class with name: ' . $class);
                                }
                                //adds the field to the part object
                                $prt->add($fld);
                            } else {
                                throw new \Exception('No class with name: ' . $class);
                            }

                            // at this point iterate tru all line words
                            // looking for field informations
                            foreach ($arr as $a => $b) {
                                switch ($b) {
                                    case 'AUTOINCREMENT':
                                        $prt->set($_name . '/ATTRIBUTES', $prt->get($_name . '/ATTRIBUTES') | Field::SQL_AUTO_INCREMENT);
                                        $prt->set($_name . '/AUTO_INCREMENT', '');
                                        break;
                                    case 'PRIMARY':
                                        $prt->set($_name . '/ATTRIBUTES', $prt->get($_name . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
                                        $prt->set($_name . '/PRIMARY_KEY', '');
                                        break;
                                    case 'UNIQUE':
                                        $prt->set($_name . '/ATTRIBUTES', $prt->get($_name . '/ATTRIBUTES') | Field::SQL_UNIQUE);
                                        $prt->set($_name . '/UNIQUE', '');
                                        break;
                                    case 'NOT':
                                        //checks whether the next word is NULL
                                        if ($arr[$a + 1] == 'NULL') {
                                            $prt->set($_name . '/ATTRIBUTES', $prt->get($_name . '/ATTRIBUTES') | Field::SQL_REQUIRED);
                                            $prt->set($_name . '/REQUIRED', '');
                                        }
                                        break;
                                }
                            }
                    } //end switch
                    $it->next();
                } //end while
                //return $prt;

            } else {
                throw new \PDOException('PDO error');
            }

        } catch (\Exception $e) {

            exception_display($e);
            die();

        }

    }

    public static function merge_view_schema_from_database(Part $prt, ViewDef $vid)
    {
        // TODO: Implement merge_view_schema_from_database() method.
    }
}

class mysqlDBU extends DBU
{
    public static function query_table_names()
    {
        return 'SHOW TABLES';
    }

    public static function query_view_names()
    {
        return "SHOW FULL TABLES IN alpha WHERE TABLE_TYPE LIKE 'VIEW';";
    }

    //php7: public static function query_table_schema_from_database($table):Part
    public static function query_table_schema_from_database($table)
    {

        $parent = parent::query_table_schema_from_database($table);
        if ($parent) return $parent;

        // consider adding a require once to get the
        // class definition only if required
        // Should be here
        $class = '\\b166er\\' . $table->key();
        if (class_exists($class)) {
            $part = new $class();
        } else {
            $part = new Part();
        }
        $part->setKey($table->key());

        $sql = 'SHOW CREATE TABLE ' . $table->key();
        $pdo = $table->getPDO();
        $stmt = $pdo->prepare($sql);
        try {
            if ($stmt->execute()) {
                $ret = $stmt->fetch(\PDO::FETCH_ASSOC);
                //at this point $schema contains the table definition:
                //CREATE TABLE etc....
                $schema = $ret['Create Table'];
                //rip in lines
                $schema = explode(PHP_EOL, $schema);
                //this var will contain the database type schema
                //as reported on alpha.ini
                $table_def_container = new Container();

                //scan every line
                foreach ($schema as $n => $s) {
                    $s = trim($s);
                    $s = trim($s, '\r');
                    $s = trim($s, ',');
                    $table_def_container->set($n, $s);
                }
                //
                // create the iterator

                $it = $table_def_container->createIterator();
                //scan every member
                while ($it->valid()) {
                    //the value contains the line of the database query
                    $str = $it->current()->get('');
                    //creates an array of words
                    $arr = explode(' ', $str);
                    //scans every word the line starts with.
                    //default case is referred to field description lines.
                    switch ($arr[0]) {
                        case ')':
                            break;
                        case 'CREATE':
                            //the table name should be trim($arr[2],"`")
                            //should be a check here
                            break;
                        case 'PRIMARY':
                            $g = trim($arr[2], ',');
                            $g = trim($g, '()');
                            $temp_fld = trim($g, '`');
                            $part->set($temp_fld . '/ATTRIBUTES', $part->get($temp_fld . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
                            $part->set($temp_fld . '/PRIMARY_KEY', '');
                            break;
                        case 'UNIQUE':
                            $g = trim($arr[2], ',');
                            $g = trim($g, '()');
                            $temp_fld = trim($g, '`');
                            $part->set($temp_fld . '/ATTRIBUTES', $part->get($temp_fld . '/ATTRIBUTES') | Field::SQL_UNIQUE);
                            $part->set($temp_fld . '/UNIQUE', '');
                            break;
                        case 'KEY':
                            $g = trim($arr[2], ',');
                            $g = trim($g, '()');
                            $temp_fld = trim($g, '`');
                            $part->set($temp_fld . '/ATTRIBUTES', $part->get($temp_fld . '/ATTRIBUTES') | Field::SQL_INDEX);
                            $part->set($temp_fld . '/INDEX', '');
                            break;
                        case 'CONSTRAINT':

                            if ($arr[2] == 'FOREIGN') {
                                //first make an array of all the foreign keys
                                //and put it in $chd
                                $chd = explode(', ', trim($arr[4], '(`)'));
                                //in $arr[4] there's the parent table name
                                $par_table = trim($arr[6], '`');
                                //makes an array of all the parent keys
                                //and put it in $par_field
                                $par_field = trim($arr[7], '(`)');

                                // $ou means ON UPDATE.
                                $ou = '';
                                // Guess what $od means
                                $od = '';

                                // Now it's time to give a look to all the words
                                //in line
                                foreach ($arr as $a => $b) {
                                    // to give a look.
                                    // it doesn't seem to be the best choice.
                                    // good for now. To recode later
                                    if ($b == 'ON') {
                                        //checks the word next to 'ON' (on the right of course)
                                        switch ($arr[$a + 1]) {
                                            // self explanatory
                                            case 'UPDATE':
                                                if ($arr[$a + 2] == 'NO') {
                                                    $ou = 'NO ACTION';
                                                    break;
                                                }
                                                $ou = $arr[$a + 2];
                                                break;
                                            case 'DELETE':
                                                if ($arr[$a + 2] == 'NO') {
                                                    $od = 'NO ACTION';
                                                    break;
                                                }
                                                if ($arr[$a + 2] == 'SET') {
                                                    $od = 'SET ' . $arr[$a + 3];
                                                    break;
                                                }
                                                $od = $arr[$a + 2];
                                                break;
                                        }
                                    }

                                }
                                // $chd contains all the foreign key names.
                                // This foreach creates subkeys in the field container
                                // to describe the foreign key constraint.
                                // provided that all the fields have already been created
                                foreach ($chd as $a => $b) {
                                    $part->set($b . '/ATTRIBUTES', $part->get($b . '/ATTRIBUTES') & Field::SQL_FOREIGN_KEY);
                                    $part->set($b . '/FOREIGN_KEY', '');
                                    $part->set($b . '/FOREIGN_KEY/PARENT', $par_table . '.' . $par_field);
                                    $part->set($b . '/FOREIGN_KEY/PARENT/TABLE', $par_table);
                                    $part->set($b . '/FOREIGN_KEY/PARENT/FIELD', $par_field);
                                    $part->set($b . '/FOREIGN_KEY/ON_UPDATE', $ou);
                                    $part->set($b . '/FOREIGN_KEY/ON_DELETE', $od);
                                }
                            }
                            break;
                        case ')':
                            //this should be the last line
                            //comments are here as well as innodb collation etc
                            break;
                        //supposedly a field description line
                        default:
                            //type is the second word
                            $fld_name = trim($arr[0], "`");
                            $type = $arr[1];
                            $type_len = 0;

                            //check if len is specified.
                            //if so captures type_len
                            if (strpos($type, '(')) {
                                $type_arr = explode('(', $type);
                                $type = $type_arr[0];
                                $type_len = intval(trim($type_arr[1], ')'));

                            }

                            // this line traduces the DBMS type name
                            // in this application's field type name.
                            // as found in alpha.ini
                            $lib_type = __app()->get('config/library/' . $table->getDBMS() . '/types/' . $type);

                            // if this type exists
                            if ($lib_type) {
                                $class = '\\b166er\\' . $lib_type . 'Field';
                                //if so, create the class with that name
                                if (class_exists($class)) {
                                    $fld = new $class($fld_name, Field::SQL_NO_ATTRIBUTE, $type_len);
                                } else {
                                    throw new \Exception('No class with name: ' . $class);
                                }
                                //adds the field to the part object
                                $part->add($fld);
                            } else {
                                throw new \Exception('No class with name: ' . $class);
                            }

                            // at this point iterate tru all line words
                            // looking for field informations
                            foreach ($arr as $a => $b) {
                                switch ($b) {
                                    case 'AUTO_INCREMENT':

                                        $part->set($fld_name . '/ATTRIBUTES', $part->get($fld_name . '/ATTRIBUTES') | Field::SQL_AUTO_INCREMENT);
                                        $part->set($fld_name . '/AUTO_INCREMENT', '');

                                        break;
                                    case 'DEFAULT':
                                        $part->set($fld_name . '/DEFAULT', $arr[$a + 1]);
                                        break;
                                    case 'NOT':
                                        //checks whether the next word is NULL
                                        if ($arr[$a + 1] == 'NULL') {
                                            $part->set($fld_name . '/ATTRIBUTES', $part->get($fld_name . '/ATTRIBUTES') | Field::SQL_REQUIRED);
                                            $part->set($fld_name . '/REQUIRED', '');
                                        }
                                        break;
                                }
                            }
                    } //end switch
                    $it->next();
                } //end while
                return $part;

            }


        } catch (\Exception $e) {

            exception_display($e);
            die();

        }
    }

    //php7: public static function query_view_schema_from_database($view):Part
    public static function query_view_schema_from_database($view)
    {

    }

    //php7: public static function query_schema_from_database($object):Part
    public static function query_schema_from_database($object)
    {

    }

    public static function query_database_object_names($type)
    {
        try {
            switch ($type) {
                case DBU::Tables :
                    return 'SHOW TABLES';
                    break;
                case DBU::Views :
                    return "SHOW FULL TABLES IN alpha WHERE TABLE_TYPE LIKE 'VIEW';";
                    break;
                default :
                    throw new \InvalidArgumentException('Passed value is not a table or a view');
            }
        } catch (\Exception $e) {
            exception_display($e);
            die();
        }

    }

    public static function merge_table_schema_from_database(Part $prt, TableDef $tbd)
    {

        $sql = 'SHOW CREATE TABLE ' . $tbd->key();
        $pdo = $tbd->getPDO();
        $stmt = $pdo->prepare($sql);
        try {
            if ($stmt->execute()) {
                $ret = $stmt->fetch(\PDO::FETCH_ASSOC);
                //at this point $schema contains the table definition:
                //CREATE TABLE etc....
                $schema = $ret['Create Table'];
                //rip in lines
                $schema = explode(PHP_EOL, $schema);
                //this var will contain the database type schema
                //as reported on alpha.ini
                $tbd_def_container = new Container();

                //scan every line
                foreach ($schema as $n => $s) {
                    $s = trim($s);
                    $s = trim($s, '\r');
                    $s = trim($s, ',');
                    $tbd_def_container->set($n, $s);
                }
                //
                // create the iterator

                $it = $tbd_def_container->createIterator();
                //scan every member
                while ($it->valid()) {
                    //the value contains the line of the database query
                    $str = $it->current()->get('');
                    //creates an array of words
                    $arr = explode(' ', $str);
                    //scans every word the line starts with.
                    //default case is referred to field description lines.
                    switch ($arr[0]) {
                        case ')':
                            break;
                        case 'CREATE':
                            //the table name should be trim($arr[2],"`")
                            //should be a check here
                            break;
                        case 'PRIMARY':
                            $g = trim($arr[2], ',');
                            $g = trim($g, '()');
                            $temp_fld = trim($g, '`');
                            $prt->set($temp_fld . '/ATTRIBUTES', $prt->get($temp_fld . '/ATTRIBUTES') | Field::SQL_PRIMARY_KEY);
                            $prt->set($temp_fld . '/PRIMARY_KEY', '');
                            break;
                        case 'UNIQUE':
                            $g = trim($arr[2], ',');
                            $g = trim($g, '()');
                            $temp_fld = trim($g, '`');
                            $prt->set($temp_fld . '/ATTRIBUTES', $prt->get($temp_fld . '/ATTRIBUTES') | Field::SQL_UNIQUE);
                            $prt->set($temp_fld . '/UNIQUE', '');
                            break;
                        case 'KEY':
                            $g = trim($arr[2], ',');
                            $g = trim($g, '()');
                            $temp_fld = trim($g, '`');
                            $prt->set($temp_fld . '/ATTRIBUTES', $prt->get($temp_fld . '/ATTRIBUTES') | Field::SQL_INDEX);
                            $prt->set($temp_fld . '/INDEX', '');
                            break;
                        case 'CONSTRAINT':

                            if ($arr[2] == 'FOREIGN') {
                                //first make an array of all the foreign keys
                                //and put it in $chd
                                $chd = explode(', ', trim($arr[4], '(`)'));
                                //in $arr[4] there's the parent table name
                                $par_table = trim($arr[6], '`');
                                //makes an array of all the parent keys
                                //and put it in $par_field
                                $par_field = trim($arr[7], '(`)');

                                // $ou means ON UPDATE.
                                $ou = '';
                                // Guess what $od means
                                $od = '';

                                // Now it's time to give a look to all the words
                                //in line
                                foreach ($arr as $a => $b) {
                                    // to give a look.
                                    // it doesn't seem to be the best choice.
                                    // good for now. To recode later
                                    if ($b == 'ON') {
                                        //checks the word next to 'ON' (on the right of course)
                                        switch ($arr[$a + 1]) {
                                            // self explanatory
                                            case 'UPDATE':
                                                if ($arr[$a + 2] == 'NO') {
                                                    $ou = 'NO ACTION';
                                                    break;
                                                }
                                                $ou = $arr[$a + 2];
                                                break;
                                            case 'DELETE':
                                                if ($arr[$a + 2] == 'NO') {
                                                    $od = 'NO ACTION';
                                                    break;
                                                }
                                                if ($arr[$a + 2] == 'SET') {
                                                    $od = 'SET ' . $arr[$a + 3];
                                                    break;
                                                }
                                                $od = $arr[$a + 2];
                                                break;
                                        }
                                    }

                                }
                                // $chd contains all the foreign key names.
                                // This foreach creates subkeys in the field container
                                // to describe the foreign key constraint.
                                // provided that all the fields have already been created
                                foreach ($chd as $a => $b) {
                                    $prt->set($b . '/ATTRIBUTES', $prt->get($b . '/ATTRIBUTES') & Field::SQL_FOREIGN_KEY);
                                    $prt->set($b . '/FOREIGN_KEY', '');
                                    $prt->set($b . '/FOREIGN_KEY/PARENT', $par_table . '.' . $par_field);
                                    $prt->set($b . '/FOREIGN_KEY/PARENT/TABLE', $par_table);
                                    $prt->set($b . '/FOREIGN_KEY/PARENT/FIELD', $par_field);
                                    $prt->set($b . '/FOREIGN_KEY/ON_UPDATE', $ou);
                                    $prt->set($b . '/FOREIGN_KEY/ON_DELETE', $od);
                                }
                            }
                            break;
                        case ')':
                            //this should be the last line
                            //comments are here as well as innodb collation etc
                            break;
                        //supposedly a field description line
                        default:
                            //type is the second word
                            $fld_name = trim($arr[0], "`");
                            $type = $arr[1];
                            $type_len = 0;

                            //check if len is specified.
                            //if so captures type_len
                            if (strpos($type, '(')) {
                                $type_arr = explode('(', $type);
                                $type = $type_arr[0];
                                $type_len = intval(trim($type_arr[1], ')'));

                            }

                            // this line traduces the DBMS type name
                            // in this application's field type name.
                            // as found in alpha.ini
                            $lib_type = __app()->get('library/' . $tbd->getDBMS() . '/types/' . $type);
                            $class = '';
                            // if this type exists
                            if ($lib_type) {
                                $class = '\\b166er\\' . $lib_type . 'Field';
                                //if so, create the class with that name
                                if (class_exists($class)) {
                                    $fld = new $class($fld_name, Field::SQL_NO_ATTRIBUTE, $type_len);
                                } else {
                                    throw new \Exception('No class with name: ' . $class);
                                }
                                //adds the field to the part object
                                $prt->add($fld);
                            } else {
                                throw new \Exception('No class with name: ' . $class);
                            }

                            // at this point iterate tru all line words
                            // looking for field informations
                            foreach ($arr as $a => $b) {
                                switch ($b) {
                                    case 'AUTO_INCREMENT':

                                        $prt->set($fld_name . '/ATTRIBUTES', $prt->get($fld_name . '/ATTRIBUTES') | Field::SQL_AUTO_INCREMENT);
                                        $prt->set($fld_name . '/AUTO_INCREMENT', '');

                                        break;
                                    case 'DEFAULT':
                                        $prt->set($fld_name . '/DEFAULT', $arr[$a + 1]);
                                        break;
                                    case 'NOT':
                                        //checks whether the next word is NULL
                                        if ($arr[$a + 1] == 'NULL') {
                                            $prt->set($fld_name . '/ATTRIBUTES', $prt->get($fld_name . '/ATTRIBUTES') | Field::SQL_REQUIRED);
                                            $prt->set($fld_name . '/REQUIRED', '');
                                        }
                                        break;
                                }
                            }
                    } //end switch
                    $it->next();
                } //end while
            }


        } catch (\Exception $e) {

            exception_display($e);
            die();

        }
    }
    
    public static function merge_view_schema_from_database(Part $prt, ViewDef $vid)
    {
        // TODO: Implement merge_view_schema_from_database() method.
    }
}

class odbcDBU extends DBU
{
    public static function query_table_names()
    {
        return '';
    }

    public static function query_view_names()
    {

    }

    //php7: public static function query_table_schema_from_database($table):Part
    public static function query_table_schema_from_database($table)
    {
        //odbc doesn't have any way to retrieve table schema (as far as I know)
        //update: there should be a way, but you need admin rights (to be continued later...)
        return new Part();
    }

    //php7: public static function query_view_schema_from_database($view):Part
    public static function query_view_schema_from_database($view)
    {

    }

    public static function query_database_object_names($type)
    {
        return '';
    }

    public static function merge_table_schema_from_database(Part $prt, TableDef $tbd)
    {
        // TODO: Implement merge_table_schema_from_database() method.
    }

    public static function merge_view_schema_from_database(Part $prt, ViewDef $vid)
    {
        // TODO: Implement merge_view_schema_from_database() method.
    }
}

/*
 *
 *
 *
 *
 *
 *
 *      DAO
 *
 *
 *
 *
 *
 *
 *
 */

abstract class Dao extends DataSet implements Iclass_definition_existance
{
    use class_definition_existance;

    const LOAD = 0b0;
    const SAVE_INSERT = 0b1;
    const SAVE_UPDATE = 0b10;
    const DELETE = 0b100;
    const FIND = 0b1000;
    protected $table;
    protected $part;
    protected $DBtable;

    public function __construct($table)
    {
        try {
            if ($table instanceof DatabaseStructure) {
                $this->setPDO($table->getPDO());
                $this->table = $table;
                $this->part = $table->key();
            } else {
                //supposedly a string by now
                $temp = object_defined_in_db_collection($table, __app()->contains('database'));
                //$temp = __app()->isTable($table);
                if ($temp instanceof DatabaseStructure) {
                    $this->buffer_DBMS = $temp->getDBMS();
                    $this->buffer_pdo = $temp->getPDO();
                    $this->table = $temp;
                    $this->part = $table;
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            exception_display($e);
            die();
        }

    }

    //php7: public function newRecord() : Part
    public function newRecord()
    {
        $DBU = DBU::deploy($this->getDBMS());
        $ret = $DBU::query_table_schema_from_database($this->table);

        $ret->fill();
        return $ret;

    }

    //php7: protected function import_part_file(string $nam, string $res = 'class') : bool
    protected function import_part_file($nam, $res = 'class') 
    {
        global $_path;

        $tar = array(
            $_path . "/parts/" . $nam . "/" . $res . ".php",
            $_path . "/" . $nam . "/" . $res . ".php",
            __DIR__ . "/" . $nam . "-" . $res . ".php",
            __DIR__ . "/parts/" . $nam . "-" . $res . ".php"
        );

        foreach ($tar as $pth) {
            $tmp = $pth . "/" . $res . ".php";
            if (is_file($tmp)) {
                require_once($tmp);
                return true;
            }
        }
        return false;
    }

    //php7: public function newPart() : Part
    public function newPart() 
    {

        /*
         * checks whether there's an object with that name in __app
         */
        $_proto = __app()->contains('prototype/' . $this->table->get());
        /*
         * if so return a copy of it
         */
        if ($_proto) {
            return $_proto->copy();
        }

        /*
         * at this point there's no prototype definition
         * with that name in __app.
         *
         * $_class stores the name the class should have if
         * it existed
        */

        $_class = "\\b166er\\" . $this->table->get();

        /*
         * importing the definition of the class provided the path
         * is a meaningful one.
         */
        if (!class_exists($_class)) {
            $this->import_part_file($this->table->get(), 'class');

        }

        /*
         * try again to check for class availability in the project scope
         */
        if (class_exists($_class)) {
            $ret = new $_class();

        } else {

            /*
             * if not, create a simple Part object. No more, no less
             */
            $ret = new Part();

        }

        /*
         *  so let's check for some database definition
         *  to store in $def
         */
        $def = __app()->isTable($this->table->get());

        /*
         * if so
         */
        if ($def) {

            $DBU = DBU::deploy($this->getDBMS());

            /*
             * merge the data into the object
             */
            $DBU->merge_schema_from_database($ret, $this->table);
        }

        /*
         * complete the definition of the object with additional
         * informations provided by the fill() function
         */
        $ret->fill();

        /*
         * add the freshly created object to the prototype collection
         */
        __app()->contains('prototype')->add($ret);

        /*
         * and return a copy of it
         */
        return $ret->copy();

    }

    //php7: public function validate(Part $prt) : bool
    public function validate(Part $prt) 
    {
        $call = $this->caller_function() . '_validate';

        $ret = $this->$call($prt);

        return $ret;
    }

    //php7: public function load_validate(Part $prt) : bool
    public function load_validate(Part $prt) 
    {
        $ret = true;

        $it = $prt->createAttributesIterator(Field::SQL_PRIMARY_KEY);
        while ($it->valid()) {

            if (is_null($it->current()->get(''))) {
                $it->current()->errorSet(ERR::REQUIRED_DATA);
                $ret = false;
                //error
            } else {
                if (!$it->current()->Validate()) {
                    $ret = false;
                    //error
                }
            }
            $it->next();
        }

        return $ret;
    }

    //php7: public function save_new_validate(Part $prt) : bool
    public function save_new_validate(Part $prt) 
    {
        $ret = true;

        //analyze primary key stuff
        $it = $prt->createIterator();
        while ($it->valid()) {
            $f = $it->current()->key();
            if (($it->current()->get('ATTRIBUTES') & Field::SQL_PRIMARY_KEY) == Field::SQL_PRIMARY_KEY) {
                if (($it->current()->get('ATTRIBUTES') & Field::SQL_AUTO_INCREMENT) == Field::SQL_AUTO_INCREMENT) {
                    if (!(is_null($it->current()->get()))) {
                        $it->current()->errorSet(ERR::AUTO_INCREMENT_FIELD, 'this field should be blank');
                        $ret = false;
                    }
                } else {
                    if (is_null($it->current()->get())) {
                        $it->current()->errorSet(ERR::REQUIRED_DATA);
                        $ret = false;
                    } else {
                        if (!$it->current()->Validate()) {
                            $ret = false;
                            //error
                        }
                    }
                }


            } else {

                if (($it->current()->get('ATTRIBUTES') & Field::SQL_REQUIRED) == Field::SQL_REQUIRED) {
                    if ((is_null($it->current()->get('')))) {
                        $it->current()->errorSet(ERR::REQUIRED_DATA, 'this field is required');
                        $ret = false;
                    } else {
                        if (!$it->current()->Validate()) {
                            $ret = false;
                            //error
                        }
                    }

                }
            }
            if (($it->current()->get('ATTRIBUTES') & Field::SQL_FOREIGN_KEY) == Field::SQL_FOREIGN_KEY) {
                if ((($it->current()->get('ATTRIBUTES') & Field::SQL_REQUIRED) == Field::SQL_REQUIRED)
                    && (!is_null($it->current()->get()))
                ) {
                    $parent_table = $it->current()->get('FOREIGN_KEY/PARENT/TABLE');
                    $parent_field = $it->current()->get('FOREIGN_KEY/PARENT/FIELD');

                    $dao = (new Dao_maker($parent_table))->newDao();
                    //$dao = new partDao($parent_table);
                    $rec = $dao->newRecord();
                    $rec->set($parent_field, $it->current()->get());
                    $result = $dao->load($rec);

                    if (empty($result->error)) {
                        //$r = $prt->set($it->current()->key() . '/FOREIGN_KEY/PARENT/RECORD', $result);
                    } else {
                        $it->current()->errorSet(ERR::FOREIGN_KEY_NOT_EXISTING, 'failed to load foreign key');
                        $ret = false;
                    }
                } else {

                }
            }
            $it->next();
        }
        return $ret;
    }

    //php7: public function update_existing_validate(Part $prt) : bool
    public function update_existing_validate(Part $prt) 
    {
        $ret = true;

        //analyze primary key stuff
        $it = $prt->createIterator();
        while ($it->valid()) {


            if (($it->current()->get('ATTRIBUTES') & Field::SQL_PRIMARY_KEY) == Field::SQL_PRIMARY_KEY) {
                //if (($it->current()->is(attr::PRIMARY_KEY))) {
                if (is_null($it->current()->get())) {
                    $it->current()->errorSet(ERR::REQUIRED_DATA, 'primary key required');
                    $ret = false;
                } else {
                    if (!$it->current()->Validate()) {
                        $ret = false;
                        //error
                    }
                }

                if (($it->current()->get('ATTRIBUTES') & Field::SQL_REQUIRED) == Field::SQL_REQUIRED) {
                    //if (($it->current()->is(attr::REQUIRED))) {
                    if ((is_null($it->current()->get('')))) {
                        $it->current()->errorSet(ERR::REQUIRED_DATA, 'this field is required');
                        $ret = false;
                    } else {
                        if (!$it->current()->Validate()) {
                            $ret = false;
                            //error
                        }
                    }

                }
            }
            if (($it->current()->get('ATTRIBUTES') & Field::SQL_FOREIGN_KEY) == Field::SQL_FOREIGN_KEY) {
                if ((($it->current()->get('ATTRIBUTES') & Field::SQL_REQUIRED) == Field::SQL_REQUIRED)
                    //if (($it->current()->is(attr::FOREIGN_KEY))) {
                    //    if (($it->current()->is(attr::REQUIRED)) && (!is_null($it->current()->get()))
                ) {
                    $parent_table = $it->current()->get('FOREIGN_KEY/PARENT/TABLE');
                    $parent_field = $it->current()->get('FOREIGN_KEY/PARENT/FIELD');

                    $dao = (new Dao_maker($parent_table))->newDao();
                    $rec = $dao->newRecord();
                    $rec->set($parent_field, $it->current()->get());
                    $result = $dao->load($rec);

                    if (empty($result->error)) {
                        //$r = $prt->set($it->current()->key() . '/FOREIGN_KEY/PARENT/RECORD', $result);
                    } else {
                        $it->current()->errorSet(ERR::FOREIGN_KEY_NOT_EXISTING, 'failed to load foreign key');
                        $ret = false;
                    }
                } else {

                }
            }
            $it->next();
        }

        $rec = $this->newRecord();
        $it = $prt->createAttributesIterator(Field::SQL_PRIMARY_KEY);
        while ($it->valid()) {
            $rec->set($it->current()->key(), $it->current()->get());
            $it->next();
        }

        $this->load($rec);

        if (!empty($rec->error)) {
            $prt->error = $rec->error;
            $prt->message = 'record not found';
            $ret = false;
        }

        return $ret;
    }

    //php7: public function delete_validate(Part $prt) : bool
    public function delete_validate(Part $prt) 
    {
    }

    //php7: public function load(Part $prt) : Part
    public function load(Part $prt) 
    {
        $val = $this->validate($prt);
        if (!$val) return $prt;
        $DBU = DBU::deploy($prt->getDBMS());
        $qselect = $DBU::load_statement($prt);

        try {
            $stmt = $prt->getPDO()->prepare($qselect);

            if ($stmt->execute()) {

                $ret = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($ret) {
                    $im = $prt->import($ret);
                } else {
                    $prt->error = ERR::FETCH_ERROR;
                    $prt->message = 'no fetch!';
                }
            } else {
                $err = $stmt->errorInfo();
                $prt->error = $err[0];
                $prt->message = $err[2];
            }
        } catch (\Exception $e) {
            //throw new DatabaseException;
        }
        return $prt;
    }

    //php7: public function save_new(Part $prt):Part
    public function save_new(Part $prt)
    {
        $val = $this->validate($prt);
        if (!$val) return $prt;

        $DBU = DBU::deploy($prt->getDBMS());
        $sql = $DBU::save_new_statement($prt);

        try {
            $stmt = $prt->getPDO()->prepare($sql);

            if ($stmt->execute()) {
                $prt->message = 'successfully saved new element';
                $it = $prt->createAttributesIterator(Field::SQL_PRIMARY_KEY);
                while ($it->valid()) {
                    if (($it->current()->get('ATTRIBUTES') & Field::SQL_AUTO_INCREMENT) == Field::SQL_AUTO_INCREMENT) {
                        $last = intval($prt->getPDO()->lastInsertId());
                        $prt->set($it->current()->key(), $last);
                    }
                    $it->next();
                }
                $this->load($prt);
            } else {
                $err = $stmt->errorInfo();
                $prt->error = $err[0];
                $prt->message = $err[2];
            }
        } catch (\Exception $e) {
            //throw new DatabaseException;
        }
        return $prt;
    }

    //php7: public function update_existing(Part $prt):Part
    public function update_existing(Part $prt)
    {
        $val = $this->validate($prt);
        if (!$val) return $prt;

        $DBU = DBU::deploy($this->getDBMS());
        $sql = $DBU::update_existing_statement($prt);

        try {
            $stmt = $prt->getPDO()->prepare($sql);

            if ($stmt->execute()) {
                $prt->message = 'element updated successfully';
                $this->load($prt);
            } else {
                $err = $stmt->errorInfo();
                $prt->error = $err[0];
                $prt->message = $err[2];
            }
        } catch (\Exception $e) {
            //throw new DatabaseException;
        }
        return $prt;
    }

    public function delete(Part $prt)
    {

        $DBU = DBU::deploy($prt->getDBMS());
        $sql = $DBU::delete_statement($prt);

        try {
            $stmt = $this->getPDO()->prepare($sql);
            if (!$stmt->execute()) {
                $err = $stmt->errorInfo();
                $prt->error = $err[0];
                $prt->message = $err[2];
            } else {
                //$prt->erase();
                $prt->message = 'element deleted successfully';
            }
        } catch (\Exception $e) {

        }
        return $prt;
    }

    protected function caller_function()
    {
        $trace = debug_backtrace();
        return $trace[2]['function'];
    }

}

class partDao extends Dao
{
    public function __construct($table)
    {
        parent::__construct($table);
    }

}

class DaoCreator extends AbstractCreator
{
    protected $topic = 'dao';

}
//
//class Dao_maker extends Maker
//{
//    private $part;
//
//    public function __construct(string $part)
//    {
//        $this->part = $part;
//
//    }
//
//    public function newDao() : Dao
//    {
//        $class_exists = $this->class_definition_exists($this->part, 'dao');
//
//        if ($class_exists) {
//            $dao_class = '\\b166er\\' . $this->part . 'Dao';
//            return new $dao_class($this->part);
//        } else {
//            return new partDao($this->part);
//        }
//    }
//
//    public function newDao2() : Dao
//    {
//        $dao_class = '\\b166er\\' . $this->part . 'Dao';
//        if (class_exists($dao_class)) {
//            return new $dao_class($this->part);
//        } else {
//            return new partDao($this->part);
//        }
//    }
//
//}