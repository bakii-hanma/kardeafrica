@extends('vendor.layouts.vendor')

@section('title', 'Modifier la carte')

@section('content')
    @include('vendor.merchant-cards._form', ['isEdit' => true])
@endsection
