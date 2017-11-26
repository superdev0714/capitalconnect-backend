<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Capital Connect</title>

    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,700" rel="stylesheet" type="text/css">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet" type="text/css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-alpha1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

    <style>
        body {
            font-family: 'Raleway';
            background: #000000;
            padding-bottom: 50px;
        }
        button .fa {
            margin-right: 6px;
        }
        .table-text div {
            padding-top: 6px;
        }
        #content {
            background: #303030;
        }
        .container {
            padding: 0;
        }
    </style>

    <script>
        $(function () {
            $('#task-name').focus();
        });
    </script>
</head>

<body>
<div class="text-center">
    <img src="/images/logo.png" style="margin: 40px 0 50px 0;">
</div>

<div id="content">
@yield('content')
</div>

</body>
</html>