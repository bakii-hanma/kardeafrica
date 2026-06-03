@extends('owner.layouts.owner')

@section('title', 'Nouvelle carte')
@section('page-title', 'Nouvelle carte locale')
@section('page-subtitle', 'Crée une carte cadeau pour ton commerce. L\'admin la valide avant publication.')

@section('topbar-actions')
    <a href="{{ route('owner.cards') }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:#F1F5F9;color:#475569;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">← Retour</a>
@endsection

@section('content')
    @include('owner.cards._form', ['isEdit' => false])
@endsection
