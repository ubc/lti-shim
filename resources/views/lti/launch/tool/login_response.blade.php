@extends('layouts.autosubmitform')

@section('title', 'OIDC Login Complete - Authentication Request')

@section('content')
  <p>Working, please wait...</p>

  <form action='{{ $auth_req_url }}' method='get' id='autoSubmitForm'>
    @foreach ($response as $key => $val)
      <div>
        <label for='{{ $key }}'>{{ $key }}</label>
        <input type='hidden' id='{{ $key }}'
               name='{{ $key }}' value='{{ $val }}' />
      </div>
    @endforeach

    <button type='submit'>
      Send Authentication Request
    </button>
  </form>
@endsection
