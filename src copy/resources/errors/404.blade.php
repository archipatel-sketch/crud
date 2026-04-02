@extends('crud::layouts.main')

@section('page-title', '404 Not Found')

@section('content')
    <style>
        /* Full-page wrapper to center content */

        .error-page-wrapper {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            /* horizontal */
            align-items: center;
            /* vertical */
            background: #ffffff;
            padding: 20px;
        }

        .error-container {
            text-align: center;
            background: rgba(242, 238, 238, 0.95);
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }

        .error-container h1 {
            font-size: 8rem;
            font-weight: 800;
            color: #ff4c60;
            margin-bottom: 20px;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        }

        .error-container h4 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 30px;
        }

        .error-container a.btn {
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        @media (max-width: 576px) {
            .error-container h1 {
                font-size: 6rem;
            }

            .error-container h4 {
                font-size: 1.2rem;
            }
        }
    </style>

    <div class="error-page-wrapper">
        <div class="error-container bg-light">
            <h1 class="text-dark">404</h1>
            <h4 class="text-secondary">{{ $message ?? 'Oops! Page not found.' }}</h4>
            <a href="{{ url('/') }}" class="btn btn-outline-dark">Go Home</a>
        </div>
    </div>
@endsection

