<?php

declare (strict_types=1);
namespace Behat\Transliterator;

use Rolling_Curl\Request;
use Rolling_Curl\Rolling_Curl;
use Yaoi\Command;
use Yaoi\Command\Definition;
use Yaoi\Http\Client;
use Yaoi\String\Lexer\Parsed;
use Yaoi\String\Lexer\Parser;
use Yaoi\String\Lexer\Renderer;
use Yaoi\String\Lexer\Token;
use Yaoi\String\Parser as StringParser;
use Yaoi\String\String_Value;
/**
 * Tool for converting char tables for Behat/Transliterator from Perl to PHP
 * @internal
 */
class Sync_Tool extends Command
{
    public const LIB_VERSION = '1.30';
    private $tokenizer;
    private $renderer;
    private $php_table;
    private $item_index;
    private $non_question_box_found;
    private $block;
    public function __construct()
    {
        $escape = ['\}' => '}', '\\\\' => '\\', '\{' => '{', '\@' => '@', '\$' => '$'];
        $this->tokenizer = new Parser();
        $this->tokenizer->add_line_stopper('#');
        $this->tokenizer->add_quote('qq{', '}', $escape);
        $this->tokenizer->add_quote('q{', '}', $escape);
        $this->tokenizer->add_quote('"', '"');
        $this->tokenizer->add_quote("'", "'");
        $this->tokenizer->add_bracket('[', ']');
        $this->tokenizer->add_delimiter(';');
        $this->renderer = new Renderer();
        $this->renderer->set_bind_key('-~z', 'z~-')->strip('#')->keep_boundaries('[');
    }
    public static function set_up_definition(Definition $definition, $options): void
    {
        $definition->name = 'update-data';
        $definition->description = 'Tool for converting char tables for Behat/Transliterator from Perl to PHP';
    }
    public function perform_action(): void
    {
        $rolling_curl = new Rolling_Curl();
        foreach ($this->get_perl_tables_url_list() as $url) {
            $rolling_curl->get($url);
        }
        $rolling_curl->set_callback(function (Request $request, Rolling_Curl $rolling_curl): void {
            $this->response->add_content($request->get_url());
            $content = $request->get_response_text();
            $this->parse_perl_table($content);
        })->execute();
    }
    private function remove_php_char_table(string $php_file_path, string $reason): void
    {
        $this->response->add_content($reason);
        if (file_exists($php_file_path)) {
            if (unlink($php_file_path)) {
                $this->response->success('Deleted');
            } else {
                $this->response->error('Failed to delete');
            }
        } else {
            $this->response->success('No PHP file, skipped');
        }
    }
    private function push_item($item): void
    {
        if ($this->item_index >= 16) {
            $this->php_table = trim($this->php_table);
            $this->php_table .= "\n";
            $this->item_index = 0;
        }
        ++$this->item_index;
        $item = new String_Value($item);
        if ($item->starts('\x') || $item->starts('\n')) {
            $this->php_table .= '"' . $item . '", ';
            $this->non_question_box_found = true;
        } else {
            // TODO check if this hack should be removed for chinese letters
            if ($item->value === '[?] ') {
                $item->value = '[?]';
            }
            if ($item->value !== '[?]') {
                $this->non_question_box_found = true;
            }
            $this->php_table .= "'" . str_replace(['\\', '\''], ['\\\\', '\\\''], $item) . "', ";
        }
    }
    private function tokenize_perl_table($content)
    {
        $tokens = $this->tokenizer->tokenize($content);
        $expression = $this->renderer->get_expression($tokens);
        $statement = $expression->get_statement();
        /** @var Parsed[] $binds */
        $binds = $expression->get_binds();
        $parser = new String_Parser($statement);
        $block = (string) $parser->inner('$Text::Unidecode::Char[', ']');
        if (!$block) {
            throw new \Exception('Block not found');
        }
        $this->block = $this->renderer->get_expression($binds[$block])->get_statement();
        $items_bind = (string) $parser->inner('[', ']');
        if (!$items_bind) {
            return [];
        }
        return $binds[$items_bind];
    }
    private function parse_perl_table($content): void
    {
        $items = $this->tokenize_perl_table($content);
        $php_file_path = __DIR__ . '/data/' . substr($this->block, 1) . '.php';
        if (!$items) {
            $this->remove_php_char_table($php_file_path, 'Empty char table for block ' . $this->block);
            return;
        }
        $this->php_table = <<<PHP
        <?php
        \$UTF8_TO_ASCII[{$this->block}] = array(
        
        PHP;
        $items_expression = $this->renderer->get_expression($items);
        $items_statement = $items_expression->get_statement();
        $items_binds = $items_expression->get_binds();
        $items_statement = explode(',', $items_statement);
        $this->item_index = 0;
        $this->non_question_box_found = false;
        foreach ($items_statement as $item) {
            $item = trim($item);
            if (!$item) {
                break;
            }
            if (isset($items_binds[$item])) {
                /** @var Token $token */
                $token = $items_binds[$item];
                $item = $token->un_escaped_content;
            }
            $this->push_item($item);
        }
        if ($this->non_question_box_found) {
            $this->php_table = trim($this->php_table) . "\n" . ');' . "\n";
            if (file_put_contents($php_file_path, $this->php_table)) {
                $this->response->success('Block ' . $this->block . ' converted to ' . $php_file_path);
            } else {
                $this->response->error('Failed to save ' . $php_file_path);
            }
        } else {
            $this->remove_php_char_table($php_file_path, 'Block ' . $this->block . ' contains only [?]');
        }
    }
    /**
     * @return non-falsy-string[]
     */
    private function get_perl_tables_url_list(): array
    {
        $client = new Client();
        $list = [];
        $page = $client->fetch('https://st.aticpan.org/source/SBURKE/Text-Unidecode-' . self::LIB_VERSION . '/lib/Text/Unidecode/');
        foreach (String_Parser::create($page)->inner_all('.pm\'>', '</a>') as $x_x_xpm) {
            $list[] = 'https://st.aticpan.org/source/SBURKE/Text-Unidecode-' . self::LIB_VERSION . '/lib/Text/Unidecode/' . $x_x_xpm;
        }
        return $list;
    }
}