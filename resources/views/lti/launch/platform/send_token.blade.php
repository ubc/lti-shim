@extends('layouts.autosubmitform')

@section('title', 'Authorization Response')

@section('content')
  <p>Working, please wait...</p>

  <form action='{{ $auth_resp_url }}' method='post' id='autoSubmitForm'>
    @foreach ($response as $key => $val)
      <div>
        <label for='{{ $key }}'>{{ $key }}</label>
        <input type='hidden' id='{{ $key }}'
               name='{{ $key }}' value='{{ $val }}' />
      </div>
    @endforeach

    <button type='submit'>
      Send Authorization Response
    </button>
  </form>
@endsection
