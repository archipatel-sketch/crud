@extends('crud::layouts.main')

@section('page-title', isset($table) ? 'Edit ' . formatTableName($table) : 'Record')

@push('header-styles')
    <style>
        .image-container {
            position: relative;
            /* Essential for absolute positioning of the close button */
            display: inline-block;
            /* Wraps the container tightly around the image */
            margin: 20px;
        }

        .small-image {
            width: 100px;
            /* Define a small size for the image */
            height: auto;
            display: block;
        }

        .btn-close {
            position: absolute;
            /* Position the button relative to the container */
            top: 5px;
            /* Adjust top and right as needed */
            right: 5px;
            /* Optional: add a slight shadow or background for better visibility */
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            padding: 0.2rem;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="shadow p-2 mb-2 rounded bg-light mt-5 border border-light">
            <h1 class="text-center m-3 text-dark">Edit {{ isset($table) ? formatTableName($table) : 'Record' }}</h1>
        </div>

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
        <div class="container mt-5 p-5 border border-light rounded shadow p-3 mb-5 bg-light">

            <form id="form-validation" method="POST"
                action="{{ route('crud.update', ['table' => $table, 'id' => $record->id]) }}"
                data-table="{{ $table }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">

                    @foreach ($fields as $field)
                        <div class="col-md-6"> {{-- Each field takes half width --}}
                            <div class="mb-3">
                                <div class="mb-3">
                                    <label>{{ $field['label'] }}</label>

                                    @php
                                        $value = old($field['name'], $record->{$field['name']} ?? '');
                                    @endphp

                                    @if ($field['type'] === 'textarea')
                                        <textarea name="{{ $field['name'] }}" class="form-control">{{ $value }}</textarea>
                                        {{-- After every input field --}}
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @elseif($field['type'] === 'password')
                                        <input type="password" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                            value=""
                                            placeholder="{{ !empty($field['placeholder']) && array_key_exists('placeholder', $field) ? $field['placeholder'] : '' }}"
                                            class="form-control" autocomplete="new-password">
                                        <small class="text-muted">Leave blank if you don't want to change password</small>
                                        {{-- After every input field --}}
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @elseif ($field['name'] == 'image')
                                        {{-- for image upload --}}
                                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}[]"
                                            id="{{ $field['name'] }}" class="form-control"
                                            placeholder="{{ !empty($field['placeholder']) && array_key_exists('placeholder', $field) ? $field['placeholder'] : '' }}"
                                            {{ isset($field['upload_type']) && $field['upload_type'] == 'multiple' ? 'multiple' : '' }}
                                            accept="image/*">

                                        {{-- display image if uploaded --}}
                                        @isset($images)
                                            <input type="hidden" name="removed_images" id="removed_images">
                                            @foreach ($images as $id => $image)
                                                <div class="image-container">
                                                    <img src="{{ asset($image) }}" class="img-fluid small-image">
                                                    <button type="button" class="btn-close remove-image"
                                                        data-img_id="{{ $id }}">
                                                    </button>
                                                </div>
                                            @endforeach
                                            {{-- After every input field --}}
                                            @error($field['name'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @endisset
                                        {{-- for select --}}
                                    @elseif ($field['type'] == 'select')
                                        @php
                                            $options = explode('|', $field['values']);
                                        @endphp
                                        @if (!empty($field['values']) && isset($options))
                                            <select name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                                class="form-select">
                                                <option value="">--Select city--</option>
                                                @foreach ($options as $option)
                                                    {{ $selected = $option == $value ? 'selected' : '' }}
                                                    <option value="{{ $option }}" {{ $selected }}>
                                                        {{ ucfirst($option) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            {{-- After every input field --}}
                                            @error($field['name'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @endif
                                        {{-- for radio --}}
                                    @elseif($field['type'] == 'radio')
                                        @php
                                            $options = explode('|', $field['values']);
                                            $currentValue = old($field['name']);
                                            if (!empty($field['default'])) {
                                                $currentValue = $field['default'];
                                            }
                                            if (!empty($value)) {
                                                $currentValue = $value;
                                            }
                                        @endphp
                                        @if (!empty($field['values']) && isset($options))
                                            <div>
                                                @foreach ($options as $option)
                                                    <div class="form-check form-check-inline">
                                                        <input type="radio"
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
                                            {{-- After every input field --}}
                                            @error($field['name'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @endif
                                        {{-- for checkbox --}}
                                    @elseif($field['type'] == 'checkbox')
                                        @php
                                            $options = explode('|', $field['values']);

                                            // Get value (old OR DB)
                                            $rawValue = old($field['name'], $value ?? null);

                                            if (is_array($rawValue)) {
                                                // When validation fails → old() returns array
                                                $currentValue = $rawValue;
                                            } elseif (is_string($rawValue)) {
                                                // When data comes from DB → JSON string
                                                $decoded = json_decode($rawValue, true);
                                                $currentValue = is_array($decoded) ? $decoded : [];
                                            } else {
                                                // Default value
                                                $currentValue = !empty($field['default']) ? [$field['default']] : [];
                                            }
                                        @endphp
                                        @foreach ($options as $option)
                                            <div class="form-check form-check-inline">
                                                <input type="checkbox" name="{{ $field['name'] }}[]"
                                                    value="{{ $option }}" class="form-check-input"
                                                    {{ in_array($option, $currentValue) ? 'checked' : '' }}>

                                                <label class="form-check-label">
                                                    {{ ucfirst($option) }}
                                                </label>
                                            </div>
                                            {{-- After every input field --}}
                                            @error($field['name'])
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        @endforeach
                                        {{-- for toggle --}}
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
                                        {{-- After every input field --}}
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        {{-- for range --}}
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

                                        <div>
                                            Value: <span id="{{ $field['name'] }}_value">{{ $value }}</span>
                                        </div>
                                        {{-- After every input field --}}
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}"
                                            id="{{ $field['name'] }}"
                                            placeholder="{{ !empty($field['placeholder']) && array_key_exists('placeholder', $field) ? $field['placeholder'] : '' }}"
                                            value="{{ $value }}" class="form-control" autocomplete="off">
                                        {{-- After every input field --}}
                                        @error($field['name'])
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-dark me-2"> Update</button>
                    <a href="{{ route('crud.index', $table) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>

                <script>
                    var formFields = @json($fields); // fields config from PHP
                    var tableName = "{{ $table }}"; // current table
                    var recordId = "{{ $record->id ?? '' }}"; // record ID (empty for create)
                </script>

            </form>

            {{-- Pass PHP fields to JS --}}
            <script>
                var formFields = @json($fields);
                var tableName = "{{ $table }}";
            </script>

        </div>
    @endsection

    @push('footer-scripts')
        <script src="{{ asset('vendor/crud/js/form-validation.js') }}"></script>

        <script>
            let removedImages = [];

            $(document).on('click', '.remove-image', function() {

                let id = $(this).data('img_id');

                removedImages.push(id);
                // alert(removedImages);
                $('#removed_images').val(removedImages.join(','));

                // alert($('#removed_images').val())

                $(this).closest('.image-container').remove();
            });
        </script>
    @endpush
