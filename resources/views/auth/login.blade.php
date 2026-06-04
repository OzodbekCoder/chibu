@extends('layouts.app')
@section('title', 'Kirish · CHIBU')

@section('content')
<div class="min-h-screen flex flex-col justify-center px-6 py-12 bg-gradient-to-b from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
    <div class="max-w-sm mx-auto w-full">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl mx-auto bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-3xl shadow-lg shadow-indigo-500/30">📦</div>
            <h1 class="mt-4 text-2xl font-bold dark:text-white">CHIBU</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Yuklarni boshqarish tizimi</p>
        </div>

        <form method="POST" action="{{ route('login.attempt') }}"
              class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700 space-y-4">
            @csrf

            @if ($errors->any())
                <div class="bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 text-sm p-3 rounded-lg border border-rose-200 dark:border-rose-700/50">
                    {{ $errors->first() }}
                </div>
            @endif

            <label class="block">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus inputmode="email"
                    class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-3 focus:border-indigo-500 border">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Parol</span>
                <input type="password" name="password" required
                    class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white px-3 py-3 focus:border-indigo-500 border">
            </label>

            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" name="remember" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600">
                <span>Eslab qolish</span>
            </label>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-medium py-3 rounded-xl transition">
                Kirish
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6">© {{ date('Y') }} CHIBU</p>
    </div>
</div>
@endsection
