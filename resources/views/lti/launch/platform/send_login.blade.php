@extends('layouts.basic')

@section('title', 'OIDC Login - Send to Tool')

@section('content')
  <h3>Login Parameters</h3>
  <form action='{{ $oidc_login_url }}' method='get'>
    @foreach ($response as $key => $val)
      <div class='form-group'>
        <label for='{{ $key }}'>{{ $key }}</label>
        <input class='form-control' type='text' id='{{ $key }}'
               name='{{ $key }}' value='{{ $val }}' />
      </div>
    @endforeach

    <button type='submit' class='btn btn-primary'>
      Send Login
    </button>
  </form>
@endsection
