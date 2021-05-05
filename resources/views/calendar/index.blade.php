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
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">{{trans('lang.order_plural')}}<small class="ml-3 mr-3">|</small><small>{{trans('lang.order_desc')}}</small></h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('/dashboard')}}"><i class="fa fa-dashboard"></i> {{trans('lang.dashboard')}}</a></li>
            <li class="breadcrumb-item"><a href="{!! route('calendar.index') !!}">{{trans('lang.order_cal')}}</a>
            </li>
            <li class="breadcrumb-item active">{{trans('lang.order_cal')}}</li>
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>

  <div class="content">
    <div class="clearfix"></div>
{{--    @include('flash::message')--}}
    <div class="card">
      <div class="card-header">
        <ul class="nav nav-tabs align-items-end card-header-tabs w-100">
          <li class="nav-item">
            <a class="nav-link active" href="{!! url()->current() !!}"><i class="fa fa-list mr-2"></i>{{trans('lang.order_table_cal')}}</a>
          </li>
          @can('calendar.create')
            <li class="nav-item">
              <a class="nav-link" href="{!! route('calendar.create') !!}"><i class="fa fa-plus mr-2"></i>{{trans('lang.order_create')}}</a>
            </li>
          @endcan
        </ul>
      </div>
      <div class="card-body">
        @include('calendar.calendar')
        <div class="clearfix"></div>
      </div>
    </div>
  </div>
  </body>
@endsection