<?php
/**
 * Multi level including example
 *
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Examples
 */

require_once '../includes/haml/HamlParser.class.php';

$parser = new HamlParser('../tpl', '../tmp/haml');

echo $parser->setFile('example7.haml');

?>
