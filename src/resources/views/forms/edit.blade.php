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


        <div class="shadow-sm p-2 mb-2 text-secondary rounded">
            <h1>Edit {{ isset($table) ? formatTableName($table) : 'Record' }}</h1>

        </div>

        <form id="form-validation" method="POST"
            action="{{ route('crud.update', ['table' => $table, 'id' => $record->id]) }}" data-table="{{ $table }}"
            enctype="multipart/form-data">
            @csrf

            @foreach ($fields as $field)
                <div class="mb-3">
                    <label>{{ $field['label'] }}</label>

                    @php
                        $value = old($field['name'], $record->{$field['name']} ?? '');
                    @endphp

                    @if ($field['type'] === 'textarea')
                        <textarea name="{{ $field['name'] }}" class="form-control">{{ $value }}</textarea>
                    @elseif($field['type'] === 'password')
                        <input type="password" name="{{ $field['name'] }}" id="{{ $field['name'] }}" value=""
                            class="form-control" autocomplete="new-password">
                        <small class="text-muted">Leave blank if you don't want to change password</small>
                    @elseif ($field['name'] == 'image')
                        {{-- for image upload --}}
                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}[]" id="{{ $field['name'] }}"
                            class="form-control"
                            {{ isset($field['upload_type']) && $field['upload_type'] == 'multiple' ? 'multiple' : '' }}
                            accept="image/*">

                        {{-- display image if uploaded --}}
                        @isset($images)
                            <input type="hidden" name="removed_images" id="removed_images">
                            @foreach ($images as $id => $image)
                                <div class="image-container">
                                    <img src="{{ asset($image) }}" class="img-fluid small-image">
                                    <button type="button" class="btn-close remove-image" data-img_id="{{ $id }}">
                                    </button>
                                </div>
                            @endforeach
                        @endisset
                        {{-- for select --}}
                    @elseif ($field['type'] == 'select')
                        @php
                            $options = explode('|', $field['values']);
                        @endphp
                        @if (!empty($field['values']) && isset($options))
                            <select name="{{ $field['name'] }}" id="{{ $field['name'] }}" class="form-select">
                                <option value="">--Select city--</option>
                                @foreach ($options as $option)
                                    {{ $selected = $option == $value ? 'selected' : '' }}
                                    <option value="{{ $option }}" {{ $selected }}>
                                        {{ ucfirst($option) }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        {{-- for radio --}}
                    @elseif($field['type'] == 'radio')
                        @php
                            $options = explode('|', $field['values']);
                            $currentValue = old($field['name']);
                            if (!empty($field['default_checked'])) {
                                $currentValue = $field['default_checked'];
                            }
                            if (!empty($value)) {
                                $currentValue = $value;
                            }
                        @endphp
                        @if (!empty($field['values']) && isset($options))
                            <div>
                                @foreach ($options as $option)
                                    <div class="form-check form-check-inline">
                                        <input type="radio" id="{{ $field['name'] }}_{{ $option }}"
                                            name="{{ $field['name'] }}" value="{{ $option }}"
                                            class="form-check-input" {{ $currentValue == $option ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $field['name'] }}_{{ $option }}">
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
                            $currentValue = [old($field['name'])];
                            if (!empty($field['default_checked'])) {
                                $currentValue = [$field['default_checked']];
                            }
                            if (!empty($value)) {
                                $currentValue = json_decode($value);
                            }
                        @endphp
                        @if (!empty($field['values']) && isset($options))
                            <div>
                                @foreach ($options as $option)
                                    <div class="form-check form-check-inline">
                                        <input type="{{ $field['type'] }}" id="{{ $field['name'] }}_{{ $option }}"
                                            name="{{ $field['name'] }}[]" value="{{ $option }}"
                                            class="form-check-input"
                                            {{ in_array($option, $currentValue) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $field['name'] }}_{{ $option }}">
                                            {{ ucfirst($option) }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                            value="{{ $value }}" class="form-control" autocomplete="off">
                    @endif
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            <a href="{{ route('crud.index', $table) }}" class="btn btn-secondary">Cancel</a>

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
