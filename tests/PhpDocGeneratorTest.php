<?php

use Ensi\LaravelOpenApiServerGenerator\Utils\PhpDocGenerator;

it('can generate phpdoc from single line string', function () {
    $generator = new PhpDocGenerator();
    $result = $generator->fromText("some text");

    $expected = <<<"EOD"
/**
 * some text
 */
EOD;
    expect($result)->toEqual($expected);
});

it('can generate phpdoc from with prepending spaces', function () {
    $generator = new PhpDocGenerator();
    $result = $generator->fromText("some text", 4);

    $expected = <<<"EOD"
    /**
     * some text
     */
EOD;
    expect($result)->toEqual($expected);
});

it('can generate phpdoc from multiline string', function () {
    $generator = new PhpDocGenerator();
    $multilineString = <<<'EOT'
This is a test comment
It is also multiline
Wow
EOT;
    $result = $generator->fromText($multilineString, 4);

    $expected = <<<"EOD"
    /**
     * This is a test comment
     * It is also multiline
     * Wow
     */
EOD;
    expect($result)->toEqual($expected);
});

it('erases phpdoc end', function () {
    $generator = new PhpDocGenerator();
    $result = $generator->fromText("broken */text");

    $expected = <<<"EOD"
/**
 * broken text
 */
EOD;
    expect($result)->toEqual($expected);
});

it('trims lines', function () {
    $generator = new PhpDocGenerator();
    $result = $generator->fromText("    some text    ");

    $expected = <<<"EOD"
/**
 * some text
 */
EOD;
    expect($result)->toEqual($expected);
});

it('deletes empty lines if configured', function () {
    $generator = new PhpDocGenerator();
    $multilineString = <<<'EOT'
This is a test comment

It is also multiline
And with empty line
Wow
EOT;
    $result = $generator->fromText($multilineString, deleteEmptyLines: true);

    $expected = <<<"EOD"
/**
 * This is a test comment
 * It is also multiline
 * And with empty line
 * Wow
 */
EOD;
    expect($result)->toEqual($expected);
});
