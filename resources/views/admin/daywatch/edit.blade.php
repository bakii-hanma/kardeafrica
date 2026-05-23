@extends('admin.layouts.admin')

@section('title', 'Modifier ' . $product->name)
@section('page-title', 'Modifier — ' . $product->name)

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">
    <div style="margin-bottom:18px;">
        <a href="{{ route('admin.daywatch.index') }}"
           style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748B;text-decoration:none;">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Retour à la liste
        </a>
    </div>

    @include('admin.daywatch._form')
</div>
@endsection
