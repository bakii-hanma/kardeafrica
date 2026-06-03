@extends('owner.layouts.owner')

@section('title', 'Modifier la carte')
@section('page-title', 'Modifier : ' . $card->name)

@section('topbar-actions')
    <a href="{{ route('owner.card.show', $card) }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:#F1F5F9;color:#475569;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">← Retour à la carte</a>
@endsection

@section('content')
    @include('owner.cards._form', ['isEdit' => true])
@endsection
