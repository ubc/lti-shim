@extends('layouts.basic')

@section('title', 'OIDC Login Complete - Authentication Request')

@section('content')
  <h3>Login Parameters</h3>
  <ul>
    @foreach ($login as $key => $val)
      <li>{{ $key }}: {{ $val }}</li>
    @endforeach
  </ul>

  <form action='{{ $auth_req_url }}' method='get'>
    @foreach ($response as $key => $val)
      <div class='form-group'>
        <label for='{{ $key }}'>{{ $key }}</label>
        <input class='form-control' type='text' id='{{ $key }}'
               name='{{ $key }}' value='{{ $val }}' />
      </div>
    @endforeach

    <button type='submit' class='btn btn-primary'>
      Send Authentication Request
    </button>
  </form>
@endsection
