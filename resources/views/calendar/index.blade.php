@extends('layouts.app')

<head>
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
{{--  <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js"></script>--}}
</head>
@section('content')
  <body>
  <!-- Content Header (Page header) -->
  <section class="content-header content-header{{setting('fixed_header')}}">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>{{trans('lang.dashboard')}}</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">{{trans('lang.order_cal')}}</a></li>
            <li class="breadcrumb-item active">{{trans('lang.order_cal')}}</li>
          </ol>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

    @include('calendar.calendar')
  </body>
@endsection