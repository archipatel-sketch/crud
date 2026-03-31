@extends('crud::layouts.main')

@section('page-title', isset($table) ? 'Create ' . formatTableName($table) : 'Record')

@section('content')
    <div class="container">

        <div class="shadow p-2 mb-2 rounded bg-light mt-5 border border-light">
            <h1 class="text-center m-3 text-dark">Create {{ isset($table) ? formatTableName($table) : 'Record' }}</h1>
        </div>
        <div class="container mt-5 p-5 border border-light rounded shadow p-3 mb-5 bg-light">
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



                <form id="form-validation" method="POST" action="{{ route('crud.store', ['table' => $table]) }}"
                    data-table="{{ $table }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3"> {{-- Bootstrap row with gutters --}}

                        @foreach ($fields as $field)
                            @php
                                $colClass = ($field['input_style'] ?? 'half') === 'full' ? 'col-12' : 'col-md-6';
                            @endphp
                            <div class="{{ $colClass }}">
                                <div class="mb-3">
                                    <label>{{ $field['label'] }}</label>

                                    {{-- Textarea --}}
                                    @if ($field['type'] === 'textarea')
                                        <textarea name="{{ $field['name'] }}" class="form-control" placeholder="{{ $field['placeholder'] ?? '' }}">{{ old($field['name']) }}</textarea>
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        {{-- File --}}
                                    @elseif ($field['type'] == 'file')
                                        <input type="file" name="{{ $field['name'] }}[]" id="{{ $field['name'] }}"
                                            class="form-control"
                                            {{ isset($field['upload_type']) && $field['upload_type'] == 'multiple' ? 'multiple' : '' }}
                                            accept="image/*">
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        {{-- Select --}}
                                    @elseif ($field['type'] == 'select')
                                        @php
                                            $options = explode('|', $field['values']);
                                            $currentValue = old($field['name'], $field['default'] ?? '');
                                        @endphp
                                        <select name="{{ $field['name'] }}" id="{{ $field['name'] }}" class="form-select">
                                            <option value="">--Select--</option>
                                            @foreach ($options as $option)
                                                <option value="{{ $option }}"
                                                    {{ $currentValue == $option ? 'selected' : '' }}>
                                                    {{ ucfirst($option) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        {{-- Radio --}}
                                    @elseif($field['type'] == 'radio')
                                        @php
                                            $options = explode('|', $field['values']);
                                            $currentValue = old($field['name'], $field['default'] ?? '');
                                        @endphp
                                        <div>
                                            @foreach ($options as $option)
                                                <div class="form-check form-check-inline">
                                                    <input type="radio" id="{{ $field['name'] }}_{{ $option }}"
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
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        {{-- Checkbox --}}
                                    @elseif($field['type'] == 'checkbox')
                                        @php
                                            $options = explode('|', $field['values']);
                                            $currentValue = old($field['name'], $field['default'] ?? []);
                                        @endphp
                                        <div>
                                            @foreach ($options as $option)
                                                <div class="form-check form-check-inline">
                                                    <input type="checkbox" id="{{ $field['name'] }}_{{ $option }}"
                                                        name="{{ $field['name'] }}[]" value="{{ $option }}"
                                                        class="form-check-input"
                                                        {{ in_array($option, (array) $currentValue) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                        for="{{ $field['name'] }}_{{ $option }}">
                                                        {{ ucfirst($option) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        {{-- Toggle --}}
                                    @elseif($field['type'] === 'toggle')
                                        @php
                                            $value = old(
                                                $field['name'],
                                                $record->{$field['name']} ?? ($field['default'] ?? 0),
                                            );
                                        @endphp
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="{{ $field['name'] }}"
                                                id="{{ $field['name'] }}" value="1" {{ $value ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $field['name'] }}">
                                                {{ $field['label'] }}
                                            </label>
                                        </div>
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        {{-- Range --}}
                                    @elseif($field['type'] === 'range')
                                        @php
                                            $value = old(
                                                $field['name'],
                                                $record->{$field['name']} ?? ($field['default'] ?? 0),
                                            );
                                        @endphp
                                        <input type="range" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                            class="form-range" min="{{ $field['min'] ?? 0 }}"
                                            max="{{ $field['max'] ?? 100 }}" step="{{ $field['step'] ?? 1 }}"
                                            value="{{ $value }}"
                                            oninput="document.getElementById('{{ $field['name'] }}_value').innerText = this.value">
                                        <div>Value: <span id="{{ $field['name'] }}_value">{{ $value }}</span>
                                        </div>
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        {{-- Default Input --}}
                                    @else
                                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}"
                                            id="{{ $field['name'] }}" value="{{ old($field['name']) }}"
                                            class="form-control" placeholder="{{ $field['placeholder'] ?? '' }}">
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @endif

                                </div>
                            </div>{{-- mb-3 --}}
                        @endforeach

                    </div> {{-- row --}}

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-dark me-2">Submit</button>
                        <a href="{{ route('crud.index', $table) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
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
