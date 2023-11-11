<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <style>
        body {
            font-family: Monaco, Consolas, Liberation Mono, Courier New, monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: rgba(74, 85, 104, 0.87);
        }

        .container {
            max-width: 800px;
            margin: 100px auto;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        h1 {
            font-size: 2rem;
            margin: 20px 0;
        }

        .title {
            text-align: center;
            color: rgba(239, 68, 68, 0.84);
        }

        .customer-info {
            font-size: 1.2rem;
            margin: 10px 60px;
        }

        .button-link {
            background-color: rgba(239, 68, 68, 0.84);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: inline-block;
        }

        .button-link:hover {
            background-color: rgba(239, 68, 68, 0.72);
        }

        .text-center {
            padding-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body class="antialiased">
<div class="container">
    <div class="title">
        <h1>Success</h1>
        <h2>Client Information</h2>
    </div>
    <p class="customer-info"><strong>Name: </strong> {{ $customer->name }}</p>
    <p class="customer-info"><strong>Email: </strong> {{ $customer->email }}</p>
    <p class="customer-info"><strong>Phone: </strong> {{ $customer->phone }}</p>

    <div class="text-center">
        <a href="{{ route('checkout.refund') }}" class="button-link">Refund</a>
    </div>
</div>
</body>
</html>
