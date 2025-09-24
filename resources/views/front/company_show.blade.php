@extends('front.layout')

@section('title', '企業詳細')

@section('content')
  <h2 style="margin:0 0 12px;">{{ $company['name'] ?? ($company->name ?? '企業名') }}</h2>
  <p>{{ $company['description'] ?? ($company->description ?? '企業の説明が入ります。') }}</p>
@endsection
