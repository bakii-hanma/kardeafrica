@extends('vendor.layouts.vendor')

@section('title', 'Nouvelle carte-cadeau')

@section('content')
    @include('vendor.merchant-cards._form', ['isEdit' => false])
@endsection
