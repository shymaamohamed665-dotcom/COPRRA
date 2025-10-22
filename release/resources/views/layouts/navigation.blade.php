<nav class="bg-white dark:bg-gray-800 shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-800 dark:text-white">
                        {{ config('app.name', 'COPRRA') }}
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        Home
                    </a>
                    <a href="{{ route('categories.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        Categories
                    </a>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        Products
                    </a>
                </div>
            </div>

            <!-- Right Side Of Navbar -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @guest
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">Log in</a>
                    <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 dark:text-gray-500 underline">Register</a>
                @else
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</span>
                            <a href="{{ route('profile.edit') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">Profile</a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-gray-700 dark:text-gray-500 underline">Log Out</button>
                            </form>
                        </div>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</nav>
