@extends('admin.layouts.admin')

@section('title', 'Modifier le propriétaire')
@section('page-title', 'Modifier le propriétaire')

@section('content')
    @include('admin.card-owners._form', ['isEdit' => true])
@endsection
