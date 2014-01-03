<?php
/**
 * Including by variable
 *
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @author Baldrs <manwe64@gmail.com>
 *
 * @package phpHaml
 * @subpackage Examples
 */

require_once '../includes/haml/HamlParser.class.php';

$parser = new HamlParser('../tpl', '../tmp/haml');

echo $parser->setFile('example10.haml');
?>