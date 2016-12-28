<?php

use b166er\ClassCreator;
//use function b166er\__app;
//use function b166er\exception_display;

require_once('b166er.php');

$d = (new ClassCreator('document'))->create();
$dao_d =(new \b166er\DaoCreator($d))->create();
$dao_d->load(15);




//include ('auth.php');


$clc = new ClassCreator('user');

$clc2 = new ClassCreator('address');

$a = (new ClassCreator('log'))->create();

$b = (new ClassCreator('people'))->create();
$c = (new ClassCreator('booking'))->create();
$d = (new ClassCreator('email'))->create();
$e = (new ClassCreator('phone'))->create();
$f = (new ClassCreator('log'))->create();

$prt_1 = $clc->create();

$prt_2 = $clc->create();

$addr = $clc2->create();

$a = __app();
$prt_0 = __app()->contains('prototype/user');


$prt_1->set('name', 'Paolo');
$prt_1->set('password', 'Paolo');
$prt_1->set('groups', 'Paolo');
$prt_1->set('password_expiry', '2010-01-01');
$prt_1->set('xuser', 'Paolo');
$prt_1->set('xpassword', 'Paolo');

$prt_2->set('name', 'Silvia');
$prt_2->set('password', 'Silvia');
$prt_2->set('groups', 'Silvia');
$prt_2->set('password_expiry', '1076-12-25');
$prt_2->set('xuser', 'Silvia');
$prt_2->set('xpassword', 'Silvia');

$r = 1;


//}

?>
<!DOCTYPE html>
<html>
<body>
<h1> Esecuzione terminata</h1>
<p><b></b></p>
<ul>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
</ul>
<?php //phpinfo(); ?>
</body>
</html>





