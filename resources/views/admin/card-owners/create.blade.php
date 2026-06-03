@extends('admin.layouts.admin')

@section('title', 'Nouveau propriétaire')
@section('page-title', 'Nouveau propriétaire de carte locale')

@section('content')
    @include('admin.card-owners._form', ['isEdit' => false])
@endsection
