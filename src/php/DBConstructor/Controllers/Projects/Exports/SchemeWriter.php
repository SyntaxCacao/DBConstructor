<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Exports;

use DBConstructor\Models\Column;
use DBConstructor\Models\Project;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\Table;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Util\MarkdownParser;
use Exception;

class SchemeWriter
{
    const FILE_NAME = "scheme.html";

    /** @var resource */
    public $handle;

    /** @var string|null */
    public $internalIdColumn;

    /** @var string|null */
    public $commentsColumn;

    public function __construct(string $internalIdColumn = null, string $commentsColumn = null)
    {
        $this->internalIdColumn = $internalIdColumn;
        $this->commentsColumn = $commentsColumn;
    }

    public function close()
    {
        fclose($this->handle);
    }

    public function open(string $dir)
    {
        $this->handle = fopen($dir."/".SchemeWriter::FILE_NAME, "w");
    }

    /**
     * @param array<Table> $tables
     */
    public function writeHead(Project $project, array $tables)
    {
        fwrite($this->handle,
            "<!doctype html>\n".
            "<html>\n". // TODO: Lang
            "  <head>\n".
            "    <meta charset='utf-8'>\n".
            "    <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0'>".
            "    <title>".htmlspecialchars($project->label)." · Scheme</title>\n".
            "    <style>\n".
            "      * { box-sizing: border-box }\n".
            "      body { font-family: -apple-system, BlinkMacSystemFontArial, 'Helvetica Neue', Arial, sans-serif; font-size: 14px; line-height: 1.5; margin: 32px 0 128px 0 }\n".
            "      .container { margin-left: auto; margin-right: auto; max-width: 900px; padding: 0 16px }\n".
            "      h1 { border-bottom: 1px solid #e1e4e8; font-size: 32px; margin-bottom: 24px; margin-top: 32px }\n".
            "      h2 { border-bottom: 1px solid #e1e4e8; font-size: 28px; margin-bottom: 24px; margin-top: 32px }\n".
            "      h3 { font-size: 24px; margin-bottom: 16px; margin-top: 28px }\n".
            "      p, ol, ul { margin: 6px 0 }\n".
            "      ol, ul { padding-left: 32px }\n".
            "      li { margin: 2px 0 }\n".
            "      blockquote { margin: 6px 32px }\n".
            "      a { color: #0366d6; text-decoration: none; text-underline-offset: 4px }\n".
            "      a:hover { text-decoration: underline }\n".
            "      .markdown h1, .markdown h2, .markdown h3 { border: none; margin-bottom: 6px; margin-top: 10px }\n".
            "      .markdown h1 { font-size: 18px }\n".
            "      .markdown h2 { font-size: 16px }\n".
            "      .markdown h3 { font-size: 14px }\n".
            "      .markdown h4, .markdown h5, .markdown h6 { font-size: 14px; font-style: italic }\n".
            "      .box { border: 1px solid #e1e4e8; border-radius: 3px; margin-top: 12px; padding: 8px 16px }\n".
            "      .descriptor { font-size: 15px; font-style: italic; font-weight: bold }\n".
            "    </style>\n".
            "  </head>\n".
            "  <body>\n".
            "    <div class='container'>\n".
            "      <h1>".htmlspecialchars($project->label)."</h1>\n".
            "      <p><span class='descriptor'>Exported:</span> On ".date("M j, Y \a\\t h:i A")."</p>\n"
        );

        if ($project->notes !== null) {
            fwrite($this->handle, "      <p class='descriptor'>Description</p>\n");
            fwrite($this->handle, "      <div class='markdown box'>".MarkdownParser::parse($project->notes)."</div>\n");
        }

        fwrite($this->handle, "      <h2>Tables</h2>\n");
        fwrite($this->handle, "      <ul>\n");

        foreach ($tables as $table) {
            fwrite($this->handle, "        <li><a href='#table".$table->id."'><em>".htmlspecialchars($table->name)."</em></a></li>\n");
        }

        fwrite($this->handle, "      </ul>\n");
    }

    /**
     * @param array<RelationalColumn> $relationalColumns
     * @param array<TextualColumn> $textualColumns
     */
    public function writeTableDocs(Table $table, array $relationalColumns, array $textualColumns, int $recordCount)
    {
        $columns = array_merge($relationalColumns, $textualColumns);

        fwrite($this->handle, "      <h2 id='table$table->id'>Table <em>".htmlspecialchars($table->name)."</em></h2>\n");
        fwrite($this->handle, "      <p><span class='descriptor'>Number of records:</span> ".number_format($recordCount)."</p>\n");

        if ($table->instructions !== null) {
            fwrite($this->handle, "      <p class='descriptor'>Instructions</p>\n");
            fwrite($this->handle, "      <div class='markdown box'>".MarkdownParser::parse($table->instructions)."</div>\n");
        }

        fwrite($this->handle, "      <h3>Fields</h3>\n");
        fwrite($this->handle, "      <ul>\n");
        fwrite($this->handle, "        <li><em>".Column::RESERVED_NAME_ID."</em> (Primary key)</li>\n");

        if ($this->internalIdColumn !== null) {
            fwrite($this->handle, "        <li><em>".htmlspecialchars($this->internalIdColumn)."</em> (Record’s ID in DatabaseConstructor)</li>\n");
        }

        foreach ($columns as $column) {
            fwrite($this->handle, "        <li><a href='#".($column instanceof RelationalColumn ? "relcol" : "textcol").$column->id."'><em>".htmlspecialchars($column->name)."</em> ");

            if ($column instanceof RelationalColumn) {
                fwrite($this->handle, "(Foreign key)");
            } else if ($column instanceof TextualColumn) {
                fwrite($this->handle, "(".TextualColumn::TYPES_EN[$column->type].")");
            }

            fwrite($this->handle, "</a></li>\n");
        }

        if ($this->commentsColumn !== null) {
            fwrite($this->handle, "        <li><em>".htmlspecialchars($this->commentsColumn)."</em> (Text; contains comments on record in DatabaseConstructor)</li>\n");
        }

        fwrite($this->handle, "      </ul>\n");

        foreach ($columns as $column) {
            /** @var Column $column */
            fwrite($this->handle, "      <h3 id='".($column instanceof RelationalColumn ? "relcol" : "textcol").$column->id."'>Field ".htmlspecialchars($table->name)." / <em>".htmlspecialchars($column->name)."</em></h3>\n");

            if ($column instanceof RelationalColumn) {
                fwrite($this->handle, "      <p><span class='descriptor'>Data type:</span> Integer (Foreign key)</p>\n");
                fwrite($this->handle, "      <p><span class='descriptor'>Nullable:</span> ".($column->nullable ? "True" : "False")."</p>\n");
                fwrite($this->handle, "      <p><span class='descriptor'>Relating to table:</span> <a href='#table$column->targetTableId'>".htmlspecialchars($column->targetTableName)."</a></p>\n");
            } else if ($column instanceof TextualColumn) {
                try {
                    fwrite($this->handle, "      ".$column->getValidationType()->toHTML())."\n";
                } catch (Exception $e) {
                }
            }

            if ($column->instructions !== null) {
                fwrite($this->handle, "      <p class='descriptor'>Instructions</p>\n");
                fwrite($this->handle, "      <div class='markdown box'>".MarkdownParser::parse($column->instructions)."</div>\n");
            }
        }
    }

    public function writeEnd()
    {
        fwrite($this->handle,
            "    </div>\n".
            "  </body>\n".
            "</html>\n"
        );
    }
}
