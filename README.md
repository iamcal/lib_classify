# lib_classify - PHP Language bayesian classifier

[![Build Status](https://travis-ci.org/iamcal/lib_classify.png?branch=master)](https://travis-ci.org/iamcal/lib_classify)

This library is a partial PHP port of Github's [linguist](https://github.com/github/linguist), allowing you to detect
the language that a given code fragment is written in. It uses a corpus built by linguist to compare input text against
and can either give you a ranked list of likely languages, or just the best match.


## Usage

    include('lib_classify.php');

    $lang = classify_text("#!/bin/perl\nuse strict");
    # "Perl"

    $langs = classify_text('print my_var', 3);
    #(
    #    [Awk] => -21.855145242662
    #    [Perl] => -21.944757401352
    #    [Matlab] => -23.618733834923
    #)

The more input you feed it, the more accurate your output will be.

The `samples.json` file needs to be somewhere that `lib_classify.php` can read it.


## Implementation

This library implements `classifier.rb` and `tokenizer.rb`. The tokenizer is required so that we can break down
the input text into tokens using the same rules as were used to generate the comparison corpus.
