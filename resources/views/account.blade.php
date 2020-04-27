@extends('layouts.app')

@section('content')
<div class="container">
    <account user-id='{{ Auth::user()->id }}'>
    </account>
</div>
@endsection
