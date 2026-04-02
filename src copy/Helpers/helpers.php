<?php

use ArchipatelSketch\Crud\Exceptions\TableNotFoundException;
use Illuminate\Support\Facades\DB;

// get form fields
if (! function_exists('getFormFields')) {
    function getFormFields($table)
    {
        return config("form-fields.$table", []);
    }

}

// print pretty array with die
if (! function_exists('pretty_array')) {
    function pretty_array($array)
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
        exit;
    }
}

// check available tables in database
if (! function_exists('getAllowedTables')) {
    function getAllowedTables()
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = 'Tables_in_'.$dbName;

        return array_map(function ($table) use ($key) {
            return $table->$key;
        }, $tables);
    }
}

// check table exists or not
if (! function_exists('checkTable')) {
    function checkTable($table)
    {
        $allowedTables = getAllowedTables();
        if (! in_array($table, $allowedTables)) {
            throw new TableNotFoundException($table);
        }
    }
}

// getc rules from the form field
if (! function_exists('getValidationRules')) {
    function getValidationRules($fields, $id = null)
    {
        $rules = [];

        foreach ($fields as $field) {
            if (! empty($field['rules'])) {
                $rule = $field['rules'];

                // Fix unique on update
                if ($id && str_contains($rule, 'unique')) {
                    $rule .= ",$id";
                }

                $rules[$field['name']] = $rule;
            }
        }

        return $rules;
    }
}

// Format a table name for display in headings
if (! function_exists('formatTableName')) {

    function formatTableName(string $table): string
    {
        // Remove trailing 's' if exists
        if (substr($table, -1) === 's') {
            $table = substr($table, 0, -1);
        }

        // Replace dashes or underscores with space
        $table = str_replace(['-', '_'], ' ', $table);

        // Capitalize each word
        $table = ucwords($table);

        return $table;
    }
}
