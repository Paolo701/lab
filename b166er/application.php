<?php
/**
 * Created by PhpStorm.
 * User: JorEL
 * Date: 19/06/16
 * Time: 01:25
 */
namespace b166er;

class Application extends Container implements ISingleton
{
    private static $_instance;

    private function __construct()
    {
        try {

            $db_coll = new DatabaseCollection();
            $this->add($db_coll);

            $proto_coll = new PrototypeCollection();
            $this->add($proto_coll);

            /*
             *
             *
             */

            merge_ini_file_in_component('lab.ini', $this);

            /*
             *
             *
             */

            scan_all_tables_imported_from_ini_file($db_coll);

            /*
             *
             *
             */

            //$this->scan_for_pattern_table();

        } catch (\Exception $e) {

            exception_display($e);
            die();

        }

    }

    public static function Instance()
    {
        if (!isset(self::$_instance)) {

            self::$_instance = new Application();
        }
        return self::$_instance;
    }

    protected function scan_for_pattern_table()
    {
        $temp = array();
        $it = $this->contains('database')->createIterator();
        while ($it->valid()) {

            $pdo = new \PDO($it->current()->get('dsn'));
            $stmt = $pdo->prepare('select * from pattern');
            if ($stmt->execute()) {
                $temp = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            if ($temp) {
                $this->set('pattern', '');
                $this->contains('pattern')->import($temp);
                break;
            }
            $it->next();
        } //end while

        return $temp;
    }

    //php7: public function isTable($t) : Table
    public function isTable($t) 
    {
//        $temp = new Table();
        try {
            //if there's no database
            if ($this->contains('database')->size() == 0) {
                throw new \Exception('Appears there is no database defined.');
            } else {
                //if in config there are databases defined
                $it = $this->contains('database')->createIterator();

                while ($it->valid()) {
                    //scroll down all tables inside
                    $tables = $it->current()->contains('tables');
                    if ($tables) {
                        $iit = $tables->createIterator();
                        while ($iit->valid()) {
                            //if matched
                            if ($iit->current()->key() == $t) {
//                                //$temp->import($it->current()->toArray());
//                                $temp->set('pdo', $it->current()->get('pdo'));
//                                $temp->set('DBMS', $it->current()->get('DBMS'));
//                                $temp->pdo = $it->current()->get('pdo');
//                                $temp->DBMS = $it->current()->get('DBMS');
//                                $temp->setKey($iit->current()->key());
//
//                                //$iit->current()->import($it->current()->Child());
                                return $iit->current();
                            }
                            $iit->next();
                        }
                    }
                    $it->next();

                }
            }
            return null;

        } catch (\Exception $e) {

            exception_display($e);
            die();

        }
    }


}

class HTTP_request extends Container implements Irequest

{
    public function Part()
    {
        return $this->get('part');
    }

    public function Verb()
    {
        return $this->get('verb');
    }


}

class HTTP_request_manager
{

    //php7: private function newRequest() : HTTP_request
    private function newRequest()
    {

        return new HTTP_request();
    }

    //php7: public function Parse() : HTTP_request
    public function Parse() 
    {
        $rq = $this->newRequest();

        //$request_uri = $_SERVER['REQUEST_URI'];
        //$request = $_REQUEST;

        $request_uri = '/lab/?part=user&verb=show&user.name=admin&user.groups=';
        $request = '';

        //IMPORTANTE:
        //TENERE PRESENTE CHE FUNZIONA SOLO SE NON SI INDICA IL NOMEFILE TIPO index.php o index.html
        //il formato deve essere http://localhost/alpha7/?XDEBUG_SESSION_STOP_NO_EXEC=netbeans-xdebug
        //

        //tolgo gli slash all'inizio della stringa di richiesta:
        // tipo /alpha/djfjr/?action=list&what=people&caller=home
        $path = ltrim($request_uri, '/');

        //estrae i comandi e li mette in un array
        //$path="alpha2/login/?user=admin&password=alpha";
        $elements = explode('/', $path);

        //procedura per indiduare i parametri
        //se presenti. Per fare ciò per primo
        //cattura in x l'ultimo elemento
        $x = end($elements);

        //dichiaro params fuori dall'if
        $params = array();
        //$strParam;

        //procedura di cattura dei GET in modo alternativo
        //verifica che sia un parametro (che comincia per ?)
        //la versione cool della func è preg_match("/^?(.*)/i",$x)
        //dovrebbe trovare il primo ? e ritornare true ma non va
        //allora adotto una via alternativa con substr
        if (substr($x, 0, 1) == "?") {
            //toglie l'ultimo elemento
            //e lo assegna a params
            $strParam = array_pop($elements);

            //toglie il punto interrogativo all'inizio
            $strParam = ltrim($strParam, "?");


            //crea una matrice di parametri grezzi
            //(sono ancora nello stato: param=value)
            $params = explode("&", $strParam);

            //reitera tra tutti i dati grezzi
            foreach ($params as $each) {
                //esplode la stringa param=value
                //in una matrice dove [0] è key
                //e [1] è value
                $temp = explode("=", $each);

                //aggiunge un component
                //per ogni parametro

                $temp[0] = str_replace('.', '/', $temp[0]);
                $rq->set($temp[0], $temp[1]);
            }
        }
        //Procedura di cattura dei POST con le superglobals
        if ($request) {
            foreach ($request as $k => $v) {

                $k = str_replace('.', '/', $k);
                //aggiunge un component
                //per ogni parametro
                $rq->set($k, $v);
            }
        }

        return (!empty($rq->Part()) && !empty($rq->Verb())) ? $rq : false;
    }

    //php7: public function Extract(HTTP_request $req, string $arg)
    public function Extract(HTTP_request $req, $arg)
    {
        $mkr = new Dao_maker($arg);
        $dao = $mkr->newDao();
        $prt = $dao->newRecord();

        $sub_req = $req->contains($arg);
        if ($sub_req) {
            $it = $sub_req->createIterator();
            while ($it->valid()) {

                $prt->set($it->current()->key(), $it->current()->get());
                $it->next();
            }
        } else {
            return false;
        }
        return $prt;
    }
}
