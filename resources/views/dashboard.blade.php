<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - CoffeePOS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-stone-100">

    <nav class="bg-stone-900 text-white px-6 py-4">
        <div class="flex items-center justify-between">

            <h1 class="text-xl font-bold">
                CoffeePOS
            </h1>

            <div class="flex items-center gap-4">

                <div>
                    {{ auth()->user()->name }}
                    <span class="text-stone-400">
                        ({{ auth()->user()->role }})
                    </span>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf

                    <button class="bg-red-600 px-4 py-2 rounded-lg">
                        Logout
                    </button>
                </form>

            </div>

        </div>
    </nav>

    <main class="p-6">

        <h2 class="text-2xl font-bold text-stone-800">
            Dashboard
        </h2>

        <p class="text-stone-500 mt-2">
            Selamat datang, {{ auth()->user()->name }}!
        </p>

    </main>

</body>
</html>