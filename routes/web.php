<?php

use Inertia\Inertia;

Route::get('', function(){
  return Inertia::render('home');
});

Route::get('/home', function(){
  return Inertia::render('home');
});

Route::get('/detail', function(){
  return Inertia::render('detail');
});