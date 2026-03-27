<?php

namespace App\Http\Controllers;

use App\Exceptions\TableNotFoundException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrudController extends Controller
{
    // list all records
    public function index($table)
    {

        try {
            checkTable($table);

            // for image column
            $data = DB::table($table)->latest()->get();
            $images = [];

            $image = collect(config('form-fields.'.$table))->where('type', 'file')->pluck('name')->first();

            if ($image != '' && $image != null) {

                $attachment_ids = DB::table($table)->select('image')->get()->pluck('image')->toArray();

                foreach ($attachment_ids as $index => $attachment_id) {
                    $image_ids = explode(',', $attachment_id);
                    $attachments = DB::table('attachments')->whereIn('id', $image_ids)->pluck('file_path', 'id')->toArray();
                    $images[] = $attachments;
                }

            }

            $visibleColumns = collect(config('form-fields.'.$table))
                ->where('visible', true)
                ->pluck('name')
                ->toArray();
        } catch (TableNotFoundException $e) {
            return response()->view('errors.404', ['message' => $e->getMessage()], 404);
        }

        return view('forms.index', compact('data', 'table', 'visibleColumns', 'images'));

    }

    // craete record
    public function create($table)
    {
        try {
            checkTable($table);
            $fields = getFormFields($table);

            return view('forms.create', compact('fields', 'table'));
        } catch (TableNotFoundException $e) {
            return response()->view('errors.404', ['message' => $e->getMessage()], 404);
        }
    }

    // store record
    public function store(Request $request, $table)
    {
        try {
            checkTable($table);
        } catch (TableNotFoundException $e) {
            return response()->view('errors.404', ['message' => $e->getMessage()], 404);
        }
        $fields = getFormFields($table);
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

                $rules[$field['name']] = $rule;
            }
        }

        // validate data server side
        try {
            $validated = $request->validate($rules);
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        foreach ($fields as $field) {

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
        }

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
            return response()->view('errors.404', ['message' => $e->getMessage()], 404);
        }

        $fields = getFormFields($table);
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

        return view('forms.edit', compact('fields', 'table', 'record', 'images'));
    }

    // update records
    public function update(Request $request, $table, $id)
    {
        checkTable($table);

        $fields = getFormFields($table);

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

                $rules[$field['name']] = $rule;
            }
        }

        try {
            $validated = $request->validate($rules);
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // ---------------- FILE HANDLING ----------------
        foreach ($fields as $field) {

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
