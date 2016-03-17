<?php

namespace CMS\Parser;


class SyntaxParser extends SubParser
{
    public function parse($source)
    {
        /*
         * Add cms_page blocks where necessary
         */
        $source = $this->replaceSyntax($source, "{% page %}", "{% block cms_page %}{% endblock %}");

        /*
         * Add layout
         */
        if ($this->parent->configHas('layout')) {
            $layout = $this->parent->getLayout();

            $source = $this->appendSyntax($source,
                "{% extends \"$layout\" %}\n{% block cms_page %}");
            $source = $this->prependSyntax($source,
                "{% endblock cms_page %}");
        }

        /*
         * Add title
         */
        if ($this->parent->bufferHas('title')) {
            $title = $this->parent->getBuffer('title');

            $source = $this->replaceSyntax($source, "{% title %}", $title);
        }

        return $source;
    }

    /*
    |--------------------------------------------------------------------------
    | Syntax functions
    |--------------------------------------------------------------------------
    |
    | Edits the Twig template source
    |
    */

    protected function replaceSyntax($source, $replace, $with)
    {
        return str_ireplace($replace, $with, $source);
    }

    protected function appendSyntax($source, $syntax)
    {
        return $syntax . "\n" . $source;
    }

    protected function prependSyntax($source, $syntax)
    {
        return $source . "\n" . $syntax;
    }
} 