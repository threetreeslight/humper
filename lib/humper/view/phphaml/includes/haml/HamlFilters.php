<?php
function __interpolate($str) {
	return  preg_replace("/#\{([a-z0-9_ \\/+*()&|~!^$<>-]+)\}?/i", "<?php echo $1 ?>", $str);
}

function __normalize($str) {
   return $str;#str_replace("\xD", "\n", $str);
}

function _cdata($str) {
	$str = __interpolate($str);
   return <<<HERE
	<![CDATA[
	$str
	]]>
HERE;
}
//[a-z0-9_ \\/+*()&|~!^-]+
function _js($str) {
	$str = __interpolate(__normalize($str));
	return <<<HERE
<script type="text/javascript">
$str
</script>
HERE;
}

function _css($str) {
	$str = __interpolate(__normalize($str));
	return <<<HERE
<script type="text/css">
$str
</script>
HERE;
}

function _plain($str) {
	return __interpolate($str);
}

function _escaped($str) {
	return __interpolate(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));
}
function _php($str) {
   $str = __normalize($str);
	return "<?php\n$str\n?>";
}
