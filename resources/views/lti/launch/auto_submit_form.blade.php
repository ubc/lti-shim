@extends('layouts.autosubmitform')

@section('title', '{{ $title }}')

@section('content')
  <p>Working, please wait...</p>

  <form action='{{ $formUrl }}' method='post' id='autoSubmitForm'>
    @foreach ($params as $key => $val)
      <div>
        <label for='{{ $key }}'>{{ $key }}</label>
        <input type='text' id='{{ $key }}'
               name='{{ $key }}' value='{{ $val }}' />
      </div>
    @endforeach

    <button type='submit'>
        Submit {{ $title }}
    </button>
  </form>
@endsection
