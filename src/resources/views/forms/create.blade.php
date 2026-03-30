@extends('crud::layouts.main')

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
                                <textarea name="{{ $field['name'] }}" class="form-control"
                                    placeholder="{{ !empty($field['placeholder']) && array_key_exists('placeholder', $field) ? $field['placeholder'] : '' }}">{{ old($field['name']) }}</textarea>
                            @elseif ($field['type'] == 'file')
                                {{-- for image upload --}}
                                <input type="{{ $field['type'] }}" name="{{ $field['name'] }}[]" id="{{ $field['name'] }}"
                                    value="{{ old($field['name']) }}" class="form-control"
                                    {{ isset($field['upload_type']) && $field['upload_type'] == 'multiple' ? 'multiple' : '' }}
                                    accept="image/*">
                                {{-- for select --}}
                            @elseif ($field['type'] == 'select')
                                @php
                                    $options = explode('|', $field['values']);
                                    $currentValue = old($field['name'], $field['default_selected'] ?? '');
                                @endphp
                                @if (!empty($field['values']) && isset($options))
                                    <select name="{{ $field['name'] }}" id="{{ $field['name'] }}" class="form-select">
                                        <option value="">--Select city--</option>
                                        @foreach ($options as $option)
                                            <option value="{{ $option }}"
                                                {{ $currentValue == $option ? 'selected' : '' }}>
                                                {{ ucfirst($option) }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            @elseif($field['type'] == 'radio')
                                @php
                                    $options = explode('|', $field['values']);
                                    $currentValue = old($field['name']);
                                    if (!empty($field['default_checked'])) {
                                        $currentValue = $field['default_checked'];
                                    }
                                @endphp
                                @if (!empty($field['values']) && isset($options))
                                    <div>
                                        @foreach ($options as $option)
                                            <div class="form-check form-check-inline">
                                                <input type="{{ $field['type'] }}"
                                                    id="{{ $field['name'] }}_{{ $option }}"
                                                    name="{{ $field['name'] }}" value="{{ $option }}"
                                                    class="form-check-input"
                                                    {{ $currentValue == $option ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                    for="{{ $field['name'] }}_{{ $option }}">
                                                    {{ ucfirst($option) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                {{-- for checkbox --}}
                            @elseif($field['type'] == 'checkbox')
                                @php
                                    $options = explode('|', $field['values']);
                                    $currentValue = old($field['name']);
                                    if (!empty($field['default_checked'])) {
                                        $currentValue = $field['default_checked'];
                                    }
                                @endphp
                                @if (!empty($field['values']) && isset($options))
                                    <div>
                                        @foreach ($options as $option)
                                            <div class="form-check form-check-inline">
                                                <input type="{{ $field['type'] }}"
                                                    id="{{ $field['name'] }}_{{ $option }}"
                                                    name="{{ $field['name'] }}[]" value="{{ $option }}"
                                                    class="form-check-input"
                                                    {{ $currentValue == $option ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                    for="{{ $field['name'] }}_{{ $option }}">
                                                    {{ ucfirst($option) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                {{-- for simple inputs like text,password,single file,email --}}
                                <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                    value="{{ old($field['name']) }}" class="form-control"
                                    placeholder="{{ !empty($field['placeholder']) && array_key_exists('placeholder', $field) ? $field['placeholder'] : '' }}"
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
    <script src="{{ asset('vendor/crud/js/form-validation.js') }}"></script>
@endpush
