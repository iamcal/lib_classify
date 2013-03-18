<?php

include('testmore.php');
include('lib_classify.php');


#
# tokenizer tests adapted from linguist/test/test_tokenizer.rb
#

$token_tests = array(

	# test_skip_string_literals
	array('print ""',				'print'),
	array('print "Josh"',				'print'),
	array("print 'Josh'",				'print'),
	array('print "Hello \"Josh\""',			'print'),
	array("print 'Hello \\'Josh\\''",		'print'),
	array("print \"Hello\", \"Josh\"",		'print'),
	array("print 'Hello', 'Josh'",			'print'),
	array("print \"Hello\", \"\", \"Josh\"",	'print'),
	array("print 'Hello', '', 'Josh'",		'print'),

	# test_skip_number_literals
	array('1 + 1',		'+'),
	array('add(123, 456)',	'add ( )'),
	array('0x01 | 0x10',	'|'),
	array('500.42 * 1.0',	'*'),

	# test_skip_comments
	array("foo\n# Comment",		'foo'),
	array("foo\n# Comment\nbar",	'foo bar'),
	array("foo\n// Comment",	'foo'),
	array("foo /* Comment */",	'foo'),
	array("foo /* \nComment\n */",	'foo'),
	array("foo <!-- Comment -->",	'foo'),
	array("foo {- Comment -}",	'foo'),
	array("foo (* Comment *)",	'foo'),
	array("2 % 10\n% Comment",	'%'),

	# test_sgml_tags
	array("<html></html>",			'<html> </html>'),
	array("<div id></div>",			'<div> id </div>'),
	array("<div id=foo></div>",		'<div> id= </div>'),
	array("<div id class></div>",		'<div> id class </div>'),
	array("<div id=\"foo bar\"></div>",	'<div> id= </div>'),
	array("<div id='foo bar'></div>",	'<div> id= </div>'),
	array("<?xml version=\"1.0\"?>",	'<?xml> version='),

	# test_operators
	array("1 + 1",		'+'),
	array("1 - 1",		'-'),
	array("1 * 1",		'*'),
	array("1 / 1",		'/'),
	array("2 % 5",		'%'),
	array("1 & 1",		'&'),
	array("1 && 1",		'&&'),
	array("1 | 1",		'|'),
	array("1 || 1",		'||'),
	array("1 < 0x01",	'<'),
	array("1 << 0x01",	'<<'),
);

foreach ($token_tests as $a){
	$tokens = classify_tokenize($a[0]);
	$flat = implode(' ', $tokens);
	$name = json_encode($a[0]);
	is($flat, $a[1], "classify_tokenize($name)");
}
