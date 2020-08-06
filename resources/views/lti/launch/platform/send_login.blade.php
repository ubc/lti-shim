@extends('layouts.autosubmitform')

@section('title', 'OIDC Login - Send to Tool')

@section('content')
  <p>Working, please wait...</p>

  <form action='{{ $oidc_login_url }}' method='post' id='autoSubmitForm'>
    @foreach ($response as $key => $val)
      <div>
        <label for='{{ $key }}'>{{ $key }}</label>
        <input type='hidden' id='{{ $key }}'
               name='{{ $key }}' value='{{ $val }}' />
      </div>
    @endforeach

    <button type='submit'>
      Send Login
    </button>
  </form>
@endsection
