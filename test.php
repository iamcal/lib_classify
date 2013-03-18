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
	array('1 + 1',			'+'),
	array('add(123, 456)',		'add ( )'),
	array('0x01 | 0x10',		'|'),
	array('500.42 * 1.0',		'*'),

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

	# test_c_tokens
	array(file_get_contents('test_files/hello.h'),		'#ifndef HELLO_H #define HELLO_H void hello ( ) ; #endif'),
	array(file_get_contents('test_files/hello.c'),		'#include <stdio.h> int main ( ) { printf ( ) ; return ; }'),

	# test_cpp_tokens
	array(file_get_contents('test_files/bar.h'),		'class Bar { protected char *name ; public void hello ( ) ; }'),
	array(file_get_contents('test_files/hello.cpp'),	'#include <iostream> using namespace std ; int main ( ) { cout << << endl ; }'),

	# test_objective_c_tokens
	array(file_get_contents('test_files/Foo.h'),		'#import <Foundation/Foundation.h> @interface Foo NSObject { } @end'),
	array(file_get_contents('test_files/Foo.m'),		'#import @implementation Foo @end'),
	array(file_get_contents('test_files/hello.m'),		'#import <Cocoa/Cocoa.h> int main ( int argc char *argv [ ] ) { NSLog ( @ ) ; return ; }'),

	# test_javascript_tokens
	array(file_get_contents('test_files/hello.js'),		'( function ( ) { console.log ( ) ; } ) .call ( this ) ;'),

	# test_json_tokens
	array(file_get_contents('test_files/product.json'),	'{ [ ] { } }'),

	# test_ruby_tokens
	array(file_get_contents('test_files/foo.rb'),		'module Foo end'),
	array(file_get_contents('test_files/Rakefile'),		'task default do puts end'),
);

$first_token_tests = array(

	# test_shebang
	array(file_get_contents('test_files/sh.script'),		'SHEBANG#!sh'),
	array(file_get_contents('test_files/bash.script'),		'SHEBANG#!bash'),
	array(file_get_contents('test_files/zsh.script'),		'SHEBANG#!zsh'),
	array(file_get_contents('test_files/perl.script'),		'SHEBANG#!perl'),
	array(file_get_contents('test_files/python.script'),		'SHEBANG#!python'),
	array(file_get_contents('test_files/ruby.script'),		'SHEBANG#!ruby'),
	array(file_get_contents('test_files/ruby2.script'),		'SHEBANG#!ruby'),
	array(file_get_contents('test_files/js.script'),		'SHEBANG#!node'),
	array(file_get_contents('test_files/php.script'),		'SHEBANG#!php'),
	array(file_get_contents('test_files/invalid-shebang.sh'),	'echo'),
);

foreach ($token_tests as $a){
	$tokens = classify_tokenize($a[0]);
	$flat = implode(' ', $tokens);
	$name = json_encode($a[0]);
	is($flat, $a[1], "classify_tokenize($name)");
}

foreach ($first_token_tests as $a){
	$tokens = classify_tokenize($a[0]);
	$name = json_encode($a[0]);
	is($tokens[0], $a[1], "classify_tokenize($name)[0]");
}
