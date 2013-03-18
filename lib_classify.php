<?php
	$GLOBALS['_classify_db'] = array();

	function classify_init(){

		$raw = file_get_contents('samples.json');
		$GLOBALS['_classify_db'] = json_decode($raw, true);

		foreach ($GLOBALS['_classify_db']['tokens'] as $lang => $tokens){
			$GLOBALS['_classify_db']['token_counts'][$lang] = 0;
			foreach ($tokens as $token => $val){
				$GLOBALS['_classify_db']['token_counts'][$lang] += $val;
			}
		}
	}

	function classify_text($text, $count=1){

		$tokens = classify_tokenize($text);

		foreach ($GLOBALS['_classify_db']['tokens'] as $lang => $junk){

			$scores[$lang] = classify_tokens_probability($tokens, $lang)
					+ classify_language_probability($lang);
		}

		arsort($scores);

		if ($count > 1){
			return array_slice($scores, 0, $count);
		}

		$keys = array_keys($scores);
		return $keys[0];
	}

	function classify_tokens_probability($tokens, $lang){

		$sum = 0;
		foreach ($tokens as $token){
			$v = classify_token_probability($token, $lang);
			#echo "$token -> $v\n";
			$sum += log($v);
		}
		return $sum;
	}

	function classify_token_probability($token, $lang){

		$v = 0;
		if (isset($GLOBALS['_classify_db']['tokens'][$lang][$token])){
			$v = floatval($GLOBALS['_classify_db']['tokens'][$lang][$token]);
		}

		if ($v) return $v / $GLOBALS['_classify_db']['token_counts'][$lang];

		return 1 / $GLOBALS['_classify_db']['tokens_total'];
	}

	function classify_language_probability($lang){

		return log($GLOBALS['_classify_db']['token_counts'][$lang] / $GLOBALS['_classify_db']['tokens_total']);
	}


	function classify_tokenize($text){

		$byte_limit = 100000;

		$single_comments = array(
			'//', # C
			'#', # Ruby
			'%', # Tex
		);

		$multi_comments = array(
			array('/*', '*/'), # C
			array('<!--', '-->'), # XML
			array('{-', '-}'), # Haskell
			array('(*', '*)'), # Coq
		);

		$bits = array();
		foreach ($single_comments as $a) $bits[] = preg_quote($a, '/');
		$single_rx = implode('|', $bits);

		$bits = array();
		$multi_map = array();
		foreach ($multi_comments as $a){
			$multi_map[$a[0]] = preg_quote($a[1], '/');
			$bits[] = preg_quote($a[0], '/');
		}
		$multi_rx = implode('|', $bits);

		$tokens = array();

		$pos = 0;
		$len = strlen($text);

		while ($pos < $len && $pos < $byte_limit){

			# shebang
			if (preg_match('/\A#!.+$/m', substr($text, $pos), $m)){
#echo 1;
				classify_extract_shebang($tokens, $m[0]);
				$pos += strlen($m[0]);
				continue;
			}

			# single line comments
			if ($pos == 0 || substr($text, $pos-1, 1) == "\n")
			if (preg_match("/^\s*({$single_rx})/", substr($text, $pos), $m)){
#echo 2;
				if (preg_match("/^.*?(\n|\Z)/", substr($text, $pos), $m)){
					$pos += strlen($m[0]);
					continue;
				}
				break;
			}

			# multiline comments
			if (preg_match("/^({$multi_rx})/", substr($text, $pos), $m)){
#echo 3;
				if (preg_match("/^.*?({$multi_map[$m[0]]})/", substr($text, $pos), $m)){
					$pos += strlen($m[0]);
					continue;
				}
				break;
			}

			# single or double quoted strings
			$ch = substr($text, $pos, 1);
			if ($ch == "'" || $ch == '"'){
#echo 4;
				if ($ch == substr($text, $pos+1, 1)){
					$pos += 2;
					continue;
				}
				if (preg_match("/^.*?[^\\\\]{$ch}/", substr($text, $pos+1), $m)){
					$pos += strlen($m[0])+1;
					continue;
				}
				break;
			}

			# Skip number literals
			if (preg_match('/\A(0x)?\d(\d|\.)*/m', substr($text, $pos), $m)){
#echo 5;
				$pos += strlen($m[0]);
				continue;
			}

			# SGML style brackets
			if (preg_match('/\A<[^\s<>][^<>]*>/m', substr($text, $pos), $m)){
#echo 6;
				classify_extract_sgml_tokens($tokens, $m[0]);
				$pos += strlen($m[0]);
				continue;
			}

			# Common programming punctuation
			if (preg_match('/\A(;|\{|\}|\(|\)|\[|\])/m', substr($text, $pos), $m)){
#echo 7;
				$tokens[] = $m[0];
				$pos += strlen($m[0]);
				continue;
			}

			# Regular token
			if (preg_match('/\A[\w\.@#\/\*]+/m', substr($text, $pos), $m)){
#echo 8;
				$tokens[] = $m[0];
				$pos += strlen($m[0]);
				continue;
			}

			# Common operators
			if (preg_match('/\A(<<?|\+|\-|\*|\/|%|&&?|\|\|?)/m', substr($text, $pos), $m)){
#echo 9;
				$tokens[] = $m[0];
				$pos += strlen($m[0]);
				continue;
			}
#echo 0;
			$pos++;
		}

		return $tokens;
	}

	function classify_extract_shebang(&$tokens, $text){

		if (!preg_match('/^#!\s*(\S+)/', $text, $m)) return;

		$pos = strlen($m[0]);
		$bits = explode('/', $m[1]);
		$script = array_pop($bits);

		if ($script == 'env'){

			preg_match('/^\s*(\S+)/', substr($text, $pos), $m);
			$script = $m[1];
		}
		preg_match('/^([^\d]+)/', $script, $m);
		$script = $m[1];

		if ($script) $tokens[] = "SHEBANG#!{$script}";
	}

	function classify_extract_sgml_tokens(&$tokens, $text){

		$pos = 0;
		$len = strlen($text);

		while ($pos < $len){

			# Emit start token
			if (preg_match('/^<\/?[^\s>]+/', substr($text, $pos), $m)){
				$tokens[] = $m[0].'>';
				$pos += strlen($m[0]);
				continue;
			}

			# Emit attributes with trailing =
			if (preg_match('/^\w+=/', substr($text, $pos), $m)){
				$tokens[] = $m[0];
				$pos += strlen($m[0]);

				# Then skip over attribute value
				$ch = substr($text, $pos, 1);
				if ($ch == '"' || $ch == "'"){
					if ($ch == substr($text, $pos+1, 1)){
						$pos += 2;
						continue;
					}
					if (preg_match("/^.*?[^\\\\]{$ch}/", substr($text, $pos+1), $m)){
						$pos += strlen($m[0])+1;
						continue;
					}
					break;
				}

				if (preg_match("/^.*?\w+/m", substr($text, $pos), $m)){

					$pos += strlen($m[0]);
					continue;
				}

				break;
			}

			# Emit lone attributes
			if (preg_match('/^\w+/', substr($text, $pos), $m)){

				$tokens[] = $m[0];
				$pos += strlen($m[0]);
			}

			# Stop at the end of the tag
			if (substr($text, $pos, 1) == '>') break;

			$pos++;
		}
	}


	classify_init();
