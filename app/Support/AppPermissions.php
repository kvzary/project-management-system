<?php

namespace App\Support;

class AppPermissions
{
    /**
     * Resource key => display label.
     * These define what shows up in the Roles permission editor.
     */
    const RESOURCES = [
        'project'    => 'Projects',
        'task'       => 'Tasks',
        'sprint'     => 'Sprints',
        'workflow'   => 'Workflows',
        'department' => 'Departments',
        'user'       => 'Users',
        'role'       => 'Roles',
    ];

    const ACTIONS = [
        'view'   => 'View',
        'create' => 'Create',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ];

    /**
     * All permission strings: ['project.view', 'project.create', ...]
     */
    public static function all(): array
    {
        $permissions = [];
        foreach (array_keys(self::RESOURCES) as $resource) {
            foreach (array_keys(self::ACTIONS) as $action) {
                $permissions[] = "{$resource}.{$action}";
            }
        }

        return $permissions;
    }

    /**
     * Options for a CheckboxList for a single resource:
     * ['project.view' => 'View', 'project.create' => 'Create', ...]
     */
    public static function optionsFor(string $resource): array
    {
        $options = [];
        foreach (self::ACTIONS as $action => $label) {
            $options["{$resource}.{$action}"] = $label;
        }

        return $options;
    }
}
