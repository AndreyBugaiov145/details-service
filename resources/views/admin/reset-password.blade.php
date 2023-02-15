@extends('layout.app')

@section('navbar')
    @include('components.navbar')
@endsection

@section('content')
    <div class="container mt-3">
        @if ($errors->has('success'))
            <div class="alert alert-success" role="alert">
                {{ $errors->first('success') }}
            </div>
        @endif

        <div class="row">
            <h3>Зміна пароля</h3>
            <form class="form-horizontal d-flex flex-column justify-content-center" method="POST" action="{{ route('reset-password') }}">
                {{ csrf_field() }}
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
                    <div class="form-group m-3">
                        <div class="col-md-12 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary">
                                Змінити
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
