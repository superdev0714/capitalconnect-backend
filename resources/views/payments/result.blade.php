@extends('layouts.app')

@section('content')

    <div class="container" style="color: #fff; width: 100%;">
        <div class="row">
            <div class="col-sm-offset-2 col-sm-8">
                <div>
                    @if (count($errors) > 0)
                        <h4 class="text-center" style="margin-top: 80px;">
                            Sorry, Something Went Wrong
                        </h4>

                        <div class="text-center">
                            <img src="/images/cross.png" style="margin: 30px 0;">
                        </div>

                        <!-- Form Error List -->
                        <div class="text-center">
                            @foreach ($errors as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>

                    @else
                        <h4 class="text-center" style="margin-top: 80px;">
                            Thank-you for your payment!
                        </h4>

                        <div class="text-center">
                            <img src="/images/tick.png" style="margin: 30px 0;">
                        </div>

                        <!-- Form Error List -->
                        <div class="text-center" style="margin-bottom: 80px;">
                            <p>
                                We have received your payment and <br>
                                we will be sending you a confirmation email soon.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if (count($errors) > 0)
            <a href="{{ URL::previous() }}" class="text-center" style="display: block; background: #ff3366; width: 100%; margin: 80px 0 0 0; padding: 15px; border: none; color: #fff; text-decoration: none;">Try Again</a>
        @endif
    </div>
@endsection
