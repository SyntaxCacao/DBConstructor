<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\Projects\Tables;

use DBConstructor\Application;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\JsonException;

class TableGenerator
{
    const META_COLUMN_ASSIGNED = 0;

    const META_COLUMN_CREATED = 1;

    const META_COLUMN_EXPORT_ID = 2;

    const META_COLUMN_LAST_EDITED = 3;

    const META_COLUMNS_DEFAULT = [
        self::META_COLUMN_ASSIGNED,
        self::META_COLUMN_CREATED,
        self::META_COLUMN_LAST_EDITED
    ];

    /** @var string */
    public $projectId;

    /** @var array<RelationalColumn> */
    public $relationalColumns;

    /** @var array<string, array<string, RelationalField>> */
    public $relationalFields;

    /** @var int */
    public $rowCount;

    /** @var array<Row> */
    public $rows;

    /** @var string */
    public $tableId;

    /** @var array<TextualColumn> */
    public $textualColumns;

    /** @var array<string, array<string, TextualField>> */
    public $textualFields;

    /**
     * @param array<int>|null $metaColumns null indicates default set of meta columns
     */
    public function generate(bool $expandable, bool $newTab, bool $selector, array $metaColumns = self::META_COLUMNS_DEFAULT)
    {
        if (count($this->rows) === 0) {
            echo '<div class="blankslate">';
            echo '<h1 class="blankslate-heading">Keine Datensätze gefunden</h1>';
            echo '<p class="blankslate-text">Der Filter ist zu eng eingestellt oder es sind noch keine Datensätze vorhanden.</p>';
            echo '</div>';
            return;
        }

        if ($expandable) {
            echo '<div class="container-expandable-outer">';
            echo '<div class="container-expandable-inner-centered">';
        } else {
            echo '<div class="table-wrapper">';
        }

        echo '<table class="table">';

        // # headings
        echo '<thead>';
        echo '<tr class="table-heading">';

        // ## actions, icons, ID, export ID
        echo '<th class="table-cell" colspan="2" scope="col">'.htmlentities(number_format($this->rowCount, 0, ",", ".")).' '.($this->rowCount === 1 ? 'Datensatz' : 'Datensätze').'</th>';
        echo '<th class="table-cell" scope="col">ID</th>';

        if (in_array(self::META_COLUMN_EXPORT_ID, $metaColumns)) {
            echo '<th class="table-cell" scope="col" title="Letzte Export-ID">Export-ID</th>';
        }

        // ## relational columns
        foreach ($this->relationalColumns as $column) {
            if (! $column->hide) {
                echo '<th class="table-cell" scope="col" title="'.htmlentities($column->name).'">';
                echo '<a href="'.Application::$instance->config["baseurl"].'/projects/'.$this->projectId.'/tables/'.$column->targetTableId.'/view/"'.($newTab ? ' target="_blank"' : '').'>'.htmlentities($column->label).'</a>';
                echo '</th>';
            }
        }

        // ## textual columns
        foreach ($this->textualColumns as $column) {
            if (! $column->hide) {
                echo '<th class="table-cell" scope="col" title="'.htmlentities($column->name).'">'.htmlentities($column->label).'</th>';
            }
        }

        // ## meta columns
        if (in_array(self::META_COLUMN_ASSIGNED, $metaColumns)) {
            echo '<th class="table-cell" scope="col">Zuordnung</th>';
        }

        if (in_array(self::META_COLUMN_LAST_EDITED, $metaColumns)) {
            echo '<th class="table-cell" scope="col">Letzte Aktivität</th>';
        }

        if (in_array(self::META_COLUMN_CREATED, $metaColumns)) {
            echo '<th class="table-cell" scope="col">Angelegt</th>';
        }

        echo '</tr>';
        echo '</thead>';

        // # rows
        echo '<tbody>';

        foreach ($this->rows as $row) {
            echo '<tr class="table-row"';

            if ($selector) {
                echo ' data-value-exists="1" data-value-valid="'.intval($row->valid).'" data-value-deleted="'.intval($row->deleted).'"';
            }

            echo '>';

            // ## actions
            echo '<td class="table-cell table-cell-actions">';

            if ($selector) {
                echo '<a class="button button-smallest js-table-selector js-close-modal" href="#" data-row-id="'.$row->id.'" data-valid="'.intval($row->valid).'" data-deleted="'.intval($row->deleted).'"><span class="bi bi-check-lg"></span>Wählen</a>';
            }

            echo '<a class="button button-smallest" href="'.Application::$instance->config["baseurl"].'/projects/'.$this->projectId.'/tables/'.$this->tableId.'/view/'.$row->id.'/"'.($newTab ? ' target="_blank"' : '').'><span class="bi bi-file-earmark-text'.($selector ? ' no-margin' : '').'"'.($selector ? ' title="Aufrufen"' : '').'></span>'.($selector ? '' : 'Aufrufen').'</a>';
            echo '</td>';

            // ## icons
            echo '<td class="table-cell page-table-view-icons">';
            echo '<span class="validation-step-icon" title="'.($row->valid ? 'Gültig' : 'Ungültig').'"><span class="bi '.($row->valid ? 'bi-check-lg' : 'bi-x-lg').'"></span></span>';

            if ($row->flagged) {
                echo '<span class="validation-step-icon" title="Zur Nachverfolgung gekennzeichnet"><span class="bi bi-flag-fill"></span></span>';
            }

            if ($row->deleted) {
                echo '<span class="validation-step-icon" title="Gelöscht"><span class="bi bi-trash"></span></span>';
            }

            echo '</td>';

            // ## id
            echo '<td class="table-cell table-cell-numeric">'.$row->id.'</td>';

            // ## export id
            if (in_array(self::META_COLUMN_EXPORT_ID, $metaColumns)) {
                echo '<td class="table-cell table-cell-numeric">'.($row->exportId === null ? '–' : $row->exportId).'</td>';
            }

            // ## relational columns
            foreach ($this->relationalColumns as $column) {
                if ($column->hide) {
                    continue;
                }

                if (isset($this->relationalFields[$row->id][$column->id])) {
                    $field = $this->relationalFields[$row->id][$column->id];

                    if ($field->targetRowId === null) {
                        echo '<td class="table-cell'.($field->valid ? '' : ' table-cell-invalid').' table-cell-null">NULL</td>';
                    } else {
                        echo '<td class="table-cell'.($field->valid ? '' : ' table-cell-invalid').' table-cell-numeric"><a class="main-link" href="'.Application::$instance->config["baseurl"].'/projects/'.$this->projectId.'/tables/'.$column->targetTableId.'/view/'.$field->targetRowId.'/"'.($newTab ? ' target="_blank"' : '').'>'.$field->targetRowId.'</a></td>';
                    }
                } else {
                    echo '<td class="table-cell table-cell-invalid table-cell-null">Zelle fehlt</td>';
                }
            }

            // ## textual columns
            foreach ($this->textualColumns as $column) {
                if ($column->hide) {
                    continue;
                }

                try {
                    if (isset($this->textualFields[$row->id][$column->id])) {
                        echo $column->generateCellValue($this->textualFields[$row->id][$column->id]);
                    } else {
                        echo $column->generateCellValue();
                    }
                } catch (JsonException $e) {
                    echo '<td class="table-cell table-cell-invalid table-cell-null">Anzeigefehler</td>';
                }
            }

            // ## meta columns
            if (in_array(self::META_COLUMN_ASSIGNED, $metaColumns)) {
                // TODO: Link to all user or rows assigned to this user?
                echo '<td class="table-cell">'.($row->assigneeId === null ? '&ndash;' : htmlentities($row->assigneeFirstName.' '.$row->assigneeLastName)).'</td>';
            }

            if (in_array(self::META_COLUMN_LAST_EDITED, $metaColumns)) {
                echo '<td class="table-cell" title="Zuletzt bearbeitet von '.htmlentities($row->lastEditorFirstName.' '.$row->lastEditorLastName).'">'.htmlentities(date('d.m.Y H:i', strtotime($row->lastUpdated))).'</td>';
            }

            if (in_array(self::META_COLUMN_CREATED, $metaColumns)) {
                echo '<td class="table-cell" title="Angelegt von '.htmlentities($row->creatorFirstName.' '.$row->creatorLastName).'">'.htmlentities(date('d.m.Y H:i', strtotime($row->created))).'</td>';
            }

            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        if ($expandable) {
            echo '</div>';
        }

        echo '</div>';
    }
}
