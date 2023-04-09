@extends('layout.app')

@section('content')
    <div class="container mt-3">
        <div class="row">
            <form class="form-horizontal d-flex flex-column justify-content-center" method="POST" action="{{ route('login') }}">
                {{ csrf_field() }}
                <div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
                    <div class="col-md-12 d-flex justify-content-center">
                        <div class="col-md-6">
                            <label for="email" class="col-md-4 control-label">Email</label>
                            <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                            @if ($errors->has('email'))
                                <span class="help-block">
                                        <strong style="color:red">{{ $errors->first('email') }}</strong>
                                    </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                    <div class="col-md-12 d-flex justify-content-center">
                        <div class="col-md-6">
                            <label for="password" class="col-md-4 control-label">Пароль</label>

                            <input id="password" type="password" class="form-control" name="password" required>

                            @if ($errors->has('password'))
                                <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-md-12 d-flex justify-content-center">
                            <div class="col-md-6 col-md-offset-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Запамьятаті мене
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-12 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary">
                                Увійти
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
