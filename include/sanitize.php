<?
// adapted from https://github.com/owncloud/core/blob/stable5/lib/util.php#L530

// my database is ISO-8859-1, you may need to set this function to use UTF-8

function sanitizeHTML (&$value) {
 	if (is_array($value)) {
		array_walk_recursive($value, 'sanitizeHTML');
	} else {
		$value = htmlentities((string)$value, ENT_QUOTES, 'ISO-8859-1');
	}
	return $value;
}
?>