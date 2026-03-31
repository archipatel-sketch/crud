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
            $data = DB::table($table)->latest()->get();
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
            $select = collect(config('form-fields.'.$table))->where('type', 'select')->first();

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

        return view('crud::forms.index', compact('data', 'table', 'visibleColumns', 'image', 'images', 'date', 'select', 'checkbox', 'toggle', 'range', 'color'));

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

            return view('crud::forms.create', compact('fields', 'table'));
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
        $rules = [];
        $columnsToCheck = array_map(fn ($field) => $field['name'], $fields);
        $tableColumns = Schema::getColumnListing($table);
        $missingColumns = array_diff($columnsToCheck, $tableColumns);

        if (! empty($missingColumns)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cannot insert record. Missing columns: '.implode(', ', $missingColumns));
        }

        // rules
        foreach ($fields as $field) {

            if (isset($field['rules'])) {
                $rule = $field['rules'];

                // Handle password optional on edit
                if ($field['type'] === 'password' && $request->route('id')) {
                    $rule = str_replace('required', '', $rule);
                }

                // Handle unique for edit
                if (str_contains($rule, 'unique') && $request->input($field['name'])) {
                    $rule = preg_replace_callback('/unique:([^,]+),([^|]+)/', function ($matches) use ($request) {
                        return "unique:{$matches[1]},{$matches[2]},".$request->route('id');
                    }, $rule);
                }

                // Handle file fields
                if ($field['type'] === 'file') {
                    $rules[$field['name']] = 'nullable|array';
                    $rules[$field['name'].'.*'] = 'sometimes|image|mimes:jpg,jpeg,png|max:2048';

                    continue;
                }

                // handle checkbox fields
                if ($field['type'] === 'checkbox') {
                    $rules[$field['name']] = 'nullable|array';
                    $values = explode('|', $field['values']);
                    $rules[$field['name'].'.*'] = 'in:'.implode(',', $values);

                    continue;

                }

                $rules[$field['name']] = $rule;
            }
        }

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
                        $extension = $file->getClientOriginalExtension();
                        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

                        $destinationPath = public_path("assets/images/$table");

                        if (! file_exists($destinationPath)) {
                            mkdir($destinationPath, 0755, true);
                        }

                        // $finalName = $originalName;
                        // $counter = 1;

                        // while (file_exists($destinationPath.'/'.$finalName)) {
                        //     $finalName = $nameWithoutExt.'('.$counter.').'.$extension;
                        //     $counter++;
                        // }

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

        return view('crud::forms.edit', compact('fields', 'table', 'record', 'images'));
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

        // ---------------- VALIDATION ----------------
        $rules = [];

        foreach ($fields as $field) {

            if (isset($field['rules'])) {

                $rule = $field['rules'];

                // Password optional on edit
                if ($field['type'] === 'password') {
                    $rule = 'nullable|min:6';
                }

                // Handle unique rule
                if (str_contains($rule, 'unique') && $request->input($field['name'])) {
                    $rule = preg_replace_callback('/unique:([^,]+),([^|]+)/', function ($matches) use ($id) {
                        return "unique:{$matches[1]},{$matches[2]},$id";
                    }, $rule);
                }

                // File validation for multiple images
                if ($field['type'] === 'file') {
                    if ($request->hasFile($field['name'])) {
                        $rules[$field['name']] = 'array';
                        $rules[$field['name'].'.*'] = 'image|mimes:jpg,jpeg,png|max:2048';
                    }

                    continue;
                }

                // handle select fields
                if ($field['type'] === 'checkbox') {
                    $rules[$field['name']] = 'nullable|array';
                    $values = explode('|', $field['values']);
                    $rules[$field['name'].'.*'] = 'in:'.implode(',', $values);

                    continue;
                }

                $rules[$field['name']] = $rule;
            }
        }

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

        }

        // ---------------- PASSWORD ----------------
        if (! empty($request->password)) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

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
}
