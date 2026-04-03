<?php

namespace ArchipatelSketch\Crud\Http\Controllers;

use ArchipatelSketch\Crud\Exceptions\TableNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;

class CrudController extends Controller
{
    // list all records
    public function index($table)
    {

        try {
            checkTable($table);

            $fields = getFormFields($table);
            if (empty($fields) && $fields == [] && $fields == null) {
                return response()->view('crud::errors.404', [
                    'message' => "Form fields not configured for '{$table}'.",
                ], 404);
            }

            // for image column
            $data = DB::table($table)->get();
            $images = [];

            $imageConfig = collect(config('form-fields.'.$table))->where('type', 'file')->first();
            $image_name = $imageConfig['name'] ?? null;
            $image = $imageConfig;

            $images = [];
            if ($image_name) {
                foreach ($data as $index => $row) {
                    $image_ids = $row->$image_name ? explode(',', $row->$image_name) : [];
                    $images[$index] = DB::table('attachments')
                        ->whereIn('id', $image_ids)
                        ->pluck('file_path', 'id')
                        ->toArray();
                }
            }

            // for date
            $date = collect(config('form-fields.'.$table))->where('type', 'date')->first();

            // for select
            $select = collect(config('form-fields.'.$table))->where('type', 'select')->all();
            $select_fields = collect(config('form-fields.'.$table))->where('type', 'select')->values()->pluck('name')->toArray();

            $relation_data = [];

            // if relation set
            foreach ($select as $array_data) {

                if (array_key_exists('relation', $array_data)) {
                    if (isset($array_data['display_column'])) {

                        $relation = $array_data['relation'];
                        $column = array_values(array_diff(explode('.', $array_data['display_column']), ['relation']))[0];

                        // if display columne set
                        if (isset($relation[$column]) && isset($relation['table_name'])) {
                            $relation_table = $relation['table_name'];
                            $label = $relation[$column];
                            $value = $relation['values'];
                            try {
                                checkTable($relation_table);
                                $relation_data[$relation_table] = DB::table($relation_table)->select($label, $value)->get()->pluck($label, $value)->toArray();
                            } catch (Exception $e) {
                                return response()->view('crud::errors.404', ['message' => "relation table not found which is you define for '{$array_data['label']}'"], 404);
                            }
                        }
                    }
                }

            }

            // for image
            $image = collect(config('form-fields.'.$table))->where('type', 'file')->first();

            // for checkbox
            $checkbox = collect(config('form-fields.'.$table))->where('type', 'checkbox')->first();

            // for toggle
            $toggle = collect(config('form-fields.'.$table))->where('type', 'toggle')->first();

            // for range
            $range = collect(config('form-fields.'.$table))->where('type', 'range')->first();

            // for color picker
            $color = collect(config('form-fields.'.$table))->where('type', 'color')->first();

            // display visible columns on DataTables
            $visibleColumns = collect(config('form-fields.'.$table))
                ->where('visible', true)
                ->pluck('name')
                ->toArray();
        } catch (TableNotFoundException $e) {
            return response()->view('crud::errors.404', ['message' => $e->getMessage()], 404);
        }

        return view('crud::forms.index', compact('data', 'table', 'visibleColumns', 'image', 'images', 'date', 'select', 'select_fields', 'relation_data', 'checkbox', 'toggle', 'range', 'color'));

    }

    // craete record
    public function create($table)
    {
        try {
            checkTable($table);
            $fields = getFormFields($table);
            if (empty($fields) && $fields == [] && $fields == null) {
                return response()->view('crud::errors.404', [
                    'message' => "Form fields not configured for '{$table}'.",
                ], 404);
            }

            $select_relation = collect(config('form-fields.'.$table))->where('type', 'select')->all();
            $relation_array = [];
            foreach ($select_relation as $field => $value) {

                if (isset($value) && array_key_exists('relation', $value)) {
                    $relation = $value['relation'];
                    $field_name = $value['name'];
                    $relation_table = $relation['table_name'];
                    $label = $relation['label'];
                    $values = $relation['values'];
                    try {
                        checkTable($relation_table);
                        $relation_array[$field_name] = DB::table($relation_table)->select("{$relation_table}.*")->get()->pluck($label, $values)->toArray();
                    } catch (Exception $e) {
                        return response()->view('crud::errors.404', ['message' => "relation table not found which is you define for '{$value['label']}'"], 404);
                    }
                }
            }

            return view('crud::forms.create', compact('fields', 'table', 'select_relation', 'relation_array'));
        } catch (TableNotFoundException $e) {
            return response()->view('crud::errors.404', ['message' => $e->getMessage()], 404);
        }
    }

    // store record
    public function store(Request $request, $table)
    {

        try {
            checkTable($table);
        } catch (TableNotFoundException $e) {
            return response()->view('crud::errors.404', ['message' => $e->getMessage()], 404);
        }

        $fields = getFormFields($table);
        if (empty($fields) && $fields == [] && $fields == null) {
            return response()->view('crud::errors.404', [
                'message' => "Form fields not configured for '{$table}'.",
            ], 404);
        }
        $columnsToCheck = array_map(fn ($field) => $field['name'], $fields);
        $tableColumns = Schema::getColumnListing($table);
        $missingColumns = array_diff($columnsToCheck, $tableColumns);

        if (! empty($missingColumns)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cannot insert record. Missing columns: '.implode(', ', $missingColumns));
        }
        // rules
        $rules = $this->createRules($request, $table, $fields);

        // validate data server side
        try {
            $validated = $request->validate($rules);
        } catch (ValidationException $e) {
            // return back()->withInput()->with('error', $e->getMessage());
            return back()->withInput()->withErrors($e->errors());
        }

        foreach ($fields as $field) {

            // move file nd save in db
            if ($field['type'] === 'file' && $request->hasFile($field['name'])) {

                $files = $request->file($field['name']);
                $attachmentIds = [];

                foreach ($files as $file) {

                    if ($file->isValid()) {

                        $originalName = $file->getClientOriginalName();
                        $destinationPath = public_path("assets/images/$table");

                        if (! file_exists($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }

                        $finalName = time().'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
                        $file->move($destinationPath, $finalName);
                        $attachmentId = DB::table('attachments')->insertGetId([
                            'attachment_name' => $finalName,
                            'file_path' => "assets/images/$table/".$finalName,
                            'original_name' => $originalName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $attachmentIds[] = $attachmentId;
                    }
                }

                $validated[$field['name']] = implode(',', $attachmentIds);
            }

            // for checkbox store values as string
            if ($field['type'] == 'checkbox') {
                if ($request->has($field['name']) && is_array($request->input($field['name'])) && is_array($validated[$field['name']])) {
                    // $validated[$field['name']] = implode('..,', $validated[$field['name']]);
                    $validated[$field['name']] = json_encode($request->input($field['name']));

                } else {
                    $validated[$field['name']] = '';
                }
            }

            // for toggle button
            if ($field['type'] === 'toggle') {
                $validated[$field['name']] = $request->has($field['name']) ? 1 : 0;
            }

            // for multiple select
            if ($field['type'] == 'select') {
                if (isset($field['select_type']) && $field['select_type'] == 'multiple') {
                    $validated[$field['name']] = implode(',', $request->input($field['name']));
                }
            }

        }

        // for password
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }


        DB::table($table)->insert($validated);

        return redirect()
            ->route('crud.index', $table)
            ->with('success', 'Record created successfully');
    }

    // Show edit form
    public function edit($table, $id)
    {

        try {
            checkTable($table);

            $select_relation = collect(config('form-fields.'.$table))->where('type', 'select')->all();
            $relation_array = [];
            foreach ($select_relation as $field => $value) {

                if (isset($value) && array_key_exists('relation', $value)) {
                    $relation = $value['relation'];
                    $field_name = $value['name'];
                    $relation_table = $relation['table_name'];
                    $label = $relation['label'];
                    $values = $relation['values'];
                    try {
                        checkTable($relation_table);
                        $relation_array[$field_name] = DB::table($relation_table)->select("{$relation_table}.*")->get()->pluck($label, $values)->toArray();
                    } catch (Exception $e) {
                        return response()->view('crud::errors.404', ['message' => "relation table not found which is you define for '{$value['label']}'"], 404);
                    }
                }
            }
        } catch (TableNotFoundException $e) {
            return response()->view('crud::errors.404', ['message' => $e->getMessage()], 404);
        }

        $fields = getFormFields($table);
        if (empty($fields) && $fields == [] && $fields == null) {
            return response()->view('crud::errors.404', [
                'message' => "Form fields not configured for '{$table}'.",
            ], 404);
        }
        $record = DB::table($table)->where('id', $id)->first();
        $images = [];

        $image = collect(config('form-fields.'.$table))->where('type', 'file')->pluck('name')->first();
        if ($image != '' && $image != null) {
            $ids = explode(',', $record->image);
            $images = DB::table('attachments')->whereIn('id', $ids)->pluck('file_path', 'id')->toArray();
        }

        if (! $record) {
            return redirect()->route('crud.index', $table)
                ->with('error', 'Record not found');
        }

        return view('crud::forms.edit', compact('fields', 'table', 'record', 'images', 'select_relation', 'relation_array'));
    }

    // update records
    public function update(Request $request, $table, $id)
    {

        checkTable($table);

        $fields = getFormFields($table);
        if (empty($fields) && $fields == [] && $fields == null) {
            return response()->view('crud::errors.404', [
                'message' => "Form fields not configured for '{$table}'.",
            ], 404);
        }

        // Get existing record
        $record = DB::table($table)->where('id', $id)->first();

        $rules = $this->createRules($request, $table, $fields);

        try {
            $validated = $request->validate($rules);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        foreach ($fields as $field) {

            // --   -------------- FILE HANDLING ----------------
            if ($field['type'] === 'file') {

                // Get old IDs
                $oldIds = ! empty($record->{$field['name']})
                    ? explode(',', $record->{$field['name']})
                    : [];

                // ---------------- REMOVE IMAGES ----------------
                if ($request->filled('removed_images')) {

                    $removeIds = explode(',', $request->removed_images);

                    foreach ($removeIds as $removeId) {
                        $attachment = DB::table('attachments')->where('id', $removeId)->first();

                        if ($attachment) {
                            // Delete file from public folder
                            $filePath = public_path($attachment->file_path);
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }

                            // Delete DB record
                            DB::table('attachments')->where('id', $removeId)->delete();
                        }
                    }

                    $oldIds = array_diff($oldIds, $removeIds);
                }

                // ---------------- ADD NEW FILES ----------------
                if ($request->hasFile($field['name'])) {

                    $files = $request->file($field['name']);

                    foreach ($files as $file) {

                        if ($file->isValid()) {

                            $originalName = $file->getClientOriginalName();
                            $extension = $file->getClientOriginalExtension();
                            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

                            $destinationPath = public_path("assets/images/$table");
                            if (! file_exists($destinationPath)) {
                                mkdir($destinationPath, 0755, true);
                            }

                            // Avoid overwriting same filename
                            // $finalName = $originalName;
                            // $counter = 1;
                            // while (file_exists($destinationPath.'/'.$finalName)) {
                            //     $finalName = $nameWithoutExt.'('.$counter.').'.$extension;
                            //     $counter++;
                            // }
                            $finalName = time().'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
                            $file->move($destinationPath, $finalName);

                            $attachmentId = DB::table('attachments')->insertGetId([
                                'attachment_name' => $finalName, // unique DB name
                                'file_path' => "assets/images/$table/".$finalName,
                                'original_name' => $originalName,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $oldIds[] = $attachmentId;
                        }
                    }
                }

                $validated[$field['name']] = ! empty($oldIds) ? implode(',', $oldIds) : null;
            }

            // for checkbox store values as string
            if ($field['type'] == 'checkbox') {
                if ($request->has($field['name']) && is_array($request->input($field['name'])) && is_array($validated[$field['name']])) {
                    // $validated[$field['name']] = implode(',', $validated[$field['name']]);
                    $validated[$field['name']] = json_encode($request->input($field['name']));

                } else {
                    $validated[$field['name']] = '';
                }
            }

            // for toggle button
            if ($field['type'] === 'toggle') {
                $validated[$field['name']] = $request->has($field['name']) ? 1 : 0;
            }

            // for multiple select
            if ($field['type'] == 'select') {
                if (isset($field['select_type']) && $field['select_type'] == 'multiple') {
                    $validated[$field['name']] = implode(',', $request->input($field['name']));
                }
            }

        }

        // for password
        if (! empty($request->password)) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        // store data on db
        DB::table($table)->where('id', $id)->update($validated);

        return redirect()
            ->route('crud.index', $table)
            ->with('success', 'Record updated successfully');
    }

    // delete record
    public function destroy($table, $id)
    {
        checkTable($table);

        // Get the record first
        $record = DB::table($table)->where('id', $id)->first();

        if ($record) {

            // Get all file fields from config
            $fields = collect(config('form-fields.'.$table))
                ->where('type', 'file');

            foreach ($fields as $field) {
                $fieldName = $field['name'];

                if (! empty($record->{$fieldName})) {

                    // Multiple attachments stored as comma-separated IDs
                    $attachmentIds = explode(',', $record->{$fieldName});

                    foreach ($attachmentIds as $attachmentId) {

                        $attachment = DB::table('attachments')->where('id', $attachmentId)->first();

                        if ($attachment) {
                            // Delete file from public folder
                            $filePath = public_path($attachment->file_path);
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }

                            // Delete attachment record
                            DB::table('attachments')->where('id', $attachmentId)->delete();
                        }
                    }
                }
            }

            // Delete main record
            DB::table($table)->where('id', $id)->delete();
        }

        return redirect()->route('crud.index', $table)
            ->with('success', 'Record deleted successfully');
    }

    // check email exists or not
    public function checkEmail(Request $request)
    {
        $table = $request->input('table');
        $email = $request->input('email');
        $id = $request->input('id');

        $allowedTables = array_keys(config('form-fields'));
        if (! in_array($table, $allowedTables)) {
            return response()->json(false);
        }

        $fields = getFormFields($table);
        if (empty($fields) && $fields == [] && $fields == null) {
            return response()->view('crud::errors.404', [
                'message' => "Form fields not configured for '{$table}'.",
            ], 404);
        }
        $hasEmailField = collect($fields)->contains('name', 'email');

        if (! $hasEmailField) {
            return response()->json(true);
        }

        $query = DB::table($table)->where('email', $email);
        if ($id) {
            $query->where('id', '!=', $id);
        } // ignore current record

        $exists = $query->exists();

        return response()->json(! $exists);
    }

    public function createRules(Request $request, $table = null, $fields = null)
    {

        $rules = [];
        foreach ($fields as $field) {

            if (isset($field['rules'])) {
                $rule = $field['rules'];

                // for on create and on edit fields rules
                if (isset($field['display_on_create']) && array_key_exists('display_on_create', $field) && $field['display_on_create'] == false) {
                    $rules[$field['name']] = str_replace('required', 'nullable', $field['rules']);

                    continue;
                }

                if (isset($field['display_on_edit']) && array_key_exists('display_on_edit', $field) && $field['display_on_edit'] == false) {
                    $rules[$field['name']] = str_replace('required', 'nullable', $field['rules']);

                    continue;
                }
                // Handle password optional on edit
                if ($field['type'] === 'password' && $request->route('id')) {
                    $rule = str_replace('required', 'nullable', $rule);
                }

                // Handle unique for edit
                if (str_contains($rule, 'unique') && $request->input($field['name'])) {
                    $rule = preg_replace_callback('/unique:([^,]+),([^|]+)/', function ($matches) use ($request) {
                        return "unique:{$matches[1]},{$matches[2]},".$request->route('id');
                    }, $rule);
                }

                // Handle file fields
                if ($field['type'] === 'file') {

                   if ($request->hasFile($field['name'])) {
                        $rules[$field['name']] = 'array';
                        $rules[$field['name'].'.*'] = 'image|mimes:jpg,jpeg,png|max:2048';
                    }

                    continue;

                }
                // handle checkbox fields
                if ($field['type'] === 'checkbox') {
                    $rules[$field['name']] = 'nullable|array';
                    $values = explode('|', $field['values']);
                    $rules[$field['name'].'.*'] = 'in:'.implode(',', $values);

                    continue;

                }

                // handle relation
                if ($field['type'] === 'select') {

                    $select = collect(config('form-fields.'.$table))->where('type', 'select')->all();
                    // convert rules into array

                    foreach ($select as $array_data) {

                        if (isset($array_data) && array_key_exists('relation', $array_data) && isset($array_data['relation'])) {
                            $field_rules = explode('|', $array_data['rules']);

                            $relation = $array_data['relation'];
                            $relation_table = $relation['table_name'];

                            try {
                                checkTable($relation_table);

                                $column = $relation['label'];

                                $simple_rules = '';

                                // $rules[$array_data['name']] = $array_data['rules'];
                                foreach ($field_rules as $key => $rule) {
                                    if (str_starts_with($rule, 'in:')) {

                                        // for simple validation exclude in:
                                        if ($simple_rules == '' && $simple_rules == null) {
                                            $target = $rule;
                                            $pattern = '/\|?'.preg_quote($target, '/').'\|?/';
                                            $result = preg_replace($pattern, '', $field_rules);
                                            $results = [];

                                            foreach ($result as $key => $value) {
                                                if ($value != '') {
                                                    array_push($results, $value);
                                                }
                                            }
                                            $simple_rules = implode('|', $results);

                                        }

                                        // for relation rules
                                        $array = array_diff(explode('relation.', $rule), ['in:']);
                                        $column = array_map(function ($value) {
                                            return preg_replace('/[^a-zA-Z0-9 ]+/', '', $value);
                                        }, $array);
                                    }

                                }

                                // rearranged column values
                                if (! is_array($column)) {
                                    $column = [$column];
                                } else {
                                    $column = array_values($column);
                                }

                                $relation_values = [];
                                foreach ($column as $key => $value) {
                                    if (array_key_exists($value, $relation)) {
                                        $relation_array = DB::table($relation_table)->select("$relation[$value]")->get()->pluck("$relation[$value]")->toArray();
                                        foreach ($relation_array as $key => $value) {
                                            array_push($relation_values, $value);
                                        }

                                    }
                                }

                                if (isset($array_data['name']) && is_array($array_data) && $simple_rules != '' && count($relation_values) > 0) {
                                    $rules[$array_data['name']] = $simple_rules;
                                    $rules[$array_data['name'].'.*'] = 'in:'.implode(',', $relation_values);

                                    continue;
                                }

                            } catch (ValidationException $e) {
                                return response()->view('crud::errors.404', ['message' => "relation table not found which is you define for '{$array_data['label']}'"], 404);
                            }
                        } else {

                            // for simple select
                            if (isset($array_data['rules'])) {
                                $in = '';
                                $valid_values = [];
                                $field_rules = explode('|', $array_data['rules']);
                                $target = $array_data['rules'];
                                $pattern = '/\|?'.preg_quote($target, '/').'\|?/';
                                $result = preg_replace($pattern, '', $field_rules);

                                $results = [];
                                foreach ($result as $key => $value) {
                                    if (! str_starts_with($value, 'in:')) {
                                        array_push($results, $value);
                                    } else {
                                        $in = explode(',', $value);
                                        foreach ($in as $key2 => $value2) {
                                            if (str_starts_with($value2, 'in:')) {
                                                $value2 = str_replace('in:', '', $value2);
                                                array_push($valid_values, $value2);
                                            } else {
                                                array_push($valid_values, $value2);
                                            }
                                        }
                                    }
                                }

                                if (is_array($results) && $results != null && is_array($in)) {
                                    $rules[$array_data['name']] = implode('|', $results);
                                    $rules[$array_data['name'].'.*'] = 'in:'.implode(',', $valid_values);
                                }
                            }

                        }
                    }

                    continue;

                }
                $rules[$field['name']] = $rule;

            }
        }

        return $rules;
    }
}
