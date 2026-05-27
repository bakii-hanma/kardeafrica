@extends('admin.layouts.admin')

@section('title', 'Nouvelle carte marchand')
@section('page-title', 'Nouvelle carte Carte Gabon')

@section('content')
    @include('admin.merchant-cards._form', ['isEdit' => false])
@endsection
