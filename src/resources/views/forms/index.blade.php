@extends('crud::layouts.main')

@section('page-title', isset($table) ? 'List ' . formatTableName($table) : 'Records')

@section('content')
    <div class="container mt-5 p-5 border border-light rounded shadow p-3 mb-5 bg-body-tertiary">

        <div class="shadow-sm p-2 mb-2 text-secondary rounded bg-light">
            <h1>All {{ isset($table) ? ucfirst($table) : 'Records' }}</h1>
        </div>

        <div class="mb-4 d-flex justify-content-end">
            <a href="{{ route('crud.create', $table) }}" class="btn btn-dark">
                <i class="fas fa-plus-square me-2"></i> Add {{ isset($table) ? formatTableName($table) : 'Record' }}

            </a>
        </div>

        @if (session('success'))
            <div class="d-flex justify-content-center alert-msg">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="d-flex justify-content-center alert-msg">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>error!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        <table id="dynamicTable" class="display table table-bordered mt-5">
            <thead class="table-dark">
                {{-- table heading --}}
                <tr>
                    <th>#</th>
                    @isset($visibleColumns)
                        @foreach ($visibleColumns as $col)
                            <th>{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                        @endforeach
                    @endisset
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {{-- table data --}}
                @foreach ($data as $index => $row)
                    <tr>
                        {{-- index column --}}
                        <td>{{ $index + 1 }}</td>

                        {{-- visible true columns --}}
                        @foreach ($visibleColumns as $col)
                            {{-- for images --}}
                            @if (isset($images[$index]) && $col == $image['name'])
                                <td>
                                    @foreach ($images[$index] as $id => $path)
                                        @if (!empty($path))
                                            <img src="{{ asset($path) }}" alt="image" height="90px" width="90px">
                                        @endif
                                    @endforeach
                                </td>

                                {{-- for date  --}}
                            @elseif (isset($date) && $col == $date['name'])
                                <td>{{ \Carbon\Carbon::parse($row->$col)->format($date['display_formate'] ?? 'Y-m-d') }}
                                </td>

                                {{-- for checkbox --}}
                            @elseif (isset($checkbox) && $col == $checkbox['name'])
                                <td>
                                    @foreach (json_decode($row->$col) as $val)
                                        <span class="badge rounded-pill text-bg-secondary">{{ $val }}</span>
                                    @endforeach
                                </td>
                            @else
                                <td>{{ $row->$col??"" }}</td>
                            @endif
                        @endforeach

                        {{-- Action column --}}
                        <td>
                            <div class="d-flex justify-content-center">
                                <a href="{{ route('crud.edit', ['table' => $table, 'id' => $row->id]) }}"
                                    class="text-dark">
                                    <i class="fas fa-edit"></i>
                                </a>&nbsp;&nbsp;
                                <p class="delete-record" role="button" data-table="{{ $table }}"
                                    data-id="{{ $row->id }}">
                                    <i class="fas fa-trash"></i>
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

@push('footer-scripts')
    <script>
        $(document).ready(function() {

            setTimeout(function() {
                $(".alert-dismissible").fadeOut();
            }, 3000);

            $('.delete-record').on('click', function() {
                let table = $(this).data('table');
                let id = $(this).data('id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `${table}/delete/${id}`;
                    }
                });
            });

            var t = $('#dynamicTable').DataTable({
                pageLength: 10,
                responsive: true,
                autoWidth: false,
                lengthMenu: [5, 10],

            });

            // Add serial numbers dynamically
            t.on('order.dt search.dt', function() {
                t.column(0, {
                    search: 'applied',
                    order: 'applied'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
            }).draw();
        });
    </script>
@endpush
