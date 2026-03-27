@extends('layouts.main')

@section('page-title', isset($table) ? 'Create ' . formatTableName($table) : 'Record')

@section('content')
    <div class="container p-5 mt-5 border border-light shadow p-3 mb-5 bg-body-tertiary rounded">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (isset($fields) && isset($table))

            <div class="container">
                <div class="shadow-sm p-2 mb-2 rounded">
                    <h1>Create {{ isset($table) ? formatTableName($table) : 'Record' }}</h1>
                </div>

                <form id="form-validation" method="POST" action="{{ route('crud.store', ['table' => $table]) }}"
                    data-table="{{ $table }}" enctype="multipart/form-data">
                    @csrf
                    @foreach ($fields as $field)
                        <div class="mb-3">
                            <label>{{ $field['label'] }}</label>

                            @if ($field['type'] === 'textarea')
                                {{-- for text area --}}
                                <textarea name="{{ $field['name'] }}" class="form-control">{{ old($field['name']) }}</textarea>
                            @elseif ($field['name'] == 'image')
                                {{-- for image upload --}}
                                <input type="{{ $field['type'] }}" name="{{ $field['name'] }}[]" id="{{ $field['name'] }}"
                                    value="{{ old($field['name']) }}" class="form-control"
                                    {{ isset($field['upload_type']) && $field['upload_type'] == 'multiple' ? 'multiple' : '' }}
                                    accept="image/*">
                            @else
                                {{-- for simple inputs like text,password,single file,email --}}
                                <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                    value="{{ old($field['name']) }}" class="form-control"
                                    autocomplete="{{ $field['type'] === 'password' ? 'new-password' : 'off' }}">
                            @endif
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="{{ route('crud.index', $table) }}" class="btn btn-secondary">Cancel</a>
                    <script>
                        var formFields = @json($fields); // fields config from PHP
                        var tableName = "{{ $table }}"; // current table
                        var recordId = "{{ $record->id ?? '' }}"; // record ID (empty for create)
                    </script>
                </form>
            </div>
        @endif

    </div>
@endsection

@push('footer-scripts')
    <script src="{{ asset('assets/js/form-validation.js') }}"></script>
@endpush
