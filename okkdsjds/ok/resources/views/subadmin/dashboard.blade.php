@extends('subadmin.layouts.app')
@section('title', 'Dashboard')
{{-- header code --}}
@section('header-css')
    <link rel="stylesheet" href="{{ asset('plugins/charts/chart.css') }}">
    <style>
        .bg-light-pink {
            background-color: #FFB6C1;
        }

        .bg-yellow {
            background-color: #FFFF00;
        }

        .bg-light-blue {
            background-color: #ADD8E6;
        }

        .bg-green {
            background-color: #00FF00;
        }

        .bg-red {
            background-color: #FF0000;
        }

        .bg-grey {
            background-color: #808080;
        }
    </style>
@endsection

@section('main')
    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">SubAdmin Dashboard</h1>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h3>Main Claim</h3>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('subadmin.case.index', 'main_claim_cases') }}" class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases }}</h3>
                                    <p>New Cases</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- footer code --}}
@section('footer-script')
    <script>

    </script>
@endsection
@endsection
