@extends('admin.layouts.admin')

@section('title', 'Modifier · ' . $card->name)
@section('page-title', 'Modifier la carte · ' . $card->name)

@section('content')
    @include('admin.merchant-cards._form', ['isEdit' => true])
@endsection
