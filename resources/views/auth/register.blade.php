@extends('layouts.app')

@section('content')

    <div class="container">
        <div class="col-sm-offset-2 col-sm-8">
            <div class="panel panel-default">
                <div class="panel-body">

                    <!-- Display Validation Errors -->
                    @include('common.errors')

                    <form method="POST" action="/auth/register" role="form">
                        {!! csrf_field() !!}

                        <div class="form-group">
                            <label class="control-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Mobile</label>
                            <input type="text" class="form-control" name="mobile" value="{{ old('mobile') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Password</label>
                            <input type="password" class="form-control" name="password">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Confirm Password</label>
                            <input type="password" class="form-control" name="password_confirmation">
                        </div>

                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
