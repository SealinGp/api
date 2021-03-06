<?php
namespace lib\html5lib;
use Data;
use InputStream;
use TreeBuilder;
use Tokenizer;

/**
 * Outwards facing interface for HTML5.
 */
class Parser
{
    /**
     * Parses a full HTML document.
     * @param $text | HTML text to parse
     * @param $builder | Custom builder implementation
     * @return DOMDocument|DOMNodeList Parsed HTML as DOMDocument
     */
    static public function parse($text, $builder = null) {
        $tokenizer = new Tokenizer($text, $builder);
        $tokenizer->parse();
        return $tokenizer->save();
    }

    /**
     * Parses an HTML fragment.
     * @param $text | HTML text to parse
     * @param $context String name of context element to pretend parsing is in.
     * @param $builder | Custom builder implementation
     * @return DOMDocument|DOMNodeList Parsed HTML as DOMDocument
     */
    static public function parseFragment($text, $context = null, $builder = null) {
        $tokenizer = new Tokenizer($text, $builder);
        $tokenizer->parseFragment($context);
        return $tokenizer->save();
    }
}
