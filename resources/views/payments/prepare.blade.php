@extends('layouts.app')

@section('content')

    <div class="container" style="background: #fff; width: 100%;">
        <form method="POST" action="/payment/process">
            <div class="col-sm-offset-3 col-sm-6" style="padding: 20px;">
                <div>
                    <h4 class="text-center">
                        The Capital Hotel Payment Processor
                    </h4>
                    <p class="text-center">
                        Thank-you for booking at The Capital Hotel Group. Please make your payment below. This is a 3D-Secure Payment Processor.
                    </p>

                    <div style="padding: 40px 60px;">
                        <!-- Display Validation Errors -->
                        @include('common.errors')

                        {!! csrf_field() !!}

                        <div class="form-group">
                            <label class="control-label">Reference Number</label>
                            <input type="text" class="form-control" name="reference" id="reference" value="{{ request('reference') }}" readonly>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Amount</label>
                            <input type="text" class="form-control" name="amount" id="amount" value="{{ request('amount') }}" readonly>
                        </div>

                        <div class="form-group hide">
                            <label class="control-label">Currency</label>
                            <input type="text" class="form-control" name="currency" id="currency" value="ZAR" readonly>
                        </div>

                        <div class="form-group">
                            <label class="control-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="first_name" value="{{ request('first_name') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="last_name" value="{{ request('last_name') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Email</label>
                            <input type="text" class="form-control" name="email" id="email" value="{{ request('email') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Credit Card</label>
                            <input type="text" class="form-control" name="card" id="card" value="{{ request('card') }}">
                        </div>

                        <div class="form-group">
                            <label class="control-label">Expiry Date</label>
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <select class="form-control" name="expiry_year" id="expiry_year">
                                        @for ($i = 2016; $i <= 2050; $i++)
                                            <option {{ $i == request('expiry_year') ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-control" name="expiry_month" id="expiry_month">
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option {{ $i == request('expiry_month') ? 'expiry_month' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label">CVV</label>
                            <input type="text" class="form-control" name="cvv" id="cvv" value="{{ request('cvv') }}">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" style="display: block; background: #ff3366; width: 100%; padding: 15px; border: none; color: #fff;">Pay Now</button>

        </form>
    </div>
@endsection
