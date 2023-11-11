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
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: rgba(74, 85, 104, 0.87);
        }

        .product {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            max-width: 400px;
        }

        .product img {
            max-width: 100%;
            border-radius: 5px;
        }

        button {
            background-color: rgba(239, 68, 68, 0.84);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: rgba(239, 68, 68, 0.72);
        }
    </style>
</head>
<body class="antialiased">
<div style="display: flex; gap: 3rem; flex-wrap: wrap;">
    @foreach($products as $product)
        <div class="product">
            <img src="{{ $product->image }}" alt="{{ $product->name }}">
            <h5> {{ $product->name }} </h5>
            <p> ${{ $product->price }} </p>
        </div>
    @endforeach
</div>

<form action="{{ route('checkout') }}" method="POST">
    @csrf

    <button>Buy</button>
</form>
</body>
</html>
