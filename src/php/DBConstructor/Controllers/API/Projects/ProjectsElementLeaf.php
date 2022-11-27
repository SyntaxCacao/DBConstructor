<?php

declare(strict_types=1);

namespace DBConstructor\Controllers\API\Projects;

use DBConstructor\Controllers\API\LeafNode;
use DBConstructor\Util\MarkdownParser;

class ProjectsElementLeaf extends LeafNode
{
    public function get(array $path): array
    {
        return [
            "id" => intval(ProjectsNode::$project->id),
            "label" => ProjectsNode::$project->label,
            "description" => ProjectsNode::$project->description,
            "notesMarkdown" => ProjectsNode::$project->notes,
            "notesHTML" => ProjectsNode::$project->notes === null ? null : MarkdownParser::parse(ProjectsNode::$project->notes),
            "userIsManager" => ProjectsNode::$participant->isManager,
            "userJoined" => ProjectsNode::$participant->created,
            "created" => ProjectsNode::$project->created,
        ];
    }
}
