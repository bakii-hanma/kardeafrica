@extends('layouts.auth-mobile')

@section('title', 'Connexion - Kardafrica')

@section('content')
    @include('auth.flip-card', ['initialState' => 'true'])
@endsection
