<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects\Tables\Records;

use DBConstructor\Application;
use DBConstructor\Controllers\API\APIController;
use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Controllers\API\Projects\ProjectsNode;
use DBConstructor\Controllers\API\Projects\Tables\TablesNode;
use DBConstructor\Models\AccessToken;
use DBConstructor\Models\Participant;
use DBConstructor\Models\RelationalColumn;
use DBConstructor\Models\RelationalField;
use DBConstructor\Models\Row;
use DBConstructor\Models\RowAction;
use DBConstructor\Models\RowAttachment;
use DBConstructor\Models\TextualColumn;
use DBConstructor\Models\TextualField;
use DBConstructor\Util\MarkdownParser;
use DBConstructor\Validation\Types\SelectionType;
use Exception;

class RecordsElementLeaf extends LeafNode
{
    /**
     * @param array<TextualColumn> $textualColumns
     * @param array<RelationalField> $relationalFields
     * @param array<TextualField> $textualFields
     * @param array<RowAttachment>|null $attachments
     * @param array<RowAction>|null $actions
     */
    public static function buildRecordArray(Row $row, array $textualColumns, array $relationalFields, array $textualFields, array $attachments = null, array $actions = null): array
    {
        $array = [
            "id" => intval($row->id),
            "valid" => $row->valid,
            "flagged" => $row->flagged,
            "deleted" => $row->deleted,
            "lastExportId" => $row->exportId === null ? null : intval($row->exportId),
            "assignee" => $row->assigneeId === null ? null : [
                "id" => intval($row->assigneeId),
                "firstName" => $row->assigneeFirstName,
                "lastName" => $row->assigneeLastName
            ],
            "lastEditor" => [
                "id" => intval($row->lastEditorId),
                "firstName" => $row->lastEditorFirstName,
                "lastName" => $row->lastEditorLastName
            ],
            "creator" => [
                "id" => intval($row->creatorId),
                "firstName" => $row->creatorFirstName,
                "lastName" => $row->creatorLastName
            ],
            "relationalValues" => [],
            "textualValues" => []
        ];

        // relationalValues
        foreach ($relationalFields as $field) {
            $array["relationalValues"][$field->columnId] = [
                "targetRowId" => $field->targetRowId === null ? null : intval($field->targetRowId),
                "targetRowExists" => $field->targetRowExists,
                "targetRowValid" => $field->targetRowValid,
            ];
        }

        // textualValues
        foreach ($textualFields as $field) {
            $value = $field->value;

            if ($textualColumns[$field->columnId]->type === TextualColumn::TYPE_SELECTION) {
                try {
                    /** @var SelectionType $type */
                    $type = $textualColumns[$field->columnId]->getValidationType();

                    if ($type->allowMultiple) {
                        $value = TextualColumn::decodeOptions($value);
                    }
                } catch (Exception $exception) {
                }
            }

            $array["textualValues"][$field->columnId] = [
                "value" => $value,
                "valid" => $field->valid
            ];
        }

        // attachments
        if ($attachments !== null) {
            $array["attachments"] = AttachmentsListLeaf::buildAttachmentsArray($attachments);
        }

        // history
        if ($actions !== null) {
            $history = [];

            foreach ($actions as $action) {
                $element = [
                    "id" => intval($action->id),
                    "user" => [
                        "id" => intval($action->userId),
                        "firstName" => $action->userFirstName,
                        "lastName" => $action->userLastName
                    ],
                    "action" => $action->action
                ];

                if ($action->action === RowAction::ACTION_ASSIGNMENT) {
                    $element["data"]["assigneeId"] = $action->data === null ? null : intval($action->data);
                }

                if ($action->action === RowAction::ACTION_CHANGE) {
                    $element["data"] = [
                        "column" => [
                            "kind" => ($action->data[RowAction::CHANGE_DATA_IS_RELATIONAL] ? "relational" : "textual"),
                            "id" => intval($action->data[RowAction::CHANGE_DATA_COLUMN_ID])
                        ],
                        "previousValue" => ($action->data[RowAction::CHANGE_DATA_IS_RELATIONAL] ? ($action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE] === null ? null : intval($action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE])) : $action->data[RowAction::CHANGE_DATA_PREVIOUS_VALUE]),
                        "newValue" => ($action->data[RowAction::CHANGE_DATA_IS_RELATIONAL] ? ($action->data[RowAction::CHANGE_DATA_NEW_VALUE] === null ? null : intval($action->data[RowAction::CHANGE_DATA_NEW_VALUE])) : $action->data[RowAction::CHANGE_DATA_NEW_VALUE])
                    ];
                }

                if ($action->action === RowAction::ACTION_COMMENT) {
                    $element["data"] = [
                        "markdown" => $action->data,
                        "html" => MarkdownParser::parse($action->data)
                    ];
                }

                if ($action->action === RowAction::ACTION_REDIRECTION_DESTINATION) {
                    $element["data"] = [
                        "origin" => $action->data[RowAction::REDIRECTION_DATA_ORIGIN],
                        "count" => $action->data[RowAction::REDIRECTION_DATA_COUNT]
                    ];
                }

                if ($action->action === RowAction::ACTION_REDIRECTION_ORIGIN) {
                    $element["data"] = [
                        "destination" => $action->data[RowAction::REDIRECTION_DATA_DESTINATION],
                        "count" => $action->data[RowAction::REDIRECTION_DATA_COUNT]
                    ];
                }

                $element["time"] = $action->created;
                $history[] = $element;
            }

            $array["history"] = $history;
        }

        // last activity & created
        $array["lastActivity"] = $row->lastUpdated;
        $array["created"] = $row->created;

        return $array;
    }

    public function delete(array $path): array
    {
        APIController::$instance->requireScope(ProjectsNode::$project->id, AccessToken::SCOPE_PROJECT_DELETE);

        RecordsNode::$record->deletePermanently(Application::$instance->user->id, ProjectsNode::$project->id);

        // TODO Log
        return [];
    }

    public function get(array $path): array
    {
        return self::buildRecordArray(RecordsNode::$record,
            TextualColumn::loadList(TablesNode::$table->id),
            RelationalField::loadRow(RecordsNode::$record->id),
            TextualField::loadRow(RecordsNode::$record->id),
            RowAttachment::loadAll(RecordsNode::$record->id),
            RowAction::loadAllRaw(RecordsNode::$record->id));
    }

    public function patch(array $path): array
    {
        $payload = $this->processPayload();
        $record = RecordsNode::$record;

        $changes = [];

        // I. Validate values
        $errors = [];

        if (isset($payload["relationalValues"]) || isset($payload["textualValues"]) || isset($payload["deleted"])) {
            APIController::$instance->requireScope(ProjectsNode::$project->id, AccessToken::SCOPE_PROJECT_WRITE);
        }

        // 1. Relational values
        if (isset($payload["relationalValues"]) && is_array($payload["relationalValues"])) {
            $relationalColumns = RelationalColumn::loadList(TablesNode::$table->id);
            $relationalFields = RelationalField::loadRow($record->id);

            foreach ($payload["relationalValues"] as $columnId => $value) {
                if (! array_key_exists($columnId, $relationalColumns)) {
                    $errors[] = "A relational column with ID $columnId does not exist in this table.";
                    continue;
                }

                $value = $value === null ? null : (string) $value;

                if ($relationalFields[$columnId]->targetRowId === $value) {
                    // value unchanged
                    continue;
                }

                if ($value === "") {
                    $value = null;
                }

                if ($value === null) {
                    $changes["relationalValues"][$columnId] = [
                        "field" => $relationalFields[$columnId],
                        "nullable" => $relationalColumns[$columnId]->nullable,
                        "value" => $value
                    ];
                    continue;
                }

                if (intval($value) === 0 || ! ctype_digit($value)) {
                    $errors[] = "Relational column $columnId: $value is not an integer.";
                    continue;
                }

                if (($targetRecord = Row::load($value)) === null) {
                    $errors[] = "Relational column $columnId: record $value does not exist.";
                    continue;
                }

                if ($targetRecord->tableId !== $relationalColumns[$columnId]->targetTableId) {
                    $errors[] = "Relational column $columnId: record $value exists but does not belong to table targeted by this relational column (belongs to table $targetRecord->tableId, targeted is table {$relationalColumns[$columnId]->targetTableId}).";
                    continue;
                }

                $changes["relationalValues"][] = [
                    "field" => $relationalFields[$columnId],
                    "nullable" => $relationalColumns[$columnId]->nullable,
                    "value" => $value
                ];
            }
        }

        // 2. Textual values
        if (isset($payload["textualValues"]) && is_array($payload["textualValues"])) {
            $textualColumns = TextualColumn::loadList(TablesNode::$table->id);
            $textualFields = TextualField::loadRow($record->id);

            foreach ($payload["textualValues"] as $columnId => $value) {
                if (! array_key_exists($columnId, $textualColumns)) {
                    $errors[] = "A textual column with ID $columnId does not exist in this table.";
                    continue;
                }

                $type = $textualColumns[$columnId]->getValidationType();

                if ($type instanceof SelectionType && $type->allowMultiple) {
                    if (TextualColumn::isEquivalent($value, TextualColumn::decodeOptions($textualFields[$columnId]->value))) {
                        // value unchanged
                        continue;
                    }
                } else {
                    if ($textualFields[$columnId]->value === $value) {
                        // value unchanged
                        continue;
                    }

                    if ($value === "") {
                        $value = null;
                    }
                }

                $valid = $type->buildValidator()->validate($value);

                if ($type instanceof SelectionType && $type->allowMultiple) {
                    $value = TextualColumn::encodeOptions($value);
                }

                $changes["textualValues"][] = [
                    "field" => $textualFields[$columnId],
                    "value" => $value,
                    "valid" => $valid
                ];
            }
        }

        // 3. Flag
        if (isset($payload["flag"])) {
            if (is_bool($payload["flag"])) {
                if ($payload["flag"] !== $record->flagged) {
                    $changes["flagged"] = $payload["flag"];
                }
            } else {
                $errors[] = "Flag is not provided as a boolean value.";
            }
        }

        // 4. Assignee
        if (array_key_exists("assignee", $payload)) {
            if ($payload["assignee"] === null) {
                if ($record->assigneeId !== null) {
                    $changes["assignee"] = null;
                }
            } else {
                if (! is_int($payload["assignee"]) || Participant::loadFromUser(ProjectsNode::$project->id, (string) $payload["assignee"]) === null) {
                    $errors[] = "Assignee: user {$payload["assignee"]} does not exist or does not participate in this project.";
                } else {
                    if ($record->assigneeId !== (string) $payload["assignee"]) {
                        $changes["assignee"] = $payload["assignee"];
                    }
                }
            }
        }

        // 5. Comment
        if (isset($payload["comment"])) {
            if (is_string($payload["comment"])) {
                if (strlen($payload["comment"]) > Row::MAX_COMMENT_LENGTH) {
                    $errors[] = "Comment may not be longer than ".Row::MAX_COMMENT_LENGTH.".";
                } else {
                    $changes["comment"] = $payload["comment"];
                }
            } else {
                $errors[] = "Comment is not provided as a string value.";
            }
        }

        // 6. Delete
        if (isset($payload["deleted"])) {
            if (is_bool($payload["deleted"])) {
                if ($payload["deleted"] !== $record->deleted) {
                    $changes["deleted"] = $payload["deleted"];
                }
            } else {
                $errors[] = "Value \"deleted\" is not provided as a boolean value.";
            }
        }

        // 7. Errors
        if (count($errors) > 0) {
            APIController::$instance->respond(422, [
                "message" => implode(" ", $errors)
            ]);
        }

        // II. Perform changes

        // 1. Relational values
        if (isset($changes["relationalValues"])) {
            foreach ($changes["relationalValues"] as $relationalChange) {
                /** @var array{field: RelationalField} $relationalChange */
                $relationalChange["field"]->edit(Application::$instance->user->id, true, $record, $relationalChange["value"], $relationalChange["nullable"]);
            }
        }

        // 2. Textual values
        if (isset($changes["textualValues"])) {
            foreach ($changes["textualValues"] as $textualChange) {
                /** @var array{field: TextualField} $textualChange */
                $textualChange["field"]->edit(Application::$instance->user->id, true, $record, $textualChange["value"], $textualChange["valid"]);
            }
        }

        // 3. Flag
        if (isset($changes["flagged"])) {
            if ($changes["flagged"]) {
                $record->flag(Application::$instance->user->id, true);
            } else {
                $record->unflag(Application::$instance->user->id, true);
            }
        }

        // 4. Assignee
        if (array_key_exists("assignee", $changes)) {
            if ($changes["assignee"] === null) {
                $record->assign(Application::$instance->user->id, true);
            } else {
                $record->assign(Application::$instance->user->id, true, (string) $changes["assignee"]);
            }
        }

        // 5. Comment
        if (isset($changes["comment"])) {
            $record->comment(Application::$instance->user->id, true, $changes["comment"]);
        }

        // 6. Delete
        if (isset($changes["deleted"])) {
            if ($changes["deleted"]) {
                $record->delete(Application::$instance->user->id, true);
            } else {
                $record->restore(Application::$instance->user->id, true);
            }
        }

        exit;
    }
}
